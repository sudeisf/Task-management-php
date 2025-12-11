<?php
/**
 * Migration Script: Add avatar column to users table
 * Run this once to add the avatar column
 */

require_once 'config/db.php';

try {
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM users LIKE 'avatar'";
    $result = $conn->query($checkSql);
    
    if ($result->num_rows > 0) {
        echo "✓ Avatar column already exists!\n";
    } else {
        // Add the avatar column
        $alterSql = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER profile_picture";
        
        if ($conn->query($alterSql)) {
            echo "✓ Successfully added avatar column to users table!\n";
        } else {
            echo "✗ Error adding avatar column: " . $conn->error . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
