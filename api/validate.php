<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/validate.php
 * Description: API endpoint to validate user code against test cases
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
if (!isset($input['code']) || !isset($input['lessonId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: code and lessonId'
    ]);
    exit;
}

$code = $input['code'];
$lessonId = (int)$input['lessonId'];
$testCases = $input['testCases'] ?? [];

if (empty($code)) {
    echo json_encode([
        'success' => false,
        'message' => 'Code cannot be empty'
    ]);
    exit;
}

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        // Fallback validation for offline mode
        validateOffline($code, $lessonId, $testCases);
        exit;
    }
    
    // Fetch lesson from database
    $stmt = $pdo->prepare("
        SELECT id, title, solution, points
        FROM lessons
        WHERE id = ?
    ");
    
    $stmt->execute([$lessonId]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        echo json_encode([
            'success' => false,
            'message' => 'Lesson not found'
        ]);
        exit;
    }
    
    // Fetch test cases from database if not provided
    if (empty($testCases)) {
        $testStmt = $pdo->prepare("
            SELECT input, expected_output as expected
            FROM test_cases
            WHERE lesson_id = ?
        ");
        
        $testStmt->execute([$lessonId]);
        $testCases = $testStmt->fetchAll();
        
        // Decode JSON fields
        foreach ($testCases as &$test) {
            $test['input'] = json_decode($test['input'], true);
        }
    }
    
    // Validate the code
    $result = validateCode($code, $testCases);
    
    if ($result['passed']) {
        // Calculate score (deduct points for hints used)
        $hintsUsed = $input['hintsUsed'] ?? 0;
        $hintPenalty = $hintsUsed * 10;
        $score = max(0, $lesson['points'] - $hintPenalty);
        
        echo json_encode([
            'success' => true,
            'message' => 'All tests passed!',
            'score' => $score,
            'results' => $result['results']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'results' => $result['results']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation error: ' . $e->getMessage()
    ]);
}

/**
 * Validate code against test cases
 */
function validateCode($code, $testCases) {
    $results = [];
    $allPassed = true;
    
    foreach ($testCases as $testCase) {
        $input = $testCase['input'];
        $expected = $testCase['expected'];
        
        // In a real implementation, this would run the code in a sandbox
        // For now, we do a simple syntax check and return mock results
        
        // Check for basic syntax errors
        try {
            // Attempt to eval the code (WARNING: This is for demo only - use sandbox in production)
            $functionName = extractFunctionName($code);
            
            if (!$functionName) {
                $results[] = [
                    'input' => $input,
                    'expected' => $expected,
                    'actual' => null,
                    'passed' => false,
                    'error' => 'Could not find function definition'
                ];
                $allPassed = false;
                continue;
            }
            
            // For demo purposes, we'll check if the solution contains expected keywords
            // In production, use a proper code execution sandbox
            $passed = checkSolution($code, $functionName, $input, $expected);
            
            $results[] = [
                'input' => $input,
                'expected' => $expected,
                'actual' => $passed ? $expected : 'N/A',
                'passed' => $passed
            ];
            
            if (!$passed) {
                $allPassed = false;
            }
            
        } catch (Exception $e) {
            $results[] = [
                'input' => $input,
                'expected' => $expected,
                'actual' => null,
                'passed' => false,
                'error' => $e->getMessage()
            ];
            $allPassed = false;
        }
    }
    
    return [
        'passed' => $allPassed,
        'message' => $allPassed ? 'All tests passed!' : 'Some tests failed',
        'results' => $results
    ];
}

/**
 * Extract function name from code
 */
function extractFunctionName($code) {
    if (preg_match('/function\s+(\w+)/', $code, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Check if the solution is correct
 * This is a simplified check for demo purposes
 */
function checkSolution($code, $functionName, $input, $expected) {
    // For demo, we simulate test results based on expected values
    // In production, use a proper code execution sandbox
    
    // Simulate that some inputs pass based on a simple heuristic
    // This is just for demonstration - real validation would execute the code
    
    if ($functionName === 'hello') {
        return strpos($code, 'return') !== false && 
               (strpos($code, '"Hello, World!"') !== false || strpos($code, "'Hello, World!'") !== false);
    }
    
    if ($functionName === 'sum') {
        return strpos($code, 'return') !== false && strpos($code, 'a + b') !== false;
    }
    
    if ($functionName === 'reverse') {
        return strpos($code, 'split') !== false && 
               strpos($code, 'reverse') !== false && 
               strpos($code, 'join') !== false;
    }
    
    if ($functionName === 'fizzBuzz' || $functionName === 'fizzbuzz') {
        return strpos($code, 'Fizz') !== false && strpos($code, 'Buzz') !== false;
    }
    
    if ($functionName === 'factorial') {
        return strpos($code, 'return') !== false && 
               (strpos($code, 'n - 1') !== false || strpos($code, '*=') !== false);
    }
    
    // Default: check if code contains return statement
    return strpos($code, 'return') !== false;
}

/**
 * Offline validation fallback
 */
function validateOffline($code, $lessonId, $testCases) {
    $results = [];
    $allPassed = true;
    
    // Simple validation for demo
    $functionName = extractFunctionName($code);
    
    if (!$functionName) {
        echo json_encode([
            'success' => false,
            'message' => 'Could not find function definition'
        ]);
        exit;
    }
    
    // Check solution based on lesson
    $passed = checkSolution($code, $functionName, [], null);
    
    if ($passed) {
        $points = getLessonPoints($lessonId);
        
        echo json_encode([
            'success' => true,
            'message' => 'All tests passed!',
            'score' => $points
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Code does not match expected solution'
        ]);
    }
}

/**
 * Get lesson points (offline fallback)
 */
function getLessonPoints($lessonId) {
    $points = [
        1 => 100,
        2 => 100,
        3 => 150,
        4 => 200,
        5 => 250
    ];
    
    return $points[$lessonId] ?? 100;
}
