<?php
/**
 * Router script for PHP built-in server
 * Routes /api/* to PHP files in api/ directory
 */

// Get the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API routes
if (strpos($uri, '/api/') === 0) {
    // Get the API path
    $apiPath = substr($uri, 5); // Remove '/api/' prefix
    
    // Find the PHP file
    $phpFile = __DIR__ . '/api/' . $apiPath;
    
    // If it's a PHP file, include it
    if (pathinfo($phpFile, PATHINFO_EXTENSION) === 'php' && file_exists($phpFile)) {
        // For GET requests with query strings, handle them
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($apiPath, '.php') === false) {
            // Check if there's a corresponding PHP file
            $files = glob(__DIR__ . '/api/' . $apiPath . '.php');
            if (!empty($files)) {
                $phpFile = $files[0];
            }
        }
        
        require $phpFile;
        return;
    }
}

// Static files in public/
$file = __DIR__ . '/public' . $uri;

if (is_file($file)) {
    // Serve static file
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];
    
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($file);
    return;
}

// Default: serve index.html for root
if ($uri === '/' || $uri === '') {
    readfile(__DIR__ . '/public/index.html');
    return;
}

// 404 Not Found
http_response_code(404);
echo 'Not Found';
