<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/db.php';
require_once '../includes/auth_middleware.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest() {
    global $pdo;
    
    $id = $_GET['id'] ?? null;
    $target_audience = $_GET['target_audience'] ?? null;
    $type = $_GET['type'] ?? null;
    $is_active = $_GET['is_active'] ?? null;
    
    if ($id) {
        // Get specific notice
        $stmt = $pdo->prepare("
            SELECT n.*, u.first_name, u.last_name, u.role 
            FROM notices n 
            JOIN users u ON n.posted_by = u.id 
            WHERE n.id = ? AND (n.end_date IS NULL OR n.end_date >= NOW())
        ");
        $stmt->execute([$id]);
        $notice = $stmt->fetch();
        
        if (!$notice) {
            http_response_code(404);
            echo json_encode(['error' => 'Notice not found']);
            return;
        }
        
        echo json_encode($notice);
    } else {
        // Get all notices with filters
        $query = "
            SELECT n.*, u.first_name, u.last_name, u.role 
            FROM notices n 
            JOIN users u ON n.posted_by = u.id 
            WHERE (n.end_date IS NULL OR n.end_date >= NOW())
        ";
        $params = [];
        
        if ($target_audience) {
            $query .= " AND n.target_audience IN (?, 'All')";
            $params[] = $target_audience;
        }
        
        if ($type) {
            $query .= " AND n.type = ?";
            $params[] = $type;
        }
        
        if ($is_active !== null) {
            $query .= " AND n.is_active = ?";
            $params[] = $is_active;
        }
        
        $query .= " ORDER BY n.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $notices = $stmt->fetchAll();
        
        echo json_encode($notices);
    }
}

function handlePostRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['title']) || empty($data['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO notices (title, description, type, target_audience, posted_by, is_active, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['title'],
        $data['description'] ?? null,
        $data['type'] ?? 'General',
        $data['target_audience'] ?? 'All',
        $user['user_id'],
        $data['is_active'] ?? 1,
        $data['start_date'] ?? date('Y-m-d H:i:s'),
        $data['end_date'] ?? null
    ]);
    
    $noticeId = $pdo->lastInsertId();
    
    http_response_code(201);
    echo json_encode([
        'message' => 'Notice created successfully',
        'notice_id' => $noticeId
    ]);
}

function handlePutRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Notice ID is required']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if notice exists and user has permission
    $stmt = $pdo->prepare("SELECT posted_by FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch();
    
    if (!$notice) {
        http_response_code(404);
        echo json_encode(['error' => 'Notice not found']);
        return;
    }
    
    // Only admin or the original poster can update
    if ($user['role'] !== 'Admin' && $notice['posted_by'] != $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        return;
    }
    
    $updateFields = [];
    $params = [];
    
    if (isset($data['title'])) {
        $updateFields[] = "title = ?";
        $params[] = $data['title'];
    }
    
    if (isset($data['description'])) {
        $updateFields[] = "description = ?";
        $params[] = $data['description'];
    }
    
    if (isset($data['type'])) {
        $updateFields[] = "type = ?";
        $params[] = $data['type'];
    }
    
    if (isset($data['target_audience'])) {
        $updateFields[] = "target_audience = ?";
        $params[] = $data['target_audience'];
    }
    
    if (isset($data['is_active'])) {
        $updateFields[] = "is_active = ?";
        $params[] = $data['is_active'];
    }
    
    if (isset($data['end_date'])) {
        $updateFields[] = "end_date = ?";
        $params[] = $data['end_date'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    
    $params[] = $id;
    
    $stmt = $pdo->prepare("
        UPDATE notices 
        SET " . implode(', ', $updateFields) . " 
        WHERE id = ?
    ");
    
    $stmt->execute($params);
    
    echo json_encode(['message' => 'Notice updated successfully']);
}

function handleDeleteRequest() {
    global $pdo;
    
    // Validate JWT token
    $user = validateJWT();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Notice ID is required']);
        return;
    }
    
    // Check if notice exists and user has permission
    $stmt = $pdo->prepare("SELECT posted_by FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch();
    
    if (!$notice) {
        http_response_code(404);
        echo json_encode(['error' => 'Notice not found']);
        return;
    }
    
    // Only admin or the original poster can delete
    if ($user['role'] !== 'Admin' && $notice['posted_by'] != $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['message' => 'Notice deleted successfully']);
}
?>
