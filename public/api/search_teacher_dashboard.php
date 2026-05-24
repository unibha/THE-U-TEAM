<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/db.php';

checkAuth(['Teacher']);

$teacherUserId = $_SESSION['user_id'];
$query = $_GET['q'] ?? '';

if (!$query) {
    echo json_encode(['students' => [], 'courses' => []]);
    exit();
}

try {
    // Get internal teacher ID
    $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
    $stmt->execute([$teacherUserId]);
    $teacherId = $stmt->fetchColumn();

    // Search students in my courses
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.first_name, u.last_name, u.email
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = :tid AND (u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR u.email LIKE :q3)
        LIMIT 5
    ");
    $searchTerm = "%$query%";
    $stmt->execute(['tid' => $teacherId, 'q1' => $searchTerm, 'q2' => $searchTerm, 'q3' => $searchTerm]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Search courses
    $stmt = $pdo->prepare("
        SELECT course_name, course_code
        FROM courses
        WHERE teacher_id = :tid AND (course_name LIKE :q1 OR course_code LIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['tid' => $teacherId, 'q1' => $searchTerm, 'q2' => $searchTerm]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['students' => $students, 'courses' => $courses]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
