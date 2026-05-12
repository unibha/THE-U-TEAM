<?php
function sendNotification($userId, $title, $message, $type = 'System') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notification (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function notifyEnrolledStudents($courseId, $title, $message, $type = 'Academic') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT s.user_id 
            FROM enrollments e 
            JOIN students s ON e.student_id = s.id 
            WHERE e.course_id = ?
        ");
        $stmt->execute([$courseId]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($students as $studentUserId) {
            sendNotification($studentUserId, $title, $message, $type);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function notifyAdmins($title, $message, $type = 'System') {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'Admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($admins as $adminId) {
            sendNotification($adminId, $title, $message, $type);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function notifyTeacher($courseId, $title, $message, $type = 'Academic') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT u.id 
            FROM courses c 
            JOIN teachers t ON c.teacher_id = t.id 
            JOIN users u ON t.user_id = u.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$courseId]);
        $teacherUserId = $stmt->fetchColumn();

        if ($teacherUserId) {
            sendNotification($teacherUserId, $title, $message, $type);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
