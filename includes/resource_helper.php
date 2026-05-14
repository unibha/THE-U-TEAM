<?php
/**
 * Resource Management Helper Utility
 */

/**
 * Handle File Upload
 */
function uploadResourceFile($file, $destinationDir = 'uploads/resources/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error or no file selected.'];
    }

    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 50MB limit.'];
    }

    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png'
    ];

    $fileType = $file['type'];
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, PPT, and Images are allowed.'];
    }

    // Generate unique name
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid('res_', true) . '.' . $ext;
    $targetPath = $destinationDir . $uniqueName;

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => true,
            'file_name' => $file['name'],
            'unique_name' => $uniqueName,
            'file_path' => $targetPath,
            'file_type' => $fileType
        ];
    }

    return ['success' => false, 'message' => 'Failed to save file on server.'];
}

/**
 * Delete Resource File
 */
function deleteResourceFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}
?>
