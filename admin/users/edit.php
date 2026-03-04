<?php
// admin/users/edit.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\UserController;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Require authentication
$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    header('Location: /admin/login.php');
    exit;
}

// Get user ID
$id = (int)($_GET['id'] ?? 0);

// Initialize controller
$controller = new UserController($auth, $csrf, $config);
$result = $controller->edit($id);
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
    <title>Edit User: <?php echo htmlspecialchars($user->username); ?> - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
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
        
        .page-title .badge {
            margin-left: 10px;
            font-size: 12px;
            padding: 5px 10px;
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
        
        /* Profile header */
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            object-fit: cover;
        }
        
        .profile-info {
            margin-left: 30px;
        }
        
        .profile-name {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-username {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .profile-meta {
            display: flex;
            gap: 30px;
        }
        
        .profile-meta-item {
            text-align: center;
        }
        
        .profile-meta-value {
            font-size: 24px;
            font-weight: 600;
        }
        
        .profile-meta-label {
            font-size: 12px;
            opacity: 0.8;
        }
        
        /* Layout */
        .edit-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .card:last-child {
            margin-bottom: 0;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .card-header .badge {
            font-size: 12px;
            padding: 5px 10px;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 20px;
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
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        
        .form-control[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-check {
            margin-bottom: 10px;
        }
        
        .form-check-input {
            margin-right: 8px;
        }
        
        /* Password strength */
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-meter {
            height: 5px;
            background: #e9ecef;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .strength-meter-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .strength-weak {
            background: #dc3545;
        }
        
        .strength-medium {
            background: #ffc107;
        }
        
        .strength-strong {
            background: #28a745;
        }
        
        .requirements {
            list-style: none;
            padding: 0;
            margin: 10px 0 0;
            font-size: 13px;
        }
        
        .requirements li {
            margin-bottom: 5px;
            color: #666;
        }
        
        .requirements li i {
            width: 20px;
            color: #dc3545;
        }
        
        .requirements li.valid i {
            color: #28a745;
        }
        
        /* Activity timeline */
        .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .timeline-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .timeline-item:last-child {
            border-bottom: none;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 3px;
        }
        
        .timeline-time {
            font-size: 12px;
            color: #999;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-locked {
            background: #fff3cd;
            color: #856404;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220,53,69,0.4);
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
        
        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        /* Chart */
        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 20px;
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-info {
                margin-left: 0;
                margin-top: 20px;
            }
            
            .profile-meta {
                justify-content: center;
            }
            
            .edit-layout {
                grid-template-columns: 1fr;
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
                <li class="active">
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
                <h1>
                    Edit User
                    <?php if ($user->isLocked()): ?>
                        <span class="badge bg-warning">Locked</span>
                    <?php elseif (!$user->is_active): ?>
                        <span class="badge bg-danger">Inactive</span>
                    <?php endif; ?>
                </h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/users/" class="breadcrumb-item">Users</a>
                    <span class="breadcrumb-item active"><?php echo htmlspecialchars($user->username); ?></span>
                </div>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell fa-lg"></i>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo htmlspecialchars($currentUser->getAvatarUrl()); ?>" alt="Avatar" class="user-avatar">
                        <span class="ms-2 d-none d-md-block"><?php echo htmlspecialchars($currentUser->getFullName()); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/admin/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Profile Header -->
        <div class="profile-header d-flex align-items-center">
            <img src="<?php echo htmlspecialchars($user->getAvatarUrl()); ?>" alt="Avatar" class="profile-avatar">
            
            <div class="profile-info">
                <div class="profile-name"><?php echo htmlspecialchars($user->full_name ?: $user->username); ?></div>
                <div class="profile-username">@<?php echo htmlspecialchars($user->username); ?></div>
                
                <div class="profile-meta">
                    <div class="profile-meta-item">
                        <div class="profile-meta-value"><?php echo $activity['pages']; ?></div>
                        <div class="profile-meta-label">Pages</div>
                    </div>
                    <div class="profile-meta-item">
                        <div class="profile-meta-value"><?php echo $activity['media']; ?></div>
                        <div class="profile-meta-label">Media</div>
                    </div>
                    <div class="profile-meta-item">
                        <div class="profile-meta-value"><?php echo $activity['logins']; ?></div>
                        <div class="profile-meta-label">Logins</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Layout -->
        <div class="edit-layout">
            <!-- Left Column - Forms -->
            <div>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Information -->
                <div class="card">
                    <div class="card-header">
                        <h2>Profile Information</h2>
                    </div>
                    
                    <form method="POST" action="/admin/users/update.php?id=<?php echo $user->id; ?>" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user->username); ?>" 
                                   readonly>
                            <div class="form-text">Username cannot be changed</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Email Address <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($formData['email'] ?? $user->email); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="full_name" 
                                   value="<?php echo htmlspecialchars($formData['full_name'] ?? $user->full_name); ?>"
                                   placeholder="Full name">
                        </div>
                        
                        <?php if ($currentUser->isSuperAdmin() || $currentUser->hasPermission('users.edit_roles')): ?>
                            <div class="form-group">
                                <label class="form-label">
                                    Role <span class="required">*</span>
                                </label>
                                <select class="form-select" name="role_id" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role->id; ?>" <?php echo ($formData['role_id'] ?? $user->role_id) == $role->id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="is_active" 
                                   id="isActive" 
                                   value="1"
                                   <?php echo (isset($formData['is_active']) ? $formData['is_active'] : $user->is_active) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isActive">
                                Active
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-primary mt-3">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h2>Change Password</h2>
                    </div>
                    
                    <form method="POST" action="/admin/users/update.php?id=<?php echo $user->id; ?>" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   name="password" 
                                   id="password"
                                   placeholder="Leave empty to keep current password">
                            
                            <!-- Password strength meter -->
                            <div class="password-strength" id="passwordStrength" style="display: none;">
                                <div class="strength-meter">
                                    <div class="strength-meter-fill" id="strengthFill" style="width: 0%"></div>
                                </div>
                                
                                <ul class="requirements" id="passwordRequirements">
                                    <li id="reqLength"><i class="fas fa-times-circle"></i> At least 8 characters</li>
                                    <li id="reqUppercase"><i class="fas fa-times-circle"></i> One uppercase letter</li>
                                    <li id="reqLowercase"><i class="fas fa-times-circle"></i> One lowercase letter</li>
                                    <li id="reqNumber"><i class="fas fa-times-circle"></i> One number</li>
                                    <li id="reqSpecial"><i class="fas fa-times-circle"></i> One special character (!@#$%^&*)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="force_password_change" 
                                   id="forcePasswordChange" 
                                   value="1"
                                   <?php echo (isset($formData['force_password_change']) ? $formData['force_password_change'] : $user->force_password_change) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="forcePasswordChange">
                                Force password change on next login
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-warning">
                            <i class="fas fa-key"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Right Column - Stats & Activity -->
            <div>
                <!-- Account Status -->
                <div class="card">
                    <div class="card-header">
                        <h2>Account Status</h2>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <?php if ($user->isLocked()): ?>
                            <span class="status-badge status-locked ms-2">
                                <i class="fas fa-lock"></i> Locked
                            </span>
                            <p class="text-muted mt-2">
                                Account is locked until <?php echo $user->locked_until->format('Y-m-d H:i:s'); ?>
                            </p>
                            <?php if ($currentUser->isSuperAdmin()): ?>
                                <button class="btn btn-sm btn-warning" onclick="unlockUser(<?php echo $user->id; ?>)">
                                    <i class="fas fa-unlock"></i>
                                    Unlock Account
                                </button>
                            <?php endif; ?>
                        <?php elseif ($user->is_active): ?>
                            <span class="status-badge status-active ms-2">
                                <i class="fas fa-check-circle"></i> Active
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-inactive ms-2">
                                <i class="fas fa-times-circle"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Failed Login Attempts:</strong>
                        <span class="ms-2"><?php echo $user->failed_login_attempts; ?> / <?php echo $config['security']['max_login_attempts']; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Login:</strong>
                        <span class="ms-2"><?php echo $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : 'Never'; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Last Login IP:</strong>
                        <span class="ms-2"><?php echo $user->last_login_ip ?: 'N/A'; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Password Last Changed:</strong>
                        <span class="ms-2"><?php echo $user->password_changed_at ? $user->password_changed_at->format('Y-m-d H:i:s') : 'Never'; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Account Created:</strong>
                        <span class="ms-2"><?php echo $user->created_at->format('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
                
                <!-- Activity Chart -->
                <div class="card">
                    <div class="card-header">
                        <h2>Activity Overview</h2>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Activity</h2>
                        <span class="badge bg-secondary">Last 10 actions</span>
                    </div>
                    
                    <?php if (empty($recentLogs)): ?>
                        <p class="text-muted text-center py-3">No recent activity</p>
                    <?php else: ?>
                        <ul class="timeline">
                            <?php foreach ($recentLogs as $log): ?>
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <?php 
                                        $icon = match($log->action) {
                                            'login.success' => 'fa-sign-in-alt',
                                            'login.failed' => 'fa-exclamation-triangle',
                                            'page.create' => 'fa-file',
                                            'page.edit' => 'fa-edit',
                                            'page.publish' => 'fa-check-circle',
                                            'media.upload' => 'fa-cloud-upload-alt',
                                            default => 'fa-circle'
                                        };
                                        ?>
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">
                                            <?php echo ucwords(str_replace('.', ' ', $log->action)); ?>
                                            <?php if ($log->entity_label): ?>
                                                <strong><?php echo htmlspecialchars($log->entity_label); ?></strong>
                                            <?php endif; ?>
                                        </div>
                                        <div class="timeline-time">
                                            <i class="far fa-clock"></i> <?php echo date('M j, Y H:i', strtotime($log->created_at)); ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <!-- Danger Zone -->
                <?php if ($currentUser->isSuperAdmin() && $user->id != $currentUser->id): ?>
                    <div class="card" style="border: 2px solid #dc3545;">
                        <div class="card-header">
                            <h2 class="text-danger">Danger Zone</h2>
                        </div>
                        
                        <p class="text-muted mb-3">Once you delete a user, there is no going back. Please be certain.</p>
                        
                        <div class="d-flex gap-3">
                            <button class="btn-danger" onclick="deleteUser(<?php echo $user->id; ?>)">
                                <i class="fas fa-trash"></i>
                                Delete User
                            </button>
                            
                            <?php if ($user->deleted_at): ?>
                                <button class="btn-warning" onclick="restoreUser(<?php echo $user->id; ?>)">
                                    <i class="fas fa-undo"></i>
                                    Restore User
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('user_actions'); ?>">
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Password strength checker
        const password = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        
        if (password) {
            password.addEventListener('focus', function() {
                passwordStrength.style.display = 'block';
            });
            
            password.addEventListener('input', function() {
                const val = this.value;
                
                if (val.length > 0) {
                    passwordStrength.style.display = 'block';
                    
                    // Length check
                    const lengthValid = val.length >= 8;
                    document.getElementById('reqLength').className = lengthValid ? 'valid' : '';
                    document.getElementById('reqLength').innerHTML = (lengthValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' At least 8 characters';
                    
                    // Uppercase check
                    const upperValid = /[A-Z]/.test(val);
                    document.getElementById('reqUppercase').className = upperValid ? 'valid' : '';
                    document.getElementById('reqUppercase').innerHTML = (upperValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One uppercase letter';
                    
                    // Lowercase check
                    const lowerValid = /[a-z]/.test(val);
                    document.getElementById('reqLowercase').className = lowerValid ? 'valid' : '';
                    document.getElementById('reqLowercase').innerHTML = (lowerValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One lowercase letter';
                    
                    // Number check
                    const numberValid = /[0-9]/.test(val);
                    document.getElementById('reqNumber').className = numberValid ? 'valid' : '';
                    document.getElementById('reqNumber').innerHTML = (numberValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One number';
                    
                    // Special char check
                    const specialValid = /[!@#$%^&*(),.?":{}|<>]/.test(val);
                    document.getElementById('reqSpecial').className = specialValid ? 'valid' : '';
                    document.getElementById('reqSpecial').innerHTML = (specialValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One special character (!@#$%^&*)';
                    
                    // Calculate strength
                    let strength = 0;
                    if (lengthValid) strength += 20;
                    if (upperValid) strength += 20;
                    if (lowerValid) strength += 20;
                    if (numberValid) strength += 20;
                    if (specialValid) strength += 20;
                    
                    const strengthFill = document.getElementById('strengthFill');
                    strengthFill.style.width = strength + '%';
                    
                    if (strength < 40) {
                        strengthFill.className = 'strength-meter-fill strength-weak';
                    } else if (strength < 80) {
                        strengthFill.className = 'strength-meter-fill strength-medium';
                    } else {
                        strengthFill.className = 'strength-meter-fill strength-strong';
                    }
                } else {
                    passwordStrength.style.display = 'none';
                }
            });
        }
        
        // Activity Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            // Sample data - you can replace with actual weekly activity
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Actions',
                        data: [12, 19, 15, 17, 14, 8, 5],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102,126,234,0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
        
        // Delete user
        function deleteUser(id) {
            Swal.fire({
                title: 'Delete User',
                text: 'Are you sure you want to delete this user? This action can be undone by restoring.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/users/delete.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        // Restore user
        function restoreUser(id) {
            Swal.fire({
                title: 'Restore User',
                text: 'Restore this deleted user?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Restore'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/users/restore.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        // Unlock user
        function unlockUser(id) {
            fetch('/admin/users/unlock.php?id=' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', 'User account unlocked', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Failed to unlock user', 'error');
                }
            });
        }
    </script>
</body>
</html>