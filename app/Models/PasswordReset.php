<?php
// app/Models/PasswordReset.php

namespace App\Models;

class PasswordReset extends BaseModel
{
    protected static string $table = 'password_reset_tokens';
    protected array $fillable = ['user_id', 'token_hash', 'expires_at', 'used_at'];
    protected array $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime'
    ];

    public function user()
    {
        return User::find($this->user_id);
    }

    public static function createForUser(int $userId, int $lifetime = 3600): self
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = new \DateTimeImmutable("+{$lifetime} seconds");
        
        $reset = new self();
        $reset->user_id = $userId;
        $reset->token_hash = $tokenHash;
        $reset->expires_at = $expires;
        $reset->save();
        
        // Store raw token for email
        $reset->raw_token = $token;
        
        return $reset;
    }

    public static function validate(string $token): ?self
    {
        $tokenHash = hash('sha256', $token);
        
        $resets = self::where(['token_hash' => $tokenHash])
                      ->where('expires_at', '>', date('Y-m-d H:i:s'))
                      ->where('used_at', 'IS', null)
                      ->get();
        
        return $resets[0] ?? null;
    }

    public function markAsUsed(): bool
    {
        $this->used_at = new \DateTimeImmutable();
        return $this->save();
    }

    public static function cleanupExpired(): int
    {
        $db = self::getConnection();
        $sql = "DELETE FROM password_reset_tokens WHERE expires_at < NOW()";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->rowCount();
    }
}