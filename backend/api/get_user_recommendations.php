<?php
/**
 * Get User Recommendations API
 * Fetches quiz/career recommendations for a specific user (admin use)
 */

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id is required']);
    exit();
}

try {
    // Get the latest test result id for this user
    $latestTestQuery = "SELECT id FROM user_test_results WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
    $latestTestStmt = $db->prepare($latestTestQuery);
    $latestTestStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $latestTestStmt->execute();
    $latestTest = $latestTestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$latestTest) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No quiz results found for this user'
        ]);
        exit();
    }

    $testResultId = $latestTest['id'];

    // Fetch recommendations for this test result, joined with careers table
    $query = "SELECT ucr.match_percentage, ucr.match_reason, ucr.created_at as recommended_at,
                     c.id as career_id, c.title, c.description, c.required_skills,
                     c.salary_min, c.salary_max, c.category, c.work_type,
                     c.growth_outlook, c.education_required
              FROM user_career_recommendations ucr
              JOIN careers c ON c.id = ucr.career_id
              WHERE ucr.user_id = :user_id AND ucr.test_result_id = :test_result_id
              ORDER BY ucr.match_percentage DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':test_result_id', $testResultId, PDO::PARAM_INT);
    $stmt->execute();
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($recommendations) === 0) {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No quiz results found for this user'
        ]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'data' => $recommendations
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
