<?php
require_once __DIR__ . '/../../src/lib/Jwt.php';

use App\Lib\Jwt as JwtLib;

function generateJWT(array $payload, int $expiry = 3600): ?string {
    $secret = getenv('JWT_SECRET') ?: getenv('SECRET_KEY') ?: 'default-secret-key-change-in-production';
    return JwtLib::generate($payload, $secret, $expiry);
}

function verifyJWT(string $token): ?array {
    $secret = getenv('JWT_SECRET') ?: getenv('SECRET_KEY') ?: 'default-secret-key-change-in-production';
    return JwtLib::verify($token, $secret);
}

function extractTokenFromHeader(): ?string {
    return JwtLib::extractHeader();
}

function requireAuth(): array {
    $token = extractTokenFromHeader();
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $payload = verifyJWT($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        exit;
    }
    
    return $payload;
}
