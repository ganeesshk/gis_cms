<?php
// admin/media/edit.php

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

// Get media ID
$id = (int)($_GET['id'] ?? 0);

// Initialize controller
$controller = new MediaController($auth, $csrf, $config);
$result = $controller->edit($id);
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Media - CMS Admin</title>
    
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
        
        /* Edit Container */
        .edit-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }
        
        .preview-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            position: sticky;
            top: 30px;
        }
        
        .preview-card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .preview-image {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .preview-image img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        .file-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .form-card h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control, .form-select {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 15px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        
        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220,53,69,0.4);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
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
            
            .edit-container {
                grid-template-columns: 1fr;
            }
            
            .preview-card {
                position: static;
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
                <h1>Edit Media</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/media/" class="breadcrumb-item">Media Library</a>
                    <span class="breadcrumb-item active">Edit</span>
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
        
        <!-- Edit Container -->
        <div class="edit-container">
            <!-- Preview Card -->
            <div class="preview-card">
                <h2>File Preview</h2>
                
                <?php if ($media->isImage()): ?>
                    <div class="preview-image">
                        <img src="<?php echo $media->public_url; ?>" alt="<?php echo htmlspecialchars($media->alt_text); ?>">
                    </div>
                    
                    <?php
                    $thumbnails = $media->thumbnails();
                    if (!empty($thumbnails)):
                    ?>
                        <h3 class="h6 mb-2">Thumbnails</h3>
                        <div class="d-flex gap-2 mb-3">
                            <?php foreach ($thumbnails as $thumb): ?>
                                <a href="<?php echo $thumb->public_url; ?>" target="_blank" class="text-decoration-none">
                                    <img src="<?php echo $thumb->public_url; ?>" 
                                         alt="<?php echo $thumb->size_label; ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                         title="<?php echo $thumb->width; ?>x<?php echo $thumb->height; ?>">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="<?php echo $media->getIcon(); ?> fa-5x text-primary mb-3"></i>
                        <p class="text-muted">No preview available</p>
                    </div>
                <?php endif; ?>
                
                <div class="file-info">
                    <div class="info-row">
                        <span class="info-label">Filename:</span>
                        <span class="info-value"><?php echo htmlspecialchars($media->original_name); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">File Type:</span>
                        <span class="info-value"><?php echo $media->mime_type; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">File Size:</span>
                        <span class="info-value"><?php echo $media->getFormattedSize(); ?></span>
                    </div>
                    <?php if ($media->width && $media->height): ?>
                        <div class="info-row">
                            <span class="info-label">Dimensions:</span>
                            <span class="info-value"><?php echo $media->width; ?> × <?php echo $media->height; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <span class="info-label">Uploaded:</span>
                        <span class="info-value"><?php echo date('M j, Y H:i', strtotime($media->created_at)); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Uploaded By:</span>
                        <span class="info-value"><?php echo htmlspecialchars($media->uploader() ? $media->uploader()->username : 'Unknown'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">File URL:</span>
                        <span class="info-value">
                            <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($media->public_url); ?>" readonly onclick="this.select()">
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Form Card -->
            <div class="form-card">
                <h2>Media Details</h2>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="/admin/media/update.php?id=<?php echo $media->id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($media->title); ?>">
                        <div class="form-text">A descriptive title for this media file</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Alt Text</label>
                        <input type="text" class="form-control" name="alt_text" value="<?php echo htmlspecialchars($media->alt_text); ?>">
                        <div class="form-text">Alternative text for accessibility and SEO</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Caption</label>
                        <textarea class="form-control" name="caption" rows="3"><?php echo htmlspecialchars($media->caption); ?></textarea>
                        <div class="form-text">Displayed below the media file</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Folder</label>
                        <select class="form-select" name="folder">
                            <option value="/">Root</option>
                            <?php foreach ($folders as $folder): ?>
                                <?php if ($folder !== '/'): ?>
                                    <option value="<?php echo htmlspecialchars($folder); ?>" <?php echo $media->folder === $folder ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($folder); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Organize files into folders</div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        
                        <a href="/admin/media/" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        
                        <button type="button" class="btn-danger" onclick="deleteMedia()">
                            <i class="fas fa-trash"></i>
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('media_delete_' . $media->id); ?>">
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function deleteMedia() {
            Swal.fire({
                title: 'Delete Media',
                text: 'Are you sure you want to delete this file? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/media/delete.php?id=<?php echo $media->id; ?>&token=' + document.getElementById('csrfToken').value;
                }
            });
        }
    </script>
</body>
</html>