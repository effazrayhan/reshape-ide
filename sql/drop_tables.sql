-- Drop all tables

USE reshape;

-- Drop tables in reverse order of dependencies
DROP TABLE IF EXISTS user_progress;
DROP TABLE IF EXISTS user_scores;
DROP TABLE IF EXISTS test_cases;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS users;
