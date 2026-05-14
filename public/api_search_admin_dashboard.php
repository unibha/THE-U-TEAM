<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

checkAuth(['Admin']);

$query = $_GET['q'] ?? '';

if (!$query) {
    echo json_encode(['users' => [], 'courses' => []]);
    exit();
}

try {
    $searchTerm = "%$query%";

    // Search users (Students and Teachers)
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, email, role
        FROM users
        WHERE (first_name LIKE :q1 OR last_name LIKE :q2 OR email LIKE :q3)
        LIMIT 5
    ");
    $stmt->execute(['q1' => $searchTerm, 'q2' => $searchTerm, 'q3' => $searchTerm]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Search courses
    $stmt = $pdo->prepare("
        SELECT course_name, course_code
        FROM courses
        WHERE (course_name LIKE :q1 OR course_code LIKE :q2)
        LIMIT 5
    ");
    $stmt->execute(['q1' => $searchTerm, 'q2' => $searchTerm]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['users' => $users, 'courses' => $courses]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
