<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api/') === 0) {
    $apiPath = substr($uri, 5);
    $phpFile = __DIR__ . '/api/' . $apiPath;
    
    if (pathinfo($phpFile, PATHINFO_EXTENSION) === 'php' && file_exists($phpFile)) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($apiPath, '.php') === false) {
            $files = glob(__DIR__ . '/api/' . $apiPath . '.php');
            if (!empty($files)) {
                $phpFile = $files[0];
            }
        }
        require $phpFile;
        return;
    }
}

$file = __DIR__ . '/public' . $uri;

if (is_file($file)) {
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

if ($uri === '/' || $uri === '') {
    readfile(__DIR__ . '/public/index.html');
    return;
}

http_response_code(404);
echo 'Not Found';
