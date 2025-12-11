<?php

class Database
{
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $conn;
    private $stmt;

    public function __construct()
    {
        // Get database config from constants file if it exists
        if (file_exists(__DIR__ . '/../config/constants.php')) {
            require_once __DIR__ . '/../config/constants.php';
            $this->host = DB_HOST ?? 'localhost';
            $this->user = DB_USER ?? 'root';
            $this->pass = DB_PASS ?? '';
            $this->dbname = DB_NAME ?? 'task_manager';
        } else {
            // Fallback to default values
            $this->host = 'localhost';
            $this->user = 'root';
            $this->pass = '';
            $this->dbname = 'task_manager';
        }

        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect()
    {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }

        // Set charset
        $this->conn->set_charset("utf8mb4");
    }

    /**
     * Prepare SQL statement
     */
    public function prepare($sql)
    {
        $this->stmt = $this->conn->prepare($sql);
        return $this->stmt;
    }

    /**
     * Execute prepared statement
     */
    public function execute($params = [])
    {
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if ($param === null) {
                    $types .= 's'; // Use string type for null values
                } elseif (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $this->stmt->bind_param($types, ...$params);
        }

        return $this->stmt->execute();
    }

    /**
     * Get result set
     */
    public function getResult()
    {
        return $this->stmt->get_result();
    }

    /**
     * Get single row as associative array
     */
    public function getRow()
    {
        $result = $this->getResult();
        return $result->fetch_assoc();
    }

    /**
     * Get all rows as associative array
     */
    public function getRows()
    {
        $result = $this->getResult();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get last inserted ID
     */
    public function getLastInsertId()
    {
        return $this->conn->insert_id;
    }

    /**
     * Get affected rows count
     */
    public function getAffectedRows()
    {
        return $this->stmt->affected_rows;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $this->conn->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        $this->conn->rollback();
    }

    /**
     * Query builder for simple SELECT queries
     */
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '')
    {
        $sql = "SELECT $columns FROM $table";

        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }

        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }

        $this->prepare($sql);
        if ($this->execute($params)) {
            return $this->getResult();
        }

        return false;
    }

    /**
     * Insert data into table
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = str_repeat('?, ', count($data) - 1) . '?';

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        $this->prepare($sql);
        return $this->execute(array_values($data));
    }

    /**
     * Update data in table
     */
    public function update($table, $data, $where, $params = [])
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = ?, ";
        }
        $set = rtrim($set, ', ');

        $sql = "UPDATE $table SET $set WHERE $where";

        $this->prepare($sql);
        return $this->execute(array_merge(array_values($data), $params));
    }

    /**
     * Delete data from table
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $where";

        $this->prepare($sql);
        return $this->execute($params);
    }

    /**
     * Get connection object (for backward compatibility)
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function close()
    {
        if ($this->stmt) {
            $this->stmt->close();
        }
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Escape string for safe queries
     */
    public function escape($string)
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Get error message
     */
    public function getError()
    {
        return $this->conn->error;
    }
}
