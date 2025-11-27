<?php

class Task {

    private $conn;
    private $table = "tasks";

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Create task
    public function create($title, $description, $priority, $deadline, $assigned_to, $created_by) {
        $sql = "INSERT INTO $this->table 
                (title, description, priority, deadline, assigned_to, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssii", 
            $title, $description, $priority, $deadline, $assigned_to, $created_by
        );

        return $stmt->execute();
    }

    // Update task
    public function update($id, $title, $description, $priority, $deadline, $status) {
        $sql = "UPDATE $this->table SET 
                title=?, description=?, priority=?, deadline=?, status=? WHERE id=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", 
            $title, $description, $priority, $deadline, $status, $id
        );

        return $stmt->execute();
    }

    // Delete task
    public function delete($id) {
        $sql = "DELETE FROM $this->table WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    // Get one task
    public function find($id) {
        $sql = "SELECT * FROM $this->table WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Get tasks for a specific user
    public function assignedTo($user_id) {
        $sql = "SELECT * FROM $this->table WHERE assigned_to=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);

        $stmt->execute();
        return $stmt->get_result();
    }

    // Get all tasks (admin or manager)
    public function all() {
        return $this->conn->query("SELECT * FROM $this->table ORDER BY created_at DESC");
    }
}
?>
