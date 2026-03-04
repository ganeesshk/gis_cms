<?php
// app/Config/config.php

return [
    'app' => [
        'name' => getenv('APP_NAME'),
        'env' => getenv('APP_ENV'),
        'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN),
        'url' => getenv('APP_URL'),
        'timezone' => getenv('APP_TIMEZONE')
    ],
    
    'database' => [
        'driver' => 'pgsql',
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'charset' => getenv('DB_CHARSET'),
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]
    ],
    
    'security' => [
        'session_lifetime' => (int) getenv('SESSION_LIFETIME'),
        'session_name' => getenv('SESSION_NAME'),
        'csrf_expiry' => (int) getenv('CSRF_EXPIRY'),
        'bcrypt_cost' => (int) getenv('BCRYPT_COST'),
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'password_min_length' => 8,
        'require_special_chars' => true
    ],
    
    'uploads' => [
        'max_size' => (int) getenv('UPLOAD_MAX_SIZE'),
        'allowed_extensions' => explode(',', getenv('ALLOWED_EXTENSIONS')),
        'path' => getenv('UPLOAD_PATH'),
        'thumb_prefix' => getenv('THUMB_PREFIX'),
        'thumb_sizes' => [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 400, 'height' => 300],
            'large' => ['width' => 800, 'height' => 600]
        ]
    ]
];