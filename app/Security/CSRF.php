<?php
// app/Security/CSRF.php

namespace App\Security;

use App\Config\Database;
use PDO;

class CSRF
{
    private Session $session;
    private PDO $db;
    private array $config;

    public function __construct(Session $session, array $config)
    {
        $this->session = $session;
        $this->db = Database::getConnection();
        $this->config = $config;
    }

    public function generate(string $action = 'default'): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + $this->config['csrf_expiry']);
        
        $sessionId = $this->getSessionId();
        
        // Only try to insert if we have a valid session ID and sessions table exists
        if ($sessionId > 0) {
            try {
                // Check if sessions table exists
                $stmt = $this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'sessions')");
                $tableExists = $stmt->fetchColumn();
                
                if ($tableExists) {
                    // Check if session exists
                    $stmt = $this->db->prepare("SELECT id FROM sessions WHERE id = ?");
                    $stmt->execute([$sessionId]);
                    if ($stmt->fetch()) {
                        $sql = "INSERT INTO csrf_tokens (session_id, token_hash, expires_at) 
                                VALUES (:session_id, :token_hash, :expires_at)";
                        
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            ':session_id' => $sessionId,
                            ':token_hash' => $hash,
                            ':expires_at' => $expires
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't break the application
                error_log('CSRF token insertion failed: ' . $e->getMessage());
            }
        }

        // Store token in session for verification
        $this->session->set('csrf_' . $action, $token);
        
        return $token;
    }

    public function validate(string $token, string $action = 'default'): bool
    {
        $storedToken = $this->session->get('csrf_' . $action);
        
        if (!$storedToken || !hash_equals($storedToken, $token)) {
            return false;
        }

        $hash = hash('sha256', $token);
        
        try {
            // Check if csrf_tokens table exists
            $stmt = $this->db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'csrf_tokens')");
            $tableExists = $stmt->fetchColumn();
            
            if ($tableExists) {
                $sql = "SELECT id FROM csrf_tokens 
                        WHERE token_hash = :hash 
                        AND expires_at > NOW() 
                        AND used = FALSE";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':hash' => $hash]);
                
                $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tokenRecord) {
                    // Mark token as used
                    $sql = "UPDATE csrf_tokens SET used = TRUE WHERE id = :id";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([':id' => $tokenRecord['id']]);
                }
            }
        } catch (\Exception $e) {
            error_log('CSRF validation failed: ' . $e->getMessage());
            // If database fails, still allow if session token matches
        }

        // Remove from session
        $this->session->remove('csrf_' . $action);

        return true;
    }

    private function getSessionId(): int
    {
        $sessionId = $this->session->get('session_id', 0);
        return (int)$sessionId;
    }

    public function getInputField(string $action = 'default'): string
    {
        $token = $this->generate($action);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}