<?php
// admin/galleries/photo/edit.php

require_once __DIR__ . '/../../../app/bootstrap.php';

use App\Controllers\PhotoGalleryController;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../../../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Require authentication
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: /admin/login.php');
    exit;
}

// Get gallery ID
$id = (int)($_GET['id'] ?? 0);

// Initialize controller
$controller = new PhotoGalleryController($auth, $csrf, $config);
$result = $controller->edit($id);
extract($result['data']);

// Restore form data from session if validation failed
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Gallery: <?php echo htmlspecialchars($gallery->name); ?> - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
        
        .page-title .badge {
            margin-left: 10px;
            font-size: 12px;
            padding: 5px 10px;
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
        
        /* Gallery header */
        .gallery-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
        }
        
        .gallery-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        
        .gallery-stat {
            text-align: center;
        }
        
        .gallery-stat-value {
            font-size: 28px;
            font-weight: 600;
            line-height: 1;
        }
        
        .gallery-stat-label {
            font-size: 13px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        /* Layout */
        .edit-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 25px;
        }
        
        /* Photos grid */
        .photos-grid {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .grid-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .grid-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .photo-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            min-height: 200px;
        }
        
        .photo-item {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            cursor: move;
            transition: all 0.3s;
            position: relative;
        }
        
        .photo-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.2);
        }
        
        .photo-item.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }
        
        .photo-item.inactive {
            opacity: 0.6;
            filter: grayscale(0.5);
        }
        
        .photo-preview {
            height: 120px;
            position: relative;
            overflow: hidden;
        }
        
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.5);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            backdrop-filter: blur(2px);
        }
        
        .photo-badge.cover {
            background: var(--primary-color);
        }
        
        .photo-info {
            padding: 10px;
        }
        
        .photo-title {
            font-size: 13px;
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .photo-meta {
            font-size: 11px;
            color: #999;
            display: flex;
            justify-content: space-between;
        }
        
        .photo-actions {
            position: absolute;
            top: 5px;
            left: 5px;
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .photo-item:hover .photo-actions {
            opacity: 1;
        }
        
        .photo-action {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: none;
            background: rgba(255,255,255,0.9);
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(2px);
        }
        
        .photo-action:hover {
            background: white;
        }
        
        .photo-action.edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .photo-action.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        .photo-action.cover:hover {
            background: #28a745;
            color: white;
        }
        
        .empty-photos {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
        }
        
        .empty-photos i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* Settings panel */
        .settings-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            position: sticky;
            top: 30px;
        }
        
        .settings-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .settings-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .settings-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            display: block;
            font-size: 13px;
        }
        
        .form-control, .form-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 12px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        
        .form-check {
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 10px;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            color: white;
            padding: 10px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-box i {
            color: var(--primary-color);
            margin-right: 8px;
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
        
        .media-browser {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }
        
        .media-item {
            position: relative;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .media-item:hover {
            border-color: var(--primary-color);
        }
        
        .media-item.selected {
            border-color: var(--primary-color);
            background: #e7f3ff;
        }
        
        .media-preview {
            height: 80px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .media-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-info {
            padding: 5px;
            font-size: 11px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .media-check {
            position: absolute;
            top: 5px;
            right: 5px;
            display: none;
        }
        
        .media-item.selected .media-check {
            display: block;
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
            
            .edit-layout {
                grid-template-columns: 1fr;
            }
            
            .settings-panel {
                position: static;
            }
            
            .gallery-stats {
                flex-wrap: wrap;
                gap: 15px;
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
                <li class="active">
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
                <h1>
                    <?php echo htmlspecialchars($gallery->name); ?>
                    <span class="badge <?php echo $gallery->is_public ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo $gallery->is_public ? 'Public' : 'Private'; ?>
                    </span>
                </h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/galleries/photo/" class="breadcrumb-item">Photo Galleries</a>
                    <span class="breadcrumb-item active"><?php echo htmlspecialchars($gallery->name); ?></span>
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
        
        <!-- Gallery Header -->
        <div class="gallery-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-2">Gallery Statistics</h2>
                    <p class="mb-0 opacity-75"><?php echo htmlspecialchars($gallery->description ?: 'No description provided.'); ?></p>
                </div>
                
                <?php if ($gallery->is_public): ?>
                    <a href="/galleries/photo/<?php echo $gallery->slug; ?>" class="btn btn-light" target="_blank">
                        <i class="fas fa-eye"></i> View Gallery
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="gallery-stats">
                <div class="gallery-stat">
                    <div class="gallery-stat-value"><?php echo count($photos); ?></div>
                    <div class="gallery-stat-label">Total Photos</div>
                </div>
                <div class="gallery-stat">
                    <div class="gallery-stat-value"><?php echo count(array_filter($photos, function($p) { return $p->is_visible; })); ?></div>
                    <div class="gallery-stat-label">Visible</div>
                </div>
                <div class="gallery-stat">
                    <div class="gallery-stat-value"><?php echo $gallery->created_at->format('M j, Y'); ?></div>
                    <div class="gallery-stat-label">Created</div>
                </div>
                <div class="gallery-stat">
                    <div class="gallery-stat-value"><?php echo $gallery->updated_at->format('M j, Y'); ?></div>
                    <div class="gallery-stat-label">Last Updated</div>
                </div>
            </div>
        </div>
        
        <!-- Edit Layout -->
        <div class="edit-layout">
            <!-- Photos Grid -->
            <div class="photos-grid">
                <div class="grid-header">
                    <h2>Gallery Photos</h2>
                    <button class="btn btn-sm btn-primary" onclick="openMediaBrowser()">
                        <i class="fas fa-plus-circle"></i>
                        Add Photos
                    </button>
                </div>
                
                <?php if (empty($photos)): ?>
                    <div class="empty-photos">
                        <i class="fas fa-images"></i>
                        <h3>No Photos Yet</h3>
                        <p>Click the "Add Photos" button to start adding images to this gallery.</p>
                    </div>
                <?php else: ?>
                    <div class="photo-items" id="photoGrid">
                        <?php foreach ($photos as $photo): ?>
                            <div class="photo-item <?php echo !$photo->is_visible ? 'inactive' : ''; ?>" 
                                 data-id="<?php echo $photo->id; ?>"
                                 data-media-id="<?php echo $photo->media_id; ?>">
                                <div class="photo-preview">
                                    <img src="<?php echo $photo->thumbnail; ?>" 
                                         alt="<?php echo htmlspecialchars($photo->alt_text ?: $photo->title); ?>">
                                    
                                    <?php if ($gallery->cover_media_id == $photo->media_id): ?>
                                        <div class="photo-badge cover" title="Cover Image">
                                            <i class="fas fa-star"></i>
                                        </div>
                                    <?php elseif (!$photo->is_visible): ?>
                                        <div class="photo-badge" title="Hidden">
                                            <i class="fas fa-eye-slash"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="photo-actions">
                                        <button class="photo-action edit" onclick="editPhoto(<?php echo $photo->id; ?>)" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="photo-action cover" onclick="setAsCover(<?php echo $photo->id; ?>)" title="Set as Cover">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <button class="photo-action delete" onclick="deletePhoto(<?php echo $photo->id; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="photo-info">
                                    <div class="photo-title"><?php echo htmlspecialchars($photo->getTitle()); ?></div>
                                    <div class="photo-meta">
                                        <span><i class="fas fa-sort"></i> <?php echo $photo->sort_order + 1; ?></span>
                                        <span><i class="fas fa-<?php echo $photo->is_visible ? 'eye' : 'eye-slash'; ?>"></i></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Settings Panel -->
            <div class="settings-panel">
                <!-- Gallery Settings -->
                <div class="settings-section">
                    <h3>Gallery Settings</h3>
                    
                    <form method="POST" action="/admin/galleries/photo/update.php?id=<?php echo $gallery->id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Gallery Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($formData['name'] ?? $gallery->name); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" 
                                      name="description" 
                                      rows="3"><?php echo htmlspecialchars($formData['description'] ?? $gallery->description); ?></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="is_public" 
                                   id="isPublic" 
                                   value="1"
                                   <?php echo (isset($formData['is_public']) ? $formData['is_public'] : $gallery->is_public) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isPublic">
                                Public Gallery
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Update Settings
                        </button>
                    </form>
                </div>
                
                <!-- Gallery Actions -->
                <div class="settings-section">
                    <h3>Actions</h3>
                    
                    <button class="btn-outline-primary mb-2" onclick="duplicateGallery()">
                        <i class="fas fa-copy"></i>
                        Duplicate Gallery
                    </button>
                    
                    <button class="btn-danger" onclick="deleteGallery()">
                        <i class="fas fa-trash"></i>
                        Delete Gallery
                    </button>
                </div>
                
                <!-- Shortcuts -->
                <div class="settings-section">
                    <h3>Shortcuts</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-arrows-alt text-primary"></i> Drag photos to reorder</li>
                        <li class="mb-2"><i class="fas fa-star text-warning"></i> Star icon = cover image</li>
                        <li class="mb-2"><i class="fas fa-eye-slash text-muted"></i> Grayed out = hidden photos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Photos Modal -->
    <div class="modal fade" id="mediaModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Photos from Media Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="mediaSearch" placeholder="Search images...">
                    </div>
                    
                    <div id="mediaBrowser" class="media-browser">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                            <p class="mt-2">Loading images...</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addSelectedPhotos()">
                        <i class="fas fa-plus-circle"></i>
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Photo Modal -->
    <div class="modal fade" id="editPhotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <form id="editPhotoForm">
                        <div class="form-group mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="photoTitle">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Caption</label>
                            <textarea class="form-control" name="caption" id="photoCaption" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Alt Text</label>
                            <input type="text" class="form-control" name="alt_text" id="photoAltText">
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_visible" id="photoVisible" value="1" checked>
                            <label class="form-check-label" for="photoVisible">Visible in gallery</label>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePhotoEdit()">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('gallery_actions'); ?>">
    <input type="hidden" id="galleryId" value="<?php echo $gallery->id; ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize Sortable for drag-and-drop reordering
        const photoGrid = document.getElementById('photoGrid');
        if (photoGrid) {
            new Sortable(photoGrid, {
                animation: 150,
                handle: '.photo-item',
                onEnd: function() {
                    saveOrder();
                }
            });
        }
        
        // Modal instances
        const mediaModal = new bootstrap.Modal(document.getElementById('mediaModal'));
        const editPhotoModal = new bootstrap.Modal(document.getElementById('editPhotoModal'));
        let currentEditPhotoId = null;
        
        // Save photo order
        function saveOrder() {
            const order = [];
            document.querySelectorAll('.photo-item').forEach(item => {
                order.push(item.dataset.id);
            });
            
            fetch('/admin/galleries/photo/reorder.php?id=' + document.getElementById('galleryId').value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify({ order: order })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire('Error', data.error || 'Failed to save order', 'error');
                }
            });
        }
        
        // Open media browser
        function openMediaBrowser() {
            mediaModal.show();
            loadMediaBrowser();
        }
        
        // Load media browser
        function loadMediaBrowser() {
            const browser = document.getElementById('mediaBrowser');
            const galleryId = document.getElementById('galleryId').value;
            
            fetch('/admin/galleries/photo/media-browser.php?id=' + galleryId)
                .then(response => response.json())
                .then(data => {
                    if (data.html) {
                        browser.innerHTML = data.html;
                    } else {
                        browser.innerHTML = '<div class="text-center py-4">No images available</div>';
                    }
                });
        }
        
        // Add selected photos
        function addSelectedPhotos() {
            const selected = [];
            document.querySelectorAll('.media-item.selected').forEach(item => {
                selected.push(item.dataset.id);
            });
            
            if (selected.length === 0) {
                Swal.fire('Info', 'Please select at least one image', 'info');
                return;
            }
            
            fetch('/admin/galleries/photo/add-photos.php?id=' + document.getElementById('galleryId').value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify({ media_ids: selected })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mediaModal.hide();
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to add photos', 'error');
                }
            });
        }
        
        // Toggle media selection
        document.addEventListener('click', function(e) {
            const mediaItem = e.target.closest('.media-item');
            if (mediaItem) {
                mediaItem.classList.toggle('selected');
            }
        });
        
        // Edit photo
        function editPhoto(id) {
            currentEditPhotoId = id;
            
            // You would fetch photo data here
            // For now, we'll just show the modal with empty fields
            document.getElementById('photoTitle').value = '';
            document.getElementById('photoCaption').value = '';
            document.getElementById('photoAltText').value = '';
            document.getElementById('photoVisible').checked = true;
            
            editPhotoModal.show();
        }
        
        // Save photo edit
        function savePhotoEdit() {
            const data = {
                title: document.getElementById('photoTitle').value,
                caption: document.getElementById('photoCaption').value,
                alt_text: document.getElementById('photoAltText').value,
                is_visible: document.getElementById('photoVisible').checked
            };
            
            fetch('/admin/galleries/photo/update-photo.php?id=' + currentEditPhotoId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    editPhotoModal.hide();
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to update photo', 'error');
                }
            });
        }
        
        // Delete photo
        function deletePhoto(id) {
            Swal.fire({
                title: 'Delete Photo',
                text: 'Remove this photo from the gallery?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/galleries/photo/delete-photo.php?id=' + id, {
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
                            Swal.fire('Error', data.error || 'Failed to delete photo', 'error');
                        }
                    });
                }
            });
        }
        
        // Set as cover
        function setAsCover(photoId) {
            fetch('/admin/galleries/photo/set-cover.php?gallery_id=' + document.getElementById('galleryId').value + '&photo_id=' + photoId, {
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
                    Swal.fire('Error', data.error || 'Failed to set cover', 'error');
                }
            });
        }
        
        // Duplicate gallery
        function duplicateGallery() {
            Swal.fire({
                title: 'Duplicate Gallery',
                input: 'text',
                inputLabel: 'New Gallery Name',
                inputValue: '<?php echo htmlspecialchars($gallery->name); ?> (Copy)',
                showCancelButton: true,
                confirmButtonText: 'Duplicate',
                preConfirm: (name) => {
                    return fetch('/admin/galleries/photo/duplicate.php?id=<?php echo $gallery->id; ?>&name=' + encodeURIComponent(name) + '&token=' + document.getElementById('csrfToken').value)
                        .then(response => {
                            if (response.redirected) {
                                window.location.href = response.url;
                            }
                        });
                }
            });
        }
        
        // Delete gallery
        function deleteGallery() {
            Swal.fire({
                title: 'Delete Gallery',
                html: 'Are you sure you want to delete this gallery? All photos will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/galleries/photo/delete.php?id=<?php echo $gallery->id; ?>&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        // Search in media browser
        let searchTimeout;
        document.getElementById('mediaSearch')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadMediaBrowser();
            }, 500);
        });
    </script>
</body>
</html>