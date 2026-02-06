<?php
/**
 * Authentication API
 * Handles user registration, login, logout, and session management
 */

// Start session
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle OPTIONS request
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch($action) {
    case 'register':
        handleRegister($db);
        break;
    case 'login':
        handleLogin($db);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheckSession();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

/**
 * Handle user registration
 */
function handleRegister($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required fields
    if(empty($data->full_name) || empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Full name, email and password are required'
        ]);
        return;
    }
    
    // Validate email format
    if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        return;
    }
    
    // Validate password length
    if(strlen($data->password) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 6 characters long'
        ]);
        return;
    }
    
    // Check if email already exists
    $query = "SELECT id FROM auth_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    if($stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
    
    // Insert new user
    $query = "INSERT INTO auth_users (full_name, email, password) VALUES (:full_name, :email, :password)";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':full_name', $data->full_name);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':password', $hashed_password);
    
    if($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully',
            'user_id' => $db->lastInsertId()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to register user'
        ]);
    }
}

/**
 * Handle user login
 */
function handleLogin($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required fields
    if(empty($data->email) || empty($data->password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required'
        ]);
        return;
    }
    
    // Get user by email
    $query = "SELECT * FROM auth_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify user exists and password is correct
    if($user && password_verify($data->password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Destroy all session data
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
}

/**
 * Check if user is logged in
 */
function handleCheckSession() {
    if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
}
?>
