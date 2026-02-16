<?php
/**
 * Get Career Details API
 * Returns full career details for a given career_id
 */

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

// Get career_id from query params
$careerId = isset($_GET['career_id']) ? intval($_GET['career_id']) : 0;

if ($careerId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid career_id is required'
    ]);
    exit();
}

try {
    // Fetch career details
    $query = "SELECT * FROM careers WHERE id = :career_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':career_id', $careerId);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Career not found'
        ]);
        exit();
    }

    $career = $stmt->fetch(PDO::FETCH_ASSOC);

    // Also fetch the user's match data for this career if available
    $userId = $_SESSION['user_id'];
    $matchQuery = "SELECT match_percentage, match_reason FROM user_career_recommendations WHERE user_id = :user_id AND career_id = :career_id ORDER BY id DESC LIMIT 1";
    $matchStmt = $db->prepare($matchQuery);
    $matchStmt->bindParam(':user_id', $userId);
    $matchStmt->bindParam(':career_id', $careerId);
    $matchStmt->execute();
    $matchData = $matchStmt->fetch(PDO::FETCH_ASSOC);

    $career['match_percentage'] = $matchData ? $matchData['match_percentage'] : null;
    $career['match_reason'] = $matchData ? $matchData['match_reason'] : null;

    echo json_encode([
        'success' => true,
        'career' => $career
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
