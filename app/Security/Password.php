<?php
// app/Security/Password.php

namespace App\Security;

class Password
{
    private static int $cost = 12;

    public static function setCost(int $cost): void
    {
        self::$cost = $cost;
    }

    public static function hash(string $password): string
    {
        $options = ['cost' => self::$cost];
        
        $hash = password_hash($password, PASSWORD_BCRYPT, $options);
        
        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password');
        }
        
        return $hash;
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => self::$cost]);
    }

    public static function validateStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
}