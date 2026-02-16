<?php
/**
 * Submit Test API
 * Saves user test answers, runs recommendation logic, returns top 3 careers
 */

session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents("php://input"));

if (empty($data->answers)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Answers are required'
    ]);
    exit();
}

try {
    // 1. Save test answers
    $answersJson = json_encode($data->answers);
    $saveQuery = "INSERT INTO user_test_results (user_id, answers_json) VALUES (:user_id, :answers_json)";
    $saveStmt = $db->prepare($saveQuery);
    $saveStmt->bindParam(':user_id', $userId);
    $saveStmt->bindParam(':answers_json', $answersJson);
    $saveStmt->execute();
    $testResultId = $db->lastInsertId();

    // 2. Get user's qualification data
    $qualQuery = "SELECT stream, skills, interests, highest_qualification, work_type FROM user_qualifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
    $qualStmt = $db->prepare($qualQuery);
    $qualStmt->bindParam(':user_id', $userId);
    $qualStmt->execute();
    $qualification = $qualStmt->fetch(PDO::FETCH_ASSOC);

    if (!$qualification) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Qualification data not found. Please complete qualification form first.'
        ]);
        exit();
    }

    $userStream = $qualification['stream'];
    $userSkills = array_map('trim', explode(',', strtolower($qualification['skills'])));
    $userInterests = strtolower($qualification['interests']);
    $userWorkType = strtolower($qualification['work_type']);

    // 3. Fetch all careers (prioritize matching category but include all)
    $careerQuery = "SELECT * FROM careers ORDER BY CASE WHEN category = :category THEN 0 ELSE 1 END, RAND()";
    $careerStmt = $db->prepare($careerQuery);
    $careerStmt->bindParam(':category', $userStream);
    $careerStmt->execute();
    $allCareers = $careerStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($allCareers) === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No careers found in the database.'
        ]);
        exit();
    }

    // 4. Calculate match percentage for each career
    $scoredCareers = [];
    foreach ($allCareers as $career) {
        $score = 0;
        $maxScore = 100;
        $reasons = [];

        // a) Category/Stream match (40 points)
        if (strtolower($career['category']) === strtolower($userStream)) {
            $score += 40;
            $reasons[] = "Your stream matches this career field";
        } else {
            // Partial match for related streams
            $relatedStreams = [
                'computer-science' => ['engineering', 'science'],
                'engineering' => ['computer-science', 'science'],
                'science' => ['engineering', 'medical', 'computer-science'],
                'medical' => ['science'],
                'commerce' => ['business'],
                'business' => ['commerce'],
                'arts' => []
            ];
            $related = $relatedStreams[strtolower($userStream)] ?? [];
            if (in_array(strtolower($career['category']), $related)) {
                $score += 20;
                $reasons[] = "Related field to your stream";
            }
        }

        // b) Skills overlap (30 points)
        $careerSkills = array_map('trim', array_map('strtolower', explode(',', $career['required_skills'])));
        $matchedSkills = array_intersect($userSkills, $careerSkills);
        $skillOverlap = count($careerSkills) > 0 ? count($matchedSkills) / count($careerSkills) : 0;
        $skillScore = round($skillOverlap * 30);
        $score += $skillScore;
        if ($skillScore > 0) {
            $reasons[] = "Your skills match " . count($matchedSkills) . " required skill(s)";
        }

        // c) Work type preference match (15 points)
        if (strtolower($career['work_type']) === $userWorkType) {
            $score += 15;
            $reasons[] = "Matches your preferred work type";
        } elseif (strtolower($career['work_type']) === 'hybrid') {
            $score += 8;
        }

        // d) Interest alignment (15 points)
        $interestMap = [
            'technology' => ['computer-science', 'engineering'],
            'creative' => ['arts'],
            'business' => ['commerce', 'business'],
            'healthcare' => ['medical'],
            'education' => ['science', 'arts'],
            'science' => ['science', 'engineering'],
            'media' => ['arts'],
            'social-services' => ['arts', 'medical']
        ];
        $interestCategories = $interestMap[$userInterests] ?? [];
        if (in_array(strtolower($career['category']), $interestCategories)) {
            $score += 15;
            $reasons[] = "Aligns with your interests";
        }

        // Add some randomness to make it feel natural (±5 points)
        $score += rand(-5, 5);
        $score = max(30, min(99, $score)); // Clamp between 30-99

        $scoredCareers[] = [
            'career' => $career,
            'match_percentage' => $score,
            'match_reason' => implode('. ', $reasons) . '.'
        ];
    }

    // 5. Sort by score descending, take top 3
    usort($scoredCareers, function($a, $b) {
        return $b['match_percentage'] - $a['match_percentage'];
    });
    $top3 = array_slice($scoredCareers, 0, 3);

    // 6. Save recommendations to database
    $recQuery = "INSERT INTO user_career_recommendations (user_id, test_result_id, career_id, match_percentage, match_reason) VALUES (:user_id, :test_result_id, :career_id, :match_percentage, :match_reason)";
    $recStmt = $db->prepare($recQuery);

    $recommendations = [];
    foreach ($top3 as $item) {
        $recStmt->bindParam(':user_id', $userId);
        $recStmt->bindParam(':test_result_id', $testResultId);
        $recStmt->bindParam(':career_id', $item['career']['id']);
        $recStmt->bindParam(':match_percentage', $item['match_percentage']);
        $recStmt->bindParam(':match_reason', $item['match_reason']);
        $recStmt->execute();

        $recommendations[] = [
            'id' => $item['career']['id'],
            'title' => $item['career']['title'],
            'description' => $item['career']['match_description'] ?: $item['career']['description'],
            'required_skills' => $item['career']['required_skills'],
            'salary_min' => $item['career']['salary_min'],
            'salary_max' => $item['career']['salary_max'],
            'match_percentage' => $item['match_percentage'],
            'match_reason' => $item['match_reason'],
            'work_type' => $item['career']['work_type'],
            'growth_outlook' => $item['career']['growth_outlook']
        ];
    }

    echo json_encode([
        'success' => true,
        'test_result_id' => $testResultId,
        'recommendations' => $recommendations
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
