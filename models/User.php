<?php

class User
{
    private $conn;
    private $table = "users";

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Register a new user
     * @param string $fullName
     * @param string $email
     * @param string $password
     * @param string $role
     * @return bool
     */
    public function create($fullName, $email, $password, $role = 'member')
    {
        // Check if email exists
        if ($this->exists($email)) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO $this->table (full_name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $fullName, $email, $hashedPassword, $role);

        return $stmt->execute();
    }

    /**
     * Check if email already exists
     * @param string $email
     * @return bool
     */
    public function exists($email)
    {
        $sql = "SELECT id FROM $this->table WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    /**
     * Verify login credentials
     * @param string $email
     * @param string $password
     * @return array|false
     */
    public function verify($email, $password)
    {
        $sql = "SELECT * FROM $this->table WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $sql = "SELECT id, full_name, email, role, status, created_at FROM $this->table WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Fetch all users (for admin)
     * @return mysqli_result
     */
    public function getAll()
    {
        $sql = "SELECT id, full_name, email, role, status, created_at FROM $this->table ORDER BY created_at DESC";
        return $this->conn->query($sql);
    }

    /**
     * Update user info (name, email, role)
     */
    public function update($id, $fullName, $email, $role)
    {
        $sql = "UPDATE $this->table SET full_name=?, email=?, role=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $fullName, $email, $role, $id);

        return $stmt->execute();
    }

    /**
     * Change user password
     */
    public function changePassword($id, $newPassword)
    {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE $this->table SET password=? WHERE id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashed, $id);

        return $stmt->execute();
    }
}
