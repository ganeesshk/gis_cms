<?php
// app/Models/Setting.php

namespace App\Models;

class Setting extends BaseModel
{
    protected static string $table = 'settings';
    
    protected array $fillable = [
        'category', 'key', 'value', 'value_type', 
        'label', 'description', 'is_public', 'updated_by'
    ];
    
    protected array $casts = [
        'is_public' => 'boolean',
        'updated_at' => 'datetime'
    ];

    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_JSON = 'json';

    public function updater()
    {
        return User::find($this->updated_by);
    }

    public function getTypedValue()
    {
        switch ($this->value_type) {
            case self::TYPE_BOOLEAN:
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
                
            case self::TYPE_INTEGER:
                return (int)$this->value;
                
            case self::TYPE_JSON:
                return json_decode($this->value, true);
                
            case self::TYPE_STRING:
            default:
                return (string)$this->value;
        }
    }

    public function setTypedValue($value)
    {
        switch ($this->value_type) {
            case self::TYPE_BOOLEAN:
                $this->value = $value ? '1' : '0';
                break;
                
            case self::TYPE_INTEGER:
                $this->value = (string)(int)$value;
                break;
                
            case self::TYPE_JSON:
                $this->value = json_encode($value);
                break;
                
            case self::TYPE_STRING:
            default:
                $this->value = (string)$value;
                break;
        }
    }

    public static function get($key, $default = null, $category = null)
    {
        $query = self::where(['key' => $key]);
        
        if ($category) {
            $query->where('category', '=', $category);
        }
        
        $settings = $query->get();
        
        if (empty($settings)) {
            return $default;
        }
        
        return $settings[0]->getTypedValue();
    }

    public static function set($key, $value, $category = null, $userId = null)
    {
        $query = self::where(['key' => $key]);
        
        if ($category) {
            $query->where('category', '=', $category);
        }
        
        $settings = $query->get();
        
        if (empty($settings)) {
            // Create new setting
            $setting = new self();
            $setting->key = $key;
            $setting->category = $category ?: 'general';
            $setting->label = ucwords(str_replace('_', ' ', $key));
            $setting->value_type = self::getTypeFromValue($value);
            $setting->setTypedValue($value);
            $setting->updated_by = $userId;
            $setting->save();
            
            return $setting;
        } else {
            // Update existing setting
            $setting = $settings[0];
            $setting->setTypedValue($value);
            $setting->updated_by = $userId;
            $setting->save();
            
            return $setting;
        }
    }

    public static function getByCategory($category)
    {
        return self::where(['category' => $category])
                  ->orderBy('key')
                  ->get();
    }

    public static function getAllGrouped()
    {
        $db = self::getConnection();
        $sql = "SELECT * FROM settings ORDER BY category, key";
        $stmt = $db->query($sql);
        
        $grouped = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $setting = new self();
            $setting->attributes = $row;
            $setting->original = $row;
            
            $category = $setting->category;
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            
            $grouped[$category][] = $setting;
        }
        
