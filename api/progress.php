<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'lib/db.php';
require_once 'lib/jwt.php';

$userId = null;

$token = extractTokenFromHeader();
if ($token) {
    $payload = verifyJWT($token);
    if ($payload && isset($payload['id'])) {
        $userId = (string)$payload['id'];
    }
}

if (!$userId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['anonymousUserId'])) {
        $userId = $input['anonymousUserId'];
    }
}

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
    
    $stmt = $pdo->prepare("
        SELECT lesson_id, score, hints_used, completed_at
        FROM user_progress
        WHERE user_id = ?
    ");
    
    $stmt->execute([$userId]);
    $progress = $stmt->fetchAll();
    
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
