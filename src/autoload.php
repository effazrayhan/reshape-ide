<?php
spl_autoload_register(fn($c) => $c[0] === 'A' && str_starts_with($c, 'App\\') && require __DIR__ . '/' . str_replace('\\', '/', substr($c, 4))) . '.php');
require_once __DIR__ . '/config/app.php';
\App\Config\AppConfig::load();
