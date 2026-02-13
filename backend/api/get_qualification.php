<?php
/**
 * Get User Qualification API
 * Fetches qualification details for a specific user
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
    $query = "SELECT uq.*, au.full_name as user_name, au.email as user_email
              FROM user_qualifications uq
              JOIN auth_users au ON au.id = uq.user_id
              WHERE uq.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $qualification = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($qualification) {
        echo json_encode([
            'success' => true,
            'data' => $qualification
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'No qualification found for this user'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
