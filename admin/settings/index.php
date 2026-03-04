<?php
// admin/settings/index.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\SettingsController;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Require authentication
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: /admin/login.php');
    exit;
}

// Initialize controller and get data
$controller = new SettingsController($auth, $csrf, $config);
$result = $controller->index();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- JSON Editor -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.10.2/jsoneditor.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand h2 {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav a {
            display: block;
            padding: 12px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav li.active a {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        
        .sidebar-nav a i {
            width: 25px;
        }
        
        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        
        .page-title .breadcrumb {
            margin: 5px 0 0;
            padding: 0;
            background: none;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Settings container */
        .settings-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        /* Tabs */
        .settings-tabs {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 0 20px;
        }
        
        .nav-tabs {
            border-bottom: none;
        }
        
        .nav-tabs .nav-link {
            color: rgba(255,255,255,0.8);
            border: none;
            padding: 15px 20px;
            margin-right: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: white;
            border-radius: 10px 10px 0 0;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 8px;
        }
        
        /* Settings content */
        .settings-content {
            padding: 30px;
        }
        
        .settings-section {
            margin-bottom: 30px;
        }
        
        .settings-section h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .settings-form {
            max-width: 800px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        
        .form-label .badge {
            margin-left: 8px;
            font-size: 10px;
            padding: 3px 6px;
        }
        
        .form-control, .form-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-check {
            margin-bottom: 10px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* JSON editor */
        .json-editor {
            height: 300px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-warning {
            background: #ffc107;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: #212529;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Toolbar */
        .settings-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .nav-tabs .nav-link {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .nav-tabs .nav-link i {
                margin-right: 4px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-cms fa-3x"></i>
            <h2>CMS Admin</h2>
            <p>Version 1.0</p>
        </div>
        
        <div class="sidebar-nav">
            <ul>
                <li>
                    <a href="/admin/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/admin/pages/">
                        <i class="fas fa-file-alt"></i>
                        Pages
                    </a>
                </li>
                <li>
                    <a href="/admin/menus/">
                        <i class="fas fa-bars"></i>
                        Menus
                    </a>
                </li>
                <li>
                    <a href="/admin/home/">
                        <i class="fas fa-home"></i>
                        Home Page
                    </a>
                </li>
                <li>
                    <a href="/admin/galleries/photo/">
                        <i class="fas fa-images"></i>
                        Photo Galleries
                    </a>
                </li>
                <li>
                    <a href="/admin/galleries/video/">
                        <i class="fas fa-video"></i>
                        Video Galleries
                    </a>
                </li>
                <li>
                    <a href="/admin/media/">
                        <i class="fas fa-folder-open"></i>
                        Media Library
                    </a>
                </li>
                <li>
                    <a href="/admin/users/">
                        <i class="fas fa-users"></i>
                        User Management
                    </a>
                </li>
                <li class="active">
                    <a href="/admin/settings/">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li>
                    <a href="/admin/audit/">
                        <i class="fas fa-history"></i>
                        Audit Logs
                    </a>
                </li>
                <li>
                    <a href="/admin/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="page-title">
                <h1>Settings</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <span class="breadcrumb-item active">Settings</span>
                </div>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell fa-lg"></i>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo htmlspecialchars($user->getAvatarUrl()); ?>" alt="Avatar" class="user-avatar">
                        <span class="ms-2 d-none d-md-block"><?php echo htmlspecialchars($user->getFullName()); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/admin/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Settings Toolbar -->
        <div class="settings-toolbar">
            <?php if ($user->isSuperAdmin()): ?>
                <button class="btn-outline-primary" onclick="exportSettings()">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                
                <button class="btn-outline-primary" onclick="importSettings()">
                    <i class="fas fa-upload"></i>
                    Import
                </button>
                
                <button class="btn-outline-primary" onclick="addSetting()">
                    <i class="fas fa-plus-circle"></i>
                    New Setting
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Settings Container -->
        <div class="settings-container">
            <!-- Tabs -->
            <div class="settings-tabs">
                <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                    <?php foreach ($categories as $index => $category): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $category === $activeTab ? 'active' : ''; ?>" 
                                    id="tab-<?php echo $category; ?>" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#content-<?php echo $category; ?>" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-<?php echo getCategoryIcon($category); ?>"></i>
                                <?php echo ucfirst($category); ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Content -->
            <div class="settings-content">
                <div class="tab-content" id="settingsTabContent">
                    <?php foreach ($categories as $category): ?>
                        <div class="tab-pane fade <?php echo $category === $activeTab ? 'show active' : ''; ?>" 
                             id="content-<?php echo $category; ?>" 
                             role="tabpanel">
                            
                            <form method="POST" action="/admin/settings/update.php" class="settings-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="category" value="<?php echo $category; ?>">
                                
                                <?php if (isset($settings[$category])): ?>
                                    <?php foreach ($settings[$category] as $setting): ?>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <?php echo htmlspecialchars($setting->label); ?>
                                                <?php if (!$setting->is_public): ?>
                                                    <span class="badge bg-secondary">Private</span>
                                                <?php endif; ?>
                                                <span class="badge bg-info"><?php echo $valueTypes[$setting->value_type]; ?></span>
                                            </label>
                                            
                                            <?php if ($setting->value_type === 'boolean'): ?>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           name="<?php echo $setting->key; ?>" 
                                                           id="<?php echo $category . '_' . $setting->key; ?>"
                                                           value="1"
                                                           <?php echo $setting->getTypedValue() ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $category . '_' . $setting->key; ?>">
                                                        Enabled
                                                    </label>
                                                </div>
                                                
                                            <?php elseif ($setting->value_type === 'json'): ?>
                                                <div class="json-editor" id="json-<?php echo $category . '_' . $setting->key; ?>"></div>
                                                <input type="hidden" name="<?php echo $setting->key; ?>" id="<?php echo $category . '_' . $setting->key; ?>_hidden">
                                                <script>
                                                    initializeJsonEditor('json-<?php echo $category . '_' . $setting->key; ?>', 
                                                                         '<?php echo $category . '_' . $setting->key; ?>_hidden',
                                                                         <?php echo json_encode($setting->getTypedValue()); ?>);
                                                </script>
                                                
                                            <?php elseif ($setting->value_type === 'integer'): ?>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="<?php echo $setting->key; ?>" 
                                                       value="<?php echo htmlspecialchars($setting->value); ?>"
                                                       step="1">
                                                
                                            <?php else: ?>
                                                <?php if (strlen($setting->value) > 100): ?>
                                                    <textarea class="form-control" 
                                                              name="<?php echo $setting->key; ?>" 
                                                              rows="3"><?php echo htmlspecialchars($setting->value); ?></textarea>
                                                <?php else: ?>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           name="<?php echo $setting->key; ?>" 
                                                           value="<?php echo htmlspecialchars($setting->value); ?>">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($setting->description): ?>
                                                <div class="form-text"><?php echo htmlspecialchars($setting->description); ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if ($user->isSuperAdmin()): ?>
                                                <div class="mt-1">
                                                    <small>
                                                        <a href="#" class="text-danger" onclick="deleteSetting(<?php echo $setting->id; ?>, '<?php echo $setting->key; ?>')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save"></i>
                                        Save Changes
                                    </button>
                                    
                                    <?php if ($user->isSuperAdmin()): ?>
                                        <button type="button" class="btn-warning" onclick="resetCategory('<?php echo $category; ?>')">
                                            <i class="fas fa-undo"></i>
                                            Reset to Defaults
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Setting Modal -->
    <div class="modal fade" id="addSettingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <form id="addSettingForm">
                        <div class="form-group mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" id="newCategory" placeholder="e.g., general, seo, security">
                            <small class="text-muted">Use lowercase letters only</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Key</label>
                            <input type="text" class="form-control" name="key" id="newKey" placeholder="e.g., site_title, max_uploads">
                            <small class="text-muted">Use lowercase letters, numbers, and underscores</small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Label</label>
                            <input type="text" class="form-control" name="label" id="newLabel" placeholder="e.g., Site Title">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Value Type</label>
                            <select class="form-select" name="value_type" id="newValueType">
                                <?php foreach ($valueTypes as $type => $label): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Default Value</label>
                            <input type="text" class="form-control" name="default_value" id="newDefaultValue" placeholder="Default value">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="newDescription" rows="2"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="is_public" id="newIsPublic" value="1" checked>
                            <label class="form-check-label" for="newIsPublic">Public (visible to frontend)</label>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewSetting()">
                        <i class="fas fa-save"></i>
                        Add Setting
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <form action="/admin/settings/import.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Select JSON File</label>
                            <input type="file" class="form-control" name="import_file" accept=".json" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            The file should contain valid JSON with the same structure as the export format.
                        </div>
                        
                        <button type="submit" class="btn-primary w-100">
                            <i class="fas fa-upload"></i>
                            Import Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('settings_ajax'); ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsoneditor/9.10.2/jsoneditor.min.js"></script>
    
    <script>
        // Store JSON editor instances
        const jsonEditors = {};
        
        // Initialize JSON editor
        function initializeJsonEditor(containerId, inputId, value) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const options = {
                mode: 'code',
                modes: ['code', 'form', 'tree'],
                onChange: function() {
                    try {
                        const json = jsonEditors[containerId].get();
                        document.getElementById(inputId).value = JSON.stringify(json);
                    } catch (e) {
                        // Invalid JSON
                    }
                }
            };
            
            const editor = new JSONEditor(container, options);
            editor.set(value || {});
            jsonEditors[containerId] = editor;
        }
        
        // Add new setting
        function addSetting() {
            const modal = new bootstrap.Modal(document.getElementById('addSettingModal'));
            modal.show();
        }
        
        // Save new setting
        function saveNewSetting() {
            const data = {
                category: document.getElementById('newCategory').value,
                key: document.getElementById('newKey').value,
                label: document.getElementById('newLabel').value,
                value_type: document.getElementById('newValueType').value,
                default_value: document.getElementById('newDefaultValue').value,
                description: document.getElementById('newDescription').value,
                is_public: document.getElementById('newIsPublic').checked
            };
            
            fetch('/admin/settings/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to create setting', 'error');
                }
            });
        }
        
        // Delete setting
        function deleteSetting(id, key) {
            Swal.fire({
                title: 'Delete Setting',
                html: `Are you sure you want to delete "<strong>${key}</strong>"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/settings/delete.php?id=' + id, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            Swal.fire('Error', data.error || 'Failed to delete setting', 'error');
                        }
                    });
                }
            });
        }
        
        // Export settings
        function exportSettings() {
            window.location.href = '/admin/settings/export.php';
        }
        
        // Import settings
        function importSettings() {
            const modal = new bootstrap.Modal(document.getElementById('importModal'));
            modal.show();
        }
        
        // Reset category
        function resetCategory(category) {
            Swal.fire({
                title: 'Reset Category',
                html: `Are you sure you want to reset all settings in "<strong>${category}</strong>" to defaults?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Reset'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/settings/reset.php?category=' + encodeURIComponent(category) + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        // Activate tab based on URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#tab=')) {
                const tabId = hash.replace('#tab=', '');
                const tab = document.querySelector(`[data-bs-target="#content-${tabId}"]`);
                if (tab) {
                    new bootstrap.Tab(tab).show();
                }
            }
        });
    </script>
</body>
</html>

<?php
// Helper function for category icons
function getCategoryIcon($category) {
    $icons = [
        'general' => 'globe',
        'seo' => 'chart-line',
        'security' => 'lock',
        'uploads' => 'cloud-upload-alt',
        'mail' => 'envelope'
    ];
    
    return $icons[$category] ?? 'cog';
}
?>