        return $grouped;
    }

    public static function getPublicSettings()
    {
        return self::where(['is_public' => true])
                  ->orderBy('category')
                  ->orderBy('key')
                  ->get();
    }

    public static function getCategories()
    {
        $db = self::getConnection();
        $sql = "SELECT DISTINCT category FROM settings ORDER BY category";
        $stmt = $db->query($sql);
        
        $categories = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }

    public static function validateValue($value, $type)
    {
        switch ($type) {
            case self::TYPE_BOOLEAN:
                return true; // Any value can be cast to boolean
                
            case self::TYPE_INTEGER:
                return is_numeric($value);
                
            case self::TYPE_JSON:
                json_decode($value);
                return json_last_error() === JSON_ERROR_NONE;
                
            case self::TYPE_STRING:
            default:
                return true;
        }
    }

    private static function getTypeFromValue($value)
    {
        if (is_bool($value)) {
            return self::TYPE_BOOLEAN;
        } elseif (is_int($value)) {
            return self::TYPE_INTEGER;
        } elseif (is_array($value) || is_object($value)) {
            return self::TYPE_JSON;
        } else {
            return self::TYPE_STRING;
        }
    }

    public static function getValueTypes()
    {
        return [
            self::TYPE_STRING => 'String',
            self::TYPE_BOOLEAN => 'Boolean',
            self::TYPE_INTEGER => 'Integer',
            self::TYPE_JSON => 'JSON'
        ];
    }

    public static function getDefaultSettings()
    {
        return [
            'general' => [
                'site_title' => [
                    'label' => 'Site Title',
                    'value' => 'My CMS Website',
                    'type' => self::TYPE_STRING,
                    'is_public' => true,
                    'description' => 'The name of your website'
                ],
                'site_tagline' => [
                    'label' => 'Site Tagline',
                    'value' => 'A powerful content management system',
                    'type' => self::TYPE_STRING,
                    'is_public' => true,
                    'description' => 'Brief description or slogan'
                ],
                'admin_email' => [
                    'label' => 'Admin Email',
                    'value' => 'admin@example.com',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'Email address for system notifications'
                ]
            ],
            'seo' => [
                'meta_description' => [
                    'label' => 'Default Meta Description',
                    'value' => '',
                    'type' => self::TYPE_STRING,
                    'is_public' => true,
                    'description' => 'Default description for search engines'
                ],
                'meta_keywords' => [
                    'label' => 'Default Meta Keywords',
                    'value' => '',
                    'type' => self::TYPE_STRING,
                    'is_public' => true,
                    'description' => 'Comma-separated keywords'
                ],
                'google_analytics_id' => [
                    'label' => 'Google Analytics ID',
                    'value' => '',
                    'type' => self::TYPE_STRING,
                    'is_public' => true,
                    'description' => 'e.g., UA-XXXXX-Y or G-XXXXXXX'
                ]
            ],
            'security' => [
                'session_timeout' => [
                    'label' => 'Session Timeout (minutes)',
                    'value' => '30',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'How long before a session expires'
                ],
                'max_login_attempts' => [
                    'label' => 'Max Login Attempts',
                    'value' => '5',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'Number of failed attempts before lockout'
                ],
                'lockout_duration' => [
                    'label' => 'Lockout Duration (minutes)',
                    'value' => '15',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'How long an account remains locked'
                ],
                'password_min_length' => [
                    'label' => 'Minimum Password Length',
                    'value' => '8',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'Minimum characters for passwords'
                ],
                'require_special_chars' => [
                    'label' => 'Require Special Characters',
                    'value' => '1',
                    'type' => self::TYPE_BOOLEAN,
                    'is_public' => false,
                    'description' => 'Require special characters in passwords'
                ]
            ],
            'uploads' => [
                'max_file_size' => [
                    'label' => 'Max File Size (MB)',
                    'value' => '10',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'Maximum upload file size in megabytes'
                ],
                'allowed_extensions' => [
                    'label' => 'Allowed File Extensions',
                    'value' => 'jpg,jpeg,png,gif,webp,pdf,doc,docx,mp4,webm',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'Comma-separated list of allowed extensions'
                ],
                'image_quality' => [
                    'label' => 'Image Quality',
                    'value' => '85',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'JPEG quality (1-100)'
                ]
            ],
            'mail' => [
                'mail_driver' => [
                    'label' => 'Mail Driver',
                    'value' => 'smtp',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'smtp, sendmail, or mail'
                ],
                'mail_host' => [
                    'label' => 'SMTP Host',
                    'value' => 'smtp.gmail.com',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'SMTP server hostname'
                ],
                'mail_port' => [
                    'label' => 'SMTP Port',
                    'value' => '587',
                    'type' => self::TYPE_INTEGER,
                    'is_public' => false,
                    'description' => 'SMTP port (usually 25, 465, or 587)'
                ],
                'mail_username' => [
                    'label' => 'SMTP Username',
                    'value' => '',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'SMTP authentication username'
                ],
                'mail_password' => [
                    'label' => 'SMTP Password',
                    'value' => '',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'SMTP authentication password'
                ],
                'mail_encryption' => [
                    'label' => 'SMTP Encryption',
                    'value' => 'tls',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'tls or ssl'
                ],
                'mail_from_address' => [
                    'label' => 'From Address',
                    'value' => 'noreply@example.com',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'Default sender email address'
                ],
                'mail_from_name' => [
                    'label' => 'From Name',
                    'value' => 'CMS System',
                    'type' => self::TYPE_STRING,
                    'is_public' => false,
                    'description' => 'Default sender name'
                ]
            ]
        ];
    }

    public static function initializeDefaults($userId = null)
    {
        $defaults = self::getDefaultSettings();
        
        foreach ($defaults as $category => $settings) {
            foreach ($settings as $key => $config) {
                $existing = self::where(['category' => $category, 'key' => $key])->get();
                
                if (empty($existing)) {
                    $setting = new self();
                    $setting->category = $category;
                    $setting->key = $key;
                    $setting->label = $config['label'];
                    $setting->value = (string)$config['value'];
                    $setting->value_type = $config['type'];
                    $setting->is_public = $config['is_public'];
                    $setting->description = $config['description'] ?? null;
                    $setting->updated_by = $userId;
                    $setting->save();
                }
            }
        }
    }
}