<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/submit-score.php
 * Description: API endpoint to submit user scores
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database helper
require_once __DIR__ . '/lib/db.php';

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

// Get user ID (in production, this would come from authentication)
$userId = getUserId();

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
    
    if ($existingProgress) {
        // Update only if new score is higher
        if ($score > $existingProgress['score']) {
            $updateStmt = $pdo->prepare("
                UPDATE user_progress
                SET score = ?, hints_used = ?, completed_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([$score, $hintsUsed, $existingProgress['id']]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Progress updated',
            'isNewHighScore' => $score > $existingProgress['score']
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
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save score: ' . $e->getMessage()
    ]);
}

/**
 * Get or create user ID
 * In production, this would come from authentication
 */
function getUserId() {
    // Try to get from session or create a persistent ID
    if (!isset($_SESSION['user_id'])) {
        // Create a simple anonymous user ID
        $_SESSION['user_id'] = 'user_' . uniqid();
    }
    
    return $_SESSION['user_id'];
}

// Start session for user tracking
session_start();
