<?php
/**
 * Database helper functions
 * Wrapper around src/config/database.php
 */

// Load environment variables from .env.local if available
$envFile = __DIR__ . '/../../.env.local';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

require_once __DIR__ . '/../../src/config/database.php';

use App\Config\Database;

/**
 * Get database connection
 * @return PDO|null
 */
function getDB() {
    return Database::getConnection();
}
