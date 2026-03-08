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

// Groq API configuration
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL', 'llama-3.1-8b-instant');

$hintId = isset($_GET['hint_id']) ? (int)$_GET['hint_id'] : 0;
$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 1;

if ($hintId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid hint ID'
    ]);
    exit;
}

try {
    // Get lesson info - either from DB or demo lessons
    $lessonInfo = getLessonInfo($lessonId);
    
    // Generate hint using Groq API
    $hint = generateHintWithGroq($lessonInfo, $hintId);
    
    if ($hint) {
        echo json_encode([
            'success' => true,
            'hint' => $hint,
            'hint_level' => $hintId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to generate hint'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}

function getLessonInfo($lessonId) {
    $pdo = getDB();
    
    if ($pdo !== null) {
        try {
            // Check if lessons table exists
            $pdo->query("SELECT 1 FROM lessons LIMIT 1");
            
            $stmt = $pdo->prepare("
                SELECT id, title, description, starter_code as starterCode, solution
                FROM lessons
                WHERE id = ?
            ");
            $stmt->execute([$lessonId]);
            $lesson = $stmt->fetch();
            
            if ($lesson) {
                return $lesson;
            }
        } catch (Exception $e) {
            // Table doesn't exist, will use demo lessons
        }
    }
    
    // Fallback to demo lessons
    return getDemoLesson($lessonId);
}

function getDemoLesson($lessonId) {
    $demoLessons = [
        1 => [
            'id' => 1,
            'title' => 'Hello World',
            'description' => 'Write a function that returns the string "Hello, World!"',
            'starterCode' => 'function hello() {\n    // Return "Hello, World!"\n    \n}',
            'solution' => 'function hello() {\n    return "Hello, World!";\n}'
        ],
        2 => [
            'id' => 2,
            'title' => 'Sum Two Numbers',
            'description' => 'Write a function that takes two numbers and returns their sum.',
            'starterCode' => 'function sum(a, b) {\n    // Return a + b\n    \n}',
            'solution' => 'function sum(a, b) {\n    return a + b;\n}'
        ],
        3 => [
            'id' => 3,
            'title' => 'Reverse a String',
            'description' => 'Write a function that reverses a string.',
            'starterCode' => 'function reverse(str) {\n    // Return the reversed string\n    \n}',
            'solution' => 'function reverse(str) {\n    return str.split("").reverse().join("");\n}'
        ],
        4 => [
            'id' => 4,
            'title' => 'FizzBuzz',
            'description' => 'Write a function that takes a number and returns "Fizz", "Buzz", "FizzBuzz", or the number.',
            'starterCode' => 'function fizzBuzz(n) {\n    // Your code here\n    \n}',
            'solution' => 'function fizzBuzz(n) {\n    if (n % 3 === 0 && n % 5 === 0) return "FizzBuzz";\n    if (n % 3 === 0) return "Fizz";\n    if (n % 5 === 0) return "Buzz";\n    return String(n);\n}'
        ],
        5 => [
            'id' => 5,
            'title' => 'Factorial',
            'description' => 'Write a function that calculates the factorial of a non-negative integer.',
            'starterCode' => 'function factorial(n) {\n    // Return n! (n factorial)\n    \n}',
            'solution' => 'function factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}'
        ]
    ];
    
    return $demoLessons[$lessonId] ?? $demoLessons[1];
}

function generateHintWithGroq($lesson, $hintLevel) {
    $apiKey = getenv('GROQ_API_KEY');
    
    if (empty($apiKey)) {
        // Fallback to default hints if no API key
        return getDefaultHint($hintLevel, $lesson['id']);
    }
    
    // Hint level prompts - progressive hints
    $hintPrompts = [
        1 => "Give a subtle hint that guides the student without giving away the answer. Focus on the concept or approach.",
        2 => "Give a more specific hint that points toward the solution but doesn't reveal it completely.",
        3 => "Give a strong hint that is very close to the solution but still requires the student to write the code."
    ];
    
    $prompt = $hintPrompts[$hintLevel] ?? $hintPrompts[1];
    
    $systemPrompt = "You are a helpful coding tutor. Provide hints for programming lessons. 
Lesson: {$lesson['title']}
Problem: {$lesson['description']}
Starter Code: {$lesson['starterCode']}

{$prompt}

Provide ONLY the hint text, no additional explanation. Keep it concise (1-2 sentences max).";
    
    $payload = [
        'model' => GROQ_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Generate hint level {$hintLevel} for this lesson."]
        ],
        'temperature' => 0.7,
        'max_tokens' => 150
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => json_encode($payload),
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents(GROQ_API_URL, false, $context);
    
    if ($response === false) {
        return getDefaultHint($hintLevel, $lesson['id']);
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return trim($result['choices'][0]['message']['content']);
    }
    
    return getDefaultHint($hintLevel, $lesson['id']);
}

function getDefaultHint($hintId, $lessonId = 1) {
    $hints = [
        // Lesson 1 - Hello World
        1 => [
            1 => 'Use the return keyword to return a value from a function.',
            2 => 'Strings in JavaScript are wrapped in quotes.',
            3 => 'The answer is: return "Hello, World!";'
        ],
        // Lesson 2 - Sum Two Numbers
        2 => [
            1 => 'You can add two numbers using the + operator.',
            2 => 'Make sure to use the return keyword.',
            3 => 'The answer is: return a + b;'
        ],
        // Lesson 3 - Reverse String
        3 => [
            1 => 'You can convert a string to an array using split().',
            2 => 'Arrays have a reverse() method.',
            3 => 'Use join() to convert the array back to a string.'
        ],
        // Lesson 4 - FizzBuzz
        4 => [
            1 => 'Use the modulo operator (%) to check divisibility.',
            2 => 'Check for divisibility by both 3 and 5 first.',
            3 => 'Use String(n) to convert a number to a string.'
        ],
        // Lesson 5 - Factorial
        5 => [
            1 => 'Factorial of 0 or 1 is 1.',
            2 => 'You can use recursion: n! = n * (n-1)!',
            3 => 'Base case: if (n <= 1) return 1;'
        ]
    ];
    
    return $hints[$lessonId][$hintId] ?? 'Keep trying! Review the problem description carefully.';
}
