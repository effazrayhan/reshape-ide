<?php
namespace App\Config;

class AppConfig {
    private static bool $loaded = false;

    public static function load(): void {
        if (self::$loaded) return;
        $envFile = dirname(__DIR__, 2) . '/.env.local';
        if (!file_exists($envFile)) { self::$loaded = true; return; }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (!$line || $line[0] === '#') continue;
            $p = explode('=', $line, 2);
            if (count($p) !== 2) continue;
            if (!isset($_ENV[$p[0]])) putenv("{$p[0]}={$p[1]}");
        }
        self::$loaded = true;
    }

    public static function getJwtConfig(): array {
        return ['secret' => getenv('JWT_SECRET') ?: '', 'expiry' => (int)(getenv('JWT_EXPIRY') ?: 86400)];
    }

    public static function getAdminConfig(): array {
        return ['email' => getenv('ADMIN_EMAIL') ?: '', 'password' => getenv('ADMIN_PASSWORD') ?: ''];
    }
}
