<?php
// register.php

require_once __DIR__ . '/app/bootstrap.php';

use App\Security\Password;
use App\Security\Session;

$config = require __DIR__ . '/app/Config/config.php';
$session = Session::getInstance($config['security']);

// For installation, use a simple session-based token
if (!isset($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
    $_SESSION['install_token_expires'] = time() + 3600;
}

// Check if already installed
$isInstalled = file_exists(__DIR__ . '/.installed');

// If already installed, redirect to login
if ($isInstalled) {
    header('Location: admin/login.php');
    exit;
}

$error = '';
$success = '';
$step = 1;

if (isset($_GET['step'])) {
    $step = (int)$_GET['step'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['install_token'] ?? '';
    $expected_token = $_SESSION['install_token'] ?? '';
    
    if ($submitted_token !== $expected_token || time() > $_SESSION['install_token_expires']) {
        $error = 'Invalid or expired security token. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? 'register';
        
        if ($action === 'check_requirements') {
            $requirements = checkRequirements();
            $allPassed = true;
            
            foreach ($requirements as $req) {
                if (!$req['passed']) {
                    $allPassed = false;
                    break;
                }
            }
            
            if ($allPassed) {
                $step = 2;
                $_SESSION['install_token'] = bin2hex(random_bytes(32));
                $_SESSION['install_token_expires'] = time() + 3600;
            } else {
                $error = 'Please fix the requirements above before continuing.';
            }
            
        } elseif ($action === 'create_admin') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($username)) {
                $errors[] = 'Username is required';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors[] = 'Username can only contain letters, numbers, and underscores';
            }
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($errors)) {
                try {
                    $db = \App\Config\Database::getConnection();
                    $db->beginTransaction();
                    
                    // Check if roles table is empty
                    $stmt = $db->query("SELECT COUNT(*) FROM roles");
                    $roleCount = $stmt->fetchColumn();
                    
                    $roleId = null;
                    
                    if ($roleCount == 0) {
                        // Insert default roles
                        $sql = "INSERT INTO roles (name, slug, is_system, permissions, created_at, updated_at) VALUES 
                                ('Super Admin', 'super_admin', true, '{\"all\": true}', NOW(), NOW()),
                                ('Admin', 'admin', true, '{\"pages\":\"*\",\"menus\":\"*\",\"galleries\":\"*\",\"media\":\"*\",\"settings\":\"*\"}', NOW(), NOW()),
                                ('Editor', 'editor', false, '{\"pages\":\"write\",\"media\":\"write\"}', NOW(), NOW()),
                                ('Viewer', 'viewer', false, '{\"pages\":\"read\"}', NOW(), NOW())";
                        $db->exec($sql);
                        
                        // Get the super admin role ID
                        $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ?");
                        $stmt->execute(['super_admin']);
                        $roleId = $stmt->fetchColumn();
                    } else {
                        // Get existing super admin role
                        $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ?");
                        $stmt->execute(['super_admin']);
                        $roleId = $stmt->fetchColumn();
                        
                        // If super admin doesn't exist, create it
                        if (!$roleId) {
                            $sql = "INSERT INTO roles (name, slug, is_system, permissions, created_at, updated_at) 
                                    VALUES ('Super Admin', 'super_admin', true, '{\"all\": true}', NOW(), NOW()) 
                                    RETURNING id";
                            $stmt = $db->query($sql);
                            $roleId = $stmt->fetchColumn();
                        }
                    }
                    
                    if (!$roleId) {
                        throw new Exception('Failed to get super admin role ID');
                    }
                    
                    // Check if username already exists
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        throw new Exception('Username already exists');
                    }
                    
                    // Check if email already exists
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        throw new Exception('Email already exists');
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Create user
                    $sql = "INSERT INTO users (username, email, password_hash, full_name, role_id, is_active, force_password_change, created_at, updated_at) 
                            VALUES (:username, :email, :password_hash, :full_name, :role_id, :is_active, :force_password_change, NOW(), NOW()) 
                            RETURNING id";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password_hash' => $hashedPassword,
                        ':full_name' => $fullName,
                        ':role_id' => $roleId,
                        ':is_active' => 't',
                        ':force_password_change' => 'f'
                    ]);
                    
                    $userId = $stmt->fetchColumn();
                    
                    if (!$userId) {
                        throw new Exception('Failed to create user');
                    }
                    
                    // Initialize default settings - First clear any existing settings to avoid conflicts
					try {
						// Clear existing settings
						$db->exec("DELETE FROM settings");
						
						// Reset the sequence
						$db->exec("ALTER SEQUENCE IF EXISTS settings_id_seq RESTART WITH 1");
						
						// Insert default settings
						$defaultSettings = [
							['general', 'site_title', 'GIS CMS', 'Site Title', 'string', 't'],
							['general', 'site_tagline', 'A powerful content management system', 'Site Tagline', 'string', 't'],
							['general', 'admin_email', $email, 'Admin Email', 'string', 'f'],
							['seo', 'meta_description', '', 'Default Meta Description', 'string', 't'],
							['seo', 'meta_keywords', '', 'Default Meta Keywords', 'string', 't'],
							['security', 'session_timeout', '30', 'Session Timeout (minutes)', 'integer', 'f'],
							['security', 'max_login_attempts', '5', 'Max Login Attempts', 'integer', 'f'],
							['security', 'lockout_duration', '15', 'Lockout Duration (minutes)', 'integer', 'f'],
							['uploads', 'max_file_size', '10', 'Max File Size (MB)', 'integer', 'f'],
							['uploads', 'allowed_extensions', 'jpg,jpeg,png,gif,webp,pdf,doc,docx,mp4,webm', 'Allowed File Extensions', 'string', 'f'],
							['uploads', 'image_quality', '85', 'Image Quality', 'integer', 'f']
						];
						
						foreach ($defaultSettings as $setting) {
							$sql = "INSERT INTO settings (category, key, value, label, value_type, is_public, updated_by, updated_at) 
									VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
							$stmt = $db->prepare($sql);
							$stmt->execute([$setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5], $userId]);
						}
						
						// Insert default menus
						$sql = "INSERT INTO menus (name, location, description, is_active, created_by, created_at, updated_at) 
								VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
						
						$stmt = $db->prepare($sql);
						$stmt->execute(['Main Navigation', 'primary', 'Main site navigation', 't', $userId]);
						$stmt->execute(['Footer Menu', 'footer', 'Footer links', 't', $userId]);
						
					} catch (Exception $e) {
						// If settings insertion fails, log but don't fail the whole installation
						error_log('Settings insertion warning: ' . $e->getMessage());
					}
                    
                    // Create installed flag
                    file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
                    
                    $success = 'Installation complete! You can now log in with your credentials.';
                    $step = 3;
                    
                    unset($_SESSION['install_token']);
                    
                } catch (Exception $e) {
                    if (isset($db)) {
                        $db->rollBack();
                    }
                    $error = 'Installation error: ' . $e->getMessage();
                    error_log('Installation error: ' . $e->getMessage());
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}

