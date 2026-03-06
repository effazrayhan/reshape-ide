<?php
/**
 * Project: Logic-Focused Educational IDE
 * File: api/lib/db.php
 * Description: Database connection helper using PDO
 */

/**
 * Load environment variables from .env.local file
 */
function loadEnvFile() {
    $envFile = __DIR__ . '/../../.env.local';
    
    if (!file_exists($envFile)) {
        return;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load environment variables
loadEnvFile();

/**
 * Get a PDO database connection
 * 
 * Reads database credentials from environment variables:
 * - DB_HOST: Database host (default: localhost)
 * - DB_PORT: Database port (default: 3306)
 * - DB_NAME: Database name
 * - DB_USER: Database username
 * - DB_PASS: Database password
 * 
 * @return PDO|null Returns PDO object on success, null on failure
 */
function getDB() {
    static $pdo = null;
    
    // Return cached connection if exists
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Get environment variables with defaults
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: '';
    $username = getenv('DB_USER') ?: '';
    $password = getenv('DB_PASS') ?: '';
    
    // Validate required parameters
    if (empty($dbname) || empty($username)) {
        error_log('Database configuration error: DB_NAME and DB_USER are required');
        return null;
    }
    
    try {
        // Check if MySQL driver is available
        if (!in_array('mysql', PDO::getAvailableDrivers())) {
            error_log('MySQL PDO driver not available - using demo mode');
            return null;
        }
        
        // Build DSN string
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $dbname
        );
        
        // Set PDO options
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];
        
        // Create PDO instance
        $pdo = new PDO($dsn, $username, $password, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Execute a query and return the statement
 * 
 * @param string $sql The SQL query
 * @param array $params Optional parameters for prepared statement
 * @return PDOStatement|null
 */
function executeQuery($sql, $params = []) {
    $pdo = getDB();
    
    if ($pdo === null) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Query error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Fetch a single row from the database
 * 
 * @param string $sql The SQL query
 * @param array $params Optional parameters
 * @return array|null
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    
    if ($stmt === null) {
        return null;
    }
    
    return $stmt->fetch();
}

/**
 * Fetch all rows from the database
 * 
 * @param string $sql The SQL query
 * @param array $params Optional parameters
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    
    if ($stmt === null) {
        return [];
    }
    
    return $stmt->fetchAll();
}
