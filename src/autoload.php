<?php

spl_autoload_register(function ($class) {
    // Check if the class uses the 'App\' namespace prefix
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/'; // src/ directory with trailing slash

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Convert namespace separators to directory separators
    $path_parts = explode('\\', $relative_class);
    
    // Build path with lowercase directories
    $dir_path = '';
    $class_name = '';
    
    $total_parts = count($path_parts);
    foreach ($path_parts as $index => $part) {
        if (!empty($part)) {
            if ($index < $total_parts - 1) {
                $dir_path .= strtolower($part) . '/';
            } else {
                $class_name = $part;
            }
        }
    }
    
    // First try: lowercase directories + lowercase class file
    $file = $base_dir . $dir_path . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    // Second try: lowercase directories + original case class file
    $file = $base_dir . $dir_path . $class_name . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    // Third try: use glob to find case-insensitive match
    $pattern = $base_dir . $dir_path . '*.php';
    $files = glob($pattern);
    if (!empty($files)) {
        foreach ($files as $f) {
            $basename = basename($f, '.php');
            if (strcasecmp($basename, $class_name) === 0) {
                require $f;
                return;
            }
        }
    }
});

require_once __DIR__ . '/config/app.php';
\App\Config\AppConfig::load();
