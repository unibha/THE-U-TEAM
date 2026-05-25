<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Student']);

$studentUserId = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';

try {
    // Get internal student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$studentUserId]);
    $studentInternalId = $stmt->fetchColumn();

    $sql = "
        SELECT r.*, c.course_name, c.course_code, u.first_name, u.last_name 
        FROM resources r 
        JOIN courses c ON r.course_id = c.id 
        JOIN enrollments e ON c.id = e.course_id 
        JOIN users u ON r.uploaded_by = u.id 
        WHERE e.student_id = :sid
    ";
    
    $params = ['sid' => $studentInternalId];
    if ($search) {
        $sql .= " AND (r.title LIKE :s1 OR r.description LIKE :s2 OR c.course_name LIKE :s3 OR c.course_code LIKE :s4)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
        $params['s3'] = "%$search%";
        $params['s4'] = "%$search%";
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
