<?php
/**
 * Optimized Database Connection Class
 * FIXED: Better error handling and connection management
 */
class Database {
    private $host = "localhost";
    private $dbname = "ticketing_v2";
    private $username = "root";
    private $password = "";
    private $conn = null;
    
    /**
     * Get database connection with persistent connection
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
                    PDO::ATTR_PERSISTENT => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                
                // Try without database name (in case database doesn't exist)
                try {
                    $dsn = "mysql:host={$this->host};charset=utf8mb4";
                    $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                    error_log("Connected to MySQL server, but database '{$this->dbname}' may not exist");
                } catch (PDOException $e2) {
                    error_log("Failed to connect to MySQL server: " . $e2->getMessage());
                    throw new Exception("Database connection failed. Please check your configuration.");
                }
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
    
    /**
     * Check if database exists
     */
    public function databaseExists() {
        try {
            $sql = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->dbname]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}