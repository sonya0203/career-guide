<?php
/**
 * Save Qualification API
 * Saves user qualification details to the database
 */

// Start session
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

// Get POST data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (
    empty($data->fullName) ||
    empty($data->age) ||
    empty($data->qualification) ||
    empty($data->stream) ||
    empty($data->skills) ||
    empty($data->interests) ||
    empty($data->workType)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required'
    ]);
    exit();
}

try {
    // Check if qualification already exists for this user
    $checkQuery = "SELECT id FROM user_qualifications WHERE user_id = :user_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $_SESSION['user_id']);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // Update existing record
        $query = "UPDATE user_qualifications SET
            full_name = :full_name,
            age = :age,
            highest_qualification = :qualification,
            stream = :stream,
            skills = :skills,
            interests = :interests,
            work_type = :work_type,
            created_at = NOW()
            WHERE user_id = :user_id";
    } else {
        // Insert new record
        $query = "INSERT INTO user_qualifications
            (user_id, full_name, age, highest_qualification, stream, skills, interests, work_type)
            VALUES
            (:user_id, :full_name, :age, :qualification, :stream, :skills, :interests, :work_type)";
    }

    $stmt = $db->prepare($query);

    // Convert skills array to comma-separated string if it's an array
    $skills = is_array($data->skills) ? implode(',', $data->skills) : $data->skills;

    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':full_name', $data->fullName);
    $stmt->bindParam(':age', $data->age);
    $stmt->bindParam(':qualification', $data->qualification);
    $stmt->bindParam(':stream', $data->stream);
    $stmt->bindParam(':skills', $skills);
    $stmt->bindParam(':interests', $data->interests);
    $stmt->bindParam(':work_type', $data->workType);

    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Qualification details saved successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to save qualification details'
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
