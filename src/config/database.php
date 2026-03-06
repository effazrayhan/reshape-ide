<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $conn = null;

    public static function getConnection(): ?PDO {
        if (self::$conn) return self::$conn;

        $cfg = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'name' => getenv('DB_NAME') ?: '',
            'user' => getenv('DB_USER') ?: '',
            'pass' => getenv('DB_PASS') ?: '',
        ];

        if (empty($cfg['name']) || empty($cfg['user'])) {
            error_log('DB config error');
            return null;
        }

        try {
            if (!in_array('mysql', PDO::getAvailableDrivers())) return null;

            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
            self::$conn = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$conn;
        } catch (PDOException $e) {
            error_log('DB error: ' . $e->getMessage());
            return null;
        }
    }
}
