<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/get-lesson.php
 * Description: API endpoint to fetch lessons
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

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        // Return demo lessons if database is not configured
        echo json_encode([
            'success' => true,
            'lessons' => getDemoLessons()
        ]);
        exit;
    }
    
    // Fetch lessons from database
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            difficulty,
            description,
            starter_code as starterCode,
            solution,
            points,
            created_at as createdAt
        FROM lessons
        ORDER BY id ASC
    ");
    
    $stmt->execute();
    $lessons = $stmt->fetchAll();
    
    // Fetch hints for each lesson
    foreach ($lessons as &$lesson) {
        $hintStmt = $pdo->prepare("
            SELECT id, text, hint_order as hintOrder
            FROM hints
            WHERE lesson_id = ?
            ORDER BY hint_order ASC
        ");
        $hintStmt->execute([$lesson['id']]);
        $lesson['hints'] = $hintStmt->fetchAll();
        
        // Fetch test cases
        $testStmt = $pdo->prepare("
            SELECT 
                input,
                expected_output as expected
            FROM test_cases
            WHERE lesson_id = ?
        ");
        $testStmt->execute([$lesson['id']]);
        $lesson['testCases'] = $testStmt->fetchAll();
        
        // Decode JSON fields
        if (!empty($lesson['testCases'])) {
            foreach ($lesson['testCases'] as &$test) {
                $test['input'] = json_decode($test['input'], true);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'lessons' => $lessons
    ]);
    
} catch (Exception $e) {
    // Return demo lessons on error
    echo json_encode([
        'success' => true,
        'lessons' => getDemoLessons()
    ]);
}

/**
 * Get demo lessons for offline/development mode
 */
function getDemoLessons() {
    return [
        [
            'id' => 1,
            'title' => 'Hello World',
            'difficulty' => 'easy',
            'description' => '<h3>Your First Program</h3><p>Write a function that returns the string "Hello, World!"</p><pre><code>function hello() {\n    // Your code here\n}</code></pre>',
            'starterCode' => 'function hello() {\n    // Return "Hello, World!"\n    \n}',
            'solution' => 'function hello() {\n    return "Hello, World!";\n}',
            'hints' => [
                ['id' => 1, 'text' => 'Use the return keyword to return a value from a function.'],
                ['id' => 2, 'text' => 'Strings in JavaScript are wrapped in quotes.'],
                ['id' => 3, 'text' => 'The answer is: return "Hello, World!";']
            ],
            'testCases' => [
                ['input' => [], 'expected' => 'Hello, World!']
            ],
            'points' => 100
        ],
        [
            'id' => 2,
            'title' => 'Sum Two Numbers',
            'difficulty' => 'easy',
            'description' => '<h3>Adding Numbers</h3><p>Write a function that takes two numbers and returns their sum.</p><pre><code>function sum(a, b) {\n    // Your code here\n}</code></pre>',
            'starterCode' => 'function sum(a, b) {\n    // Return a + b\n    \n}',
            'solution' => 'function sum(a, b) {\n    return a + b;\n}',
            'hints' => [
                ['id' => 1, 'text' => 'You can add two numbers using the + operator.'],
                ['id' => 2, 'text' => 'Make sure to use the return keyword.'],
                ['id' => 3, 'text' => 'The answer is: return a + b;']
            ],
            'testCases' => [
                ['input' => [2, 3], 'expected' => 5],
                ['input' => [10, 20], 'expected' => 30],
                ['input' => [-1, 1], 'expected' => 0]
            ],
            'points' => 100
        ],
        [
            'id' => 3,
            'title' => 'Reverse a String',
            'difficulty' => 'medium',
            'description' => '<h3>String Reversal</h3><p>Write a function that reverses a string.</p><pre><code>function reverse(str) {\n    // Your code here\n}</code></pre>',
            'starterCode' => 'function reverse(str) {\n    // Return the reversed string\n    \n}',
            'solution' => 'function reverse(str) {\n    return str.split("").reverse().join("");\n}',
            'hints' => [
                ['id' => 1, 'text' => 'You can convert a string to an array using split().'],
                ['id' => 2, 'text' => 'Arrays have a reverse() method.'],
                ['id' => 3, 'text' => 'Use join() to convert the array back to a string.']
            ],
            'testCases' => [
                ['input' => ['hello'], 'expected' => 'olleh'],
                ['input' => ['JavaScript'], 'expected' => 'tpircSavaJ'],
                ['input' => ['a'], 'expected' => 'a']
            ],
            'points' => 150
        ],
        [
            'id' => 4,
            'title' => 'FizzBuzz',
            'difficulty' => 'medium',
            'description' => '<h3>The Classic Problem</h3><p>Write a function that takes a number and returns:</p><ul><li>"Fizz" if divisible by 3</li><li>"Buzz" if divisible by 5</li><li>"FizzBuzz" if divisible by both</li><li>The number as a string otherwise</li></ul><pre><code>function fizzBuzz(n) {\n    // Your code here\n}</code></pre>',
            'starterCode' => 'function fizzBuzz(n) {\n    // Your code here\n    \n}',
            'solution' => 'function fizzBuzz(n) {\n    if (n % 3 === 0 && n % 5 === 0) return "FizzBuzz";\n    if (n % 3 === 0) return "Fizz";\n    if (n % 5 === 0) return "Buzz";\n    return String(n);\n}',
            'hints' => [
                ['id' => 1, 'text' => 'Use the modulo operator (%) to check divisibility.'],
                ['id' => 2, 'text' => 'Check for divisibility by both 3 and 5 first.'],
                ['id' => 3, 'text' => 'Use String(n) to convert a number to a string.']
            ],
            'testCases' => [
                ['input' => [3], 'expected' => 'Fizz'],
                ['input' => [5], 'expected' => 'Buzz'],
                ['input' => [15], 'expected' => 'FizzBuzz'],
                ['input' => [7], 'expected' => '7']
            ],
            'points' => 200
        ],
        [
            'id' => 5,
            'title' => 'Factorial',
            'difficulty' => 'hard',
            'description' => '<h3>Factorial Calculation</h3><p>Write a function that calculates the factorial of a non-negative integer.</p><pre><code>function factorial(n) {\n    // Your code here\n}</code></pre>',
            'starterCode' => 'function factorial(n) {\n    // Return n! (n factorial)\n    \n}',
            'solution' => 'function factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}',
            'hints' => [
                ['id' => 1, 'text' => 'Factorial of 0 or 1 is 1.'],
                ['id' => 2, 'text' => 'You can use recursion: n! = n * (n-1)!'],
                ['id' => 3, 'text' => 'Base case: if (n <= 1) return 1;']
            ],
            'testCases' => [
                ['input' => [0], 'expected' => 1],
                ['input' => [1], 'expected' => 1],
                ['input' => [5], 'expected' => 120],
                ['input' => [10], 'expected' => 3628800]
            ],
            'points' => 250
        ]
    ];
}
