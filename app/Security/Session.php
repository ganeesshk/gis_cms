<?php
// app/Security/Session.php

namespace App\Security;

class Session
{
    private static ?self $instance = null;
    private array $config;

    private function __construct(array $config)
    {
        $this->config = $config;
        $this->start();
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Set secure session parameters
        session_name($this->config['session_name']);
        
        session_set_cookie_params([
            'lifetime' => $this->config['session_lifetime'],
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();

        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            $_SESSION['_regenerate'] = time();
        } else {
            // Regenerate ID every 30 minutes
            if (time() - $_SESSION['_regenerate'] > 1800) {
                $this->regenerate();
            }
        }

        // Check session expiration
        if (isset($_SESSION['_last_activity'])) {
            if (time() - $_SESSION['_last_activity'] > $this->config['session_lifetime']) {
                $this->destroy();
                session_start();
            }
        }
        
        $_SESSION['_last_activity'] = time();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_regenerate'] = time();
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }

    public function flash(string $key, $value = null)
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return;
        }
        
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function getFlashed(string $key, $default = null)
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }
}