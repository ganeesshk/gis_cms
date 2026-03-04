<?php
// admin/reset-password.php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\PasswordReset;
use App\Models\User;
use App\Security\Session;
use App\Security\CSRF;
use App\Security\Password;

$config = require __DIR__ . '/../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);

// Redirect if already logged in
if ($session->get('user_id')) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

$reset = PasswordReset::validate($token);

if (!$reset) {
    $error = 'Invalid or expired reset token. Please request a new one.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    if (!$csrf->validate($_POST['csrf_token'] ?? '', 'reset_password')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate password strength
        $passwordErrors = Password::validateStrength($password);
        
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Get user and update password
            $user = User::find($reset->user_id);
            
            if ($user) {
                $user->setPassword($password);
                $user->force_password_change = false;
                $user->save();
                
                // Mark token as used
                $reset->markAsUsed();
                
                // Log password change
                \App\Models\AuditLog::log([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'action' => 'password.reset_completed',
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                    'result' => 'success'
                ]);
                
                $session->flash('success', 'Your password has been reset successfully. Please login with your new password.');
                header('Location: login.php');
                exit;
            }
        }
    }
}

$csrfToken = $csrf->generate('reset_password');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .card-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .card-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .input-group {
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 1;
        }
        .input-group input {
            padding-left: 45px;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            z-index: 2;
        }
        .toggle-password:hover {
            color: #667eea;
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .password-strength {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .strength-meter {
            height: 5px;
            background: #e1e1e1;
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
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-key fa-3x mb-3"></i>
            <h1>Reset Password</h1>
            <p>Create a new strong password</p>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$reset): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    Invalid or expired reset token. 
                    <a href="forgot-password.php" class="alert-link">Request a new one</a>.
                </div>
            <?php else: ?>
                <form method="POST" action="" id="resetForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter new password"
                                   required 
                                   autofocus>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                        </div>
                    </div>
                    
                    <div class="password-strength">
                        <div class="strength-meter">
                            <div class="strength-meter-fill" id="strengthFill" style="width: 0%"></div>
                        </div>
                        <div id="strengthText" class="text-center small">Enter a password</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirm new password"
                                   required>
                            <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        <div id="matchMessage" class="small mt-1"></div>
                    </div>
                    
                    <ul class="requirements" id="passwordRequirements">
                        <li id="reqLength"><i class="fas fa-times-circle"></i> At least 8 characters</li>
                        <li id="reqUppercase"><i class="fas fa-times-circle"></i> One uppercase letter</li>
                        <li id="reqLowercase"><i class="fas fa-times-circle"></i> One lowercase letter</li>
                        <li id="reqNumber"><i class="fas fa-times-circle"></i> One number</li>
                        <li id="reqSpecial"><i class="fas fa-times-circle"></i> One special character (!@#$%^&*)</li>
                    </ul>
                    
                    <button type="submit" class="btn-primary" id="submitBtn" disabled>
                        <i class="fas fa-save"></i>
                        Reset Password
                    </button>
                    
                    <div class="back-to-login">
                        <a href="login.php">
                            <i class="fas fa-arrow-left"></i>
                            Back to Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const password = document.getElementById(fieldId);
            const icon = event.target;
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        function checkPasswordStrength() {
            const val = password.value;
            let strength = 0;
            
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
            if (lengthValid) strength += 20;
            if (upperValid) strength += 20;
            if (lowerValid) strength += 20;
            if (numberValid) strength += 20;
            if (specialValid) strength += 20;
            
            strengthFill.style.width = strength + '%';
            
            if (strength < 40) {
                strengthFill.className = 'strength-meter-fill strength-weak';
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
            } else if (strength < 80) {
                strengthFill.className = 'strength-meter-fill strength-medium';
                strengthText.textContent = 'Medium password';
                strengthText.style.color = '#ffc107';
            } else {
                strengthFill.className = 'strength-meter-fill strength-strong';
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
            }
            
            checkMatch();
        }
        
        function checkMatch() {
            const match = password.value === confirm.value && password.value.length > 0;
            const allValid = document.querySelectorAll('.requirements li.valid').length === 5;
            
            if (confirm.value.length > 0) {
                if (match) {
                    document.getElementById('matchMessage').innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
                    document.getElementById('matchMessage').className = 'text-success small mt-1';
                } else {
                    document.getElementById('matchMessage').innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
                    document.getElementById('matchMessage').className = 'text-danger small mt-1';
                }
            } else {
                document.getElementById('matchMessage').innerHTML = '';
            }
            
            submitBtn.disabled = !(allValid && match);
        }
        
        password.addEventListener('input', checkPasswordStrength);
        confirm.addEventListener('input', checkMatch);
        
        document.getElementById('resetForm').addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
        });
    </script>
</body>
</html>