<?php
// admin/audit/view.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\AuditController;
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

// Get log ID
$id = (int)($_GET['id'] ?? 0);

// Initialize controller
$controller = new AuditController($auth, $csrf, $config);
$result = $controller->view($id);
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Details - CMS Admin</title>
    
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
        
        /* Log details */
        .details-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .detail-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-section h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-item .label {
            font-size: 12px;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
        }
        
        .detail-item .value {
            font-size: 14px;
            color: #333;
            word-break: break-word;
        }
        
        .value-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .result-success {
            background: #d4edda;
            color: #155724;
        }
        
        .result-failure {
            background: #f8d7da;
            color: #721c24;
        }
        
        .result-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .json-view {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .btn-back {
            display: inline-block;
            padding: 10px 25px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn-back:hover {
            background: #5a6268;
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
            
            .detail-grid {
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
                <li class="active">
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
                <h1>Log Details #<?php echo $log->id; ?></h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/audit/" class="breadcrumb-item">Audit Logs</a>
                    <span class="breadcrumb-item active">Details</span>
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
        
        <!-- Details Card -->
        <div class="details-card">
            <!-- Basic Information -->
            <div class="detail-section">
                <h2>Basic Information</h2>
                
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="label">Log ID</span>
                        <span class="value">#<?php echo $log->id; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Timestamp</span>
                        <span class="value"><?php echo $log->created_at->format('Y-m-d H:i:s'); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Result</span>
                        <span class="value">
                            <span class="value-badge result-<?php echo $log->result; ?>">
                                <?php echo ucfirst($log->result); ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">IP Address</span>
                        <span class="value"><code><?php echo htmlspecialchars($log->ip_address); ?></code></span>
                    </div>
                </div>
            </div>
            
            <!-- User Information -->
            <div class="detail-section">
                <h2>User Information</h2>
                
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="label">User ID</span>
                        <span class="value"><?php echo $log->user_id ?: '—'; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Username</span>
                        <span class="value"><?php echo htmlspecialchars($log->username ?: 'System'); ?></span>
                    </div>
                    
                    <?php if ($log->user_id): ?>
                        <?php $logUser = $log->user(); ?>
                        <?php if ($logUser): ?>
                            <div class="detail-item">
                                <span class="label">Full Name</span>
                                <span class="value"><?php echo htmlspecialchars($logUser->full_name ?: '—'); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="label">Email</span>
                                <span class="value"><?php echo htmlspecialchars($logUser->email); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <span class="label">User Agent</span>
                        <span class="value"><small><?php echo htmlspecialchars($log->user_agent ?: '—'); ?></small></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Session ID</span>
                        <span class="value"><?php echo $log->session_id ?: '—'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Action Information -->
            <div class="detail-section">
                <h2>Action Information</h2>
                
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="label">Action</span>
                        <span class="value"><code><?php echo htmlspecialchars($log->action); ?></code></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Entity Type</span>
                        <span class="value"><?php echo $log->entity_type ?: '—'; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Entity ID</span>
                        <span class="value"><?php echo $log->entity_id ?: '—'; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="label">Entity Label</span>
                        <span class="value"><?php echo htmlspecialchars($log->entity_label ?: '—'); ?></span>
                    </div>
                    
                    <?php if ($log->error_message): ?>
                        <div class="detail-item" style="grid-column: span 2;">
                            <span class="label">Error Message</span>
                            <span class="value text-danger"><?php echo htmlspecialchars($log->error_message); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Data Changes -->
            <?php if ($oldValues || $newValues): ?>
                <div class="detail-section">
                    <h2>Data Changes</h2>
                    
                    <div class="detail-grid">
                        <?php if ($oldValues): ?>
                            <div class="detail-item" style="grid-column: span 2;">
                                <span class="label">Old Values</span>
                                <pre class="json-view"><?php echo json_encode($oldValues, JSON_PRETTY_PRINT); ?></pre>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($newValues): ?>
                            <div class="detail-item" style="grid-column: span 2;">
                                <span class="label">New Values</span>
                                <pre class="json-view"><?php echo json_encode($newValues, JSON_PRETTY_PRINT); ?></pre>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Raw Data -->
            <div class="detail-section">
                <h2>Raw Data</h2>
                
                <pre class="json-view"><?php echo json_encode($log->toArray(), JSON_PRETTY_PRINT); ?></pre>
            </div>
            
            <a href="/admin/audit/" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Audit Logs
            </a>
        </div>
    </div>
</body>
</html>