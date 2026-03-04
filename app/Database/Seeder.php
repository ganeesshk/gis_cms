<?php
// app/Database/Seeder.php

namespace App\Database;

use App\Config\Database;
use App\Models\Role;
use App\Models\User;
use App\Security\Password;

class Seeder
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function seed()
    {
        $this->seedRoles();
        $this->seedUsers();
        $this->seedSettings();
    }

    private function seedRoles()
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'is_system' => true,
                'permissions' => json_encode(['all' => true])
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'is_system' => true,
                'permissions' => json_encode([
                    'pages' => '*',
                    'menus' => '*',
                    'galleries' => '*',
                    'media' => '*',
                    'settings' => '*'
                ])
            ],
            [
                'name' => 'Editor',
                'slug' => 'editor',
                'is_system' => false,
                'permissions' => json_encode([
                    'pages' => 'write',
                    'media' => 'write'
                ])
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'is_system' => false,
                'permissions' => json_encode([
                    'pages' => 'read'
                ])
            ]
        ];

        foreach ($roles as $role) {
            $stmt = $this->db->prepare(
                "INSERT INTO roles (name, slug, is_system, permissions, created_at, updated_at) 
                 VALUES (:name, :slug, :is_system, :permissions, NOW(), NOW())
                 ON CONFLICT (slug) DO NOTHING"
            );
            
            $stmt->execute([
                ':name' => $role['name'],
                ':slug' => $role['slug'],
                ':is_system' => $role['is_system'],
                ':permissions' => $role['permissions']
            ]);
        }
    }

    private function seedUsers()
    {
        // Get super admin role
        $stmt = $this->db->query("SELECT id FROM roles WHERE slug = 'super_admin'");
        $superAdminRole = $stmt->fetch();

        if (!$superAdminRole) {
            return;
        }

        // Create super admin user if not exists
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash, full_name, role_id, is_active, created_at, updated_at)
             VALUES (:username, :email, :password, :full_name, :role_id, true, NOW(), NOW())
             ON CONFLICT (username) DO NOTHING"
        );

        $stmt->execute([
            ':username' => 'admin',
            ':email' => 'admin@example.com',
            ':password' => Password::hash('Admin@123'),
            ':full_name' => 'System Administrator',
            ':role_id' => $superAdminRole['id']
        ]);
    }

    private function seedSettings()
    {
        $settings = [
            ['general', 'site_title', 'My Website', 'Site Title', 'string', true],
            ['general', 'site_tagline', '', 'Site Tagline', 'string', true],
            ['general', 'admin_email', 'admin@example.com', 'Admin Email', 'string', false],
            ['seo', 'meta_description', '', 'Default Meta Description', 'string', true],
            ['seo', 'google_analytics_id', null, 'Google Analytics ID', 'string', true],
            ['security', 'session_timeout_min', '30', 'Session Timeout (minutes)', 'integer', false],
            ['security', 'max_login_attempts', '5', 'Max Login Attempts', 'integer', false],
            ['security', 'lockout_duration_min', '15', 'Lockout Duration (minutes)', 'integer', false],
            ['uploads', 'max_file_size_mb', '10', 'Max Upload Size (MB)', 'integer', false],
            ['uploads', 'allowed_image_types', '["jpeg","png","gif","webp"]', 'Allowed Image Types', 'json', false]
        ];

        foreach ($settings as $setting) {
            $stmt = $this->db->prepare(
                "INSERT INTO settings (category, key, value, label, value_type, is_public, updated_at)
                 VALUES (:category, :key, :value, :label, :value_type, :is_public, NOW())
                 ON CONFLICT (category, key) DO NOTHING"
            );
            
            $stmt->execute([
                ':category' => $setting[0],
                ':key' => $setting[1],
                ':value' => $setting[2],
                ':label' => $setting[3],
                ':value_type' => $setting[4],
                ':is_public' => $setting[5]
            ]);
        }
    }
}