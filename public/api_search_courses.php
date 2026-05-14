<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

checkAuth(['Admin']);

$search = $_GET['q'] ?? '';

try {
    $sql = "
        SELECT c.*, u.first_name, u.last_name 
        FROM courses c 
        LEFT JOIN teachers t ON c.teacher_id = t.id 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE 1=1
    ";
    
    $params = [];
    if ($search) {
        $sql .= " AND (c.course_name LIKE :q1 OR c.course_code LIKE :q2 OR u.first_name LIKE :q3 OR u.last_name LIKE :q4)";
        $params['q1'] = "%$search%";
        $params['q2'] = "%$search%";
        $params['q3'] = "%$search%";
        $params['q4'] = "%$search%";
    }
    
    $sql .= " ORDER BY c.course_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($courses);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
