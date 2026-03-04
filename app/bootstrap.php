<?php
// app/bootstrap.php

// Error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/error.log');

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load configuration
$config = require __DIR__ . '/Config/config.php';

// Initialize database
use App\Config\Database;
Database::init($config['database']);

// Start session
use App\Security\Session;
Session::getInstance($config['security']);

// Helper functions
function base_path($path = '') {
    return __DIR__ . '/../' . ltrim($path, '/');
}

function public_path($path = '') {
    return base_path('public/' . ltrim($path, '/'));
}

function asset($path) {
    return getenv('APP_URL') . '/public/' . ltrim($path, '/');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function old($key, $default = '') {
    return $_POST[$key] ?? $default;
}

function csrf_field() {
    $csrf = new App\Security\CSRF(
        App\Security\Session::getInstance(),
        require __DIR__ . '/Config/config.php'['security']
    );
    return $csrf->getInputField();
}