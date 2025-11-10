<?php
/**
 * Enhanced Database Connection Class
 * Provides secure PDO connection with error handling
 */
class Database {
    private $host = "localhost";
    private $dbname = "ticketing_v2";
    private $username = "root";
    private $password = "";
    private $conn = null;
    
    /**
     * Get database connection
     * @return PDO
     */
    public function connect() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                die("Database connection failed. Please contact administrator.");
            }
        }
        
        return $this->conn;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connect()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connect()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connect()->rollBack();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connect()->lastInsertId();
    }
}