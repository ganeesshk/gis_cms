<?php
// app/Models/User.php

namespace App\Models;

use App\Security\Password;
use DateTimeImmutable;

class User extends BaseModel
{
    protected static string $table = 'users';
    
    protected array $fillable = [
        'username', 'email', 'password_hash', 'full_name', 
        'avatar_path', 'role_id', 'is_active', 'force_password_change'
    ];
    
    protected array $guarded = ['id', 'created_at', 'updated_at', 'deleted_at', 
                                'failed_login_attempts', 'locked_until', 
                                'last_login_at', 'last_login_ip', 'password_changed_at'];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'force_password_change' => 'boolean',
        'failed_login_attempts' => 'int',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];
	
	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);
	}
	
	
    public function setPassword(string $password): self
    {
        $this->password_hash = Password::hash($password);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return Password::verify($password, $this->password_hash);
    }

    public function needsRehash(): bool
    {
        return Password::needsRehash($this->password_hash);
    }

    public function isLocked(): bool
    {
        if (!$this->locked_until) {
            return false;
        }
        
        $now = new DateTimeImmutable();
        return $this->locked_until > $now;
    }

    public function incrementFailedAttempts(): void
    {
        $this->failed_login_attempts++;
        
        $maxAttempts = $this->getConfig('security.max_login_attempts', 5);
        if ($this->failed_login_attempts >= $maxAttempts) {
            $lockoutMinutes = $this->getConfig('security.lockout_duration', 15);
            $this->locked_until = new DateTimeImmutable("+{$lockoutMinutes} minutes");
        }
        
        $this->save();
    }

    public function resetFailedAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    public function recordLogin(string $ip): void
    {
        $this->last_login_at = new DateTimeImmutable();
        $this->last_login_ip = $ip;
        $this->failed_login_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    public function role()
    {
        return Role::find($this->role_id);
    }

    public function hasPermission(string $permission): bool
	{
		$role = $this->role();
		if (!$role) {
			return false;
		}
		
		// Super admin has all permissions
		if ($role->slug === 'super_admin') {
			return true;
		}
		
		return $role->hasPermission($permission);
	}

    public function isSuperAdmin(): bool
    {
        $role = $this->role();
        return $role && $role->slug === 'super_admin';
    }

    private function getConfig(string $key, $default = null)
    {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/../Config/config.php';
        }
        
        $keys = explode('.', $key);
        $value = $config;
        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }

    public function getAvatarUrl(): string
    {
        if ($this->avatar_path) {
            return '/uploads/avatars/' . $this->avatar_path;
        }
        
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?s=200&d=mp";
    }

    public function getFullName(): string
    {
        return $this->full_name ?: $this->username;
    }

    public function pages()
    {
        return Page::where(['author_id' => $this->id, 'deleted_at' => null])
                   ->orderBy('created_at', 'DESC')
                   ->get();
    }

    public function media()
    {
        return Media::where(['uploaded_by' => $this->id, 'deleted_at' => null])
                    ->orderBy('created_at', 'DESC')
                    ->get();
    }

    public function auditLogs()
    {
        return AuditLog::where(['user_id' => $this->id])
                       ->orderBy('created_at', 'DESC')
                       ->limit(100)
                       ->get();
    }

    public function getPermissions()
    {
        $role = $this->role();
        return $role ? $role->getPermissions() : [];
    }

    public function getActivitySummary()
    {
        $db = $this->db;
        
        $sql = "SELECT COUNT(*) FROM pages WHERE author_id = :user_id AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $this->id]);
        $pageCount = $stmt->fetchColumn();
        
        $sql = "SELECT COUNT(*) FROM media WHERE uploaded_by = :user_id AND deleted_at IS NULL";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $this->id]);
        $mediaCount = $stmt->fetchColumn();
        
        $lastLogin = $this->last_login_at ? $this->last_login_at->format('Y-m-d H:i:s') : 'Never';
        
        $sql = "SELECT COUNT(*) FROM audit_logs WHERE user_id = :user_id AND action = 'login.success'";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $this->id]);
        $loginCount = $stmt->fetchColumn();
        
        return [
            'pages' => $pageCount,
            'media' => $mediaCount,
            'last_login' => $lastLogin,
            'logins' => $loginCount,
            'joined' => $this->created_at->format('Y-m-d')
        ];
    }

    public static function getByRole($roleSlug)
    {
        $db = self::getConnection();
        $sql = "SELECT u.* FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE r.slug = :slug AND u.deleted_at IS NULL
                ORDER BY u.username";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':slug' => $roleSlug]);
        
        $users = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $user = new self();
            $user->attributes = $row;
            $user->original = $row;
            $users[] = $user;
        }
        
        return $users;
    }

    public static function getActiveUsers($days = 30)
    {
        $db = self::getConnection();
        $sql = "SELECT DISTINCT u.* FROM users u
                JOIN audit_logs al ON al.user_id = u.id
                WHERE al.created_at >= NOW() - INTERVAL ':days DAYS'
                AND al.action LIKE 'login.%'
                AND u.deleted_at IS NULL
                ORDER BY al.created_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':days' => $days]);
        
        $users = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $user = new self();
            $user->attributes = $row;
            $user->original = $row;
            $users[] = $user;
        }
        
        return $users;
    }

    public static function getStats()
    {
        $db = self::getConnection();
        
        $sql = "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $total = $stmt->fetchColumn();
        
        $sql = "SELECT 
                SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN is_active = false THEN 1 ELSE 0 END) as inactive
                FROM users WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $status = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $sql = "SELECT r.name, COUNT(u.id) as count
                FROM roles r
                LEFT JOIN users u ON u.role_id = r.id AND u.deleted_at IS NULL
                GROUP BY r.id, r.name
                ORDER BY r.name";
        $stmt = $db->query($sql);
        $byRole = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $sql = "SELECT COUNT(*) FROM users 
                WHERE deleted_at IS NULL 
                AND created_at >= DATE_TRUNC('month', CURRENT_DATE)";
        $stmt = $db->query($sql);
        $newThisMonth = $stmt->fetchColumn();
        
        $sql = "SELECT COUNT(*) FROM users 
                WHERE deleted_at IS NULL 
                AND locked_until > NOW()";
        $stmt = $db->query($sql);
        $locked = $stmt->fetchColumn();
        
        return [
            'total' => $total,
            'active' => (int)$status['active'],
            'inactive' => (int)$status['inactive'],
            'by_role' => $byRole,
            'new_this_month' => $newThisMonth,
            'locked' => $locked
        ];
    }

    public static function validate($data, $id = null)
    {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 60) {
            $errors[] = 'Username must not exceed 60 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        } else {
            $db = self::getConnection();
            $sql = "SELECT id FROM users WHERE username = :username AND deleted_at IS NULL";
            $params = [':username' => $data['username']];
            
            if ($id) {
                $sql .= " AND id != :id";
                $params[':id'] = $id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $errors[] = 'Username already exists';
            }
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } else {
            $db = self::getConnection();
            $sql = "SELECT id FROM users WHERE email = :email AND deleted_at IS NULL";
            $params = [':email' => $data['email']];
            
            if ($id) {
                $sql .= " AND id != :id";
                $params[':id'] = $id;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists';
            }
        }
        
        if (!$id && empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (!empty($data['password'])) {
            $passwordErrors = Password::validateStrength($data['password']);
            $errors = array_merge($errors, $passwordErrors);
        }
        
        if (empty($data['role_id'])) {
            $errors[] = 'Role is required';
        } else {
            $role = Role::find($data['role_id']);
            if (!$role) {
                $errors[] = 'Invalid role selected';
            }
        }
        
        return $errors;
    }
}
