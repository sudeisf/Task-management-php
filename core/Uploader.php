<?php

class Uploader
{
    private $uploadPath;
    private $maxFileSize;
    private $allowedExtensions;
    private $errors = [];

    public function __construct($uploadPath = null, $maxFileSize = null, $allowedExtensions = null)
    {
        $this->uploadPath = $uploadPath ?: UPLOAD_PATH;
        $this->maxFileSize = $maxFileSize ?: MAX_FILE_SIZE;
        $this->allowedExtensions = $allowedExtensions ?: ALLOWED_EXTENSIONS;

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Upload single file
     */
    public function uploadFile($file, $customName = null)
    {
        $this->errors = [];

        if (!$this->validateFile($file)) {
            return false;
        }

        $fileName = $this->generateFileName($file['name'], $customName);
        $filePath = $this->uploadPath . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'original_name' => $file['name'],
                'file_name' => $fileName,
                'file_path' => $fileName,
                'file_size' => $file['size'],
                'file_type' => $file['type'],
                'extension' => $this->getFileExtension($file['name'])
            ];
        } else {
            $this->errors[] = "Failed to move uploaded file.";
            return false;
        }
    }

    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles($files, $customNames = [])
    {
        $uploadedFiles = [];
        $this->errors = [];

        if (!is_array($files['name'])) {
            // Single file uploaded as multiple
            $result = $this->uploadFile($files, $customNames[0] ?? null);
            if ($result) {
                $uploadedFiles[] = $result;
            }
            return $uploadedFiles;
        }

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip empty file slots
            }

            $result = $this->uploadFile($file, $customNames[$i] ?? null);
            if ($result) {
                $uploadedFiles[] = $result;
            }
        }

        return $uploadedFiles;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = "File size exceeds maximum allowed size of " . $this->formatBytes($this->maxFileSize) . ".";
            return false;
        }

        // Check file extension
        $extension = $this->getFileExtension($file['name']);
        if (!in_array(strtolower($extension), $this->allowedExtensions)) {
            $this->errors[] = "File type '{$extension}' is not allowed. Allowed types: " . implode(', ', $this->allowedExtensions);
            return false;
        }

        // Check if file is actually uploaded via HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = "File was not uploaded via HTTP POST.";
            return false;
        }

        return true;
    }

    /**
     * Generate unique file name
     */
    private function generateFileName($originalName, $customName = null)
    {
        $extension = $this->getFileExtension($originalName);

        if ($customName) {
            $baseName = $this->sanitizeFileName($customName);
        } else {
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $baseName = $this->sanitizeFileName($baseName);
        }

        // Add timestamp and random string to ensure uniqueness
        $timestamp = date('Y-m-d_H-i-s');
        $random = bin2hex(random_bytes(4));

        return $timestamp . '_' . $random . '_' . $baseName . '.' . $extension;
    }

    /**
     * Sanitize file name
     */
    private function sanitizeFileName($fileName)
    {
        // Remove or replace problematic characters
        $fileName = preg_replace('/[^a-zA-Z0-9\-_\.\s]/', '', $fileName);
        $fileName = preg_replace('/\s+/', '_', $fileName);
        $fileName = trim($fileName, '.-_');

        // Ensure filename is not too long
        if (strlen($fileName) > 100) {
            $fileName = substr($fileName, 0, 100);
        }

        return $fileName;
    }

    /**
     * Get file extension
     */
    private function getFileExtension($fileName)
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
            case UPLOAD_ERR_FORM_SIZE:
                return "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.";
            case UPLOAD_ERR_PARTIAL:
                return "The uploaded file was only partially uploaded.";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing a temporary folder.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk.";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload.";
            default:
                return "Unknown upload error.";
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Delete file from filesystem
     */
    public function deleteFile($filePath)
    {
        $fullPath = $this->uploadPath . '/' . $filePath;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Check if file exists
     */
    public function fileExists($filePath)
    {
        return file_exists($this->uploadPath . '/' . $filePath);
    }

    /**
     * Get file size
     */
    public function getFileSize($filePath)
    {
        $fullPath = $this->uploadPath . '/' . $filePath;

        if (file_exists($fullPath)) {
            return filesize($fullPath);
        }

        return false;
    }

    /**
     * Get file info
     */
    public function getFileInfo($filePath)
    {
        $fullPath = $this->uploadPath . '/' . $filePath;

        if (file_exists($fullPath)) {
            return [
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath),
                'type' => mime_content_type($fullPath),
                'extension' => $this->getFileExtension($filePath)
            ];
        }

        return false;
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function getFirstError()
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Clear errors
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * Get upload path
     */
    public function getUploadPath()
    {
        return $this->uploadPath;
    }

    /**
     * Set upload path
     */
    public function setUploadPath($path)
    {
        $this->uploadPath = $path;

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Get max file size
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Set max file size
     */
    public function setMaxFileSize($size)
    {
        $this->maxFileSize = $size;
    }

    /**
     * Get allowed extensions
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * Set allowed extensions
     */
    public function setAllowedExtensions($extensions)
    {
        $this->allowedExtensions = $extensions;
    }

    /**
     * Add allowed extension
     */
    public function addAllowedExtension($extension)
    {
        if (!in_array(strtolower($extension), $this->allowedExtensions)) {
            $this->allowedExtensions[] = strtolower($extension);
        }
    }

    /**
     * Remove allowed extension
     */
    public function removeAllowedExtension($extension)
    {
        $key = array_search(strtolower($extension), $this->allowedExtensions);
        if ($key !== false) {
            unset($this->allowedExtensions[$key]);
            $this->allowedExtensions = array_values($this->allowedExtensions);
        }
    }
}
