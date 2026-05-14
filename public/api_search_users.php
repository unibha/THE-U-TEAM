<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only Admin allowed
checkAuth(['Admin']);

$search = $_GET['q'] ?? '';
$roleFilter = $_GET['role'] ?? '';

try {
    $sql = "
        SELECT u.*, s.class_name, t.department 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        LEFT JOIN teachers t ON u.id = t.user_id 
        WHERE u.role != 'Admin'
    ";
    
    $params = [];
    if ($search) {
        $sql .= " AND (u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR u.email LIKE :q3)";
        $params['q1'] = "%$search%";
        $params['q2'] = "%$search%";
        $params['q3'] = "%$search%";
    }
    
    if ($roleFilter && in_array($roleFilter, ['Student', 'Teacher'])) {
        $sql .= " AND u.role = :role";
        $params['role'] = $roleFilter;
    }
    
    $sql .= " ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
