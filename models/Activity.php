<?php

class Activity {

    private $conn;
    private $table = "activity_logs";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function log($user_id, $task_id, $action) {
        $sql = "INSERT INTO $this->table (user_id, task_id, action) VALUES (?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $task_id, $action);

        return $stmt->execute();
    }

    public function all() {
        $sql = "SELECT a.*, u.name FROM $this->table a
                JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC";

        return $this->conn->query($sql);
    }
}
?>
