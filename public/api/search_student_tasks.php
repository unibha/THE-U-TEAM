<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Student']);

$studentId = $_SESSION['user_id'];
$search = $_GET['q'] ?? '';

try {
    // Get internal student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$studentId]);
    $studentData = $stmt->fetch();
    $internalStudentId = $studentData['id'] ?? 0;

    $sql = "
        SELECT a.*, c.course_name 
        FROM assignment a 
        JOIN courses c ON a.course_id = c.id 
        JOIN enrollments e ON c.id = e.course_id 
        LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = :uid
        WHERE e.student_id = :sid AND a.due_date >= NOW() AND s.submission_id IS NULL
    ";
    
    $params = ['uid' => $studentId, 'sid' => $internalStudentId];
    if ($search) {
        $sql .= " AND (a.title LIKE :s1 OR c.course_name LIKE :s2)";
        $params['s1'] = "%$search%";
        $params['s2'] = "%$search%";
    }
    
    $sql .= " ORDER BY a.due_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
