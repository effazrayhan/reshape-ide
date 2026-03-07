<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'lib/db.php';
require_once 'lib/jwt.php';

$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@reshape.com';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if ($email === $adminEmail && $password === $adminPassword) {
    $token = generateJWT([
        'id' => 0,
        'email' => $adminEmail,
        'username' => 'admin',
        'is_admin' => true
    ]);
    
    if (!$token) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to generate token']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin login successful',
        'token' => $token,
        'user' => [
            'id' => 0,
            'email' => $adminEmail,
            'username' => 'admin',
            'is_admin' => true
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid admin credentials']);
}
