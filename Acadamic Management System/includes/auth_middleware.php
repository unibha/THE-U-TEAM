<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/jwttoken.php';

function checkAuth($allowedRoles = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: login.php");
        exit();
    }
    
    // Check if account is active (Pro Security Check)
    require_once __DIR__ . '/db.php';
    global $pdo;
    $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $activeStatus = $stmt->fetchColumn();

    if (!$activeStatus) {
        $_SESSION['verifying_email'] = $_SESSION['email'] ?? '';
        header("Location: verify_otp.php");
        exit();
    }
    
    $userRole = $_SESSION['role'];
    if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
        if ($userRole === 'Admin') {
            header("Location: admin_dashboard.php");
        } else if ($userRole === 'Teacher') {
            header("Location: teacher_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    }
}
?>
