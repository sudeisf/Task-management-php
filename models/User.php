<?php

class User
{
    private $conn;
    private $table = "users";

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    // ---------------- REGISTER ----------------
    public function create($fullName, $email, $password, $roleName = 'member')
    {
        // Check if email exists
        if ($this->exists($email)) {
            return false;
        }

        // Get role_id from roles table
        $role_id = $this->getRoleId($roleName);
        if (!$role_id) return false; // Role not found

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO $this->table (full_name, email, password, role_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("sssi", $fullName, $email, $hashedPassword, $role_id);
        return $stmt->execute();
    }

    // ---------------- GET ROLE ID ----------------
    private function getRoleId($roleName)
    {
        $sql = "SELECT id FROM roles WHERE name = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $roleName);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        return $role['id'] ?? false;
    }

    // ---------------- CHECK EMAIL EXISTS ----------------
    public function exists($email)
    {
        $sql = "SELECT id FROM $this->table WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    // ---------------- VERIFY LOGIN ----------------
    public function verify($email, $password)
    {
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return $user; // now includes role_name
        }
        return false;
    }

    // ---------------- GET USER BY ID ----------------
    public function getById($id)
    {
        $sql = "SELECT u.id, u.full_name, u.email, u.avatar, r.name as role, u.status, u.created_at
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ---------------- GET ALL USERS ----------------
    public function getAll()
    {
        $sql = "SELECT u.id, u.full_name, u.email, r.name as role, u.status, u.created_at
                FROM users u
                JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->execute();
        return $stmt->get_result();
    }

    // ---------------- UPDATE USER ----------------
    public function update($id, $fullName, $email, $roleName)
    {
        $role_id = $this->getRoleId($roleName);
        if (!$role_id) return false;

        $sql = "UPDATE $this->table SET full_name=?, email=?, role_id=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("ssii", $fullName, $email, $role_id, $id);
        return $stmt->execute();
    }

    // Update user profile (flexible for profile updates)
    public function updateProfile($id, $data)
    {
        $fields = [];
        $values = [];
        $types = '';

        foreach ($data as $key => $value) {
            $fields[] = "$key=?";
            $values[] = $value;
            
            // Determine type for bind_param
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_double($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $values[] = $id;
        $types .= 'i';

        $sql = "UPDATE $this->table SET " . implode(', ', $fields) . " WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) return false;

        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    // ---------------- CHANGE PASSWORD ----------------
    public function changePassword($id, $newPassword)
    {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE $this->table SET password=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("si", $hashed, $id);
        return $stmt->execute();
    }
}
