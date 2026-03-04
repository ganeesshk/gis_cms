<?php
// admin/login.php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Security\CSRF;
use App\Security\Session;

$config = require __DIR__ . '/../app/Config/config.php';
$session = Session::getInstance($config['security']);

// Check if this is a fresh install - we'll use a simpler approach for first login
$isFirstLogin = false;
try {
    $db = \App\Config\Database::getConnection();
    
    // Check if users table has any records
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    
    // Check if sessions table exists
    $stmt = $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'sessions')");
    $sessionsExist = $stmt->fetchColumn();
    
    // If we have users but no sessions table or no sessions, this might be first login
    if ($userCount > 0 && (!$sessionsExist)) {
        $isFirstLogin = true;
    }
} catch (\Exception $e) {
    // If there's an error, assume it might be first login
    $isFirstLogin = true;
}

// For first login, we'll use a simpler CSRF approach
if ($isFirstLogin) {
    // Create a simple session token instead of using database CSRF
    if (!isset($_SESSION['login_token'])) {
        $_SESSION['login_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_token_expires'] = time() + 3600;
    }
    define('USE_SIMPLE_CSRF', true);
} else {
    define('USE_SIMPLE_CSRF', false);
}

$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Redirect if already logged in
if ($auth->getCurrentUser()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = $session->flash('success');

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (USE_SIMPLE_CSRF) {
        // Simple CSRF validation for first login
        $submitted_token = $_POST['csrf_token'] ?? '';
        $expected_token = $_SESSION['login_token'] ?? '';
        
        if ($submitted_token !== $expected_token || time() > $_SESSION['login_token_expires']) {
            $error = 'Invalid security token. Please refresh the page.';
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                $result = $auth->attempt($username, $password, $remember);
                
                if ($result['success']) {
                    // After successful login, generate a new token for next time
                    $_SESSION['login_token'] = bin2hex(random_bytes(32));
                    
                    if ($result['force_password_change']) {
                        header('Location: change-password.php?required=1');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    } else {
        // Regular CSRF validation
        if (!$csrf->validate($_POST['csrf_token'] ?? '', 'login')) {
            $error = 'Invalid security token. Please try again.';
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                $result = $auth->attempt($username, $password, $remember);
                
                if ($result['success']) {
                    if ($result['force_password_change']) {
                        header('Location: change-password.php?required=1');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Generate CSRF token
if (USE_SIMPLE_CSRF) {
    $csrfToken = $_SESSION['login_token'];
} else {
    $csrfToken = $csrf->generate('login');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-control {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger { background: #fee; color: #c33; }
        .alert-success { background: #efe; color: #3c3; }
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
        .input-group input { padding-left: 45px; }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-cms fa-3x mb-3"></i>
            <h1>Welcome Back</h1>
            <p>Sign in to your dashboard</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" name="username" placeholder="Username or Email" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <label class="d-flex align-items-center">
                        <input type="checkbox" name="remember" class="me-2"> Remember me
                    </label>
                    <a href="forgot-password.php" class="text-decoration-none" style="color: #667eea;">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
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
        
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('loginBtn').disabled = true;
            document.getElementById('loginBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
        });
    </script>
</body>
</html>