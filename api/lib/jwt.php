<?php
/**
 * JWT helper functions
 * Wrapper around src/lib/Jwt.php
 */

require_once __DIR__ . '/../../src/lib/Jwt.php';

use App\Lib\Jwt as JwtLib;

/**
 * Generate a JWT token
 * @param array $payload
 * @param int $expiry
 * @return string|null
 */
function generateJWT(array $payload, int $expiry = 3600): ?string {
    $secret = getenv('JWT_SECRET') ?: getenv('SECRET_KEY') ?: 'default-secret-key-change-in-production';
    return JwtLib::generate($payload, $secret, $expiry);
}

/**
 * Verify a JWT token
 * @param string $token
 * @return array|null
 */
function verifyJWT(string $token): ?array {
    $secret = getenv('JWT_SECRET') ?: getenv('SECRET_KEY') ?: 'default-secret-key-change-in-production';
    return JwtLib::verify($token, $secret);
}

/**
 * Extract token from Authorization header
 * @return string|null
 */
function extractTokenFromHeader(): ?string {
    return JwtLib::extractHeader();
}