// Check system requirements function
function checkRequirements() {
    $requirements = [];
    
    // PHP version
    $requirements[] = [
        'name' => 'PHP Version',
        'required' => '8.0+',
        'current' => PHP_VERSION,
        'passed' => version_compare(PHP_VERSION, '8.0.0', '>=')
    ];
    
    // Required extensions
    $extensions = ['pdo', 'pdo_pgsql', 'json', 'session', 'ctype', 'filter', 'hash', 'openssl', 'mbstring', 'curl', 'gd'];
    foreach ($extensions as $ext) {
        $requirements[] = [
            'name' => "PHP Extension: {$ext}",
            'required' => 'Installed',
            'current' => extension_loaded($ext) ? 'Installed' : 'Missing',
            'passed' => extension_loaded($ext)
        ];
    }
    
    // Database connection
    try {
        $db = \App\Config\Database::getConnection();
        $db->query('SELECT 1');
        $dbPassed = true;
        $dbMessage = 'Connected';
    } catch (\Exception $e) {
        $dbPassed = false;
        $dbMessage = 'Failed: ' . $e->getMessage();
    }
    
    $requirements[] = [
        'name' => 'Database Connection',
        'required' => 'PostgreSQL',
        'current' => $dbMessage,
        'passed' => $dbPassed
    ];
    
    // Write permissions
    $writablePaths = [
        'storage/logs' => is_writable(__DIR__ . '/storage/logs'),
        'public/uploads' => is_writable(__DIR__ . '/public/uploads'),
        'storage/cache' => is_writable(__DIR__ . '/storage/cache')
    ];
    
    foreach ($writablePaths as $path => $writable) {
        $requirements[] = [
            'name' => "Write Permission: {$path}",
            'required' => 'Writable',
            'current' => $writable ? 'Writable' : 'Not writable',
            'passed' => $writable
        ];
    }
    
    // .env file
    $envExists = file_exists(__DIR__ . '/.env');
    $requirements[] = [
        'name' => 'Environment File',
        'required' => '.env file',
        'current' => $envExists ? 'Found' : 'Missing',
        'passed' => $envExists
    ];
    
    return $requirements;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GIS CMS Installation</title>
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
            padding: 20px;
        }
        .installer-container { max-width: 800px; width: 100%; }
        .installer-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .installer-header h1 { margin: 20px 0 10px; font-size: 32px; font-weight: 600; }
        .installer-body { padding: 40px; }
        
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e1e1e1;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            background: white;
            text-align: center;
            flex: 1;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #e1e1e1;
            color: #999;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
        }
        .step.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step.completed .step-number { background: #28a745; color: white; }
        
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .requirement-status {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .status-passed { background: #d4edda; color: #28a745; }
        .status-failed { background: #f8d7da; color: #dc3545; }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.4); }
        
        .form-group { margin-bottom: 25px; }
        .form-control {
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            padding: 12px 15px;
            width: 100%;
        }
        .form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .requirements {
            list-style: none;
            padding: 0;
            margin: 10px 0 0;
            font-size: 13px;
        }
        .requirements li { margin-bottom: 5px; color: #666; }
        .requirements li i { width: 20px; color: #dc3545; }
        .requirements li.valid i { color: #28a745; }
        
        .strength-meter {
            height: 5px;
            background: #e1e1e1;
            border-radius: 5px;
            margin: 10px 0;
        }
        .strength-meter-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s;
        }
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .alert-danger { background: #fee; color: #c33; }
        .alert-success { background: #efe; color: #3c3; }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-card">
            <div class="installer-header">
                <i class="fas fa-cms fa-4x"></i>
                <h1>GIS CMS Installation</h1>
                <p>Welcome to the GIS CMS setup wizard.</p>
            </div>
            
            <div class="installer-body">
                <div class="steps">
                    <div class="step <?php echo $step >= 1 ? 'completed' : ''; ?> <?php echo $step == 1 ? 'active' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-label">Requirements</div>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step == 2 ? 'active' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-label">Admin Account</div>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step == 3 ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">Complete</div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($step == 1): ?>
                    <?php $requirements = checkRequirements(); 
                    $allPassed = true;
                    foreach ($requirements as $req) { if (!$req['passed']) { $allPassed = false; break; } }
                    ?>
                    
                    <?php foreach ($requirements as $req): ?>
                        <div class="requirement-item">
                            <div class="requirement-status <?php echo $req['passed'] ? 'status-passed' : 'status-failed'; ?>">
                                <i class="fas fa-<?php echo $req['passed'] ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600"><?php echo $req['name']; ?></div>
                                <div style="font-size:13px; color:#666">Required: <?php echo $req['required']; ?></div>
                            </div>
                            <div style="font-weight:600; color:<?php echo $req['passed'] ? '#28a745' : '#dc3545'; ?>">
                                <?php echo $req['current']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="install_token" value="<?php echo $_SESSION['install_token']; ?>">
                        <input type="hidden" name="action" value="check_requirements">
                        
                        <?php if ($allPassed): ?>
                            <button type="submit" class="btn-primary">Continue to Admin Account Setup</button>
                            <div class="text-center mt-3">
                                <small><a href="?step=2">Or click here to proceed manually</a></small>
                            </div>
                        <?php else: ?>
                            <button type="submit" class="btn-primary" style="background:#ccc; cursor:not-allowed;" disabled>
                                Please Fix Requirements Above
                            </button>
                        <?php endif; ?>
                    </form>
                    
                <?php elseif ($step == 2): ?>
                    <form method="POST" id="registrationForm">
                        <input type="hidden" name="install_token" value="<?php echo $_SESSION['install_token']; ?>">
                        <input type="hidden" name="action" value="create_admin">
                        
                        <div class="form-group">
                            <label>Username <span style="color:#dc3545">*</span></label>
                            <input type="text" class="form-control" name="username" required 
                                   pattern="[a-zA-Z0-9_]+" placeholder="admin">
                        </div>
                        
                        <div class="form-group">
                            <label>Email <span style="color:#dc3545">*</span></label>
                            <input type="email" class="form-control" name="email" required 
                                   placeholder="admin@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" class="form-control" name="full_name" 
                                   placeholder="John Doe">
                        </div>
                        
                        <div class="form-group">
                            <label>Password <span style="color:#dc3545">*</span></label>
                            <input type="password" class="form-control" name="password" id="password" required>
                            
                            <div class="strength-meter">
                                <div class="strength-meter-fill" id="strengthFill" style="width:0%"></div>
                            </div>
                            
                            <ul class="requirements" id="passwordRequirements">
                                <li id="reqLength"><i class="fas fa-times-circle"></i> At least 8 characters</li>
                                <li id="reqUppercase"><i class="fas fa-times-circle"></i> One uppercase letter</li>
                                <li id="reqLowercase"><i class="fas fa-times-circle"></i> One lowercase letter</li>
                                <li id="reqNumber"><i class="fas fa-times-circle"></i> One number</li>
                            </ul>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password <span style="color:#dc3545">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                            <div id="matchMessage" class="small mt-1"></div>
                        </div>
                        
                        <button type="submit" class="btn-primary" id="submitBtn">Complete Installation</button>
                    </form>
                    
                <?php elseif ($step == 3): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                        <h4>Installation Complete!</h4>
                        <p class="text-muted mb-4">You can now log in with your admin credentials.</p>
                        <a href="/admin/login.php" class="btn-primary" style="width:auto; padding:12px 30px;">
                            Go to Login Page
                        </a>
                        <p class="mt-4 text-muted small">For security, please delete register.php</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        <?php if ($step == 2): ?>
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        function checkPasswordStrength() {
            const val = password.value;
            
            const lengthValid = val.length >= 8;
            document.getElementById('reqLength').className = lengthValid ? 'valid' : '';
            document.getElementById('reqLength').innerHTML = (lengthValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' At least 8 characters';
            
            const upperValid = /[A-Z]/.test(val);
            document.getElementById('reqUppercase').className = upperValid ? 'valid' : '';
            document.getElementById('reqUppercase').innerHTML = (upperValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One uppercase letter';
            
            const lowerValid = /[a-z]/.test(val);
            document.getElementById('reqLowercase').className = lowerValid ? 'valid' : '';
            document.getElementById('reqLowercase').innerHTML = (lowerValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One lowercase letter';
            
            const numberValid = /[0-9]/.test(val);
            document.getElementById('reqNumber').className = numberValid ? 'valid' : '';
            document.getElementById('reqNumber').innerHTML = (numberValid ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>') + ' One number';
            
            let strength = 0;
            if (lengthValid) strength += 25;
            if (upperValid) strength += 25;
            if (lowerValid) strength += 25;
            if (numberValid) strength += 25;
            
            const fill = document.getElementById('strengthFill');
            fill.style.width = strength + '%';
            fill.className = 'strength-meter-fill ' + 
                (strength < 50 ? 'strength-weak' : strength < 75 ? 'strength-medium' : 'strength-strong');
            
            checkMatch();
        }
        
        function checkMatch() {
            const match = password.value === confirm.value && password.value.length > 0;
            const allValid = document.querySelectorAll('.requirements li.valid').length === 4;
            
            if (confirm.value.length > 0) {
                document.getElementById('matchMessage').innerHTML = match ? 
                    '<i class="fas fa-check-circle text-success"></i> Passwords match' : 
                    '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
            }
            
            submitBtn.disabled = !(allValid && match);
        }
        
        password.addEventListener('input', checkPasswordStrength);
        confirm.addEventListener('input', checkMatch);
        <?php endif; ?>
    </script>
</body>
</html>