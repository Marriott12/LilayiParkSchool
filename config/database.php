<?php
/**
 * Database Configuration and Connection Handler
 * Uses PDO for secure database operations
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'lilayiparkschool';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        
        // Debug logging
        error_log("Database constructor called");
        error_log("  DB_HOST from ENV: " . ($_ENV['DB_HOST'] ?? 'NOT SET'));
        error_log("  DB_NAME from ENV: " . ($_ENV['DB_NAME'] ?? 'NOT SET'));
        error_log("  DB_USER from ENV: " . ($_ENV['DB_USER'] ?? 'NOT SET'));
        error_log("  Using host: " . $this->host);
        error_log("  Using database: " . $this->db_name);
        error_log("  Using username: " . $this->username);
        
        $this->connect();
    }
    
    private function connect() {
        $this->connection = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            error_log("Attempting database connection with DSN: mysql:host=" . $this->host . ";dbname=" . $this->db_name);
            
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            error_log("Database connection successful!");
        } catch(PDOException $e) {
            // Log the full error
            error_log("Database Connection FAILED: " . $e->getMessage());
            error_log("  Tried to connect to: " . $this->host . "/" . $this->db_name);
            error_log("  With username: " . $this->username);
            
            // DEBUG: Show error directly in browser
            echo '<pre style="color:red;">Database Connection Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
            echo '<pre>Host: ' . htmlspecialchars($this->host) . '</pre>';
            echo '<pre>Database: ' . htmlspecialchars($this->db_name) . '</pre>';
            echo '<pre>Username: ' . htmlspecialchars($this->username) . '</pre>';
            exit;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize global database connection for backward compatibility
$db = Database::getInstance()->getConnection();
