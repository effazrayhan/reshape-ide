<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/progress.php
 * Description: Get user progress for all lessons
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'lib/db.php';
require_once 'lib/jwt.php';

// Get user ID from JWT or session
$userId = null;

// Check for JWT token
$token = extractTokenFromHeader();
if ($token) {
    $payload = verifyJWT($token);
    if ($payload && isset($payload['id'])) {
        $userId = (string)$payload['id'];
    }
}

// If no JWT, check for anonymous user ID in POST body
if (!$userId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['anonymousUserId'])) {
        $userId = $input['anonymousUserId'];
    }
}

// Fallback to anonymous user
if (!$userId) {
    if (!isset($_SESSION)) {
        session_start();
    }
    if (!isset($_SESSION['anonymous_user_id'])) {
        $_SESSION['anonymous_user_id'] = 'anon_' . uniqid();
    }
    $userId = $_SESSION['anonymous_user_id'];
}

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        echo json_encode([
            'success' => true,
            'progress' => [],
            'mode' => 'offline'
        ]);
        exit;
    }
    
    // Get all completed lessons for this user
    $stmt = $pdo->prepare("
        SELECT lesson_id, score, hints_used, completed_at
        FROM user_progress
        WHERE user_id = ?
    ");
    
    $stmt->execute([$userId]);
    $progress = $stmt->fetchAll();
    
    // Convert to associative array by lesson_id
    $progressMap = [];
    foreach ($progress as $p) {
        $progressMap[$p['lesson_id']] = [
            'score' => (int)$p['score'],
            'hints_used' => (int)$p['hints_used'],
            'completed_at' => $p['completed_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'progress' => $progressMap,
        'mode' => 'authenticated'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load progress: ' . $e->getMessage()
    ]);
}
