<?php
// admin/pages/edit.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\PageController;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;
use App\Models\Page;

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

// Get page ID
$id = (int)($_GET['id'] ?? 0);

// Initialize controller
$controller = new PageController($auth, $csrf, $config);
$result = $controller->edit($id);
extract($result['data']);

// Check permission
if (!$user->hasPermission('pages.write') && $page->author_id !== $user->id) {
    $_SESSION['error'] = 'You do not have permission to edit this page.';
    header('Location: ../../admin/pages/');
    exit;
}

// Restore form data from session if validation failed
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Get page tags as array of names
$pageTagNames = array_map(function($tag) {
    return $tag->name;
}, $pageTags);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Page: <?php echo htmlspecialchars($page->title); ?> - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- Flatpickr -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/material_blue.css" rel="stylesheet">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
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
        
        /* Sidebar styles (same as create) */
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
        
        .page-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 15px;
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
        
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #eee;
            margin-bottom: 25px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 12px 20px;
            margin-right: 10px;
        }
        
        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-link.active {
            border: none;
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background: transparent;
        }
        
        .nav-tabs .nav-link i {
            margin-right: 8px;
        }
        
        /* Form */
        .form-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        .main-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .sidebar-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            position: sticky;
            top: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .form-section h3 {
            font-size: 16px;
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
        }
        
        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        /* Slug input */
        .slug-input-group {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 0 15px;
        }
        
        .slug-prefix {
            color: #666;
            font-size: 14px;
            padding-right: 10px;
        }
        
        .slug-input-group .form-control {
            border: none;
            padding-left: 0;
            background: transparent;
        }
        
        .slug-input-group .form-control:focus {
            box-shadow: none;
        }
        
        /* Revision history */
        .revisions-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .revision-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .revision-item:hover {
            background: #f8f9fa;
            border-color: var(--primary-color);
        }
        
        .revision-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .revision-content {
            flex: 1;
        }
        
        .revision-number {
            font-weight: 600;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .revision-meta {
            font-size: 12px;
            color: #666;
        }
        
        .revision-meta i {
            margin-right: 3px;
        }
        
        .revision-note {
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 13px;
            margin-top: 5px;
            color: #666;
        }
        
        .btn-revision {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .action-buttons .btn {
            flex: 1;
            padding: 12px;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        /* Meta box */
        .meta-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .character-count {
            font-size: 12px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }
        
        .character-count.limit-reached {
            color: #dc3545;
        }
        
        /* Change note */
        .change-note {
            background: #e3f2fd;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .change-note textarea {
            background: white;
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
                transition: transform 0.3s;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar-form {
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
                <li class="active">
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
                    Edit Page
                    <?php echo $page->getStatusBadge(); ?>
                </h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/pages/" class="breadcrumb-item">Pages</a>
                    <span class="breadcrumb-item active"><?php echo htmlspecialchars($page->title); ?></span>
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
        
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="pageTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">
                    <i class="fas fa-edit"></i>
                    Edit Content
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="revisions-tab" data-bs-toggle="tab" data-bs-target="#revisions" type="button" role="tab">
                    <i class="fas fa-history"></i>
                    Revisions (<?php echo count($revisions); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">
                    <i class="fas fa-chart-line"></i>
                    SEO
                </button>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="pageTabsContent">
            <!-- Edit Tab -->
            <div class="tab-pane fade show active" id="edit" role="tabpanel">
                <form method="POST" action="update.php?id=<?php echo $page->id; ?>" id="pageForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
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
                    
                    <!-- Change Note -->
                    <div class="change-note">
                        <label class="form-label">Change Note (optional)</label>
                        <textarea class="form-control" 
                                  name="change_note" 
                                  rows="2"
                                  placeholder="Briefly describe what you changed..."><?php echo htmlspecialchars($formData['change_note'] ?? ''); ?></textarea>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            This note will be saved with the revision for future reference.
                        </small>
                    </div>
                    
                    <div class="form-container">
                        <!-- Main Form -->
                        <div class="main-form">
                            <!-- Title -->
                            <div class="form-section">
                                <h3>Page Content</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        Page Title <span class="required">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="title" 
                                           id="title"
                                           value="<?php echo htmlspecialchars($formData['title'] ?? $page->title); ?>"
                                           placeholder="Enter page title"
                                           required>
                                </div>
                                
                                <!-- Slug -->
                                <div class="form-group">
                                    <label class="form-label">URL Slug</label>
                                    <div class="slug-input-group">
                                        <span class="slug-prefix">/</span>
                                        <input type="text" 
                                               class="form-control" 
                                               name="slug" 
                                               id="slug"
                                               value="<?php echo htmlspecialchars($formData['slug'] ?? $page->slug); ?>"
                                               placeholder="leave-empty-to-auto-generate">
                                    </div>
                                </div>
                                
                                <!-- Content (WYSIWYG) -->
                                <div class="form-group">
                                    <label class="form-label">Content</label>
                                    <textarea class="form-control" 
                                              name="content" 
                                              id="content"
                                              rows="15"><?php echo htmlspecialchars($formData['content'] ?? $page->content); ?></textarea>
                                </div>
                                
                                <!-- Excerpt -->
                                <div class="form-group">
                                    <label class="form-label">Excerpt</label>
                                    <textarea class="form-control" 
                                              name="excerpt" 
                                              id="excerpt"
                                              rows="3"
                                              placeholder="Brief summary of the page (optional)"><?php echo htmlspecialchars($formData['excerpt'] ?? $page->excerpt); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sidebar Form -->
                        <div class="sidebar-form">
                            <!-- Status -->
                            <div class="form-section">
                                <h3>Publishing</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="draft" <?php echo ($formData['status'] ?? $page->status) === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo ($formData['status'] ?? $page->status) === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="unpublished" <?php echo ($formData['status'] ?? $page->status) === 'unpublished' ? 'selected' : ''; ?>>Unpublished</option>
                                        <option value="scheduled" <?php echo ($formData['status'] ?? $page->status) === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    </select>
                                </div>
                                
                                <!-- Scheduled Date -->
                                <div class="form-group" id="scheduledGroup" style="<?php echo ($formData['status'] ?? $page->status) === 'scheduled' ? 'display: block;' : 'display: none;'; ?>">
                                    <label class="form-label">Schedule Date & Time</label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="scheduled_at" 
                                           id="scheduledAt"
                                           value="<?php echo htmlspecialchars($formData['scheduled_at'] ?? ($page->scheduled_at ? date('Y-m-d H:i:s', strtotime($page->scheduled_at)) : '')); ?>"
                                           placeholder="Select date and time">
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               name="is_in_sitemap" 
                                               id="isInSitemap" 
                                               value="1"
                                               <?php echo (isset($formData['is_in_sitemap']) ? $formData['is_in_sitemap'] : $page->is_in_sitemap) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="isInSitemap">
                                            Include in sitemap
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Template -->
                            <div class="form-section">
                                <h3>Template</h3>
                                
                                <div class="form-group">
                                    <select class="form-select" name="template">
                                        <option value="default" <?php echo ($formData['template'] ?? $page->template) === 'default' ? 'selected' : ''; ?>>Default Template</option>
                                        <option value="full-width" <?php echo ($formData['template'] ?? $page->template) === 'full-width' ? 'selected' : ''; ?>>Full Width</option>
                                        <option value="sidebar-left" <?php echo ($formData['template'] ?? $page->template) === 'sidebar-left' ? 'selected' : ''; ?>>Sidebar Left</option>
                                        <option value="sidebar-right" <?php echo ($formData['template'] ?? $page->template) === 'sidebar-right' ? 'selected' : ''; ?>>Sidebar Right</option>
                                        <option value="landing" <?php echo ($formData['template'] ?? $page->template) === 'landing' ? 'selected' : ''; ?>>Landing Page</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Tags -->
                            <div class="form-section">
                                <h3>Tags</h3>
                                
                                <div class="form-group">
                                    <select class="tag-input" name="tags[]" id="tags" multiple>
                                        <?php foreach ($tags as $tag): ?>
                                            <option value="<?php echo htmlspecialchars($tag['name']); ?>" 
                                                    <?php echo in_array($tag['name'], $pageTagNames) || (isset($formData['tags']) && in_array($tag['name'], $formData['tags'])) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($tag['name']); ?> (<?php echo $tag['page_count']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Page Info -->
                            <div class="form-section">
                                <h3>Page Information</h3>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Created:</small>
                                    <div><?php echo date('M j, Y H:i', strtotime($page->created_at)); ?></div>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Last Modified:</small>
                                    <div><?php echo date('M j, Y H:i', strtotime($page->updated_at)); ?></div>
                                </div>
                                
                                <?php if ($page->published_at): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Published:</small>
                                        <div><?php echo date('M j, Y H:i', strtotime($page->published_at)); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($page->author): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">Author:</small>
                                        <div class="d-flex align-items-center mt-1">
                                            <img src="<?php echo htmlspecialchars($page->author->getAvatarUrl()); ?>" 
                                                 alt="" class="rounded-circle me-2" width="24" height="24">
                                            <?php echo htmlspecialchars($page->author->getFullName()); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <button type="submit" name="save_draft" class="btn btn-secondary">
                                    <i class="fas fa-save"></i>
                                    Update
                                </button>
                                <button type="submit" name="save_and_preview" class="btn btn-info">
                                    <i class="fas fa-eye"></i>
                                    Preview
                                </button>
                                <?php if ($user->hasPermission('pages.publish') && $page->status !== 'published'): ?>
                                    <button type="submit" name="save_and_publish" class="btn btn-success">
                                        <i class="fas fa-check-circle"></i>
                                        Publish
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Revisions Tab -->
            <div class="tab-pane fade" id="revisions" role="tabpanel">
                <div class="main-form">
                    <h3 class="mb-4">Revision History</h3>
                    
                    <?php if (empty($revisions)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-4x text-muted mb-3"></i>
                            <p class="text-muted">No revisions yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="revisions-list">
                            <?php foreach ($revisions as $revision): ?>
                                <div class="revision-item">
                                    <img src="<?php echo htmlspecialchars($revision->author ? $revision->author->getAvatarUrl() : '/admin/assets/img/default-avatar.png'); ?>" 
                                         alt="" class="revision-avatar">
                                    
                                    <div class="revision-content">
                                        <div>
                                            <span class="revision-number">#<?php echo $revision->revision_number; ?></span>
                                            <span class="fw-bold"><?php echo htmlspecialchars($revision->title); ?></span>
                                        </div>
                                        
                                        <div class="revision-meta">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($revision->author ? $revision->author->username : 'Unknown'); ?>
                                            <i class="fas fa-clock ms-3"></i> <?php echo date('M j, Y H:i', strtotime($revision->created_at)); ?>
                                        </div>
                                        
                                        <?php if ($revision->change_note): ?>
                                            <div class="revision-note">
                                                <i class="fas fa-quote-left"></i>
                                                <?php echo htmlspecialchars($revision->change_note); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($revision->revision_number < $page->revisions()[0]->revision_number): ?>
                                        <a href="/admin/pages/restore-revision.php?page_id=<?php echo $page->id; ?>&revision_id=<?php echo $revision->id; ?>&token=<?php echo $csrf->generate('revision_restore_' . $revision->id); ?>" 
                                           class="btn btn-outline-primary btn-revision"
                                           onclick="return confirm('Are you sure you want to restore this revision? Current changes will be saved as a new revision.')">
                                            <i class="fas fa-undo"></i>
                                            Restore
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Current</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- SEO Tab -->
            <div class="tab-pane fade" id="seo" role="tabpanel">
                <div class="main-form">
                    <h3 class="mb-4">SEO Settings</h3>
                    
                    <div class="meta-box">
                        <div class="form-group">
                            <label class="form-label">Meta Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="metaTitle"
                                   value="<?php echo htmlspecialchars($formData['meta_title'] ?? $page->meta_title); ?>"
                                   placeholder="Leave empty to use page title"
                                   maxlength="300"
                                   disabled>
                            <div class="character-count" id="metaTitleCount"><?php echo strlen($formData['meta_title'] ?? $page->meta_title ?? ''); ?>/300</div>
                            <small class="text-muted">To edit SEO settings, go to the Edit tab.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" 
                                      id="metaDescription"
                                      rows="3"
                                      placeholder="Brief description for search engines"
                                      maxlength="500"
                                      disabled><?php echo htmlspecialchars($formData['meta_description'] ?? $page->meta_description); ?></textarea>
                            <div class="character-count" id="metaDescCount"><?php echo strlen($formData['meta_description'] ?? $page->meta_description ?? ''); ?>/500</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($formData['meta_keywords'] ?? $page->meta_keywords); ?>"
                                   placeholder="Comma-separated keywords"
                                   disabled>
                        </div>
                    </div>
                    
                    <!-- Google Preview -->
                    <div class="mt-4">
                        <h4>Google Search Preview</h4>
                        <div class="google-preview p-3 border rounded">
                            <div class="text-primary h5 mb-1"><?php echo htmlspecialchars($formData['meta_title'] ?? $page->title); ?></div>
                            <div class="text-success small"><?php echo $config['app']['url']; ?>/<?php echo htmlspecialchars($page->slug); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars(substr($formData['meta_description'] ?? $page->meta_description ?? $page->excerpt ?? '', 0, 160)); ?>...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<script>
    ClassicEditor
        .create(document.querySelector('#content'))
        .then(editor => {
            console.log('CKEditor initialized');
        })
        .catch(error => {
            console.error('CKEditor error:', error);
        });
</script>
</body>
</html>