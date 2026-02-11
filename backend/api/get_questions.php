<?php
/**
 * Get Questions API
 * Fetches relevant career questions based on user's qualification stream
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
    // 1. Get user's stream from user_qualifications
    $query = "SELECT stream, interests FROM user_qualifications WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
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
    
    // Map stream names from frontend to database categories if needed
    // Frontend values: science, commerce, arts, engineering, medical, business, computer-science, other
    // Database categories: science, commerce, arts, engineering, medical, computer-science
    
    // 2. Fetch questions based on stream
    $questionQuery = "SELECT id, category, question_text, option_a, option_b, option_c, option_d 
                      FROM career_questions 
                      WHERE category = :category 
                      ORDER BY RAND() LIMIT 5";
    
    $qStmt = $db->prepare($questionQuery);
    $qStmt->bindParam(':category', $stream);
    $qStmt->execute();
    
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no questions found for specific stream, try to get some general ones or from related fields
    if (count($questions) == 0) {
        // Fallback logic could go here, for now just return empty or generic
        // Maybe return 'science' questions as default/fallback for testing?
        // Or if stream is 'business', maybe map to 'commerce'
        if ($stream == 'business') {
             $qStmt->bindParam(':category', $commerce = 'commerce');
             $qStmt->execute();
             $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    echo json_encode([
        'success' => true,
        'stream' => $stream,
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
