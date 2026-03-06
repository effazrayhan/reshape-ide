<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/submit-score.php
 * Description: API endpoint to submit user scores
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

// Include database and JWT helpers
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/jwt.php';

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['lessonId']) || !isset($input['score'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: lessonId and score'
    ]);
    exit;
}

$lessonId = (int)$input['lessonId'];
$score = (int)$input['score'];
$hintsUsed = $input['hintsUsed'] ?? 0;
$anonymousUserId = $input['anonymousUserId'] ?? null;

// Try to get authenticated user from JWT
$userId = null;
$payload = null;

$token = extractTokenFromHeader();
if ($token) {
    $payload = verifyJWT($token);
    if ($payload && isset($payload['id'])) {
        $userId = (string)$payload['id'];
    }
}

// Fallback to anonymous user if not authenticated
if (!$userId) {
    // Try to use provided anonymous user ID
    if ($anonymousUserId) {
        $userId = $anonymousUserId;
    } else {
        // Try session-based anonymous ID for backward compatibility
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['anonymous_user_id'])) {
            $_SESSION['anonymous_user_id'] = 'anon_' . uniqid();
        }
        $userId = $_SESSION['anonymous_user_id'];
    }
}

// Validate score
if ($score < 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid score value'
    ]);
    exit;
}

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        // Return success for offline mode
        echo json_encode([
            'success' => true,
            'message' => 'Score saved (offline mode)'
        ]);
        exit;
    }
    
    // Check if user has already completed this lesson
    $checkStmt = $pdo->prepare("
        SELECT id, score
        FROM user_progress
        WHERE user_id = ? AND lesson_id = ?
    ");
    
    $checkStmt->execute([$userId, $lessonId]);
    $existingProgress = $checkStmt->fetch();
    
    $isNewHighScore = false;
    
    if ($existingProgress) {
        // Update only if new score is higher
        if ($score > $existingProgress['score']) {
            $updateStmt = $pdo->prepare("
                UPDATE user_progress
                SET score = ?, hints_used = ?, completed_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([$score, $hintsUsed, $existingProgress['id']]);
            $isNewHighScore = true;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Progress updated',
            'isNewHighScore' => $isNewHighScore
        ]);
    } else {
        // Insert new progress
        $insertStmt = $pdo->prepare("
            INSERT INTO user_progress (user_id, lesson_id, score, hints_used, completed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $insertStmt->execute([$userId, $lessonId, $score, $hintsUsed]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Score saved',
            'isNewHighScore' => true
        ]);
    }
    
    // Update user_scores summary if authenticated
    if ($payload && isset($payload['id'])) {
        try {
            // Update total score
            $scoreStmt = $pdo->prepare("
                INSERT INTO user_scores (user_id, total_score, lessons_completed, last_activity_at)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                    total_score = total_score + ?,
                    lessons_completed = lessons_completed + 1,
                    last_activity_at = NOW()
            ");
            $scoreStmt->execute([$userId, $score, $score]);
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save score: ' . $e->getMessage()
    ]);
}
