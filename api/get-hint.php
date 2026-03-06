<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/get-hint.php
 * Description: API endpoint to fetch hints for a lesson
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

// Get hint_id from query parameter
$hintId = isset($_GET['hint_id']) ? (int)$_GET['hint_id'] : 0;

if ($hintId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid hint ID'
    ]);
    exit;
}

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        // Return default hint if database is not configured
        echo json_encode([
            'success' => true,
            'hint' => getDefaultHint($hintId)
        ]);
        exit;
    }
    
    // Fetch hint from database
    $stmt = $pdo->prepare("
        SELECT id, text, hint_order as hintOrder
        FROM hints
        WHERE id = ?
    ");
    
    $stmt->execute([$hintId]);
    $hint = $stmt->fetch();
    
    if ($hint) {
        echo json_encode([
            'success' => true,
            'hint' => $hint['text']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Hint not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch hint'
    ]);
}

/**
 * Get default hint for offline mode
 */
function getDefaultHint($hintId) {
    $hints = [
        1 => 'Use the return keyword to return a value from a function.',
        2 => 'Strings in JavaScript are wrapped in quotes.',
        3 => 'The answer is: return "Hello, World!";'
    ];
    
    return $hints[$hintId] ?? 'Keep trying! Review the problem description carefully.';
}
