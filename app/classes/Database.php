<?php
/**
 * Database Connection Class
 * Blog Advanced v2.0
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $config = parse_ini_file(__DIR__ . '/../../data/config.ini');
        
        try {
            // Check if MySQL is configured
            if (isset($config['db_type']) && $config['db_type'] === 'mysql') {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    $config['db_host'] ?? 'localhost',
                    $config['db_port'] ?? '3306',
                    $config['db_name'] ?? 'blog',
                    $config['db_charset'] ?? 'utf8mb4'
                );
                
                $this->connection = new PDO(
                    $dsn,
                    $config['db_user'] ?? 'root',
                    $config['db_pass'] ?? '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } else {
                // SQLite (default)
                $dbPath = $config['db_path'] ?? __DIR__ . '/../../data/blog.db';
                $this->connection = new PDO(
                    "sqlite:" . $dbPath,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            }
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed");
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
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
