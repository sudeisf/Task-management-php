<?php

require_once __DIR__ . '/../core/Database.php';

class Attachment
{
    private $db;
    private $table = "attachments";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Create new attachment
    public function create($data)
    {
        $sql = "INSERT INTO $this->table (task_id, uploaded_by, file_path, file_name)
                VALUES (?, ?, ?, ?)";

        $this->db->prepare($sql);
        $params = [
            $data['task_id'],
            $data['uploaded_by'],
            $data['file_path'],
            $data['file_name'] ?? null
        ];

        if ($this->db->execute($params)) {
            return $this->db->getLastInsertId();
        }

        return false;
    }

    // Get attachment by ID
    public function find($id)
    {
        $sql = "SELECT a.*, u.full_name as uploader_name, t.title as task_title
                FROM $this->table a
                LEFT JOIN users u ON a.uploaded_by = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE a.id = ?";

        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
    }

    // Get attachments by task ID
    public function getByTask($task_id, $limit = null, $offset = null)
    {
        $sql = "SELECT a.*, u.full_name as uploader_name, u.email
                FROM $this->table a
                LEFT JOIN users u ON a.uploaded_by = u.id
                WHERE a.task_id = ?
                ORDER BY a.created_at DESC";

        $params = [$task_id];

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get attachments by user ID
    public function getByUser($user_id, $limit = null, $offset = null)
    {
        $sql = "SELECT a.*, t.title as task_title, u.full_name, u.email
                FROM $this->table a
                LEFT JOIN tasks t ON a.task_id = t.id
                LEFT JOIN users u ON a.uploaded_by = u.id
                WHERE a.uploaded_by = ?
                ORDER BY a.created_at DESC";

        $params = [$user_id];

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Delete attachment
    public function delete($id, $user_id = null, $user_role = null)
    {
        // First get the attachment to check permissions and get file path
        $attachment = $this->find($id);

        if (!$attachment) {
            return false;
        }

        // Check permissions
        if (!$this->canDelete($attachment, $user_id, $user_role)) {
            return false;
        }

        // Delete from database
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $this->db->prepare($sql);

        if ($this->db->execute([$id])) {
            // Delete physical file
            $filePath = UPLOAD_PATH . '/' . $attachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            return true;
        }

        return false;
    }

    // Get attachment count for a task
    public function getCountByTask($task_id)
    {
        $sql = "SELECT COUNT(*) as count FROM $this->table WHERE task_id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$task_id]);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    // Get total file size for a task
    public function getTotalSizeByTask($task_id)
    {
        $attachments = $this->getByTask($task_id);
        $totalSize = 0;

        foreach ($attachments as $attachment) {
            $filePath = UPLOAD_PATH . '/' . $attachment['file_path'];
            if (file_exists($filePath)) {
                $totalSize += filesize($filePath);
            }
        }

        return $totalSize;
    }

    // Search attachments
    public function search($query, $user_id = null, $user_role = null, $limit = null, $offset = null)
    {
        $sql = "SELECT a.*, t.title as task_title, u.full_name, u.email
                FROM $this->table a
                LEFT JOIN tasks t ON a.task_id = t.id
                LEFT JOIN users u ON a.uploaded_by = u.id
                WHERE (a.file_name LIKE ? OR t.title LIKE ?)
                AND t.id IS NOT NULL"; // Ensure task still exists

        $params = ["%$query%", "%$query%"];

        // Restrict to user's attachments if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND a.uploaded_by = ?";
            $params[] = $user_id;
        }

        $sql .= " ORDER BY a.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get file extension from path
    public function getFileExtension($filePath)
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }

    // Get file type category
    public function getFileType($filePath)
    {
        $extension = $this->getFileExtension($filePath);

        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $documentTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        $spreadsheetTypes = ['xls', 'xlsx', 'csv'];
        $archiveTypes = ['zip', 'rar', '7z', 'tar', 'gz'];

        if (in_array($extension, $imageTypes)) {
            return 'image';
        } elseif (in_array($extension, $documentTypes)) {
            return 'document';
        } elseif (in_array($extension, $spreadsheetTypes)) {
            return 'spreadsheet';
        } elseif (in_array($extension, $archiveTypes)) {
            return 'archive';
        } else {
            return 'other';
        }
    }

    // Get file icon class for display
    public function getFileIconClass($filePath)
    {
        $fileType = $this->getFileType($filePath);

        switch ($fileType) {
            case 'image':
                return 'bi-file-earmark-image text-primary';
            case 'document':
                return 'bi-file-earmark-text text-secondary';
            case 'spreadsheet':
                return 'bi-file-earmark-spreadsheet text-success';
            case 'archive':
                return 'bi-file-earmark-zip text-warning';
            default:
                return 'bi-file-earmark text-muted';
        }
    }

    // Check if user can delete attachment
    private function canDelete($attachment, $user_id, $user_role)
    {
        if ($user_role === 'admin' || $user_role === 'manager') {
            return true;
        }

        // Users can delete their own attachments
        return $attachment['uploaded_by'] == $user_id;
    }

    // Get attachment statistics
    public function getStatistics($user_id = null, $user_role = null)
    {
        $stats = [
            'total_attachments' => 0,
            'total_size' => 0,
            'by_type' => []
        ];

        // Base query
        $baseSql = "SELECT COUNT(*) as count FROM $this->table WHERE 1=1";
        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $baseSql .= " AND uploaded_by = ?";
            $params = [$user_id];
        }

        // Total attachments
        $this->db->prepare($baseSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['total_attachments'] = $result['count'] ?? 0;

        // Get attachments for size calculation
        $attachmentsSql = "SELECT file_path FROM $this->table WHERE 1=1";
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $attachmentsSql .= " AND uploaded_by = ?";
        }

        $this->db->prepare($attachmentsSql);
        $this->db->execute($params);
        $attachments = $this->db->getResult();

        $totalSize = 0;
        $typeCount = [];

        while ($attachment = $attachments->fetch_assoc()) {
            $filePath = UPLOAD_PATH . '/' . $attachment['file_path'];
            if (file_exists($filePath)) {
                $totalSize += filesize($filePath);
                $fileType = $this->getFileType($attachment['file_path']);
                $typeCount[$fileType] = ($typeCount[$fileType] ?? 0) + 1;
            }
        }

        $stats['total_size'] = $totalSize;
        $stats['by_type'] = $typeCount;

        return $stats;
    }
}
