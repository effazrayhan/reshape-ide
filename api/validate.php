<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/lib/db.php';

define('PISTON_API', 'https://emkc.org/api/v2/piston/execute');
define('JAVASCRIPT_VERSION', '18.15.0');

$input = json_decode(file_get_contents('php://input'), true);

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
    
    // Try to get lesson from database, fallback to demo lessons
    $lesson = null;
    $testCases = $input['testCases'] ?? [];
    $dbAvailable = false;
    
    if ($pdo !== null) {
        try {
            // Check if lessons table exists
            $pdo->query("SELECT 1 FROM lessons LIMIT 1");
            $dbAvailable = true;
        } catch (Exception $e) {
            // Table doesn't exist, will use demo lessons
            $dbAvailable = false;
        }
    }
    
    if ($dbAvailable) {
        $stmt = $pdo->prepare("
            SELECT id, title, solution, points
            FROM lessons
            WHERE id = ?
        ");
        
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();
        
        if (empty($testCases) && $lesson) {
            $testStmt = $pdo->prepare("
                SELECT input, expected_output as expected
                FROM test_cases
                WHERE lesson_id = ?
            ");
            
            $testStmt->execute([$lessonId]);
            $testCases = $testStmt->fetchAll();
            
            foreach ($testCases as &$test) {
                $test['input'] = json_decode($test['input'], true);
            }
        }
    }
    
    // Fallback to demo lessons if not found in DB
    if (!$lesson) {
        $demoLessons = getDemoLessons();
        $lesson = $demoLessons[$lessonId] ?? null;
        
        if (!$lesson) {
            echo json_encode([
                'success' => false,
                'message' => 'Lesson not found'
            ]);
            exit;
        }
        
        // Use demo test cases if not provided
        if (empty($testCases)) {
            $testCases = $lesson['testCases'] ?? [];
        }
    }
    
    $result = validateWithPiston($code, $testCases);
    
    if ($result['passed']) {
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
        $response = [
            'success' => false,
            'message' => $result['message'],
            'results' => $result['results']
        ];

        if (isset($result['fallback']) && $result['fallback']) {
            $response['fallback'] = true;
        }
        
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation error: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $e->getMessage()
    ]);
}

function validateWithPiston($userCode, $testCases) {
    $results = [];
    $allPassed = true;
    
    $functionName = extractFunctionName($userCode);
    
    if (!$functionName) {
        return [
            'passed' => false,
            'message' => 'Could not find function definition',
            'results' => [[
                'input' => [],
                'expected' => 'N/A',
                'actual' => null,
                'passed' => false,
                'error' => 'Could not find function definition'
            ]]
        ];
    }
    
    // Check first test case to see if Piston is available
    $firstTest = $testCases[0] ?? null;
    if ($firstTest) {
        $testCode = buildTestCode($userCode, $functionName, $firstTest['input']);
        $output = runPiston($testCode);
        
        // Check if fallback is needed
        if (isset($output['fallback']) && $output['fallback']) {
            return [
                'passed' => false,
                'message' => $output['error'],
                'fallback' => true
            ];
        }
    }
    
    foreach ($testCases as $testCase) {
        $inputArgs = $testCase['input'];
        $expected = $testCase['expected'];
        
        $testCode = buildTestCode($userCode, $functionName, $inputArgs);
        
        $output = runPiston($testCode);
        
        $actual = trim($output['output'] ?? '');
        $error = $output['error'] ?? null;
        
        if ($error) {
            $results[] = [
                'input' => $inputArgs,
                'expected' => json_encode($expected),
                'actual' => null,
                'passed' => false,
                'error' => $error
            ];
            $allPassed = false;
            continue;
        }
        
        $expectedStr = is_string($expected) ? $expected : json_encode($expected);
        $actualStr = trim($actual);
        
        $passed = ($actualStr === $expectedStr);
        
        $results[] = [
            'input' => $inputArgs,
            'expected' => json_encode($expected),
            'actual' => $actual,
            'passed' => $passed
        ];
        
        if (!$passed) {
            $allPassed = false;
        }
    }
    
    return [
        'passed' => $allPassed,
        'message' => $allPassed ? 'All tests passed!' : 'Some tests failed',
        'results' => $results
    ];
}

