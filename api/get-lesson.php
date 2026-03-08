<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/lib/db.php';

try {
    $pdo = getDB();
    
    if ($pdo === null) {
        echo json_encode([
            'success' => true,
            'lessons' => getDemoLessons()
        ]);
        exit;
    }
    
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
    
    foreach ($lessons as &$lesson) {
        // Hints now come from Groq API - provide placeholder structure
        $lesson['hints'] = [
            ['id' => 1, 'hintOrder' => 1],
            ['id' => 2, 'hintOrder' => 2],
            ['id' => 3, 'hintOrder' => 3]
        ];
        
        $testStmt = $pdo->prepare("
            SELECT 
                input,
                expected_output as expected
            FROM test_cases
            WHERE lesson_id = ?
        ");
        $testStmt->execute([$lesson['id']]);
        $lesson['testCases'] = $testStmt->fetchAll();
        
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
    echo json_encode([
        'success' => true,
        'lessons' => getDemoLessons()
    ]);
}

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
            'description' => '<h3>Adding Numbers</h3><p>Write a function that takes two numbers and returns their sum.</p>',
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
            'description' => '<h3>String Reversal</h3><p>Write a function that reverses a string.</p>',
            'starterCode' => 'function reverse(str) {\n    // Return the reversed string\n    \n}',
            'solution' => 'function reverse(str) {\n    return str.split("").reverse().join("");\n}',
            'hints' => [
                ['id' => 1, 'text' => 'You can convert a string to an array using split().'],
                ['id' => 2, 'text' => 'Arrays have a reverse() method.'],
                ['id' => 3, 'text' => 'Use join() to convert the array back to a string.']
            ],
            'testCases' => [
                ['input' => ['hello'], 'expected' => 'olleh'],
                ['input' => ['JavaScript'], 'expected' => 'tpircSavaJ']
            ],
            'points' => 150
        ],
        [
            'id' => 4,
            'title' => 'FizzBuzz',
            'difficulty' => 'medium',
            'description' => '<h3>The Classic Problem</h3><p>Write a function that takes a number and returns "Fizz", "Buzz", "FizzBuzz", or the number.</p>',
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
            'description' => '<h3>Factorial Calculation</h3><p>Write a function that calculates the factorial of a non-negative integer.</p>',
            'starterCode' => 'function factorial(n) {\n    // Return n! (n factorial)\n    \n}',
            'solution' => 'function factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}',
            'hints' => [
                ['id' => 1, 'text' => 'Factorial of 0 or 1 is 1.'],
                ['id' => 2, 'text' => 'You can use recursion: n! = n * (n-1)!'],
                ['id' => 3, 'text' => 'Base case: if (n <= 1) return 1;']
            ],
            'testCases' => [
                ['input' => [0], 'expected' => 1],
                ['input' => [5], 'expected' => 120],
                ['input' => [10], 'expected' => 3628800]
            ],
            'points' => 250
        ]
    ];
}
