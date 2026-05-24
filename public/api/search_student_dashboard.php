<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Student']);

$studentUserId = $_SESSION['user_id'];
$query = $_GET['q'] ?? '';

if (!$query) {
    echo json_encode(['courses' => [], 'tasks' => []]);
    exit();
}

try {
    // Get internal student ID
    $stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$studentUserId]);
    $studentId = $stmt->fetchColumn();

    $searchTerm = "%$query%";

    // Search my enrolled courses
    $stmt = $pdo->prepare("
        SELECT c.course_name, c.course_code
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.student_id = :sid AND (c.course_name LIKE :q1 OR c.course_code LIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['sid' => $studentId, 'q1' => $searchTerm, 'q2' => $searchTerm]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Search my pending assignments
    $stmt = $pdo->prepare("
        SELECT a.title, c.course_name, a.due_date
        FROM assignment a
        JOIN courses c ON a.course_id = c.id
        JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = :uid
        WHERE e.student_id = :sid AND s.submission_id IS NULL AND (a.title LIKE :q1 OR c.course_name LIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['uid' => $studentUserId, 'sid' => $studentId, 'q1' => $searchTerm, 'q2' => $searchTerm]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['courses' => $courses, 'tasks' => $tasks]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
