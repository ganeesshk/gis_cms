<?php
// admin/pages/index.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\PageController;
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
    header('Location: ../../admin/login.php');
    exit;
}

// Initialize controller and get data
$controller = new PageController($auth, $csrf, $config);
$result = $controller->index();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pages - CMS Admin</title>
    
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
        
        /* Sidebar styles (same as dashboard) */
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
        
        /* Content card */
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        /* Filters */
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        
        /* Table */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #666;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-draft {
            background: #e1e1e1;
            color: #666;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unpublished {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-scheduled {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-trashed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        /* Pagination */
        .pagination-info {
            color: #666;
            font-size: 14px;
        }
        
        .pagination {
            margin-bottom: 0;
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
            
            .filters-bar {
                flex-direction: column;
                gap: 15px;
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
                    <a href="../dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="active">
                    <a href="../../admin/pages/">
                        <i class="fas fa-file-alt"></i>
                        Pages
                    </a>
                </li>
                <li>
                    <a href="../../admin/menus/">
                        <i class="fas fa-bars"></i>
                        Menus
                    </a>
                </li>
                <li>
                    <a href="../../admin/home/">
                        <i class="fas fa-home"></i>
                        Home Page
                    </a>
                </li>
                <li>
                    <a href="../../admin/galleries/photo/">
                        <i class="fas fa-images"></i>
                        Photo Galleries
                    </a>
                </li>
                <li>
                    <a href="../../admin/galleries/video/">
                        <i class="fas fa-video"></i>
                        Video Galleries
                    </a>
                </li>
                <li>
                    <a href="../../admin/media/">
                        <i class="fas fa-folder-open"></i>
                        Media Library
                    </a>
                </li>
                <li>
                    <a href="../../admin/users/">
                        <i class="fas fa-users"></i>
                        User Management
                    </a>
                </li>
                <li>
                    <a href="../../admin/settings/">
                        <i class="fas fa-cog"></i>
                        Settings
                    </a>
                </li>
                <li>
                    <a href="../../admin/audit/">
                        <i class="fas fa-history"></i>
                        Audit Logs
                    </a>
                </li>
                <li>
                    <a href="../../admin/logout.php">
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
                <h1>Pages</h1>
                <div class="breadcrumb">
                    <a href="../../admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <span class="breadcrumb-item active">Pages</span>
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
                        <li><a class="dropdown-item" href="../../admin/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="content-card">
            <div class="filters-bar">
                <div class="filters">
                    <select class="form-select" id="statusFilter" onchange="applyFilters()">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $status === $value ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="searchInput" 
                               placeholder="Search pages..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="applyFilters()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <a href="../../admin/pages/create.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    New Page
                </a>
            </div>
            
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
                <table class="table table-hover" id="pagesTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Author</th>
                            <th>Last Modified</th>
                            <th>Published</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No pages found.</p>
                                    <a href="../../admin/pages/create.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i>
                                        Create Your First Page
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($page->title); ?></div>
                                        <small class="text-muted">/<?php echo htmlspecialchars($page->slug); ?></small>
                                    </td>
                                    <td>
                                        <?php echo $page->getStatusBadge(); ?>
                                        <?php if ($page->status === 'scheduled' && $page->scheduled_at): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y H:i', strtotime($page->scheduled_at)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($page->author): ?>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($page->author->getAvatarUrl()); ?>" 
                                                     alt="" class="rounded-circle me-2" width="24" height="24">
                                                <?php echo htmlspecialchars($page->author->username); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Unknown</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($page->updated_at)); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($page->updated_at)); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($page->published_at): ?>
                                            <div><?php echo date('M j, Y', strtotime($page->published_at)); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($page->published_at)); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($page->status !== 'trashed'): ?>
                                                <a href="../../admin/pages/edit.php?id=<?php echo $page->id; ?>" 
                                                   class="btn btn-sm btn-outline-primary btn-icon" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <?php if ($page->status === 'published'): ?>
                                                    <a href="<?php echo $page->getPublicUrl(); ?>" 
                                                       class="btn btn-sm btn-outline-success btn-icon" 
                                                       target="_blank"
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo $page->getPreviewUrl(); ?>" 
                                                       class="btn btn-sm btn-outline-info btn-icon" 
                                                       target="_blank"
                                                       title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($page->status !== 'published' && $user->hasPermission('pages.publish')): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-success btn-icon"
                                                            onclick="publishPage(<?php echo $page->id; ?>, '<?php echo htmlspecialchars($page->title); ?>')"
                                                            title="Publish">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($page->status === 'published' && $user->hasPermission('pages.publish')): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-warning btn-icon"
                                                            onclick="unpublishPage(<?php echo $page->id; ?>, '<?php echo htmlspecialchars($page->title); ?>')"
                                                            title="Unpublish">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-icon"
                                                        onclick="deletePage(<?php echo $page->id; ?>, '<?php echo htmlspecialchars($page->title); ?>')"
                                                        title="Move to Trash">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success btn-icon"
                                                        onclick="restorePage(<?php echo $page->id; ?>, '<?php echo htmlspecialchars($page->title); ?>')"
                                                        title="Restore">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                                
                                                <?php if ($user->isSuperAdmin()): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger btn-icon"
                                                            onclick="forceDeletePage(<?php echo $page->id; ?>, '<?php echo htmlspecialchars($page->title); ?>')"
                                                            title="Delete Permanently">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
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
            <?php if ($totalPagesCount > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="pagination-info">
                        Showing page <?php echo $currentPage; ?> of <?php echo $totalPagesCount; ?>
                        (<?php echo $totalPages; ?> total pages)
                    </div>
                    
                    <nav>
                        <ul class="pagination">
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">
                                    Previous
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPagesCount; $i++): ?>
                                <?php if ($i >= $currentPage - 2 && $i <= $currentPage + 2): ?>
                                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $currentPage >= $totalPagesCount ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('page_actions'); ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;
            
            window.location.href = '?status=' + encodeURIComponent(status) + '&search=' + encodeURIComponent(search);
        }
        
        function publishPage(id, title) {
            Swal.fire({
                title: 'Publish Page',
                html: `Are you sure you want to publish "<strong>${title}</strong>"?<br>
                       The page will be visible to the public.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, publish it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../admin/pages/publish.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        function unpublishPage(id, title) {
            Swal.fire({
                title: 'Unpublish Page',
                html: `Are you sure you want to unpublish "<strong>${title}</strong>"?<br>
                       The page will be hidden from the public.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, unpublish it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../admin/pages/unpublish.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        function deletePage(id, title) {
            Swal.fire({
                title: 'Move to Trash',
                html: `Are you sure you want to move "<strong>${title}</strong>" to trash?<br>
                       You can restore it later from the trash.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, move to trash!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../admin/pages/delete.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        function restorePage(id, title) {
            Swal.fire({
                title: 'Restore Page',
                html: `Are you sure you want to restore "<strong>${title}</strong>"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, restore it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../admin/pages/restore.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        function forceDeletePage(id, title) {
            Swal.fire({
                title: 'Permanently Delete',
                html: `Are you sure you want to permanently delete "<strong>${title}</strong>"?<br>
                       <span class="text-danger">This action cannot be undone!</span>`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete permanently!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../admin/pages/force-delete.php?id=' + id + '&token=' + document.getElementById('csrfToken').value;
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