function extractFunctionName($code) {
    if (preg_match('/function\s+(\w+)/', $code, $matches)) {
        return $matches[1];
    }
    if (preg_match('/const\s+(\w+)\s*=\s*(?:async\s*)?\(/', $code, $matches)) {
        return $matches[1];
    }
    if (preg_match('/let\s+(\w+)\s*=\s*(?:async\s*)?\(/', $code, $matches)) {
        return $matches[1];
    }
    if (preg_match('/var\s+(\w+)\s*=\s*(?:async\s*)?\(/', $code, $matches)) {
        return $matches[1];
    }
    return null;
}

function buildTestCode($userCode, $functionName, $args) {
    $argsJson = json_encode($args);
    
    return $userCode . "\n\nconsole.log(JSON.stringify(" . $functionName . "(" . $argsJson . ")));";
}

function runPiston($code) {
    $payload = [
        'language' => 'javascript',
        'version' => JAVASCRIPT_VERSION,
        'files' => [
            ['content' => $code]
        ],
        'run_timeout' => 10000,
        'compile_timeout' => 10000
    ];
    
    $jsonPayload = json_encode($payload);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nUser-Agent: PHP/Reshape-IDE\r\n",
            'content' => $jsonPayload,
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents(PISTON_API, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        $errMsg = $error['message'] ?? 'Unknown error';
        
        // Check for auth error - return fallback flag
        if (strpos($errMsg, '401') !== false || strpos($errMsg, 'Unauthorized') !== false) {
            return ['error' => 'Piston API requires authentication', 'output' => '', 'fallback' => true];
        }
        
        return ['error' => 'Connection failed: ' . $errMsg, 'output' => ''];
    }
    
    if (empty($response)) {
        return ['error' => 'Empty response from Piston', 'output' => ''];
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        return ['error' => 'Failed to parse Piston response', 'output' => ''];
    }
    
    $run = $result['run'] ?? null;
    
    if (!$run) {
        return ['error' => 'No run data in response', 'output' => ''];
    }
    
    $stderr = $run['stderr'] ?? '';
    $stdout = $run['stdout'] ?? '';
    $code = $run['code'] ?? 0;
    
    if ($code !== 0) {
        return ['error' => !empty($stderr) ? trim($stderr) : 'Execution failed', 'output' => ''];
    }
    
    if (!empty($stderr)) {
        return ['error' => trim($stderr), 'output' => ''];
    }
    
    return ['output' => $stdout, 'error' => null];
}

    function normalizeOutput($output) {
        return trim($output);
    }

function getDemoLessons() {
    return [
        1 => [
            'id' => 1,
            'title' => 'Hello World',
            'solution' => 'function hello() {\n    return "Hello, World!";\n}',
            'points' => 100,
            'testCases' => [
                ['input' => [], 'expected' => 'Hello, World!']
            ]
        ],
        2 => [
            'id' => 2,
            'title' => 'Sum Two Numbers',
            'solution' => 'function sum(a, b) {\n    return a + b;\n}',
            'points' => 100,
            'testCases' => [
                ['input' => [2, 3], 'expected' => 5],
                ['input' => [10, 20], 'expected' => 30],
                ['input' => [-1, 1], 'expected' => 0]
            ]
        ],
        3 => [
            'id' => 3,
            'title' => 'Reverse a String',
            'solution' => 'function reverse(str) {\n    return str.split("").reverse().join("");\n}',
            'points' => 150,
            'testCases' => [
                ['input' => ['hello'], 'expected' => 'olleh'],
                ['input' => ['JavaScript'], 'expected' => 'tpircSavaJ']
            ]
        ],
        4 => [
            'id' => 4,
            'title' => 'FizzBuzz',
            'solution' => 'function fizzBuzz(n) {\n    if (n % 3 === 0 && n % 5 === 0) return "FizzBuzz";\n    if (n % 3 === 0) return "Fizz";\n    if (n % 5 === 0) return "Buzz";\n    return String(n);\n}',
            'points' => 200,
            'testCases' => [
                ['input' => [3], 'expected' => 'Fizz'],
                ['input' => [5], 'expected' => 'Buzz'],
                ['input' => [15], 'expected' => 'FizzBuzz'],
                ['input' => [7], 'expected' => '7']
            ]
        ],
        5 => [
            'id' => 5,
            'title' => 'Factorial',
            'solution' => 'function factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}',
            'points' => 250,
            'testCases' => [
                ['input' => [0], 'expected' => 1],
                ['input' => [5], 'expected' => 120],
                ['input' => [10], 'expected' => 3628800]
            ]
        ]
    ];
}
