<?php
/**
 * Assignment Helper - Manages file operations for Student Submissions
 */

/**
 * Handle Student Submission Upload
 */
function uploadSubmissionFile($file, $destinationDir = 'uploads/submissions/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error or no file selected.'];
    }

    $maxSize = 50 * 1024 * 1024; // 50MB (Increased from 10MB as per previous update)
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 50MB limit.'];
    }

    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ];

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, and Images are allowed.'];
    }

    // Ensure directory exists (relative to public/)
    $targetPath = __DIR__ . '/../public/' . $destinationDir;
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0777, true);
    }

    $fileName = basename($file['name']);
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
    $uniqueName = 'sub_' . uniqid() . '.' . $fileExt;
    $filePath = $destinationDir . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $targetPath . $uniqueName)) {
        return [
            'success' => true,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $file['type']
        ];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

/**
 * Delete Submission File
 */
function deleteSubmissionFile($filePath) {
    $fullPath = __DIR__ . '/../public/' . $filePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}
?>
