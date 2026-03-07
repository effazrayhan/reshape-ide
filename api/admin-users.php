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

$token = extractTokenFromHeader();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$payload = verifyJWT($token);
if (!$payload || !isset($payload['is_admin']) || $payload['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$pdo = getDB();

if ($pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.email,
            u.username,
            u.created_at,
            COALESCE(us.total_score, 0) as total_score,
            COALESCE(us.lessons_completed, 0) as lessons_completed,
            us.last_activity_at
        FROM users u
        LEFT JOIN user_scores us ON u.id = us.user_id
        ORDER BY us.total_score DESC, u.created_at DESC
    ");
    
    $users = $stmt->fetchAll();
    
    $lessonsStmt = $pdo->query("SELECT id, title FROM lessons");
    $lessons = $lessonsStmt->fetchAll();
    $lessonTitles = [];
    foreach ($lessons as $lesson) {
        $lessonTitles[$lesson['id']] = $lesson['title'];
    }
    
    $progressStmt = $pdo->query("
        SELECT user_id, lesson_id, score, completed_at 
        FROM user_progress 
        WHERE score > 0
    ");
    $allProgress = $progressStmt->fetchAll();
    
    $progressByUser = [];
    foreach ($allProgress as $progress) {
        $uid = $progress['user_id'];
        if (!isset($progressByUser[$uid])) {
            $progressByUser[$uid] = [];
        }
        $progressByUser[$uid][] = [
            'lesson_id' => $progress['lesson_id'],
            'lesson_title' => $lessonTitles[$progress['lesson_id']] ?? 'Unknown',
            'score' => $progress['score'],
            'completed_at' => $progress['completed_at']
        ];
    }
    
    $result = [];
    foreach ($users as $user) {
        $uid = (string)$user['id'];
        $result[] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'created_at' => $user['created_at'],
            'total_score' => (int)$user['total_score'],
            'lessons_completed' => (int)$user['lessons_completed'],
            'last_activity_at' => $user['last_activity_at'],
            'solved_problems' => $progressByUser[$uid] ?? []
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $result,
        'total_users' => count($result)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
