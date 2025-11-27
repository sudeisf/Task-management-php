<?php

class Comment {

    private $conn;
    private $table = "comments";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($task_id, $user_id, $comment) {
        $sql = "INSERT INTO $this->table (task_id, user_id, comment) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param("iis", $task_id, $user_id, $comment);
        return $stmt->execute();
    }

    public function getByTask($task_id) {
        $sql = "SELECT c.*, u.name FROM $this->table c
                JOIN users u ON c.user_id = u.id
                WHERE task_id = ?
                ORDER BY c.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $task_id);

        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
