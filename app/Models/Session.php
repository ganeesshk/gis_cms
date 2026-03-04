<?php
// app/Models/Session.php

namespace App\Models;

class Session extends BaseModel
{
    protected static string $table = 'sessions';
    protected array $fillable = ['user_id', 'token_hash', 'ip_address', 'user_agent', 'expires_at'];
    protected array $casts = [
        'expires_at' => 'datetime',
        'last_seen' => 'datetime'
    ];

    public function user()
    {
        return User::find($this->user_id);
    }

    public function isValid(): bool
    {
        return $this->expires_at > new \DateTimeImmutable();
    }

    public function touch(): bool
    {
        $this->last_seen = new \DateTimeImmutable();
        return $this->save();
    }

    public static function createForUser(int $userId, string $ip, string $userAgent, int $lifetime = 1800): self
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = new \DateTimeImmutable("+{$lifetime} seconds");
        
        $session = new self();
        $session->user_id = $userId;
        $session->token_hash = $tokenHash;
        $session->ip_address = $ip;
        $session->user_agent = $userAgent;
        $session->expires_at = $expires;
        $session->save();
        
        // Store the raw token in a property for cookie usage
        $session->raw_token = $token;
        
        return $session;
    }

    public static function validate(string $token): ?self
	{
		$tokenHash = hash('sha256', $token);
		
		$sessions = self::where(['token_hash' => $tokenHash])
					   ->where('expires_at', '>', date('Y-m-d H:i:s'))
					   ->get();
		
		if (empty($sessions)) {
			return null;
		}
		
		$session = $sessions[0];
		$session->touch();
		
		return $session;
	}

    public static function purgeExpired(): int
    {
        $db = self::getConnection();
        $sql = "DELETE FROM sessions WHERE expires_at < NOW()";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }
}