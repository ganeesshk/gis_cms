<?php
// admin/users/index.php

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

// Initialize controller and get data
$controller = new UserController($auth, $csrf, $config);
$result = $controller->index();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
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
        
        /* Sidebar styles */
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-info h3 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin: 0;
            line-height: 1.2;
        }
        
        .stat-info p {
            margin: 5px 0 0;
            color: #666;
            font-size: 13px;
        }
        
        /* Role badges */
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .role-super-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .role-admin {
            background: #dc3545;
            color: white;
        }
        
        .role-editor {
            background: #28a745;
            color: white;
        }
        
        .role-viewer {
            background: #ffc107;
            color: #212529;
        }
        
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
        
        /* Toolbar */
        .toolbar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filters .form-select,
        .filters .form-control {
            width: auto;
            min-width: 150px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #666;
        }
        
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon:hover {
            background: #e9ecef;
        }
        
        .btn-icon.edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-icon.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        .btn-icon.restore:hover {
            background: #28a745;
            color: white;
        }
        
        .btn-icon.unlock:hover {
            background: #ffc107;
            color: #212529;
        }
        
        /* Pagination */
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Alert */
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        /* Modal */
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .user-detail-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            width: 120px;
            font-weight: 500;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .filters .form-select,
            .filters .form-control {
                width: 100%;
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
                <h1>User Management</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <span class="breadcrumb-item active">Users</span>
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
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['active']; ?></h3>
                    <p>Active</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['inactive']; ?></h3>
                    <p>Inactive</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['locked']; ?></h3>
                    <p>Locked</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['new_this_month']; ?></h3>
                    <p>New This Month</p>
                </div>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="filters">
                <select class="form-select" id="roleFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $currentRole === 'all' ? 'selected' : ''; ?>>All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role->id; ?>" <?php echo $currentRole == $role->id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($role->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select class="form-select" id="statusFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $currentStatus === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="active" <?php echo $currentStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $currentStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           id="searchInput" 
                           placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="applyFilters()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <?php if ($user->isSuperAdmin()): ?>
                    <button class="btn-outline-primary" onclick="exportUsers()">
                        <i class="fas fa-download"></i>
                        Export CSV
                    </button>
                <?php endif; ?>
                
                <a href="/admin/users/create.php" class="btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Add New User
                </a>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No users found.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $userObj): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($userObj->getAvatarUrl()); ?>" 
                                                 alt="" class="user-avatar-small me-3">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($userObj->full_name ?: $userObj->username); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($userObj->email); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $role = $userObj->role();
                                        $roleClass = 'role-' . str_replace('_', '-', $role->slug ?? 'viewer');
                                        ?>
                                        <span class="role-badge <?php echo $roleClass; ?>">
                                            <?php echo htmlspecialchars($role->name ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($userObj->isLocked()): ?>
                                            <span class="status-badge status-locked">
                                                <i class="fas fa-lock"></i> Locked
                                            </span>
                                        <?php elseif ($userObj->is_active): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fas fa-times-circle"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($userObj->last_login_at): ?>
                                            <div><?php echo date('M j, Y', strtotime($userObj->last_login_at)); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($userObj->last_login_at)); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($userObj->created_at)); ?></div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon view" onclick="viewUser(<?php echo $userObj->id; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <a href="/admin/users/edit.php?id=<?php echo $userObj->id; ?>" class="btn-icon edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($user->isSuperAdmin() && $userObj->id != $user->id): ?>
                                                <?php if ($userObj->deleted_at): ?>
                                                    <button class="btn-icon restore" onclick="restoreUser(<?php echo $userObj->id; ?>)" title="Restore">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-icon delete" onclick="deleteUser(<?php echo $userObj->id; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($userObj->isLocked() && $user->isSuperAdmin()): ?>
                                                <button class="btn-icon unlock" onclick="unlockUser(<?php echo $userObj->id; ?>)" title="Unlock Account">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&role=<?php echo urlencode($currentRole); ?>&status=<?php echo urlencode($currentStatus); ?>&search=<?php echo urlencode($search); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i >= $currentPage - 2 && $i <= $currentPage + 2): ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo urlencode($currentRole); ?>&status=<?php echo urlencode($currentStatus); ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&role=<?php echo urlencode($currentRole); ?>&status=<?php echo urlencode($currentStatus); ?>&search=<?php echo urlencode($search); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="userModalBody">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading...</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="editUserBtn" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit User
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('user_actions'); ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#usersTable').DataTable({
                paging: false,
                searching: false,
                ordering: true,
                info: false
            });
        });
        
        // Apply filters
        function applyFilters() {
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;
            
            window.location.href = '?role=' + encodeURIComponent(role) + '&status=' + encodeURIComponent(status) + '&search=' + encodeURIComponent(search);
        }
        
        // View user details
        function viewUser(id) {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            
            fetch('/admin/users/get-details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    let statusHtml = '';
                    if (data.is_locked) {
                        statusHtml = '<span class="status-badge status-locked"><i class="fas fa-lock"></i> Locked</span>';
                    } else if (data.is_active) {
                        statusHtml = '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Active</span>';
                    } else {
                        statusHtml = '<span class="status-badge status-inactive"><i class="fas fa-times-circle"></i> Inactive</span>';
                    }
                    
                    const html = `
                        <div class="text-center mb-4">
                            <img src="${data.avatar}" alt="${data.username}" class="user-detail-avatar">
                            <h4>${data.full_name || data.username}</h4>
                            <p class="text-muted">@${data.username}</p>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Email:</div>
                            <div class="detail-value">${data.email}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Role:</div>
                            <div class="detail-value">${data.role}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">${statusHtml}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Last Login:</div>
                            <div class="detail-value">${data.last_login}</div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Joined:</div>
                            <div class="detail-value">${data.joined}</div>
                        </div>
                        
                        <h5 class="mt-4 mb-3">Activity Summary</h5>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold">${data.activity.pages}</div>
                                <small class="text-muted">Pages</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">${data.activity.media}</div>
                                <small class="text-muted">Media</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">${data.activity.logins}</div>
                                <small class="text-muted">Logins</small>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('userModalBody').innerHTML = html;
                    document.getElementById('editUserBtn').href = '/admin/users/edit.php?id=' + id;
                    
                    modal.show();
                });
        }
        
        // Delete user
        function deleteUser(id) {
            Swal.fire({
                title: 'Delete User',
                text: 'Are you sure you want to delete this user? They can be restored later.',
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
        
        // Export users
        function exportUsers() {
            window.location.href = '/admin/users/export.php';
        }
        
        // Toggle user status (for inline toggle)
        function toggleStatus(id, currentStatus) {
            fetch('/admin/users/toggle-status.php?id=' + id, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to toggle status', 'error');
                }
            });
        }
        
        // Press Enter in search field
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
</body>
</html>