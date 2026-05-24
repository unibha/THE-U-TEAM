<?php
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear JWT cookie
setcookie("jwt_token", "", time() - 3600, "/");

session_destroy();
header("Location: " . ROOT_URL . "/public/auth/login.php");
exit();
?>
