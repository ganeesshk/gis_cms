<?php
// admin/galleries/video/edit.php

require_once __DIR__ . '/../../../app/bootstrap.php';

use App\Controllers\VideoGalleryController;
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
$controller = new VideoGalleryController($auth, $csrf, $config);
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
    <title>Edit Video Gallery: <?php echo htmlspecialchars($gallery->name); ?> - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --youtube-color: #ff0000;
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
        
        /* Videos grid */
        .videos-grid {
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
        
        .add-video-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .add-video-form .input-group {
            display: flex;
            gap: 10px;
        }
        
        .add-video-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .add-video-form input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .add-video-form button {
            background: var(--youtube-color);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .add-video-form button:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }
        
        .add-video-form button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .video-items {
            min-height: 200px;
        }
        
        .video-item {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 15px;
            cursor: move;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .video-item:hover {
            border-color: var(--primary-color);
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.2);
        }
        
        .video-item.dragging {
            opacity: 0.5;
            transform: scale(0.98);
        }
        
        .video-item.inactive {
            opacity: 0.7;
            background: #f1f1f1;
            border-color: #ddd;
        }
        
        .drag-handle {
            color: #999;
            font-size: 20px;
            cursor: move;
        }
        
        .video-thumbnail {
            width: 120px;
            height: 68px;
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            flex-shrink: 0;
        }
        
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-thumbnail .duration {
            position: absolute;
            bottom: 4px;
            right: 4px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .video-info {
            flex: 1;
        }
        
        .video-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .video-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #666;
        }
        
        .video-meta i {
            margin-right: 3px;
            color: var(--primary-color);
        }
        
        .video-meta .youtube {
            color: var(--youtube-color);
        }
        
        .video-actions {
            display: flex;
            gap: 5px;
        }
        
        .video-action {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .video-action:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .video-action.edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .video-action.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        .video-action.refresh:hover {
            background: #28a745;
            color: white;
        }
        
        .empty-videos {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
        }
        
        .empty-videos i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--youtube-color);
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
        
        .thumbnail-preview {
            width: 100%;
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .thumbnail-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            
            .video-item {
                flex-wrap: wrap;
            }
            
            .video-thumbnail {
                width: 100%;
                height: auto;
                aspect-ratio: 16/9;
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
                <li class="active">
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
                    <a href="/admin/galleries/video/" class="breadcrumb-item">Video Galleries</a>
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
                    <a href="/galleries/video/<?php echo $gallery->slug; ?>" class="btn btn-light" target="_blank">
                        <i class="fas fa-eye"></i> View Gallery
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="gallery-stats">
                <div class="gallery-stat">
                    <div class="gallery-stat-value"><?php echo count($videos); ?></div>
                    <div class="gallery-stat-label">Total Videos</div>
                </div>
                <div class="gallery-stat">
                    <div class="gallery-stat-value"><?php echo count(array_filter($videos, function($v) { return $v->is_visible; })); ?></div>
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
            <!-- Videos Grid -->
            <div class="videos-grid">
                <div class="grid-header">
                    <h2>Gallery Videos</h2>
                </div>
                
                <!-- Add Video Form -->
                <div class="add-video-form">
                    <div class="input-group">
                        <input type="url" 
                               id="youtubeUrl" 
                               placeholder="Enter YouTube URL (e.g., https://youtu.be/xxx or https://youtube.com/watch?v=xxx)"
                               value="">
                        <button type="button" id="addVideoBtn" onclick="addVideo()">
                            <i class="fab fa-youtube"></i>
                            Add Video
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i>
                        Supports YouTube, Shorts, and youtu.be links
                    </small>
                </div>
                
                <?php if (empty($videos)): ?>
                    <div class="empty-videos">
                        <i class="fab fa-youtube"></i>
                        <h3>No Videos Yet</h3>
                        <p>Add a YouTube URL above to start building your gallery.</p>
                    </div>
                <?php else: ?>
                    <div class="video-items" id="videoGrid">
                        <?php foreach ($videos as $video): ?>
                            <div class="video-item <?php echo !$video->is_visible ? 'inactive' : ''; ?>" 
                                 data-id="<?php echo $video->id; ?>">
                                <i class="fas fa-grip-vertical drag-handle"></i>
                                
                                <div class="video-thumbnail">
                                    <img src="<?php echo $video->thumbnail_url; ?>" alt="<?php echo htmlspecialchars($video->title); ?>">
                                    <?php if ($video->duration): ?>
                                        <span class="duration"><?php echo $video->duration; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="video-info">
                                    <div class="video-title"><?php echo htmlspecialchars($video->title); ?></div>
                                    <div class="video-meta">
                                        <span><i class="fab fa-youtube youtube"></i> <?php echo htmlspecialchars($video->channel_name ?: 'YouTube'); ?></span>
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($video->adder ? $video->adder->username : 'Unknown'); ?></span>
                                        <span><i class="fas fa-eye<?php echo !$video->is_visible ? '-slash' : ''; ?>"></i> <?php echo $video->is_visible ? 'Visible' : 'Hidden'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="video-actions">
                                    <button class="video-action edit" onclick="editVideo(<?php echo $video->id; ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="video-action refresh" onclick="refreshVideo(<?php echo $video->id; ?>)" title="Refresh from YouTube">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <button class="video-action delete" onclick="deleteVideo(<?php echo $video->id; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
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
                    
                    <form method="POST" action="/admin/galleries/video/update.php?id=<?php echo $gallery->id; ?>">
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
                
                <!-- YouTube Tips -->
                <div class="settings-section">
                    <h3>YouTube Tips</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fab fa-youtube text-danger"></i> Videos are embedded with privacy-enhanced mode</li>
                        <li class="mb-2"><i class="fas fa-sync-alt text-primary"></i> Click refresh to update metadata</li>
                        <li class="mb-2"><i class="fas fa-image text-success"></i> Custom thumbnails can be uploaded</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Video Modal -->
    <div class="modal fade" id="editVideoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="thumbnail-preview" id="videoThumbnail">
                        <img src="" alt="Video thumbnail">
                    </div>
                    
                    <form id="editVideoForm">
                        <div class="form-group mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" id="videoTitle">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="videoDescription" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="is_visible" id="videoVisible" value="1" checked>
                            <label class="form-check-label" for="videoVisible">Visible in gallery</label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Custom Thumbnail (optional)</label>
                            <select class="form-select" name="custom_thumbnail_id" id="videoThumbnailId">
                                <option value="">Use YouTube thumbnail</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                            <small class="text-muted">Select an image from media library</small>
                        </div>
                    </form>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveVideoEdit()">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('video_gallery_actions'); ?>">
    <input type="hidden" id="galleryId" value="<?php echo $gallery->id; ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize Sortable for drag-and-drop reordering
        const videoGrid = document.getElementById('videoGrid');
        if (videoGrid) {
            new Sortable(videoGrid, {
                animation: 150,
                handle: '.drag-handle',
                onEnd: function() {
                    saveOrder();
                }
            });
        }
        
        // Modal instance
        const editVideoModal = new bootstrap.Modal(document.getElementById('editVideoModal'));
        let currentEditVideoId = null;
        
        // Add video
        function addVideo() {
            const url = document.getElementById('youtubeUrl').value.trim();
            if (!url) {
                Swal.fire('Info', 'Please enter a YouTube URL', 'info');
                return;
            }
            
            const btn = document.getElementById('addVideoBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            fetch('/admin/galleries/video/add-video.php?id=' + document.getElementById('galleryId').value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify({ youtube_url: url })
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fab fa-youtube"></i> Add Video';
                
                if (data.success) {
                    document.getElementById('youtubeUrl').value = '';
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to add video', 'error');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fab fa-youtube"></i> Add Video';
                Swal.fire('Error', 'Failed to add video', 'error');
            });
        }
        
        // Save video order
        function saveOrder() {
            const order = [];
            document.querySelectorAll('.video-item').forEach(item => {
                order.push(item.dataset.id);
            });
            
            fetch('/admin/galleries/video/reorder.php?id=' + document.getElementById('galleryId').value, {
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
        
        // Edit video
        function editVideo(id) {
            currentEditVideoId = id;
            
            // Fetch video data
            fetch('/admin/galleries/video/get-video.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('videoTitle').value = data.title;
                    document.getElementById('videoDescription').value = data.description || '';
                    document.getElementById('videoVisible').checked = data.is_visible;
                    document.getElementById('videoThumbnail').innerHTML = '<img src="' + data.thumbnail + '" alt="Thumbnail">';
                    
                    // Load custom thumbnail options
                    loadThumbnailOptions(data.custom_thumbnail_id);
                    
                    editVideoModal.show();
                });
        }
        
        // Load thumbnail options
        function loadThumbnailOptions(selectedId) {
            fetch('/admin/media/browser.php?type=images&limit=50')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('videoThumbnailId');
                    select.innerHTML = '<option value="">Use YouTube thumbnail</option>';
                    
                    // Parse HTML response to get media items
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    
                    tempDiv.querySelectorAll('.media-item').forEach(item => {
                        const id = item.dataset.id;
                        const name = item.querySelector('.media-name')?.textContent || 'Image ' + id;
                        const option = document.createElement('option');
                        option.value = id;
                        option.textContent = name;
                        if (selectedId == id) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                });
        }
        
        // Save video edit
        function saveVideoEdit() {
            const data = {
                title: document.getElementById('videoTitle').value,
                description: document.getElementById('videoDescription').value,
                is_visible: document.getElementById('videoVisible').checked,
                custom_thumbnail_id: document.getElementById('videoThumbnailId').value || null
            };
            
            fetch('/admin/galleries/video/update-video.php?id=' + currentEditVideoId, {
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
                    editVideoModal.hide();
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to update video', 'error');
                }
            });
        }
        
        // Delete video
        function deleteVideo(id) {
            Swal.fire({
                title: 'Remove Video',
                text: 'Remove this video from the gallery?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Remove'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/galleries/video/delete-video.php?id=' + id, {
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
                            Swal.fire('Error', data.error || 'Failed to remove video', 'error');
                        }
                    });
                }
            });
        }
        
        // Refresh video metadata
        function refreshVideo(id) {
            Swal.fire({
                title: 'Refresh Video',
                text: 'Update video metadata from YouTube?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Refresh'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/galleries/video/refresh-video.php?id=' + id, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', 'Video metadata updated', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.error || 'Failed to refresh video', 'error');
                        }
                    });
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
                    return fetch('/admin/galleries/video/duplicate.php?id=<?php echo $gallery->id; ?>&name=' + encodeURIComponent(name) + '&token=' + document.getElementById('csrfToken').value)
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
                html: 'Are you sure you want to delete this gallery? All videos will be removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/galleries/video/delete.php?id=<?php echo $gallery->id; ?>&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
        
        // Enter key in URL input
        document.getElementById('youtubeUrl').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addVideo();
            }
        });
    </script>
</body>
</html>