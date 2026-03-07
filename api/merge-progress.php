<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'lib/db.php';
require_once 'lib/jwt.php';

$token = extractTokenFromHeader();
$payload = verifyJWT($token);

if (!$payload || !isset($payload['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

$userId = (string)$payload['id'];

$input = json_decode(file_get_contents('php://input'), true);
$anonUserId = $input['anonymousUserId'] ?? null;

if (!$anonUserId) {
    echo json_encode([
        'success' => false,
        'error' => 'Anonymous user ID required'
    ]);
    exit;
}

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        echo json_encode([
            'success' => true,
            'message' => 'Offline mode'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT lesson_id, score, hints_used, completed_at
        FROM user_progress
        WHERE user_id = ?
    ");
    $stmt->execute([$anonUserId]);
    $anonProgress = $stmt->fetchAll();
    
    $merged = 0;
    
    foreach ($anonProgress as $progress) {
        $checkStmt = $pdo->prepare("
            SELECT id, score FROM user_progress
            WHERE user_id = ? AND lesson_id = ?
        ");
        $checkStmt->execute([$userId, $progress['lesson_id']]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            if ($progress['score'] > $existing['score']) {
                $updateStmt = $pdo->prepare("
                    UPDATE user_progress
                    SET score = ?, hints_used = ?, completed_at = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $progress['score'],
                    $progress['hints_used'],
                    $progress['completed_at'],
                    $existing['id']
                ]);
                $merged++;
            }
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO user_progress (user_id, lesson_id, score, hints_used, completed_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $userId,
                $progress['lesson_id'],
                $progress['score'],
                $progress['hints_used'],
                $progress['completed_at']
            ]);
            $merged++;
        }
    }
    
    if ($merged > 0) {
        $scoreStmt = $pdo->prepare("
            SELECT SUM(score) as total_score, COUNT(*) as lessons_count
            FROM user_progress
            WHERE user_id = ?
        ");
        $scoreStmt->execute([$userId]);
        $scores = $scoreStmt->fetch();
        
        $upsertStmt = $pdo->prepare("
            INSERT INTO user_scores (user_id, total_score, lessons_completed, last_activity_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                total_score = VALUES(total_score),
                lessons_completed = VALUES(lessons_completed),
                last_activity_at = VALUES(last_activity_at)
        ");
        $upsertStmt->execute([
            $userId,
            $scores['total_score'] ?? 0,
            $scores['lessons_count'] ?? 0
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Merged {$merged} lesson(s)"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to merge progress: ' . $e->getMessage()
    ]);
}
