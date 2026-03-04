<?php
// admin/audit/index.php

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

// Initialize controller and get data
$controller = new AuditController($auth, $csrf, $config);
$result = $controller->index();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DateRangePicker -->
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css" rel="stylesheet">
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
        
        /* Filter panel */
        .filter-panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-panel h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            font-size: 12px;
            font-weight: 500;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        
        .filter-group .form-select,
        .filter-group .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 13px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-filter {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-filter:hover {
            background: #5a6fd8;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-reset:hover {
            background: #5a6268;
        }
        
        .btn-export {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            background: #218838;
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
            font-size: 13px;
        }
        
        .table td {
            vertical-align: middle;
            font-size: 13px;
        }
        
        .result-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
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
        
        .action-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #e2e3e5;
            color: #383d41;
        }
        
        .entity-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #cce5ff;
            color: #004085;
        }
        
        .user-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-avatar-small {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .view-details {
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-details:hover {
            color: #5a6fd8;
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
            max-height: 70vh;
            overflow-y: auto;
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
        
        .json-view {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
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
            
            .filter-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-actions {
                width: 100%;
                justify-content: stretch;
            }
            
            .filter-actions button {
                flex: 1;
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
                <h1>Audit Logs</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <span class="breadcrumb-item active">Audit Logs</span>
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
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($summary['total']); ?></h3>
                    <p>Total Events</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($summary['success']); ?></h3>
                    <p>Successful</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($summary['failure']); ?></h3>
                    <p>Failed</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($summary['warning']); ?></h3>
                    <p>Warnings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($summary['today']); ?></h3>
                    <p>Today</p>
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
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
        
        <!-- Filter Panel -->
        <div class="filter-panel">
            <h3>Filter Logs</h3>
            
            <form method="GET" action="" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>User</label>
                        <select class="form-select" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u->id; ?>" <?php echo $filters['user_id'] == $u->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u->username); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Action</label>
                        <select class="form-select" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?>>
                                    <?php echo $action; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Entity Type</label>
                        <select class="form-select" name="entity_type">
                            <option value="">All Types</option>
                            <?php foreach ($entityTypes as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $filters['entity_type'] === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Result</label>
                        <select class="form-select" name="result">
                            <option value="">All Results</option>
                            <?php foreach ($results as $result): ?>
                                <option value="<?php echo $result; ?>" <?php echo $filters['result'] === $result ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($result); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row mt-3">
                    <div class="filter-group">
                        <label>Date Range</label>
                        <input type="text" class="form-control" id="dateRange" name="date_range" 
                               value="<?php echo ($filters['date_from'] && $filters['date_to']) ? $filters['date_from'] . ' - ' . $filters['date_to'] : ''; ?>"
                               placeholder="Select date range">
                        <input type="hidden" name="date_from" id="date_from" value="<?php echo $filters['date_from']; ?>">
                        <input type="hidden" name="date_to" id="date_to" value="<?php echo $filters['date_to']; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                               placeholder="Search in logs...">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                        
                        <a href="/admin/audit/" class="btn-reset">
                            <i class="fas fa-undo"></i>
                            Reset
                        </a>
                        
                        <?php if ($user->isSuperAdmin()): ?>
                            <button type="button" class="btn-export" onclick="exportLogs()">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Logs Table -->
        <div class="table-container">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                    <h3 class="h5 text-muted">No logs found</h3>
                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="logsTable">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Result</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div><?php echo $log->created_at->format('Y-m-d H:i:s'); ?></div>
                                        <small class="text-muted"><?php echo timeAgo($log->created_at); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($log->user_id): ?>
                                            <div class="user-cell">
                                                <?php 
                                                $logUser = $log->user();
                                                if ($logUser): 
                                                ?>
                                                    <img src="<?php echo $logUser->getAvatarUrl(); ?>" alt="" class="user-avatar-small">
                                                    <span><?php echo htmlspecialchars($log->username); ?></span>
                                                <?php else: ?>
                                                    <i class="fas fa-user-circle text-muted"></i>
                                                    <span><?php echo htmlspecialchars($log->username ?: 'System'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log->ip_address); ?></code>
                                    </td>
                                    <td>
                                        <span class="action-badge"><?php echo htmlspecialchars($log->action); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($log->entity_type): ?>
                                            <span class="entity-badge"><?php echo ucfirst($log->entity_type); ?></span>
                                            <?php if ($log->entity_label): ?>
                                                <div class="small text-muted mt-1">
                                                    <?php echo htmlspecialchars($log->entity_label); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="result-badge result-<?php echo $log->result; ?>">
                                            <?php echo ucfirst($log->result); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-eye view-details" onclick="viewLog(<?php echo $log->id; ?>)" title="View Details"></i>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($currentPage - 1, $filters); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i >= $currentPage - 2 && $i <= $currentPage + 2): ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPaginationUrl($i, $filters); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo buildPaginationUrl($currentPage + 1, $filters); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Log Details Modal -->
    <div class="modal fade" id="logModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="logModalBody">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading...</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <p>Choose export format:</p>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="#" class="btn btn-success" onclick="exportFormat('csv')">
                            <i class="fas fa-file-csv"></i>
                            CSV
                        </a>
                        <a href="#" class="btn btn-primary" onclick="exportFormat('json')">
                            <i class="fas fa-file-code"></i>
                            JSON
                        </a>
                    </div>
                    
                    <hr>
                    
                    <?php if ($user->isSuperAdmin()): ?>
                        <div class="mt-3">
                            <h6>Cleanup Old Logs</h6>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cleanupDays" value="90" min="1" max="365">
                                <button class="btn btn-warning" onclick="cleanupLogs()">
                                    <i class="fas fa-trash"></i>
                                    Delete Older Than
                                </button>
                            </div>
                            <small class="text-muted">This action cannot be undone.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('audit_actions'); ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#logsTable').DataTable({
                paging: false,
                searching: false,
                ordering: true,
                info: false
            });
        });
        
        // Initialize Date Range Picker
        $('#dateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        
        $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
            $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
        });
        
        $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $('#date_from').val('');
            $('#date_to').val('');
        });
        
        // View log details
        function viewLog(id) {
            const modal = new bootstrap.Modal(document.getElementById('logModal'));
            
            fetch('/admin/audit/get-log.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    let html = `
                        <div class="detail-row">
                            <div class="detail-label">ID:</div>
                            <div class="detail-value">#${data.id}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Timestamp:</div>
                            <div class="detail-value">${data.created_at}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">User:</div>
                            <div class="detail-value">${data.username || 'System'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">IP Address:</div>
                            <div class="detail-value">${data.ip_address}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">User Agent:</div>
                            <div class="detail-value">${data.user_agent || '—'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Action:</div>
                            <div class="detail-value">${data.action}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Entity:</div>
                            <div class="detail-value">${data.entity_type ? data.entity_type + ' #' + data.entity_id : '—'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Entity Label:</div>
                            <div class="detail-value">${data.entity_label || '—'}</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Result:</div>
                            <div class="detail-value">
                                <span class="result-badge result-${data.result}">${data.result}</span>
                            </div>
                        </div>
                    `;
                    
                    if (data.error_message) {
                        html += `
                            <div class="detail-row">
                                <div class="detail-label">Error:</div>
                                <div class="detail-value text-danger">${data.error_message}</div>
                            </div>
                        `;
                    }
                    
                    if (data.old_values) {
                        html += `
                            <div class="detail-row">
                                <div class="detail-label">Old Values:</div>
                                <div class="detail-value">
                                    <pre class="json-view">${JSON.stringify(data.old_values, null, 2)}</pre>
                                </div>
                            </div>
                        `;
                    }
                    
                    if (data.new_values) {
                        html += `
                            <div class="detail-row">
                                <div class="detail-label">New Values:</div>
                                <div class="detail-value">
                                    <pre class="json-view">${JSON.stringify(data.new_values, null, 2)}</pre>
                                </div>
                            </div>
                        `;
                    }
                    
                    document.getElementById('logModalBody').innerHTML = html;
                    modal.show();
                });
        }
        
        // Export logs
        function exportLogs() {
            const modal = new bootstrap.Modal(document.getElementById('exportModal'));
            modal.show();
        }
        
        // Export in specific format
        function exportFormat(format) {
            const form = document.getElementById('filterForm');
            const params = new URLSearchParams(new FormData(form)).toString();
            
            window.location.href = '/admin/audit/export.php?format=' + format + '&token=' + document.getElementById('csrfToken').value + '&' + params;
        }
        
        // Cleanup old logs
        function cleanupLogs() {
            const days = document.getElementById('cleanupDays').value;
            
            Swal.fire({
                title: 'Cleanup Logs',
                html: `Are you sure you want to delete all logs older than <strong>${days} days</strong>?<br><br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/audit/cleanup.php?days=' + days + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        // Auto-refresh stats (optional)
        function refreshStats() {
            fetch('/admin/audit/stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update stats display if needed
                });
        }
        
        // Refresh stats every 60 seconds
        // setInterval(refreshStats, 60000);
    </script>
</body>
</html>

<?php
// Helper functions
function timeAgo($datetime) {
    $now = new DateTime();
    $diff = $now->diff($datetime);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

function buildPaginationUrl($page, $filters) {
    $params = ['page' => $page];
    
    if (!empty($filters['user_id'])) $params['user_id'] = $filters['user_id'];
    if (!empty($filters['action'])) $params['action'] = $filters['action'];
    if (!empty($filters['entity_type'])) $params['entity_type'] = $filters['entity_type'];
    if (!empty($filters['entity_id'])) $params['entity_id'] = $filters['entity_id'];
    if (!empty($filters['result'])) $params['result'] = $filters['result'];
    if (!empty($filters['search'])) $params['search'] = $filters['search'];
    if (!empty($filters['date_from'])) $params['date_from'] = $filters['date_from'];
    if (!empty($filters['date_to'])) $params['date_to'] = $filters['date_to'];
    
    return '?' . http_build_query($params);
}
?>