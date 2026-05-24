<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$assignmentId = $_GET['assignment_id'] ?? '';
$search = $_GET['q'] ?? '';

if (!$assignmentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing assignment ID']);
    exit();
}

try {
    // Verify ownership
    $stmt = $pdo->prepare("SELECT assignment_id FROM assignment WHERE assignment_id = ? AND created_by = ?");
    $stmt->execute([$assignmentId, $teacherUserId]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }

    $sql = "
        SELECT s.*, u.first_name, u.last_name, u.email as student_reg
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        WHERE s.assignment_id = :aid
    ";
    
    $params = ['aid' => $assignmentId];
    if ($search) {
        $sql .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.email LIKE :s3)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
        $params['s3'] = "%$search%";
    }
    
    $sql .= " ORDER BY s.submitted_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
