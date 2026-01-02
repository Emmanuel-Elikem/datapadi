<?php
// config/database.php (Corrected Version)

class Database {
    private $host = 'sql105.infinityfree.com';
    private $db_name = 'if0_38664997_datapadi';
    private $username = 'if0_38664997';
    private $password = '49p5qd32'; // Your password
    private $conn = null; // Initialize connection as null
    
    // The constructor should NOT connect to the DB. It just prepares the object.
    public function __construct() {
        // Leave this empty.
    }
    
    // This is the only place the connection should be created.
    public function getConnection() {
        // If a connection hasn't been made yet, create one.
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                    $this->username,
                    $this->password
                );
                // Set attributes for error handling and how data is returned.
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                error_log("FATAL: Database Connection Error: " . $e->getMessage());
                // Stop the script with a user-friendly message.
                die("A critical error occurred. Please try again later.");
            }
        }
        // Return the existing connection.
        return $this->conn;
    }

    // This is the single, correct prepare method.
    public function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }
    
    // These methods are fine, but they should use getConnection() for consistency.
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    public function rollback() {
        return $this->getConnection()->rollback();
    }
}