<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';

try {
    $sql = "
        SELECT a.*, c.course_name, 
        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.assignment_id) as submission_count
        FROM assignment a
        JOIN courses c ON a.course_id = c.id
        WHERE a.created_by = :uid
    ";
    
    $params = ['uid' => $teacherUserId];
    if ($search) {
        $sql .= " AND (a.title LIKE :s1 OR c.course_name LIKE :s2)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
    }
    
    $sql .= " ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
