<?php
/**
 * Users API - CRUD Operations
 * Handles GET, POST, PUT, DELETE requests for users
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all users or single user
        if(isset($_GET['id'])) {
            $id = $_GET['id'];
            $query = "SELECT id, full_name, email, role, created_at, updated_at FROM auth_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                echo json_encode([
                    'success' => true,
                    'data' => $user
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }
        } else {
            // Pagination parameters
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $roleFilter = isset($_GET['role']) && in_array($_GET['role'], ['user', 'admin']) ? $_GET['role'] : '';
            
            // Build WHERE clause
            $conditions = [];
            $params = [];
            
            if($roleFilter) {
                $conditions[] = "role = :role";
                $params[':role'] = $roleFilter;
            }
            
            if($search) {
                $searchParam = "%{$search}%";
                $conditions[] = "(full_name LIKE :search OR email LIKE :search2)";
                $params[':search'] = $searchParam;
                $params[':search2'] = $searchParam;
            }
            
            $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM auth_users {$whereClause}";
            $countStmt = $db->prepare($countQuery);
            foreach($params as $key => $val) {
                $countStmt->bindValue($key, $val);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get paginated users
            $query = "SELECT id, full_name, email, role, created_at, updated_at FROM auth_users {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $db->prepare($query);
            foreach($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $users,
                'count' => count($users),
                'total' => intval($total),
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($total / $limit)
            ]);
        }
        break;
        
    case 'POST':
        // Create new user
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->full_name) && !empty($data->email) && !empty($data->password)) {
            $query = "INSERT INTO auth_users (full_name, email, password) VALUES (:full_name, :email, :password)";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':full_name', $data->full_name);
            $stmt->bindParam(':email', $data->email);
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $password_hash);
            
            if($stmt->execute()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'User created successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to create user'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Full name, email and password are required'
            ]);
        }
        break;
        
    case 'PUT':
        // Update user
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id) && !empty($data->full_name) && !empty($data->email)) {
            $query = "UPDATE auth_users SET full_name = :full_name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':id', $data->id);
            $stmt->bindParam(':full_name', $data->full_name);
            $stmt->bindParam(':email', $data->email);
            
            if($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to update user'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'ID, full name and email are required'
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete user
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "DELETE FROM auth_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data->id);
            
            if($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to delete user'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>