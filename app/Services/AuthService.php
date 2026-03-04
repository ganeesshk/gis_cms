<?php
// app/Services/AuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\Session;
use App\Models\AuditLog;
use App\Security\Session as SessionManager;
use App\Security\CSRF;

class AuthService
{
    private SessionManager $session;
    private CSRF $csrf;
    private array $config;

    public function __construct(SessionManager $session, CSRF $csrf, array $config)
    {
        $this->session = $session;
        $this->csrf = $csrf;
        $this->config = $config;
    }

    public function attempt(string $username, string $password, bool $remember = false): array
    {
        // Find user by username or email
        $user = $this->findUserByUsernameOrEmail($username);
        
        if (!$user) {
            $this->logFailedAttempt(null, $username);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        // Check if account is locked
        if ($user->isLocked()) {
            $this->logFailedAttempt($user->id, $username, 'Account locked');
            return ['success' => false, 'message' => 'Account is locked. Please try again later.'];
        }

        // Check if user is active
        if (!$user->is_active) {
            $this->logFailedAttempt($user->id, $username, 'Account inactive');
            return ['success' => false, 'message' => 'Your account has been deactivated.'];
        }

        // Verify password
        if (!$user->verifyPassword($password)) {
            $user->incrementFailedAttempts();
            $this->logFailedAttempt($user->id, $username, 'Invalid password');
            
            $attemptsLeft = $this->config['security']['max_login_attempts'] - $user->failed_login_attempts;
            $message = $attemptsLeft > 0 
                ? "Invalid credentials. {$attemptsLeft} attempts remaining." 
                : 'Account locked due to too many failed attempts.';
            
            return ['success' => false, 'message' => $message];
        }

        // Check if password needs rehash
        if ($user->needsRehash()) {
            $user->setPassword($password)->save();
        }

        // Reset failed attempts and record login
        $user->recordLogin($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        // Create session
        $session = $this->createSession($user, $remember);
        
        // Log successful login
        $this->logSuccessfulLogin($user);

        return [
            'success' => true,
            'user' => $user,
            'session' => $session,
            'force_password_change' => $user->force_password_change
        ];
    }

    private function findUserByUsernameOrEmail(string $username): ?User
    {
        // Fix: Use proper where/orWhere syntax
        $users = User::where(['username' => $username])->get();
        
        if (empty($users)) {
            $users = User::where(['email' => $username])->get();
        }
        
        return $users[0] ?? null;
    }

    private function createSession(User $user, bool $remember): Session
    {
        $lifetime = $remember ? 604800 : $this->config['security']['session_lifetime']; // 7 days for remember me
        
        $session = Session::createForUser(
            $user->id,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $lifetime
        );

        // Store in PHP session
        $this->session->set('user_id', $user->id);
        $this->session->set('session_token', $session->raw_token);
        $this->session->set('session_id', $session->id);
        $this->session->regenerate();

        // Set remember me cookie if requested
        if ($remember) {
            $this->setRememberMeCookie($session);
        }

        return $session;
    }

    private function setRememberMeCookie(Session $session): void
    {
        $expires = $session->expires_at->getTimestamp();
        setcookie(
            'remember_token',
            $session->raw_token,
            [
                'expires' => $expires,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    public function logout(): void
    {
        $token = $this->session->get('session_token');
        
        if ($token) {
            // Invalidate session in database
            $tokenHash = hash('sha256', $token);
            $sessions = Session::where(['token_hash' => $tokenHash])->get();
            
            if (!empty($sessions)) {
                $sessions[0]->delete();
            }
            
            // Clear remember me cookie
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }

        // Destroy session
        $this->session->destroy();
    }

    public function getCurrentUser(): ?User
    {
        $userId = $this->session->get('user_id');
        $token = $this->session->get('session_token');
        
        if (!$userId || !$token) {
            // Try remember me cookie
            return $this->authenticateFromRememberMe();
        }

        $session = Session::validate($token);
        
        if (!$session || $session->user_id != $userId) {
            return null;
        }

        return User::find($userId);
    }

    private function authenticateFromRememberMe(): ?User
    {
        if (!isset($_COOKIE['remember_token'])) {
            return null;
        }

        $token = $_COOKIE['remember_token'];
        $session = Session::validate($token);
        
        if (!$session) {
            return null;
        }

        $user = User::find($session->user_id);
        
        if ($user) {
            // Restore session
            $this->session->set('user_id', $user->id);
            $this->session->set('session_token', $token);
            $this->session->set('session_id', $session->id);
        }

        return $user;
    }

    public function requireAuth(): void
    {
        if (!$this->getCurrentUser()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public function requirePermission(string $permission): void
    {
        $user = $this->getCurrentUser();
        
        if (!$user || !$user->hasPermission($permission)) {
            http_response_code(403);
            include __DIR__ . '/../Views/errors/403.php';
            exit;
        }
    }

    private function logFailedAttempt(?int $userId, string $username, string $reason = ''): void
    {
        AuditLog::log([
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'action' => 'login.failed',
            'entity_type' => 'user',
            'result' => 'failure',
            'error_message' => $reason ?: 'Invalid credentials'
        ]);
    }

    private function logSuccessfulLogin(User $user): void
    {
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'action' => 'login.success',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'entity_label' => $user->username,
            'result' => 'success'
        ]);
    }
}