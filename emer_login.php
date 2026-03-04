<?php
// emergency_login.php - DELETE AFTER USE

require_once __DIR__ . '/app/bootstrap.php';

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = \App\Config\Database::getConnection();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Direct database login
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = true");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Manual session creation
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();
        
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .emergency-card { background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 400px; margin: 0 auto; }
        .emergency-header { background: #dc3545; color: white; padding: 20px; border-radius: 15px 15px 0 0; text-align: center; }
        .emergency-body { padding: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="emergency-card">
            <div class="emergency-header">
                <h4 class="mb-0">⚠️ Emergency Login</h4>
            </div>
            <div class="emergency-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="admin" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" value="Admin@123" required>
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Emergency Login</button>
                </form>
                <div class="alert alert-warning mt-3 small">
                    <strong>⚠️ WARNING:</strong> This bypasses all normal security. 
                    <strong>DELETE THIS FILE</strong> after successful login!
                </div>
            </div>
        </div>
    </div>
</body>
</html>