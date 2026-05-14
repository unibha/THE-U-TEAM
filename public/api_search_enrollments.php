<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

checkAuth(['Admin']);

$search = $_GET['q'] ?? '';
$courseId = $_GET['course'] ?? '';

if (!$courseId) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "
        SELECT e.id as enrollment_id, u.first_name, u.last_name, u.email, s.class_name
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE e.course_id = :course
    ";
    
    $params = ['course' => $courseId];
    if ($search) {
        $sql .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
        $params['s3'] = "%$search%";
    }
    
    $sql .= " ORDER BY u.first_name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
