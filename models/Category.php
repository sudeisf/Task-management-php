<?php

require_once __DIR__ . '/../core/Database.php';

class Category
{
    private $db;
    private $table = "categories";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get all categories (system + user specific)
    public function all($user_id = null)
    {
        if ($user_id) {
            $sql = "SELECT * FROM $this->table WHERE user_id IS NULL OR user_id = ? ORDER BY name ASC";
            $this->db->prepare($sql);
            $this->db->execute([$user_id]);
        } else {
            $sql = "SELECT * FROM $this->table WHERE user_id IS NULL ORDER BY name ASC";
            $this->db->prepare($sql);
            $this->db->execute();
        }
        return $this->db->getRows();
    }

    // Find category by ID
    public function find($id)
    {
        $sql = "SELECT * FROM $this->table WHERE id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
    }

    // Create new category
    public function create($data)
    {
        $sql = "INSERT INTO $this->table (name, description, user_id) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Category::create prepare failed: " . $this->db->getError());
            echo "Category::create prepare failed: " . $this->db->getError() . "\n";
            return false;
        }
        
        $result = $this->db->execute([
            $data['name'], 
            $data['description'] ?? null,
            $data['user_id'] ?? null
        ]);
        
        if (!$result) {
             error_log("Category::create execute failed: " . $this->db->getError());
             echo "Category::create execute failed: " . $this->db->getError() . "\n";
        }
        
        return $result;
    }

    // Update category
    public function update($id, $data)
    {
        $sql = "UPDATE $this->table SET name = ?, description = ? WHERE id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$data['name'], $data['description'] ?? null, $id]);
    }

    // Delete category
    public function delete($id)
    {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$id]);
    }
}
