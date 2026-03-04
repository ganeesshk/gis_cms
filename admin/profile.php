<?php
// admin/profile.php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;
use App\Security\Password;
use App\Models\AuditLog;

$config = require __DIR__ . '/../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Require authentication
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validate($_POST['csrf_token'] ?? '', 'profile')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'profile') {
            // Update profile info
            $fullName = trim($_POST['full_name'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                $error = 'Please enter a valid email address.';
            } else {
                // Check if email is already taken by another user
                $existingUsers = User::where(['email' => $email])->get();
                foreach ($existingUsers as $existingUser) {
                    if ($existingUser->id !== $user->id) {
                        $error = 'Email address is already in use.';
                        break;
                    }
                }
                
                if (!$error) {
                    $user->full_name = $fullName;
                    $user->email = $email;
                    $user->save();
                    
                    AuditLog::log([
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'action' => 'profile.updated',
                        'entity_type' => 'user',
                        'entity_id' => $user->id,
                        'result' => 'success'
                    ]);
                    
                    $success = 'Profile updated successfully.';
                }
            }
        } elseif ($action === 'password') {
            // Change password
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (!$user->verifyPassword($currentPassword)) {
                $error = 'Current password is incorrect.';
            } else {
                $passwordErrors = Password::validateStrength($newPassword);
                
                if (!empty($passwordErrors)) {
                    $error = implode('<br>', $passwordErrors);
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match.';
                } else {
                    $user->setPassword($newPassword);
                    $user->force_password_change = false;
                    $user->save();
                    
                    AuditLog::log([
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        'action' => 'password.changed',
                        'entity_type' => 'user',
                        'entity_id' => $user->id,
                        'result' => 'success'
                    ]);
                    
                    $success = 'Password changed successfully.';
                }
            }
        }
    }
}

$csrfToken = $csrf->generate('profile');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Add your styles here - similar to dashboard */
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .profile-name {
            margin: 15px 0 5px;
            font-size: 24px;
            font-weight: 600;
        }
        
        .profile-role {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 25px;
            font-weight: 600;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 10px 15px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .requirement {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .requirement.valid {
            color: #28a745;
        }
        
        .requirement i {
            width: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="container">
                        <div class="text-center">
                            <img src="<?php echo htmlspecialchars($user->getAvatarUrl()); ?>" alt="Avatar" class="profile-avatar">
                            <h1 class="profile-name"><?php echo htmlspecialchars($user->getFullName()); ?></h1>
                            <div class="profile-role"><?php echo htmlspecialchars($user->role->name ?? 'User'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="container">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-user"></i>
                                    Profile Information
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="profile">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user->username); ?>" disabled>
                                            <small class="text-muted">Username cannot be changed</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="full_name" 
                                                   value="<?php echo htmlspecialchars($user->full_name); ?>"
                                                   placeholder="Enter your full name">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" 
                                                   class="form-control" 
                                                   name="email" 
                                                   value="<?php echo htmlspecialchars($user->email); ?>"
                                                   placeholder="Enter your email"
                                                   required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i>
                                            Update Profile
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Change Password -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <i class="fas fa-lock"></i>
                                    Change Password
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="passwordForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="password">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="current_password" 
                                                   id="current_password"
                                                   required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">New Password</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="new_password" 
                                                   id="new_password"
                                                   required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="confirm_password" 
                                                   id="confirm_password"
                                                   required>
                                        </div>
                                        
                                        <div class="password-requirements">
                                            <div class="requirement" id="reqLength">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                At least 8 characters
                                            </div>
                                            <div class="requirement" id="reqUppercase">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                One uppercase letter
                                            </div>
                                            <div class="requirement" id="reqLowercase">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                One lowercase letter
                                            </div>
                                            <div class="requirement" id="reqNumber">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                One number
                                            </div>
                                            <div class="requirement" id="reqSpecial">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                One special character
                                            </div>
                                            <div class="requirement" id="reqMatch">
                                                <i class="fas fa-times-circle text-danger"></i>
                                                Passwords match
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="fas fa-key"></i>
                                            Change Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Recent Activity -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <i class="fas fa-history"></i>
                                    Recent Activity
                                </div>
                                <div class="card-body">
                                    <?php
                                    $recentLogs = AuditLog::getByUser($user->id, 5);
                                    ?>
                                    <?php if (empty($recentLogs)): ?>
                                        <p class="text-muted text-center py-3">No recent activity</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($recentLogs as $log): ?>
                                            <li class="mb-3">
                                                <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($log->created_at)); ?></small>
                                                <div>
                                                    <i class="fas fa-<?php echo $log->result === 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'; ?>"></i>
                                                    <?php echo htmlspecialchars($log->action); ?>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Password strength checker
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        function checkPasswordStrength() {
            const val = newPassword.value;
            
            // Length check
            const lengthValid = val.length >= 8;
            updateRequirement('reqLength', lengthValid, 'At least 8 characters');
            
            // Uppercase check
            const upperValid = /[A-Z]/.test(val);
            updateRequirement('reqUppercase', upperValid, 'One uppercase letter');
            
            // Lowercase check
            const lowerValid = /[a-z]/.test(val);
            updateRequirement('reqLowercase', lowerValid, 'One lowercase letter');
            
            // Number check
            const numberValid = /[0-9]/.test(val);
            updateRequirement('reqNumber', numberValid, 'One number');
            
            // Special char check
            const specialValid = /[!@#$%^&*(),.?":{}|<>]/.test(val);
            updateRequirement('reqSpecial', specialValid, 'One special character');
            
            // Match check
            const matchValid = val === confirmPassword.value && val.length > 0;
            updateRequirement('reqMatch', matchValid, 'Passwords match');
            
            // Enable/disable submit button
            const allValid = lengthValid && upperValid && lowerValid && numberValid && specialValid && matchValid;
            submitBtn.disabled = !allValid;
        }
        
        function updateRequirement(id, isValid, text) {
            const element = document.getElementById(id);
            if (isValid) {
                element.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + text;
                element.classList.add('valid');
            } else {
                element.innerHTML = '<i class="fas fa-times-circle text-danger"></i> ' + text;
                element.classList.remove('valid');
            }
        }
        
        newPassword.addEventListener('input', checkPasswordStrength);
        confirmPassword.addEventListener('input', checkPasswordStrength);
    </script>
</body>
</html>