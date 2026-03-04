<?php
// admin/users/create.php

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
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: /admin/login.php');
    exit;
}

// Initialize controller
$controller = new UserController($auth, $csrf, $config);
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
    <title>Create User - CMS Admin</title>
    
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
            max-width: 800px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .form-section h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
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
                <h1>Create New User</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/users/" class="breadcrumb-item">Users</a>
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
            
            <form method="POST" action="/admin/users/store.php" id="userForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <!-- Account Information -->
                <div class="form-section">
                    <h3>Account Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Username <span class="required">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               name="username" 
                               value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>"
                               placeholder="johndoe"
                               required
                               pattern="[a-zA-Z0-9_]+"
                               title="Username can only contain letters, numbers, and underscores">
                        <div class="form-text">3-60 characters, letters, numbers, and underscores only</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                               placeholder="john@example.com"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" 
                               class="form-control" 
                               name="full_name" 
                               value="<?php echo htmlspecialchars($formData['full_name'] ?? ''); ?>"
                               placeholder="John Doe">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Password <span class="required">*</span>
                        </label>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password"
                               required>
                        
                        <!-- Password strength meter -->
                        <div class="password-strength">
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
                </div>
                
                <!-- Role and Permissions -->
                <div class="form-section">
                    <h3>Role & Permissions</h3>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Role <span class="required">*</span>
                        </label>
                        <select class="form-select" name="role_id" required>
                            <option value="">Select a role...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role->id; ?>" <?php echo ($formData['role_id'] ?? '') == $role->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">The role determines what the user can do in the system</div>
                    </div>
                </div>
                
                <!-- Account Settings -->
                <div class="form-section">
                    <h3>Account Settings</h3>
                    
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
                        <div class="form-text">Inactive users cannot log in</div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               name="force_password_change" 
                               id="forcePasswordChange" 
                               value="1"
                               <?php echo isset($formData['force_password_change']) && $formData['force_password_change'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="forcePasswordChange">
                            Force password change on next login
                        </label>
                        <div class="form-text">User will be required to change their password when they next log in</div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Create User
                    </button>
                    <a href="/admin/users/" class="btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Password strength checker
        const password = document.getElementById('password');
        
        function checkPasswordStrength() {
            const val = password.value;
            
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
        }
        
        password.addEventListener('input', checkPasswordStrength);
        
        // Form validation
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const username = document.querySelector('input[name="username"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            const role = document.querySelector('select[name="role_id"]').value;
            
            if (!username) {
                e.preventDefault();
                alert('Please enter a username.');
                return false;
            }
            
            if (!email) {
                e.preventDefault();
                alert('Please enter an email address.');
                return false;
            }
            
            if (!password) {
                e.preventDefault();
                alert('Please enter a password.');
                return false;
            }
            
            if (!role) {
                e.preventDefault();
                alert('Please select a role.');
                return false;
            }
            
            // Check password strength
            const allValid = document.querySelectorAll('.requirements li.valid').length === 5;
            if (!allValid) {
                e.preventDefault();
                alert('Please ensure your password meets all requirements.');
                return false;
            }
        });
    </script>
</body>
</html>