-- Project: Logic-Focused Educational IDE
-- File: sql/schema.sql
-- Description: Database schema for the Logic IDE application

-- =====================================================
-- Database Creation
-- =====================================================

-- Create database (run separately if needed)
-- CREATE DATABASE IF NOT EXISTS logic_ide CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE logic_ide;

-- =====================================================
-- Tables
-- =====================================================

-- Lessons table
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'easy',
    description TEXT NOT NULL,
    starter_code TEXT NOT NULL,
    solution TEXT NOT NULL,
    points INT NOT NULL DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_difficulty (difficulty),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hints table
CREATE TABLE IF NOT EXISTS hints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    text TEXT NOT NULL,
    hint_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_hint_order (hint_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test cases table
CREATE TABLE IF NOT EXISTS test_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    input JSON NOT NULL,
    expected_output JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson_id (lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User progress table
CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    lesson_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    hints_used INT NOT NULL DEFAULT 0,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_lesson (user_id, lesson_id),
    INDEX idx_user_id (user_id),
    INDEX idx_completed_at (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User scores summary table
CREATE TABLE IF NOT EXISTS user_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    total_score INT NOT NULL DEFAULT 0,
    lessons_completed INT NOT NULL DEFAULT 0,
    last_activity_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user (user_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Data - Lessons
-- =====================================================

INSERT INTO lessons (title, difficulty, description, starter_code, solution, points) VALUES
('Hello World', 'easy', '<h3>Your First Program</h3><p>Write a function that returns the string "Hello, World!"</p><pre><code>function hello() {\n    // Your code here\n}</code></pre>', 'function hello() {\n    // Return "Hello, World!"\n    \n}', 'function hello() {\n    return "Hello, World!";\n}', 100),
('Sum Two Numbers', 'easy', '<h3>Adding Numbers</h3><p>Write a function that takes two numbers and returns their sum.</p><pre><code>function sum(a, b) {\n    // Your code here\n}</code></pre>', 'function sum(a, b) {\n    // Return a + b\n    \n}', 'function sum(a, b) {\n    return a + b;\n}', 100),
('Reverse a String', 'medium', '<h3>String Reversal</h3><p>Write a function that reverses a string.</p><pre><code>function reverse(str) {\n    // Your code here\n}</code></pre>', 'function reverse(str) {\n    // Return the reversed string\n    \n}', 'function reverse(str) {\n    return str.split("").reverse().join("");\n}', 150),
('FizzBuzz', 'medium', '<h3>The Classic Problem</h3><p>Write a function that takes a number and returns:</p><ul><li>"Fizz" if divisible by 3</li><li>"Buzz" if divisible by 5</li><li>"FizzBuzz" if divisible by both</li><li>The number as a string otherwise</li></ul><pre><code>function fizzBuzz(n) {\n    // Your code here\n}</code></pre>', 'function fizzBuzz(n) {\n    // Your code here\n    \n}', 'function fizzBuzz(n) {\n    if (n % 3 === 0 && n % 5 === 0) return "FizzBuzz";\n    if (n % 3 === 0) return "Fizz";\n    if (n % 5 === 0) return "Buzz";\n    return String(n);\n}', 200),
('Factorial', 'hard', '<h3>Factorial Calculation</h3><p>Write a function that calculates the factorial of a non-negative integer.</p><pre><code>function factorial(n) {\n    // Your code here\n}</code></pre>', 'function factorial(n) {\n    // Return n! (n factorial)\n    \n}', 'function factorial(n) {\n    if (n <= 1) return 1;\n    return n * factorial(n - 1);\n}', 250);

-- =====================================================
-- Sample Data - Hints
-- =====================================================

-- Hints for Lesson 1: Hello World
INSERT INTO hints (lesson_id, text, hint_order) VALUES
(1, 'Use the return keyword to return a value from a function.', 1),
(1, 'Strings in JavaScript are wrapped in quotes.', 2),
(1, 'The answer is: return "Hello, World!";', 3);

-- Hints for Lesson 2: Sum Two Numbers
INSERT INTO hints (lesson_id, text, hint_order) VALUES
(2, 'You can add two numbers using the + operator.', 1),
(2, 'Make sure to use the return keyword.', 2),
(2, 'The answer is: return a + b;', 3);

-- Hints for Lesson 3: Reverse a String
INSERT INTO hints (lesson_id, text, hint_order) VALUES
(3, 'You can convert a string to an array using split().', 1),
(3, 'Arrays have a reverse() method.', 2),
(3, 'Use join() to convert the array back to a string.', 3);

-- Hints for Lesson 4: FizzBuzz
INSERT INTO hints (lesson_id, text, hint_order) VALUES
(4, 'Use the modulo operator (%) to check divisibility.', 1),
(4, 'Check for divisibility by both 3 and 5 first.', 2),
(4, 'Use String(n) to convert a number to a string.', 3);

-- Hints for Lesson 5: Factorial
INSERT INTO hints (lesson_id, text, hint_order) VALUES
(5, 'Factorial of 0 or 1 is 1.', 1),
(5, 'You can use recursion: n! = n * (n-1)!', 2),
(5, 'Base case: if (n <= 1) return 1;', 3);

-- =====================================================
-- Sample Data - Test Cases
-- =====================================================

-- Test cases for Lesson 1: Hello World
INSERT INTO test_cases (lesson_id, input, expected_output) VALUES
(1, '[]', '"Hello, World!"');

-- Test cases for Lesson 2: Sum Two Numbers
INSERT INTO test_cases (lesson_id, input, expected_output) VALUES
(2, '[2, 3]', '5'),
(2, '[10, 20]', '30'),
(2, '[-1, 1]', '0');

-- Test cases for Lesson 3: Reverse a String
INSERT INTO test_cases (lesson_id, input, expected_output) VALUES
(3, '["hello"]', '"olleh"'),
(3, '["JavaScript"]', '"tpircSavaJ"'),
(3, '["a"]', '"a"');

-- Test cases for Lesson 4: FizzBuzz
INSERT INTO test_cases (lesson_id, input, expected_output) VALUES
(4, '[3]', '"Fizz"'),
(4, '[5]', '"Buzz"'),
(4, '[15]', '"FizzBuzz"'),
(4, '[7]', '"7"');

-- Test cases for Lesson 5: Factorial
INSERT INTO test_cases (lesson_id, input, expected_output) VALUES
(5, '[0]', '1'),
(5, '[1]', '1'),
(5, '[5]', '120'),
(5, '[10]', '3628800');
