<?php
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

require_once __DIR__ . '/jwttoken.php';

/**
 * Main Authorization Middleware
 * Verifies JWT token and checks role-based access
 */
function checkAuth($allowedRoles = []) {
    $jwt = $_SESSION['token'] ?? '';

    // 1. Check if token exists
    if (!$jwt) {
        header("Location: " . ROOT_URL . "/public/auth/login.php");
        exit();
    }

    // 2. Validate JWT
    $decoded = validateJWT($jwt);

    if (!$decoded || $decoded === 'expired') {
        // Destroy session if token is invalid or expired
        session_destroy();
        header("Location: " . ROOT_URL . "/public/auth/login.php?error=session_expired");
        exit();
    }

    // 3. Refresh token if user is active (extends session)
    $_SESSION['token'] = refreshTokenIfNeeded($jwt);

    // 4. Role-Based Route Protection
    $userRole = $decoded['role'];
    
    // Check if role is allowed for this specific page
    if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
        // Unauthorized cross-role access: Redirect to own dashboard
        switch ($userRole) {
            case 'Admin':
                header("Location: " . ROOT_URL . "/public/admin/dashboard.php");
                break;
            case 'Teacher':
                header("Location: " . ROOT_URL . "/public/teacher/dashboard.php");
                break;
            case 'Student':
                header("Location: " . ROOT_URL . "/public/student/dashboard.php");
                break;
            default:
                header("Location: " . ROOT_URL . "/public/auth/login.php");
        }
        exit();
    }

    // Populate session with fresh data from JWT if needed
    $_SESSION['user_id'] = $decoded['user_id'];
    $_SESSION['username'] = $decoded['username'];
    $_SESSION['role'] = $decoded['role'];
}
?>
