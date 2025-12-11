<?php

require_once __DIR__ . '/../core/Database.php';

class Priority
{
    private $db;
    private $table = "priority_levels";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get all priority levels
    public function all()
    {
        $sql = "SELECT * FROM $this->table ORDER BY weight ASC";
        $this->db->prepare($sql);
        $this->db->execute();
        return $this->db->getRows();
    }

    // Find priority by ID
    public function find($id)
    {
        $sql = "SELECT * FROM $this->table WHERE id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
    }

    // Get priority by name
    public function findByName($name)
    {
        $sql = "SELECT * FROM $this->table WHERE name = ?";
        $this->db->prepare($sql);
        $this->db->execute([$name]);
        return $this->db->getRow();
    }

    // Create new priority level
    public function create($data)
    {
        $sql = "INSERT INTO $this->table (name, weight) VALUES (?, ?)";
        $this->db->prepare($sql);
        return $this->db->execute([$data['name'], $data['weight']]);
    }

    // Update priority level
    public function update($id, $data)
    {
        $sql = "UPDATE $this->table SET name = ?, weight = ? WHERE id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$data['name'], $data['weight'], $id]);
    }

    // Delete priority level
    public function delete($id)
    {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$id]);
    }
}
