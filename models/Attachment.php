<?php

class Attachment {

    private $conn;
    private $table = "attachments";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($task_id, $file_path, $uploaded_by) {
        $sql = "INSERT INTO $this->table (task_id, file_path, uploaded_by) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("isi", $task_id, $file_path, $uploaded_by);
        return $stmt->execute();
    }

    public function findByTask($task_id) {
        $sql = "SELECT * FROM $this->table WHERE task_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $task_id);

        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
