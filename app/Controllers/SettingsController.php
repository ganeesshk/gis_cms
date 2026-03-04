<?php
// app/Controllers/SettingsController.php

namespace App\Controllers;

use App\Models\Setting;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;

class SettingsController
{
    private $auth;
    private $csrf;
    private $config;

    public function __construct(AuthService $auth, CSRF $csrf, array $config)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->config = $config;
    }

    /**
     * Show settings page
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin and users with settings permission can access
        if (!$user->isSuperAdmin() && !$user->hasPermission('settings.view')) {
            $_SESSION['error'] = 'You do not have permission to view settings';
            header('Location: /admin/dashboard.php');
            exit;
        }
        
        // Get all settings grouped by category
        $settings = Setting::getAllGrouped();
        $categories = Setting::getCategories();
        $valueTypes = Setting::getValueTypes();
        
        // Get active tab from session or default to first category
        $activeTab = $_SESSION['settings_tab'] ?? ($categories[0] ?? 'general');
        
        $csrfToken = $this->csrf->generate('settings');
        
        return [
            'view' => 'settings/index.php',
            'data' => [
                'user' => $user,
                'settings' => $settings,
                'categories' => $categories,
                'valueTypes' => $valueTypes,
                'activeTab' => $activeTab,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Update settings
     */
    public function update()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'settings')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/settings/');
            exit;
        }
        
        $category = $_POST['category'] ?? 'general';
        $_SESSION['settings_tab'] = $category;
        
        // Get all settings in this category
        $settings = Setting::getByCategory($category);
        
        $updated = [];
        $errors = [];
        
        foreach ($settings as $setting) {
            $key = $setting->key;
            
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
                
                // Handle checkboxes (if not checked, value is 0)
                if ($setting->value_type === Setting::TYPE_BOOLEAN && !isset($_POST[$key])) {
                    $value = '0';
                }
                
                // Validate based on type
                if (!Setting::validateValue($value, $setting->value_type)) {
                    $errors[] = "Invalid value for {$setting->label}";
                    continue;
                }
                
                // Store old value for audit
                $oldValue = $setting->getTypedValue();
                
                // Update setting
                $setting->setTypedValue($value);
                $setting->updated_by = $user->id;
                $setting->save();
                
                $updated[] = [
                    'key' => $setting->key,
                    'label' => $setting->label,
                    'old' => $oldValue,
                    'new' => $setting->getTypedValue()
                ];
            }
        }
        
        // Log activity
        if (!empty($updated)) {
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'settings.update',
                'entity_type' => 'settings',
                'entity_label' => $category,
                'new_values' => json_encode(['category' => $category, 'updated' => count($updated)]),
                'result' => 'success'
            ]);
        }
        
        if (empty($errors)) {
            $_SESSION['success'] = 'Settings updated successfully';
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: /admin/settings/#tab=' . $category);
        exit;
    }

    /**
     * Create new setting
     */
    public function create()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'settings_create')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Only super admin can create settings
        if (!$user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $errors = $this->validateSettingInput($input);
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }
        
        // Check if setting already exists
        $existing = Setting::where([
            'category' => $input['category'],
            'key' => $input['key']
        ])->get();
        
        if (!empty($existing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Setting already exists']);
            exit;
        }
        
        // Create setting
        $setting = new Setting();
        $setting->category = $input['category'];
        $setting->key = $input['key'];
        $setting->label = $input['label'];
        $setting->description = $input['description'] ?? null;
        $setting->value_type = $input['value_type'];
        $setting->is_public = isset($input['is_public']) ? (bool)$input['is_public'] : false;
        $setting->setTypedValue($input['default_value'] ?? '');
        $setting->updated_by = $user->id;
        $setting->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'settings.create',
            'entity_type' => 'settings',
            'entity_id' => $setting->id,
            'entity_label' => $setting->key,
            'new_values' => json_encode($setting->toArray()),
            'result' => 'success'
        ]);
        
        echo json_encode([
            'success' => true,
            'setting' => $setting->toArray()
        ]);
        exit;
    }

    /**
     * Delete setting
     */
    public function delete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'settings_delete')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Only super admin can delete settings
        if (!$user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        $setting = Setting::find($id);
        
        if (!$setting) {
            http_response_code(404);
            echo json_encode(['error' => 'Setting not found']);
            exit;
        }
        
        // Store for audit
        $oldValues = $setting->toArray();
        $key = $setting->key;
        
        // Delete setting
        $setting->delete();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'settings.delete',
            'entity_type' => 'settings',
            'entity_id' => $id,
            'entity_label' => $key,
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Reset to defaults
     */
    public function reset()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'settings_reset')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/settings/');
            exit;
        }
        
        // Only super admin can reset settings
        if (!$user->isSuperAdmin()) {
            $_SESSION['error'] = 'Permission denied';
            header('Location: /admin/settings/');
            exit;
        }
        
        $category = $_GET['category'] ?? null;
        
        if ($category) {
            // Reset specific category
            $defaults = Setting::getDefaultSettings();
            
            if (!isset($defaults[$category])) {
                $_SESSION['error'] = 'Invalid category';
                header('Location: /admin/settings/');
                exit;
            }
            
            $settings = Setting::getByCategory($category);
            
            foreach ($settings as $setting) {
                if (isset($defaults[$category][$setting->key])) {
                    $default = $defaults[$category][$setting->key];
                    $setting->setTypedValue($default['value']);
                    $setting->updated_by = $user->id;
                    $setting->save();
                }
            }
            
            $_SESSION['success'] = "Settings in '{$category}' category reset to defaults";
        } else {
            // Reset all settings
            Setting::initializeDefaults($user->id);
            $_SESSION['success'] = 'All settings reset to defaults';
        }
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'settings.reset',
            'entity_type' => 'settings',
            'entity_label' => $category ?: 'all',
            'result' => 'success'
        ]);
        
        header('Location: /admin/settings/');
        exit;
    }

    /**
     * Export settings
     */
    public function export()
    {
        $user = $this->auth->getCurrentUser();
        
        // Only super admin can export settings
        if (!$user->isSuperAdmin()) {
            $_SESSION['error'] = 'Permission denied';
            header('Location: /admin/settings/');
            exit;
        }
        
        $settings = Setting::getAllGrouped();
        
        $data = [];
        foreach ($settings as $category => $categorySettings) {
            $data[$category] = [];
            foreach ($categorySettings as $setting) {
                $data[$category][$setting->key] = [
                    'value' => $setting->getTypedValue(),
                    'label' => $setting->label,
                    'type' => $setting->value_type,
                    'is_public' => $setting->is_public,
                    'description' => $setting->description
                ];
            }
        }
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="settings_' . date('Y-m-d') . '.json"');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Import settings
     */
    public function import()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'settings_import')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/settings/');
            exit;
        }
        
        // Only super admin can import settings
        if (!$user->isSuperAdmin()) {
            $_SESSION['error'] = 'Permission denied';
            header('Location: /admin/settings/');
            exit;
        }
        
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please select a valid JSON file';
            header('Location: /admin/settings/');
            exit;
        }
        
        $content = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $_SESSION['error'] = 'Invalid JSON file';
            header('Location: /admin/settings/');
            exit;
        }
        
        $imported = 0;
        $errors = [];
        
        foreach ($data as $category => $categorySettings) {
            foreach ($categorySettings as $key => $config) {
                // Check if setting exists
                $existing = Setting::where(['category' => $category, 'key' => $key])->get();
                
                if (!empty($existing)) {
                    $setting = $existing[0];
                    
                    // Validate value type
                    if (isset($config['type']) && $config['type'] !== $setting->value_type) {
                        $errors[] = "Type mismatch for {$category}.{$key}";
                        continue;
                    }
                    
                    $setting->setTypedValue($config['value']);
                    $setting->updated_by = $user->id;
                    $setting->save();
                    $imported++;
                }
            }
        }
        
        if (empty($errors)) {
            $_SESSION['success'] = "Imported {$imported} settings successfully";
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: /admin/settings/');
        exit;
    }

    /**
     * Validate setting input
     */
    private function validateSettingInput($data)
    {
        $errors = [];
        
        if (empty($data['category'])) {
            $errors[] = 'Category is required';
        }
        
        if (empty($data['key'])) {
            $errors[] = 'Key is required';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $data['key'])) {
            $errors[] = 'Key can only contain lowercase letters, numbers, and underscores';
        }
        
        if (empty($data['label'])) {
            $errors[] = 'Label is required';
        }
        
        $validTypes = [Setting::TYPE_STRING, Setting::TYPE_BOOLEAN, Setting::TYPE_INTEGER, Setting::TYPE_JSON];
        if (empty($data['value_type']) || !in_array($data['value_type'], $validTypes)) {
            $errors[] = 'Invalid value type';
        }
        
        return $errors;
    }
}