<?php
// actions/upload.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload error', 'code' => $file['error']]);
    exit;
}

$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$originalName = basename($file['name']);
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
// sanitize filename (basic)
$base = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
$targetName = $base . '_' . time() . ($ext ? '.' . $ext : '');
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Return relative path for convenience
$relativePath = 'uploads/' . $targetName;

echo json_encode(['success' => true, 'path' => $relativePath, 'original' => $originalName]);
