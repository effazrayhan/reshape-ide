@echo off
REM #############################################################################
REM Logic IDE Setup Script for Windows
REM #############################################################################

setlocal enabledelayedexpansion

set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%"

echo ========================================
echo   Logic IDE Setup Script - Windows
echo ========================================
echo.

REM Check for PHP
echo Checking for PHP...
where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP is not installed!
    echo Please install PHP from: https://windows.php.net/download/
    pause
    exit /b 1
)

for /f "delims=" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo [OK] PHP found: %PHP_VERSION%
echo.

REM Check for MySQL
echo Checking for MySQL...
where mysql >nul 2>&1
set MYSQL_AVAILABLE=!errorlevel!

if !MYSQL_AVAILABLE! equ 1 (
    echo [WARNING] MySQL client not found.
    echo You can run in demo mode without a database.
    set /p CONTINUE=Continue without MySQL? (y/n): 
    if /i not "!CONTINUE!"=="y" exit /b 1
    set MYSQL_SKIP=true
) else (
    set MYSQL_SKIP=false
    echo [OK] MySQL client found
)
echo.

REM Create .env.local if needed
if not exist ".env.local" (
    echo Setting up environment configuration...
    if exist ".env.example" (
        copy ".env.example" ".env.local" >nul
        echo   Created .env.local from .env.example
    )
    
    if !MYSQL_SKIP! equ false (
        echo.
        echo Database Configuration:
        set /p DB_HOST=  MySQL Host [localhost]: 
        if "!DB_HOST!"=="" set DB_HOST=localhost
        set /p DB_PORT=  MySQL Port [3306]: 
        if "!DB_PORT!"=="" set DB_PORT=3306
        set /p DB_USER=  MySQL Username [root]: 
        if "!DB_USER!"=="" set DB_USER=root
        set /p DB_PASS=  MySQL Password: 
        set /p DB_NAME=  Database Name [logic_ide]: 
        if "!DB_NAME!"=="" set DB_NAME=logic_ide
        
        echo   Configuration saved
    )
) else (
    echo .env.local already exists
)
echo.

REM Setup database
if !MYSQL_SKIP! equ false (
    echo Setting up database...
    
    for /f "tokens=2 delims==" %%a in ('findstr /i "^DB_HOST=" .env.local') do set DB_HOST=%%a
    for /f "tokens=2 delims==" %%a in ('findstr /i "^DB_PORT=" .env.local') do set DB_PORT=%%a
    for /f "tokens=2 delims==" %%a in ('findstr /i "^DB_USER=" .env.local') do set DB_USER=%%a
    for /f "tokens=2 delims==" %%a in ('findstr /i "^DB_PASS=" .env.local') do set DB_PASS=%%a
    for /f "tokens=2 delims==" %%a in ('findstr /i "^DB_NAME=" .env.local') do set DB_NAME=%%a
    
    if "!DB_PASS!"=="" (
        set MYSQL_AUTH=-u !DB_USER!
    ) else (
        set MYSQL_AUTH=-u !DB_USER! -p!DB_PASS!
    )
    
    echo   Creating database '!DB_NAME!'...
    mysql !MYSQL_AUTH! -h !DB_HOST! -P !DB_PORT! -e "CREATE DATABASE IF NOT EXISTS \`!DB_NAME!\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
    if errorlevel 1 (
        echo   [WARNING] Could not create database. Will try without password...
        mysql -u !DB_USER! -h !DB_HOST! -P !DB_PORT! -e "CREATE DATABASE IF NOT EXISTS \`!DB_NAME!\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
    ) else (
        echo   [OK] Database created
    )
    
    echo   Importing tables...
    mysql !MYSQL_AUTH! -h !DB_HOST! -P !DB_PORT! !DB_NAME! < sql\init_tables.sql >nul 2>&1
    if errorlevel 1 (
        echo   [WARNING] Could not import tables
    ) else (
        echo   [OK] Tables created
    )
    
    echo   Importing lessons and hints...
    mysql !MYSQL_AUTH! -h !DB_HOST! -P !DB_PORT! !DB_NAME! < sql\init_lessons_hints.sql >nul 2>&1
    if errorlevel 1 (
        echo   [WARNING] Could not import lessons
    ) else (
        echo   [OK] Lessons and hints imported
    )
)

REM Create router.php if needed
if not exist "router.php" (
    echo Creating router.php...
    (
        echo ^<?php
        echo $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        echo.
        echo if (strpos($uri, '/api/') === 0) {
        echo     $apiPath = substr($uri, 5);
        echo     $phpFile = __DIR__ . '/api/' . $apiPath;
        echo.
        echo     if (pathinfo($phpFile, PATHINFO_EXTENSION) === 'php' && file_exists($phpFile)) {
        echo         require $phpFile;
        echo         return;
        echo     }
        echo }
        echo.
        echo $file = __DIR__ . '/public' . $uri;
        echo.
        echo if (is_file($file)) {
        echo     $ext = pathinfo($file, PATHINFO_EXTENSION);
        echo     $mimeTypes = ['html' =^> 'text/html', 'css' =^> 'text/css', 'js' =^> 'application/javascript'];
        echo     $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        echo     header('Content-Type: ' . $mime);
        echo     readfile($file);
        echo     return;
        echo }
        echo.
        echo if ($uri === '/' || $uri === '') {
        echo     readfile(__DIR__ . '/public/index.html');
        echo     return;
        echo }
        echo.
        echo http_response_code(404);
        echo echo 'Not Found';
    ) > router.php
    echo   Created router.php
)

echo.
echo ========================================
echo Setup complete!
echo ========================================
echo.
echo Starting PHP development server...
echo.
echo Open your browser and visit:
echo   http://localhost:3699
echo.
echo Press Ctrl+C to stop the server
echo.

php -S localhost:3699 router.php
