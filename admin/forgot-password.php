<?php
// admin/forgot-password.php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\User;
use App\Models\PasswordReset;
use App\Security\Session;
use App\Security\CSRF;
use App\Services\EmailService;

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validate($_POST['csrf_token'] ?? '', 'forgot_password')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $error = 'Please enter a valid email address.';
        } else {
            // Find user by email
            $users = User::where(['email' => $email])->get();
            $user = $users[0] ?? null;
            
            if ($user) {
                // Create password reset token
                $reset = PasswordReset::createForUser($user->id);
                
                // In a real application, you would send an email here
                // For demo purposes, we'll show the link
                $resetLink = $config['app']['url'] . '/admin/reset-password.php?token=' . $reset->raw_token;
                
                // Log the password reset request
                \App\Models\AuditLog::log([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'action' => 'password.reset_requested',
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                    'result' => 'success'
                ]);
                
                // In production, you'd send an email here
                // For development, we'll show the link
                if ($config['app']['env'] === 'development') {
                    $success = 'Password reset link: <a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a>';
                } else {
                    $success = 'If your email exists in our system, you will receive a password reset link shortly.';
                }
            } else {
                // Don't reveal that email doesn't exist
                $success = 'If your email exists in our system, you will receive a password reset link shortly.';
            }
        }
    }
}

$csrfToken = $csrf->generate('forgot_password');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CMS Admin</title>
    
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
        .alert-info {
            background: #e6f3ff;
            color: #0066cc;
            border: 1px solid #b8daff;
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
        .info-text {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-lock fa-3x mb-3"></i>
            <h1>Forgot Password?</h1>
            <p>Enter your email to reset your password</p>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; // Don't escape as it may contain HTML ?>
                </div>
            <?php else: ?>
                <div class="info-text">
                    <i class="fas fa-info-circle"></i>
                    Enter your email address and we'll send you a link to reset your password.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="forgotForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="Enter your email address"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               required 
                               autofocus>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Link
                </button>
                
                <div class="back-to-login">
                    <a href="login.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.getElementById('forgotForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>