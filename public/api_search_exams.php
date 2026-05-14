<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

checkAuth(['Admin']);

$search = $_GET['q'] ?? '';

try {
    $sql = "
        SELECT e.*, c.course_name, c.course_code 
        FROM exam e 
        JOIN courses c ON e.course_id = c.id 
        WHERE 1=1
    ";
    
    $params = [];
    if ($search) {
        $sql .= " AND (e.exam_name LIKE :q1 OR c.course_name LIKE :q2 OR c.course_code LIKE :q3)";
        $params['q1'] = "%$search%";
        $params['q2'] = "%$search%";
        $params['q3'] = "%$search%";
    }
    
    $sql .= " ORDER BY e.exam_date ASC, e.start_time ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($exams);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
