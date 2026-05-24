<?php
// config.php – central configuration for the Academic Management System

// Absolute path to the project root directory
define('ROOT_DIR', __DIR__);

// Base URL for the web application (adjust if the project is served from a different host or subfolder)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $protocol = 'https://';
} else {
    $protocol = 'http://';
}
define('ROOT_URL', $protocol . $_SERVER['HTTP_HOST'] . '/Academic Management System');

// Start a session if not already started (needed for auth, CSRF, JWT, etc.)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection (creates $pdo variable)
require_once ROOT_DIR . '/includes/db.php';

// Optionally, you can set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
