<?php
require_once '../includes/auth_middleware.php';
require_once '../includes/db.php';

// Only logged in users can download
checkAuth();

$fileId = $_GET['id'] ?? '';
$type = $_GET['type'] ?? 'resource';

if (!$fileId) {
    die("Invalid request.");
}

if ($type === 'submission') {
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE submission_id = ?");
} else {
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE resource_id = ?");
}

$stmt->execute([$fileId]);
$item = $stmt->fetch();

if (!$item) {
    die("File not found in database.");
}

// In the database, paths are usually stored as 'uploads/resources/filename' or 'uploads/submissions/filename'
// These are relative to the 'public/' directory where this script lives.
$filePath = $item['file_path'];
$fullPath = __DIR__ . '/' . $filePath;

// Check if file exists on disk
if (file_exists($fullPath)) {
    // Determine mime type if not stored
    $mimeType = isset($item['file_type']) ? $item['file_type'] : '';
    if (!$mimeType) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath);
        finfo_close($finfo);
    }

    // Set headers to force download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $item['file_name'] . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    // If not found, try one level up if the path was stored differently
    $altPath = dirname(__DIR__) . '/' . $filePath;
    if (file_exists($altPath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $item['file_name'] . '"');
        readfile($altPath);
        exit;
    }
    
    die("File not found on server.");
}
?>
