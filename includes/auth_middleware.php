<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'jwttoken.php';

/**
 * Main Authorization Middleware
 * Verifies JWT token and checks role-based access
 */
function checkAuth($allowedRoles = []) {
    $jwt = $_SESSION['token'] ?? '';

    // 1. Check if token exists
    if (!$jwt) {
        header("Location: login.php");
        exit();
    }

    // 2. Validate JWT
    $decoded = validateJWT($jwt);

    if (!$decoded || $decoded === 'expired') {
        // Destroy session if token is invalid or expired
        session_destroy();
        header("Location: login.php?error=session_expired");
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
                header("Location: admin_dashboard.php");
                break;
            case 'Teacher':
                header("Location: teacher_dashboard.php");
                break;
            case 'Student':
                header("Location: student_dashboard.php");
                break;
            default:
                header("Location: login.php");
        }
        exit();
    }

    // Populate session with fresh data from JWT if needed
    $_SESSION['user_id'] = $decoded['user_id'];
    $_SESSION['username'] = $decoded['username'];
    $_SESSION['role'] = $decoded['role'];
}
?>
