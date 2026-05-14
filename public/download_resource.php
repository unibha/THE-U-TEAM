<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only logged in users can download
checkAuth();

$fileId = $_GET['id'] ?? '';

if (!$fileId) {
    die("Invalid request.");
}

$stmt = $pdo->prepare("SELECT * FROM resources WHERE resource_id = ?");
$stmt->execute([$fileId]);
$resource = $stmt->fetch();

if (!$resource) {
    die("File not found.");
}

$filePath = $resource['file_path']; // This is 'uploads/resources/...'
$fullPath = __DIR__ . '/' . $filePath;

if (file_exists($fullPath)) {
    // Set headers to force download or view
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $resource['file_type']);
    header('Content-Disposition: inline; filename="' . $resource['file_name'] . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    echo "<h1>File Debug Info</h1>";
    echo "<b>DB Path:</b> " . htmlspecialchars($filePath) . "<br>";
    echo "<b>Resolved Path:</b> " . htmlspecialchars($fullPath) . "<br>";
    echo "<b>Directory:</b> " . __DIR__ . "<br>";
    echo "<b>Existence:</b> " . (file_exists($fullPath) ? "YES" : "NO") . "<br>";
    die("<br>Please tell me what the information above says!");
}
?>
