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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

$title = trim($input['title'] ?? '');
$difficulty = strtolower($input['difficulty'] ?? 'easy');
$description = $input['description'] ?? '';
$starterCode = $input['starterCode'] ?? '';
$solution = $input['solution'] ?? '';
$points = (int)($input['points'] ?? 100);
$testCases = $input['testCases'] ?? [];

if (empty($title) || empty($description) || empty($starterCode) || empty($solution)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title, description, starterCode, and solution are required']);
    exit;
}

if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Difficulty must be easy, medium, or hard']);
    exit;
}

$pdo = getDB();

if ($pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO lessons (title, difficulty, description, starter_code, solution, points)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$title, $difficulty, $description, $starterCode, $solution, $points]);
    $lessonId = (int)$pdo->lastInsertId();
    
    if (!empty($testCases)) {
        $testStmt = $pdo->prepare("
            INSERT INTO test_cases (lesson_id, input, expected_output)
            VALUES (?, ?, ?)
        ");
        
        foreach ($testCases as $testCase) {
            $inputJson = json_encode($testCase['input'] ?? []);
            $outputJson = json_encode($testCase['expected'] ?? '');
            $testStmt->execute([$lessonId, $inputJson, $outputJson]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Lesson created successfully',
        'lesson' => [
            'id' => $lessonId,
            'title' => $title,
            'difficulty' => $difficulty,
            'points' => $points
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
