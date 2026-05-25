<?php
require_once __DIR__ . '/../../config.php';

/**
 * Global Notification Helper Utilities
 */

/**
 * Send notification to a specific user
 */
function sendNotification($userId, $title, $message, $type = 'System', $isUrgent = 0) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notification (user_id, title, message, type, is_urgent, is_read) VALUES (?, ?, ?, ?, ?, 0)");
        return $stmt->execute([$userId, $title, $message, $type, $isUrgent]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Notify all students enrolled in a specific course
 */
function notifyEnrolledStudents($courseId, $title, $message, $type = 'Academic', $isUrgent = 0) {
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
            sendNotification($studentUserId, $title, $message, $type, $isUrgent);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Notify all users of a specific role
 */
function notifyRole($role, $title, $message, $type = 'System', $isUrgent = 0) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ?");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $userId) {
            sendNotification($userId, $title, $message, $type, $isUrgent);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Notify target audience of a notice
 */
function notifyAudience($audience, $title, $message, $type = 'System', $isUrgent = 0) {
    if ($audience === 'All') {
        notifyRole('Student', $title, $message, $type, $isUrgent);
        notifyRole('Teacher', $title, $message, $type, $isUrgent);
    } else {
        notifyRole($audience, $title, $message, $type, $isUrgent);
    }
}

/**
 * Notify teacher of a course
 */
function notifyTeacher($courseId, $title, $message, $type = 'Academic', $isUrgent = 0) {
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
            sendNotification($teacherUserId, $title, $message, $type, $isUrgent);
        }
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Time Ago Formatter
 */
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 0) $diff = 0; // Safeguard against slight drift
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return round($diff / 60) . "m ago";
    if ($diff < 86400) return round($diff / 3600) . "h ago";
    return date('M d', $time);
}

/**
 * Notify all admins
 */
function notifyAdmins($title, $message, $type = 'System', $isUrgent = 0) {
    return notifyRole('Admin', $title, $message, $type, $isUrgent);
}


/**
 * Mark a single notification as read
 */
function markNotificationRead(int $notificationId): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$notificationId]);
}

/**
 * Mark all notifications for a user as read
 */
function markAllNotificationsRead(int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notification SET is_read = 1 WHERE user_id = ?");
    return $stmt->execute([$userId]);
}

/**
 * Get unread notification count for a user
 */
function getUnreadCount(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
?>
