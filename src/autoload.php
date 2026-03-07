<?php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $path_parts = explode('\\', $relative_class);
    
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
    
    $file = $base_dir . $dir_path . strtolower($class_name) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    
    $file = $base_dir . $dir_path . $class_name . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }
    
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
