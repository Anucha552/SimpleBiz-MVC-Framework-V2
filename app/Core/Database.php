<?php
/**
 * DATABASE CONNECTION CLASS
 * 
 * Purpose: Provides secure PDO database connection using singleton pattern
 * Security: Uses prepared statements exclusively to prevent SQL injection
 * 
 * Why Singleton?
 * - Ensures only one database connection exists throughout application lifecycle
 * - Reduces connection overhead and resource usage
 * - Centralizes connection management
 * 
 * SECURITY RULES:
 * - NEVER use string concatenation for queries
 * - ALWAYS use prepared statements with bound parameters
 * - Disable emulated prepares for true prepared statements
 * - Set error mode to throw exceptions for better debugging
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    /**
     * Singleton instance
     */
    private static ?Database $instance = null;
    
    /**
     * PDO connection object
     */
    private ?PDO $connection = null;

    /**
     * Private constructor to prevent direct instantiation
     * Enforces singleton pattern
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Get singleton instance
     * 
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     * 
     * Configuration is loaded from config/database.php
     * 
     * PDO Options Explained:
     * - ATTR_ERRMODE: Throw exceptions on errors (better than silent failures)
     * - ATTR_DEFAULT_FETCH_MODE: Return associative arrays by default
     * - ATTR_EMULATE_PREPARES: False for real prepared statements (more secure)
     * - ATTR_STRINGIFY_FETCHES: False to preserve data types
     * 
     * @throws PDOException if connection fails
     */
    private function connect(): void
    {
        // Load database configuration
        $config = require __DIR__ . '/../../config/database.php';

        $host = $config['host'];
        $port = $config['port'];
        $dbname = $config['database'];
        $charset = $config['charset'];
        $username = $config['username'];
        $password = $config['password'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Real prepared statements
                PDO::ATTR_STRINGIFY_FETCHES => false, // Preserve data types
            ]);
        } catch (PDOException $e) {
            // In production, log this error instead of displaying it
            if (getenv('APP_ENV') === 'production') {
                error_log("Database connection failed: " . $e->getMessage());
                throw new PDOException("Database connection failed");
            } else {
                throw $e;
            }
        }
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
