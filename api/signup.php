<?php
/**
 * Project: Reshape IDE
 * File: api/signup.php
 * Description: User registration endpoint
 */

require_once __DIR__ . '/../src/autoload.php';

use App\Models\UserModel;
use App\Models\ProgressModel;
use App\Config\AppConfig;
use App\Lib\Jwt;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

// Validate required fields
$email = trim($input['email'] ?? '');
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email, username, and password are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate username
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username must be 3-20 characters and contain only letters, numbers, and underscores']);
    exit;
}

// Validate password
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Create user model
$userModel = new UserModel();

// Check if email exists
if ($userModel->findByEmail($email)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// Check if username exists
if ($userModel->findByUsername($username)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit;
}

// Create user
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$userId = $userModel->create($email, $username, $passwordHash);

if (!$userId) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
    exit;
}

// Initialize user score
$progressModel = new ProgressModel();
$progressModel->updateUserScore($userId);

// Generate JWT token
$jwtConfig = AppConfig::getJwtConfig();
$token = Jwt::generate([
    'id' => $userId,
    'email' => $email,
    'username' => $username
], $jwtConfig['secret'], $jwtConfig['expiry']);

if (!$token) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to generate token']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Registration successful',
    'token' => $token,
    'user' => [
        'id' => $userId,
        'email' => $email,
        'username' => $username
    ]
]);
