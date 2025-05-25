<?php

// sno/config/database.php

// Helper function to reliably get environment variables
if (!function_exists('env_get')) {
    function env_get($key, $default = null) {
        // Try $_ENV first
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        // Then try $_SERVER
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        // Then try getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        // Return default if not found anywhere
        return $default;
    }
}

return [
    'driver'    => 'mysql',
    'host'      => env_get('DB_HOST', '127.0.0.1'),
    'port'      => env_get('DB_PORT', '3306'),
    'database'  => env_get('DB_DATABASE', 'shineo'),
    'username'  => env_get('DB_USERNAME', 'root'),
    'password'  => env_get('DB_PASSWORD', ''), // Default to empty string if not set
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];