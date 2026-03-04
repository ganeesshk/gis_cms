<?php
// app/Models/Role.php

namespace App\Models;

class Role extends BaseModel
{
    protected static string $table = 'roles';
    protected array $fillable = ['name', 'slug', 'permissions', 'is_system'];
    protected array $casts = [
        'permissions' => 'json',
        'is_system' => 'boolean'
    ];

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        if (isset($permissions['all']) && $permissions['all'] === true) {
            return true;
        }
        
        $parts = explode('.', $permission);
        $current = $permissions;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return false;
            }
            $current = $current[$part];
        }
        
        if (is_bool($current)) {
            return $current;
        }
        
        if (is_string($current)) {
            return $current === '*' || $current === $permission;
        }
        
        return false;
    }

    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }
}