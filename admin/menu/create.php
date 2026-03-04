<?php
// admin/menus/create.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\MenuController;
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

// Initialize controller
$controller = new MenuController($auth, $csrf, $config);
$result = $controller->create();
extract($result['data']);

// Restore form data from session if validation failed
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Menu - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }
        
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
        
        /* Form */
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            max-width: 600px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 12px 15px;
            transition: all 0.3s;
            width: 100%;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        
        .form-text {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .form-check-label {
            cursor: pointer;
        }
        
        .location-info {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .location-info i {
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .location-info ul {
            margin: 10px 0 0 20px;
            color: #666;
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
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
            padding: 15px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
        }
        
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
                <li class="active">
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
                <li>
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
                <h1>Create New Menu</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/menus/" class="breadcrumb-item">Menus</a>
                    <span class="breadcrumb-item active">Create</span>
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
        
        <!-- Form -->
        <div class="form-container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/admin/menus/store.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <!-- Location Info -->
                <div class="location-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>About Menu Locations:</strong>
                    <ul>
                        <li>Each location can only be used by one menu</li>
                        <li>Primary and Footer are system locations</li>
                        <li>Choose a location that matches where you want this menu to appear</li>
                    </ul>
                </div>
                
                <!-- Menu Name -->
                <div class="form-group">
                    <label class="form-label">
                        Menu Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           name="name" 
                           value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                           placeholder="e.g., Main Navigation, Footer Menu"
                           required>
                    <div class="form-text">
                        A descriptive name to identify this menu in the admin panel.
                    </div>
                </div>
                
                <!-- Location -->
                <div class="form-group">
                    <label class="form-label">
                        Location <span class="required">*</span>
                    </label>
                    <select class="form-select" name="location" required>
                        <option value="">Select a location...</option>
                        <?php foreach ($locations as $value => $label): ?>
                            <option value="<?php echo $value; ?>" 
                                    <?php echo ($formData['location'] ?? '') === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?> (<?php echo $value; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Where this menu will appear on your site.
                    </div>
                </div>
                
                <!-- Description -->
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" 
                              name="description" 
                              rows="3"
                              placeholder="Brief description of this menu (optional)"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                </div>
                
                <!-- Active Status -->
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               name="is_active" 
                               id="isActive" 
                               value="1"
                               <?php echo !isset($formData['is_active']) || $formData['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isActive">
                            Active
                        </label>
                    </div>
                    <div class="form-text">
                        If inactive, this menu will not be displayed on the site.
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Create Menu
                    </button>
                    <a href="/admin/menus/" class="btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const location = document.querySelector('select[name="location"]').value;
            
            if (!name) {
                e.preventDefault();
                alert('Please enter a menu name.');
                return false;
            }
            
            if (!location) {
                e.preventDefault();
                alert('Please select a location.');
                return false;
            }
        });
    </script>
</body>
</html>