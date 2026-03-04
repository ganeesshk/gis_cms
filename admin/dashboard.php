<?php
// admin/dashboard.php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Page;
use App\Models\Media;

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

// Get dashboard statistics
$totalPages = Page::where(['deleted_at' => null])->count();
$publishedPages = Page::where(['status' => 'published', 'deleted_at' => null])->count();
$draftPages = Page::where(['status' => 'draft', 'deleted_at' => null])->count();
$totalMedia = Media::where(['deleted_at' => null])->count();
$totalUsers = User::where(['deleted_at' => null])->count();

// Get recent activity
$recentActivity = AuditLog::getRecent(10);

// Get current time greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar */
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
        
        .sidebar-brand p {
            margin: 5px 0 0;
            font-size: 13px;
            opacity: 0.8;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin: 5px 0;
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
            font-size: 18px;
        }
        
        .sidebar-nav .nav-label {
            margin-left: 10px;
        }
        
        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
        }
        
        /* Header */
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
        
        .notifications {
            position: relative;
        }
        
        .notifications .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 3px 6px;
            font-size: 10px;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 10px;
            transition: background 0.3s;
        }
        
        .user-dropdown:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: #333;
        }
        
        .user-role {
            font-size: 12px;
            color: #666;
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }
        
        .stat-info .stat-number {
            font-size: 32px;
            font-weight: 600;
            color: #333;
            margin: 5px 0 0;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.pages {
            background: rgba(102,126,234,0.1);
            color: #667eea;
        }
        
        .stat-icon.media {
            background: rgba(40,167,69,0.1);
            color: #28a745;
        }
        
        .stat-icon.users {
            background: rgba(255,193,7,0.1);
            color: #ffc107;
        }
        
        .stat-icon.activity {
            background: rgba(23,162,184,0.1);
            color: #17a2b8;
        }
        
        /* Charts */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        /* Recent activity */
        .activity-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .activity-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .activity-meta {
            font-size: 12px;
            color: #999;
        }
        
        .activity-meta i {
            margin-right: 5px;
        }
        
        .activity-result {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .result-success {
            background: rgba(40,167,69,0.1);
            color: #28a745;
        }
        
        .result-failure {
            background: rgba(220,53,69,0.1);
            color: #dc3545;
        }
        
        .result-warning {
            background: rgba(255,193,7,0.1);
            color: #ffc107;
        }
        
        /* Quick actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 25px;
        }
        
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border: 1px solid #eee;
        }
        
        .quick-action:hover {
            background: #f8f9fa;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .quick-action i {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .quick-action span {
            display: block;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .charts-row {
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
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="pages/">
                        <i class="fas fa-file-alt"></i>
                        <span class="nav-label">Pages</span>
                    </a>
                </li>
                <li>
                    <a href="menus/">
                        <i class="fas fa-bars"></i>
                        <span class="nav-label">Menus</span>
                    </a>
                </li>
                <li>
                    <a href="home/">
                        <i class="fas fa-home"></i>
                        <span class="nav-label">Home Page</span>
                    </a>
                </li>
                <li>
                    <a href="galleries/photo/">
                        <i class="fas fa-images"></i>
                        <span class="nav-label">Photo Galleries</span>
                    </a>
                </li>
                <li>
                    <a href="galleries/video/">
                        <i class="fas fa-video"></i>
                        <span class="nav-label">Video Galleries</span>
                    </a>
                </li>
                <li>
                    <a href="media/">
                        <i class="fas fa-folder-open"></i>
                        <span class="nav-label">Media Library</span>
                    </a>
                </li>
                <li class="nav-divider"></li>
                <li>
                    <a href="users/">
                        <i class="fas fa-users"></i>
                        <span class="nav-label">User Management</span>
                    </a>
                </li>
                <li>
                    <a href="settings/">
                        <i class="fas fa-cog"></i>
                        <span class="nav-label">Settings</span>
                    </a>
                </li>
                <li>
                    <a href="audit/">
                        <i class="fas fa-history"></i>
                        <span class="nav-label">Audit Logs</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-label">Logout</span>
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
                <h1>Dashboard</h1>
                <div class="breadcrumb">
                    <span class="breadcrumb-item active">Home</span>
                </div>
            </div>
            
            <div class="user-menu">
                <div class="notifications">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="badge bg-danger">3</span>
                </div>
                
                <div class="user-dropdown" onclick="toggleUserMenu()">
                    <img src="<?php echo htmlspecialchars($user->getAvatarUrl()); ?>" alt="Avatar" class="user-avatar">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($user->getFullName()); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($user->role->name ?? 'User'); ?></div>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Pages</h3>
                    <div class="stat-number"><?php echo $totalPages; ?></div>
                    <small><?php echo $publishedPages; ?> published, <?php echo $draftPages; ?> drafts</small>
                </div>
                <div class="stat-icon pages">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Media Files</h3>
                    <div class="stat-number"><?php echo $totalMedia; ?></div>
                    <small>Images, documents, etc.</small>
                </div>
                <div class="stat-icon media">
                    <i class="fas fa-images"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Users</h3>
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <small>Active accounts</small>
                </div>
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Today's Activity</h3>
                    <div class="stat-number"><?php echo count($recentActivity); ?></div>
                    <small>Last 10 actions</small>
                </div>
                <div class="stat-icon activity">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Page Views</h3>
                    <select class="form-select" style="width: auto;">
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>Last 90 days</option>
                    </select>
                </div>
                <canvas id="viewsChart" height="200"></canvas>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3>Content Distribution</h3>
                </div>
                <canvas id="distributionChart" height="200"></canvas>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="activity-card">
            <div class="activity-header">
                <h3>Recent Activity</h3>
                <a href="audit/" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            
            <ul class="activity-list">
                <?php foreach ($recentActivity as $log): ?>
                <li class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-<?php 
                            echo match($log->action) {
                                'login.success' => 'sign-in-alt',
                                'page.create' => 'file',
                                'page.edit' => 'edit',
                                'page.publish' => 'check-circle',
                                'media.upload' => 'upload',
                                default => 'circle'
                            };
                        ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            <?php echo htmlspecialchars($log->action); ?>
                            <?php if ($log->entity_label): ?>
                                <strong><?php echo htmlspecialchars($log->entity_label); ?></strong>
                            <?php endif; ?>
                        </div>
                        <div class="activity-meta">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($log->username ?? 'System'); ?>
                            <i class="fas fa-clock ms-3"></i> <?php echo date('M j, Y H:i', strtotime($log->created_at)); ?>
                            <i class="fas fa-globe ms-3"></i> <?php echo htmlspecialchars($log->ip_address); ?>
                        </div>
                    </div>
                    <div class="activity-result result-<?php echo $log->result; ?>">
                        <?php echo ucfirst($log->result); ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="pages/create.php" class="quick-action">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Page</span>
                </a>
                <a href="media/upload.php" class="quick-action">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload Media</span>
                </a>
                <a href="home/edit.php" class="quick-action">
                    <i class="fas fa-paint-brush"></i>
                    <span>Edit Home</span>
                </a>
                <a href="users/create.php" class="quick-action">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- User Menu Dropdown -->
    <div class="dropdown-menu dropdown-menu-end" id="userMenu" style="display: none;">
        <a href="profile.php" class="dropdown-item">
            <i class="fas fa-user"></i> My Profile
        </a>
        <a href="settings.php" class="dropdown-item">
            <i class="fas fa-cog"></i> Settings
        </a>
        <div class="dropdown-divider"></div>
        <a href="logout.php" class="dropdown-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
        
        // Toggle user menu
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userDropdown = document.querySelector('.user-dropdown');
            
            if (!userDropdown.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.style.display = 'none';
            }
        });
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Views Chart
            const ctx1 = document.getElementById('viewsChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Page Views',
                        data: [65, 59, 80, 81, 56, 55, 40],
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
            
            // Distribution Chart
            const ctx2 = document.getElementById('distributionChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Published Pages', 'Draft Pages', 'Media Files'],
                    datasets: [{
                        data: [<?php echo $publishedPages; ?>, <?php echo $draftPages; ?>, <?php echo $totalMedia; ?>],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#17a2b8'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '60%'
                }
            });
        });
    </script>
</body>
</html>