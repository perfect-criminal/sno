<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use Exception; // Make sure to use the global Exception

class Connection
{
    private static ?PDO $instance = null;

    /**
     * Get a PDO database connection instance (Singleton).
     *
     * @return PDO
     * @throws Exception if database configuration is not found or connection fails.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $configFile = __DIR__ . '/../../../config/database.php'; // Path from Core/Database to config/

            if (!file_exists($configFile)) {
                throw new Exception("Database configuration file not found: {$configFile}");
            }

            $config = require $configFile;

            // Check if essential config keys are loaded
            if (empty($config['driver']) || empty($config['host']) || empty($config['database']) || !isset($config['username'])) {
                throw new Exception("Database configuration is incomplete. Check config/database.php and your .env file. Loaded config: " . print_r($config, true));
            }

            $dsn = "{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // In a real app, log this error and show a user-friendly message
                // Avoid echoing detailed error messages in production
                throw new Exception("Database connection failed: " . $e->getMessage() . " (DSN: {$dsn})");
            }
        }
        return self::$instance;
    }

    private function __construct() {} // Private constructor for Singleton
    private function __clone() {}     // Private clone method
    public function __wakeup() {}    // Private unserialize method
}