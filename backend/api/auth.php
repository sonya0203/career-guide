<?php
/**
 * Authentication API
 * Handles user registration, login, logout, and session management
 */

// Start session
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../../config/database.php';
require_once '../../config/mail.php';
require_once '../lib/PHPMailer.php';
require_once '../lib/SMTP.php';
require_once '../lib/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP as SMTPClass;
use PHPMailer\PHPMailer\Exception as MailException;

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
    case 'send-otp':
        handleSendOtp($db);
        break;
    case 'verify-otp':
        handleVerifyOtp($db);
        break;
    case 'reset-password':
        handleResetPassword($db);
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

/**
 * Send OTP for password reset
 */
function handleSendOtp($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required fields
    if(empty($data->email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email is required'
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
    
    // Check if email exists in database
    $query = "SELECT id, email FROM auth_users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    if(!$stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'No account found with this email address'
        ]);
        return;
    }
    
    // Generate 6-digit OTP
    $otp = sprintf("%06d", mt_rand(0, 999999));
    
    // Delete any existing OTPs for this email
    $deleteQuery = "DELETE FROM password_reset_otps WHERE email = :email";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':email', $data->email);
    $deleteStmt->execute();
    
    // Insert new OTP - use MySQL's NOW() + INTERVAL to avoid PHP/MySQL timezone mismatch
    $insertQuery = "INSERT INTO password_reset_otps (email, otp, expires_at) VALUES (:email, :otp, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':email', $data->email);
    $insertStmt->bindParam(':otp', $otp);
    
    if($insertStmt->execute()) {
        // Send OTP via email using PHPMailer
        $mailSent = false;
        $mailError = '';
        
        try {
            $mail = new PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            
            // Recipients
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($data->email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code - Career Guide';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2 style="color: #667eea; text-align: center;">Career Guide</h2>
                    <div style="background: #f7fafc; border-radius: 10px; padding: 30px; text-align: center;">
                        <h3 style="color: #2d3748;">Password Reset Code</h3>
                        <p style="color: #718096;">Use the following code to reset your password:</p>
                        <div style="background: #667eea; color: white; font-size: 32px; letter-spacing: 8px; padding: 15px 30px; border-radius: 8px; display: inline-block; font-weight: bold;">' . $otp . '</div>
                        <p style="color: #718096; margin-top: 20px;">This code will expire in <strong>10 minutes</strong>.</p>
                        <p style="color: #a0aec0; font-size: 12px;">If you didn\'t request this code, please ignore this email.</p>
                    </div>
                </div>';
            $mail->AltBody = "Your password reset code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";
            
            $mail->send();
            $mailSent = true;
        } catch (MailException $e) {
            $mailError = $mail->ErrorInfo;
        }
        
        if ($mailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Verification code sent to your email'
            ]);
        } else {
            // Mail failed but OTP is saved - return OTP for development
            echo json_encode([
                'success' => true,
                'message' => 'Verification code generated (email delivery failed: ' . $mailError . ')',
                'debug_otp' => $otp // Remove this in production!
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to generate verification code'
        ]);
    }
}

/**
 * Verify OTP
 */
function handleVerifyOtp($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required fields
    if(empty($data->email) || empty($data->otp)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Email and OTP are required'
        ]);
        return;
    }
    
    // Get OTP from database
    $query = "SELECT * FROM password_reset_otps WHERE email = :email AND otp = :otp AND used = 0 AND expires_at > NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->bindParam(':otp', $data->otp);
    $stmt->execute();
    
    $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($otpRecord) {
        // Mark OTP as used
        $updateQuery = "UPDATE password_reset_otps SET used = 1 WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':id', $otpRecord['id']);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired verification code'
        ]);
    }
}

/**
 * Reset password
 */
function handleResetPassword($db) {
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
    
    // Validate password length
    if(strlen($data->password) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 6 characters long'
        ]);
        return;
    }
    
    // Check if user exists
    $checkQuery = "SELECT id FROM auth_users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $data->email);
    $checkStmt->execute();
    
    if(!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        return;
    }
    
    // Hash new password
    $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
    
    // Update password
    $updateQuery = "UPDATE auth_users SET password = :password WHERE email = :email";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashed_password);
    $updateStmt->bindParam(':email', $data->email);
    
    if($updateStmt->execute()) {
        // Delete used OTP records for this email
        $deleteQuery = "DELETE FROM password_reset_otps WHERE email = :email";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':email', $data->email);
        $deleteStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to reset password'
        ]);
    }
}
?>
