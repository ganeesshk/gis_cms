<?php
// admin/media/index.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\MediaController;
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
$controller = new MediaController($auth, $csrf, $config);
$result = $controller->index();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Lightbox2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
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
        
        .view-options {
            display: flex;
            gap: 5px;
        }
        
        .view-btn {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            background: white;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-btn:hover,
        .view-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .btn-upload {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        /* Media grid */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .media-grid.list-view {
            grid-template-columns: 1fr;
        }
        
        .media-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .media-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .media-item.selected {
            outline: 3px solid var(--primary-color);
        }
        
        .media-preview {
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-preview i {
            font-size: 48px;
            color: var(--primary-color);
            opacity: 0.5;
        }
        
        .media-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.5);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .media-info {
            padding: 12px;
        }
        
        .media-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-meta {
            font-size: 11px;
            color: #999;
            display: flex;
            justify-content: space-between;
        }
        
        /* List view */
        .list-view .media-item {
            display: flex;
            align-items: center;
        }
        
        .list-view .media-preview {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
        }
        
        .list-view .media-preview i {
            font-size: 24px;
        }
        
        .list-view .media-info {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .list-view .media-name {
            margin-bottom: 0;
            width: 30%;
        }
        
        .list-view .media-meta {
            width: 40%;
            display: flex;
            gap: 20px;
        }
        
        /* Selection toolbar */
        .selection-toolbar {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .selection-toolbar.show {
            display: flex;
        }
        
        .selection-info {
            font-weight: 500;
            color: #333;
        }
        
        .selection-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Folder tree */
        .folder-tree {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .folder-tree h4 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .folder-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .folder-item {
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .folder-item:hover {
            background: #f8f9fa;
        }
        
        .folder-item.active {
            background: #e3f2fd;
            color: var(--primary-color);
        }
        
        .folder-item i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .folder-children {
            list-style: none;
            padding-left: 25px;
            margin: 5px 0;
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
        
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .thumbnails-list {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .thumbnail-item {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .thumbnail-item:hover,
        .thumbnail-item.active {
            border-color: var(--primary-color);
        }
        
        .thumbnail-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            width: 120px;
            font-weight: 500;
            color: #666;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        /* Pagination */
        .pagination {
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
                <li class="active">
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
                <h1>Media Library</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <span class="breadcrumb-item active">Media</span>
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
                    <i class="fas fa-file"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_files']; ?></h3>
                    <p>Total Files</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['images']; ?></h3>
                    <p>Images</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['videos']; ?></h3>
                    <p>Videos</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['documents']; ?></h3>
                    <p>Documents</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_size_formatted']; ?></h3>
                    <p>Total Size</p>
                </div>
            </div>
        </div>
        
        <!-- Folder Tree -->
        <div class="folder-tree">
            <h4><i class="fas fa-folder-open"></i> Folders</h4>
            <ul class="folder-list">
                <li class="folder-item <?php echo $currentFolder === '/' ? 'active' : ''; ?>" onclick="filterByFolder('/')">
                    <i class="fas fa-folder"></i>
                    All Files
                </li>
                <?php echo renderFolderTree($folders, $currentFolder); ?>
                <li class="folder-item" onclick="createFolder()">
                    <i class="fas fa-plus-circle"></i>
                    New Folder
                </li>
            </ul>
        </div>
        
        <!-- Toolbar -->
        <div class="toolbar">
            <div class="filters">
                <select class="form-select" id="typeFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $currentType === 'all' ? 'selected' : ''; ?>>All Files</option>
                    <option value="images" <?php echo $currentType === 'images' ? 'selected' : ''; ?>>Images</option>
                    <option value="videos" <?php echo $currentType === 'videos' ? 'selected' : ''; ?>>Videos</option>
                    <option value="documents" <?php echo $currentType === 'documents' ? 'selected' : ''; ?>>Documents</option>
                </select>
                
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           id="searchInput" 
                           placeholder="Search files..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="button" onclick="applyFilters()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="view-options">
                    <button class="view-btn <?php echo !isset($_COOKIE['media_view']) || $_COOKIE['media_view'] === 'grid' ? 'active' : ''; ?>" onclick="setView('grid')">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="view-btn <?php echo isset($_COOKIE['media_view']) && $_COOKIE['media_view'] === 'list' ? 'active' : ''; ?>" onclick="setView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
                
                <a href="/admin/media/upload.php" class="btn-upload">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload Files
                </a>
            </div>
        </div>
        
        <!-- Selection Toolbar -->
        <div class="selection-toolbar" id="selectionToolbar">
            <div class="selection-info">
                <span id="selectedCount">0</span> items selected
            </div>
            <div class="selection-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                    <i class="fas fa-check-double"></i>
                    Select All
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i>
                    Delete Selected
                </button>
            </div>
        </div>
        
        <!-- Media Grid -->
        <?php if (empty($files)): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h3 class="h5 text-muted">No files found</h3>
                <p class="text-muted">Upload your first file to get started.</p>
                <a href="/admin/media/upload.php" class="btn btn-primary mt-3">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload Files
                </a>
            </div>
        <?php else: ?>
            <div class="media-grid" id="mediaGrid">
                <?php foreach ($files as $file): ?>
                    <div class="media-item" data-id="<?php echo $file->id; ?>" onclick="toggleSelect(this, <?php echo $file->id; ?>)">
                        <div class="media-preview">
                            <?php if ($file->isImage()): ?>
                                <img src="<?php echo $file->getThumbnail('medium'); ?>" 
                                     alt="<?php echo htmlspecialchars($file->alt_text ?: $file->original_name); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <i class="<?php echo $file->getIcon(); ?>"></i>
                            <?php endif; ?>
                            
                            <div class="media-type-badge">
                                <?php if ($file->isImage()): ?>
                                    <i class="fas fa-image"></i>
                                <?php elseif ($file->isVideo()): ?>
                                    <i class="fas fa-video"></i>
                                <?php elseif ($file->isDocument()): ?>
                                    <i class="fas fa-file-pdf"></i>
                                <?php else: ?>
                                    <i class="fas fa-file"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="media-info">
                            <div class="media-name" title="<?php echo htmlspecialchars($file->original_name); ?>">
                                <?php echo htmlspecialchars($file->original_name); ?>
                            </div>
                            <div class="media-meta">
                                <span><?php echo $file->getFormattedSize(); ?></span>
                                <span><?php echo date('M j, Y', strtotime($file->created_at)); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination">
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&type=<?php echo urlencode($currentType); ?>&folder=<?php echo urlencode($currentFolder); ?>&search=<?php echo urlencode($search); ?>">
                            Previous
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i >= $currentPage - 2 && $i <= $currentPage + 2): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo urlencode($currentType); ?>&folder=<?php echo urlencode($currentFolder); ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&type=<?php echo urlencode($currentType); ?>&folder=<?php echo urlencode($currentFolder); ?>&search=<?php echo urlencode($search); ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <!-- Media Details Modal -->
    <div class="modal fade" id="mediaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Media Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="mediaModalBody">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading...</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="editMediaBtn" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Details
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('media_actions'); ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize variables
        let selectedItems = new Set();
        const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
        
        // Toggle selection
        function toggleSelect(element, id) {
            if (element.classList.contains('selected')) {
                element.classList.remove('selected');
                selectedItems.delete(id);
            } else {
                element.classList.add('selected');
                selectedItems.add(id);
            }
            
            updateSelectionToolbar();
        }
        
        // Select all
        function selectAll() {
            document.querySelectorAll('.media-item').forEach(item => {
                item.classList.add('selected');
                selectedItems.add(parseInt(item.dataset.id));
            });
            updateSelectionToolbar();
        }
        
        // Clear selection
        function clearSelection() {
            document.querySelectorAll('.media-item').forEach(item => {
                item.classList.remove('selected');
            });
            selectedItems.clear();
            updateSelectionToolbar();
        }
        
        // Update selection toolbar
        function updateSelectionToolbar() {
            const toolbar = document.getElementById('selectionToolbar');
            const countSpan = document.getElementById('selectedCount');
            const count = selectedItems.size;
            
            if (count > 0) {
                toolbar.classList.add('show');
                countSpan.textContent = count;
            } else {
                toolbar.classList.remove('show');
            }
        }
        
        // Delete selected
        function deleteSelected() {
            if (selectedItems.size === 0) return;
            
            Swal.fire({
                title: 'Delete Files',
                html: `Are you sure you want to delete <strong>${selectedItems.size}</strong> file(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/media/delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        },
                        body: JSON.stringify({ ids: Array.from(selectedItems) })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            Swal.fire('Error', data.error || 'Failed to delete files', 'error');
                        }
                    });
                }
            });
        }
        
        // Show media details
        function showMediaDetails(id) {
            fetch('/admin/media/get-details.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    
                    if (data.type.startsWith('image/')) {
                        html += `
                            <div class="text-center mb-4">
                                <a href="${data.url}" data-lightbox="media-${data.id}">
                                    <img src="${data.thumbnail}" alt="${data.name}" class="preview-image">
                                </a>
                                
                                <div class="thumbnails-list justify-content-center">
                                    ${data.thumbnails.map(t => `
                                        <div class="thumbnail-item" onclick="viewThumbnail('${t.url}')">
                                            <img src="${t.url}" alt="${t.size}">
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="text-center mb-4">
                                <i class="${data.icon} fa-5x text-primary"></i>
                            </div>
                        `;
                    }
                    
                    html += `
                        <div class="info-row">
                            <div class="info-label">Filename:</div>
                            <div class="info-value">${data.name}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Title:</div>
                            <div class="info-value">${data.title || '—'}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Alt Text:</div>
                            <div class="info-value">${data.alt_text || '—'}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Caption:</div>
                            <div class="info-value">${data.caption || '—'}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">File Type:</div>
                            <div class="info-value">${data.type}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">File Size:</div>
                            <div class="info-value">${data.size}</div>
                        </div>
                        ${data.dimensions ? `
                            <div class="info-row">
                                <div class="info-label">Dimensions:</div>
                                <div class="info-value">${data.dimensions}</div>
                            </div>
                        ` : ''}
                        <div class="info-row">
                            <div class="info-label">Folder:</div>
                            <div class="info-value">${data.folder}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Uploaded By:</div>
                            <div class="info-value">${data.uploaded_by}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Uploaded At:</div>
                            <div class="info-value">${data.uploaded_at}</div>
                        </div>
                    `;
                    
                    document.getElementById('mediaModalBody').innerHTML = html;
                    document.getElementById('editMediaBtn').href = '/admin/media/edit.php?id=' + data.id;
                    
                    mediaModal.show();
                });
        }
        
        // View thumbnail
        function viewThumbnail(url) {
            window.open(url, '_blank');
        }
        
        // Set view mode
        function setView(mode) {
            document.cookie = 'media_view=' + mode + '; path=/';
            location.reload();
        }
        
        // Apply filters
        function applyFilters() {
            const type = document.getElementById('typeFilter').value;
            const search = document.getElementById('searchInput').value;
            const folder = getCurrentFolder();
            
            window.location.href = '?type=' + encodeURIComponent(type) + '&folder=' + encodeURIComponent(folder) + '&search=' + encodeURIComponent(search);
        }
        
        // Filter by folder
        function filterByFolder(folder) {
            const type = document.getElementById('typeFilter').value;
            const search = document.getElementById('searchInput').value;
            
            window.location.href = '?type=' + encodeURIComponent(type) + '&folder=' + encodeURIComponent(folder) + '&search=' + encodeURIComponent(search);
        }
        
        // Get current folder
        function getCurrentFolder() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('folder') || '/';
        }
        
        // Create folder
        function createFolder() {
            Swal.fire({
                title: 'Create New Folder',
                input: 'text',
                inputLabel: 'Folder Name',
                inputPlaceholder: 'Enter folder name',
                showCancelButton: true,
                confirmButtonText: 'Create',
                preConfirm: (name) => {
                    if (!name) {
                        Swal.showValidationMessage('Folder name is required');
                        return false;
                    }
                    
                    return fetch('/admin/media/create-folder.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        },
                        body: JSON.stringify({
                            path: getCurrentFolder(),
                            name: name
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to create folder');
                        }
                        return data;
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }
        
        // Double-click to show details
        document.querySelectorAll('.media-item').forEach(item => {
            item.addEventListener('dblclick', function(e) {
                e.stopPropagation();
                showMediaDetails(this.dataset.id);
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+A to select all
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                selectAll();
            }
            
            // Escape to clear selection
            if (e.key === 'Escape') {
                clearSelection();
            }
            
            // Delete key to delete selected
            if (e.key === 'Delete' && selectedItems.size > 0) {
                e.preventDefault();
                deleteSelected();
            }
        });
    </script>
</body>
</html>

<?php
// Helper function to render folder tree
function renderFolderTree($folders, $currentFolder, $parent = '/') {
    $html = '';
    
    foreach ($folders as $folder) {
        if ($folder === '/' || $folder === $parent) continue;
        
        if (strpos($folder, $parent) === 0 && $folder !== $parent) {
            $remaining = substr($folder, strlen($parent));
            $parts = explode('/', trim($remaining, '/'));
            $name = $parts[0];
            
            if (count($parts) === 1) {
                $html .= '<li class="folder-item ' . ($currentFolder === $folder ? 'active' : '') . '" onclick="filterByFolder(\'' . $folder . '\')">';
                $html .= '<i class="fas fa-folder"></i>';
                $html .= $name;
                $html .= '</li>';
                
                // Render children
                $childHtml = renderFolderTree($folders, $currentFolder, $folder);
                if ($childHtml) {
                    $html .= '<ul class="folder-children">' . $childHtml . '</ul>';
                }
            }
        }
    }
    
    return $html;
}
?>