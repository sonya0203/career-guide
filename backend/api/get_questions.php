<?php
/**
 * Get Questions API
 * Fetches relevant career questions based on user's stream, qualification, interests, and age
 */

// Start session
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

try {
    // 1. Get user's stream, qualification, interests, and age from user_qualifications
    $query = "SELECT stream, highest_qualification, interests, age FROM user_qualifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Qualification data not found'
        ]);
        exit();
    }

    $userOne = $stmt->fetch(PDO::FETCH_ASSOC);
    $stream = $userOne['stream'];
    $qualification = $userOne['highest_qualification'];
    $interest = $userOne['interests'];
    $userAge = intval($userOne['age']);

    // Map business to commerce if needed
    $categoryStream = $stream;
    if ($stream == 'business') {
        $categoryStream = 'commerce';
    }

    $questions = [];

    // 2. Try to fetch questions matching ALL criteria (stream + qualification + interest + age range)
    $questionQuery = "SELECT id, category, qualification, interest, age_min, age_max, question_text, option_a, option_b, option_c, option_d 
                      FROM career_questions 
                      WHERE category = :category 
                        AND qualification = :qualification 
                        AND interest = :interest 
                        AND age_min <= :age1 AND age_max >= :age2
                      ORDER BY RAND() LIMIT 5";
    
    $qStmt = $db->prepare($questionQuery);
    $qStmt->bindParam(':category', $categoryStream);
    $qStmt->bindParam(':qualification', $qualification);
    $qStmt->bindParam(':interest', $interest);
    $qStmt->bindParam(':age1', $userAge, PDO::PARAM_INT);
    $qStmt->bindParam(':age2', $userAge, PDO::PARAM_INT);
    $qStmt->execute();
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fallback: stream + qualification + age
    if (count($questions) < 5) {
        $existingIds = array_column($questions, 'id');
        $excludeClause = count($existingIds) > 0 ? "AND id NOT IN (" . implode(',', array_map('intval', $existingIds)) . ")" : "";
        $remaining = 5 - count($questions);

        $fallbackQuery = "SELECT id, category, qualification, interest, age_min, age_max, question_text, option_a, option_b, option_c, option_d 
                          FROM career_questions 
                          WHERE category = :category 
                            AND qualification = :qualification 
                            AND age_min <= :age1 AND age_max >= :age2
                            $excludeClause
                          ORDER BY RAND() LIMIT :remaining";
        
        $fbStmt = $db->prepare($fallbackQuery);
        $fbStmt->bindParam(':category', $categoryStream);
        $fbStmt->bindParam(':qualification', $qualification);
        $fbStmt->bindParam(':age1', $userAge, PDO::PARAM_INT);
        $fbStmt->bindParam(':age2', $userAge, PDO::PARAM_INT);
        $fbStmt->bindParam(':remaining', $remaining, PDO::PARAM_INT);
        $fbStmt->execute();
        $extraQuestions = $fbStmt->fetchAll(PDO::FETCH_ASSOC);
        $questions = array_merge($questions, $extraQuestions);
    }

    // 4. Fallback: stream + interest + age
    if (count($questions) < 5) {
        $existingIds = array_column($questions, 'id');
        $excludeClause = count($existingIds) > 0 ? "AND id NOT IN (" . implode(',', array_map('intval', $existingIds)) . ")" : "";
        $remaining = 5 - count($questions);

        $fallbackQuery2 = "SELECT id, category, qualification, interest, age_min, age_max, question_text, option_a, option_b, option_c, option_d 
                           FROM career_questions 
                           WHERE category = :category 
                             AND interest = :interest 
                             AND age_min <= :age1 AND age_max >= :age2
                             $excludeClause
                           ORDER BY RAND() LIMIT :remaining";
        
        $fbStmt2 = $db->prepare($fallbackQuery2);
        $fbStmt2->bindParam(':category', $categoryStream);
        $fbStmt2->bindParam(':interest', $interest);
        $fbStmt2->bindParam(':age1', $userAge, PDO::PARAM_INT);
        $fbStmt2->bindParam(':age2', $userAge, PDO::PARAM_INT);
        $fbStmt2->bindParam(':remaining', $remaining, PDO::PARAM_INT);
        $fbStmt2->execute();
        $extraQuestions2 = $fbStmt2->fetchAll(PDO::FETCH_ASSOC);
        $questions = array_merge($questions, $extraQuestions2);
    }

    // 5. Fallback: stream + age only
    if (count($questions) < 5) {
        $existingIds = array_column($questions, 'id');
        $excludeClause = count($existingIds) > 0 ? "AND id NOT IN (" . implode(',', array_map('intval', $existingIds)) . ")" : "";
        $remaining = 5 - count($questions);

        $fallbackQuery3 = "SELECT id, category, qualification, interest, age_min, age_max, question_text, option_a, option_b, option_c, option_d 
                           FROM career_questions 
                           WHERE category = :category 
                             AND age_min <= :age1 AND age_max >= :age2
                             $excludeClause
                           ORDER BY RAND() LIMIT :remaining";
        
        $fbStmt3 = $db->prepare($fallbackQuery3);
        $fbStmt3->bindParam(':category', $categoryStream);
        $fbStmt3->bindParam(':age1', $userAge, PDO::PARAM_INT);
        $fbStmt3->bindParam(':age2', $userAge, PDO::PARAM_INT);
        $fbStmt3->bindParam(':remaining', $remaining, PDO::PARAM_INT);
        $fbStmt3->execute();
        $extraQuestions3 = $fbStmt3->fetchAll(PDO::FETCH_ASSOC);
        $questions = array_merge($questions, $extraQuestions3);
    }

    // 6. Final fallback: stream only (ignore age/qualification/interest)
    if (count($questions) < 5) {
        $existingIds = array_column($questions, 'id');
        $excludeClause = count($existingIds) > 0 ? "AND id NOT IN (" . implode(',', array_map('intval', $existingIds)) . ")" : "";
        $remaining = 5 - count($questions);

        $fallbackQuery4 = "SELECT id, category, qualification, interest, age_min, age_max, question_text, option_a, option_b, option_c, option_d 
                           FROM career_questions 
                           WHERE category = :category 
                             $excludeClause
                           ORDER BY RAND() LIMIT :remaining";
        
        $fbStmt4 = $db->prepare($fallbackQuery4);
        $fbStmt4->bindParam(':category', $categoryStream);
        $fbStmt4->bindParam(':remaining', $remaining, PDO::PARAM_INT);
        $fbStmt4->execute();
        $extraQuestions4 = $fbStmt4->fetchAll(PDO::FETCH_ASSOC);
        $questions = array_merge($questions, $extraQuestions4);
    }

    echo json_encode([
        'success' => true,
        'stream' => $stream,
        'qualification' => $qualification,
        'interest' => $interest,
        'age' => $userAge,
        'count' => count($questions),
        'questions' => $questions
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
