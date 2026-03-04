<?php
// admin/menus/edit.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\MenuController;
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

// Get menu ID
$id = (int)($_GET['id'] ?? 0);

// Initialize controller
$controller = new MenuController($auth, $csrf, $config);
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
    <title>Edit Menu: <?php echo htmlspecialchars($menu->name); ?> - CMS Admin</title>
    
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
        
        .menu-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 15px;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
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
        
        /* Layout */
        .menu-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
        }
        
        /* Menu Structure */
        .structure-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .structure-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .structure-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .menu-tree {
            min-height: 200px;
            border: 2px dashed #e1e1e1;
            border-radius: 10px;
            padding: 15px;
        }
        
        .menu-item {
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            margin-bottom: 8px;
            padding: 12px 15px;
            cursor: move;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(102,126,234,0.1);
        }
        
        .menu-item.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }
        
        .menu-item-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .drag-handle {
            color: #999;
            cursor: move;
            font-size: 16px;
        }
        
        .item-icon {
            width: 24px;
            color: var(--primary-color);
        }
        
        .item-title {
            flex: 1;
            font-weight: 500;
        }
        
        .item-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .badge-page {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-url {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-anchor {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-separator {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .item-actions {
            display: flex;
            gap: 5px;
        }
        
        .item-action {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .item-action:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .item-action.edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .item-action.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        .item-action.clone:hover {
            background: #28a745;
            color: white;
        }
        
        .nested-items {
            margin-left: 30px;
            margin-top: 8px;
            padding-left: 15px;
            border-left: 2px dashed #e1e1e1;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* Settings Panel */
        .settings-card {
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
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            padding: 10px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px;
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
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
            color: white;
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
        
        .shortcut-hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
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
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
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
            
            .menu-layout {
                grid-template-columns: 1fr;
            }
            
            .settings-card {
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
                <li class="active">
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
                    <?php echo htmlspecialchars($menu->name); ?>
                    <span class="menu-status <?php echo $menu->is_active ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $menu->is_active ? 'Active' : 'Inactive'; ?>
                    </span>
                </h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/menus/" class="breadcrumb-item">Menus</a>
                    <span class="breadcrumb-item active"><?php echo htmlspecialchars($menu->name); ?></span>
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
        
        <!-- Menu Layout -->
        <div class="menu-layout">
            <!-- Menu Structure -->
            <div class="structure-card">
                <div class="structure-header">
                    <h2>Menu Structure</h2>
                    <button class="btn btn-sm btn-primary" onclick="openAddItemModal()">
                        <i class="fas fa-plus-circle"></i>
                        Add Item
                    </button>
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
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Drag and drop</strong> to reorder items. Drag items onto other items to create sub-menus (max 2 levels).
                </div>
                
                <div id="menuTree" class="menu-tree">
                    <?php if (empty($menuTree)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bars"></i>
                            <p>No menu items yet. Click "Add Item" to create your first menu item.</p>
                        </div>
                    <?php else: ?>
                        <?php echo renderMenuTree($menuTree); ?>
                    <?php endif; ?>
                </div>
                
                <div class="shortcut-hint mt-3">
                    <i class="fas fa-keyboard"></i>
                    Tip: Hold Ctrl/Cmd to select multiple items for bulk actions
                </div>
            </div>
            
            <!-- Settings Panel -->
            <div class="settings-card">
                <!-- Menu Settings -->
                <div class="settings-section">
                    <h3>Menu Settings</h3>
                    
                    <form method="POST" action="/admin/menus/update.php?id=<?php echo $menu->id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Menu Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($formData['name'] ?? $menu->name); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <select class="form-select" name="location" required>
                                <?php foreach ($locations as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo ($formData['location'] ?? $menu->location) === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" 
                                      name="description" 
                                      rows="2"><?php echo htmlspecialchars($formData['description'] ?? $menu->description); ?></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="is_active" 
                                   id="isActive" 
                                   value="1"
                                   <?php echo (isset($formData['is_active']) ? $formData['is_active'] : $menu->is_active) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isActive">
                                Active
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Update Menu Settings
                        </button>
                    </form>
                </div>
                
                <!-- Menu Actions -->
                <div class="settings-section">
                    <h3>Menu Actions</h3>
                    
                    <button class="btn-outline-primary mb-2" onclick="openAddItemModal()">
                        <i class="fas fa-plus-circle"></i>
                        Add New Item
                    </button>
                    
                    <button class="btn-outline-primary mb-2" onclick="expandAll()">
                        <i class="fas fa-expand-alt"></i>
                        Expand All
                    </button>
                    
                    <button class="btn-outline-primary mb-2" onclick="collapseAll()">
                        <i class="fas fa-compress-alt"></i>
                        Collapse All
                    </button>
                    
                    <?php if ($menu->location !== 'primary' && $menu->location !== 'footer'): ?>
                        <button class="btn-outline-primary mb-2" onclick="duplicateMenu()">
                            <i class="fas fa-copy"></i>
                            Duplicate Menu
                        </button>
                        
                        <button class="btn-danger" onclick="deleteMenu()">
                            <i class="fas fa-trash"></i>
                            Delete Menu
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Preview -->
                <div class="settings-section">
                    <h3>Preview</h3>
                    <div class="preview-box">
                        <?php if (!empty($menuTree)): ?>
                            <ul class="preview-menu">
                                <?php echo renderPreviewMenu($menuTree); ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted text-center">Add items to see preview</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Item Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form id="itemForm">
                    <input type="hidden" name="item_id" id="itemId">
                    <input type="hidden" name="parent_id" id="parentId" value="">
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Navigation Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" id="itemLabel" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Link Type</label>
                            <select class="form-select" name="link_type" id="linkType" onchange="toggleLinkFields()">
                                <?php foreach ($linkTypes as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Page Select (for page type) -->
                        <div class="form-group link-field" id="pageField" style="display: none;">
                            <label class="form-label">Select Page</label>
                            <select class="form-select" name="page_id" id="pageId">
                                <option value="">Select a page...</option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo $page['id']; ?>">
                                        <?php echo htmlspecialchars($page['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- URL Field (for url type) -->
                        <div class="form-group link-field" id="urlField" style="display: none;">
                            <label class="form-label">URL</label>
                            <input type="url" class="form-control" name="url" id="itemUrl" placeholder="https://example.com">
                        </div>
                        
                        <!-- Anchor Field (for anchor type) -->
                        <div class="form-group link-field" id="anchorField" style="display: none;">
                            <label class="form-label">Anchor Name</label>
                            <input type="text" class="form-control" name="anchor" id="itemAnchor" placeholder="section-name">
                            <small class="text-muted">Use without #, e.g., "contact" for #contact</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Open Link In</label>
                            <select class="form-select" name="target" id="itemTarget">
                                <option value="_self">Same Tab</option>
                                <option value="_blank">New Tab</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CSS Class (optional)</label>
                            <input type="text" class="form-control" name="css_class" id="itemCssClass" placeholder="btn btn-primary">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Icon Class (optional)</label>
                            <input type="text" class="form-control" name="icon_class" id="itemIconClass" placeholder="fas fa-home">
                            <small class="text-muted">Font Awesome or other icon classes</small>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" id="itemIsActive" value="1" checked>
                            <label class="form-check-label" for="itemIsActive">Active</label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo $csrf->generate('menu_actions'); ?>">
    <input type="hidden" id="menuId" value="<?php echo $menu->id; ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize drag and drop
        const menuTree = document.getElementById('menuTree');
        
        new Sortable(menuTree, {
            group: 'menu',
            animation: 150,
            handle: '.drag-handle',
            draggable: '.menu-item',
            onEnd: function(evt) {
                saveOrder();
            }
        });
        
        // Initialize nested Sortables
        document.querySelectorAll('.nested-items').forEach(nested => {
            new Sortable(nested, {
                group: 'menu',
                animation: 150,
                handle: '.drag-handle',
                draggable: '.menu-item',
                onEnd: function(evt) {
                    saveOrder();
                }
            });
        });
        
        // Save menu order
        function saveOrder() {
            const order = getMenuOrder(menuTree);
            
            fetch('/admin/menus/reorder.php?id=' + document.getElementById('menuId').value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify({ order: order })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Get menu order as nested array
        function getMenuOrder(element) {
            const items = [];
            
            element.querySelectorAll(':scope > .menu-item').forEach(item => {
                const itemData = {
                    id: item.dataset.id,
                    children: []
                };
                
                const nested = item.querySelector(':scope > .nested-items');
                if (nested && nested.children.length > 0) {
                    itemData.children = getMenuOrder(nested);
                }
                
                items.push(itemData);
            });
            
            return items;
        }
        
        // Modal functions
        const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
        
        function openAddItemModal(parentId = null) {
            document.getElementById('modalTitle').textContent = 'Add Menu Item';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            document.getElementById('parentId').value = parentId || '';
            document.getElementById('itemIsActive').checked = true;
            toggleLinkFields();
            itemModal.show();
        }
        
        function editItem(id) {
            // Fetch item data
            fetch('/admin/menus/get-item.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Menu Item';
                    document.getElementById('itemId').value = data.id;
                    document.getElementById('parentId').value = data.parent_id || '';
                    document.getElementById('itemLabel').value = data.label;
                    document.getElementById('linkType').value = data.link_type;
                    document.getElementById('itemTarget').value = data.target;
                    document.getElementById('itemCssClass').value = data.css_class || '';
                    document.getElementById('itemIconClass').value = data.icon_class || '';
                    document.getElementById('itemIsActive').checked = data.is_active;
                    
                    if (data.link_type === 'page') {
                        document.getElementById('pageId').value = data.page_id;
                    } else if (data.link_type === 'url') {
                        document.getElementById('itemUrl').value = data.url;
                    } else if (data.link_type === 'anchor') {
                        document.getElementById('itemAnchor').value = data.anchor;
                    }
                    
                    toggleLinkFields();
                    itemModal.show();
                });
        }
        
        function deleteItem(id, label) {
            Swal.fire({
                title: 'Delete Menu Item',
                html: `Are you sure you want to delete "<strong>${label}</strong>"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/menus/delete-item.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            Swal.fire('Error', data.error || 'Failed to delete item', 'error');
                        }
                    });
                }
            });
        }
        
        function cloneItem(id) {
            // Implement clone functionality
        }
        
        function toggleLinkFields() {
            const type = document.getElementById('linkType').value;
            
            document.querySelectorAll('.link-field').forEach(field => {
                field.style.display = 'none';
            });
            
            if (type === 'page') {
                document.getElementById('pageField').style.display = 'block';
            } else if (type === 'url') {
                document.getElementById('urlField').style.display = 'block';
            } else if (type === 'anchor') {
                document.getElementById('anchorField').style.display = 'block';
            }
        }
        
        // Form submission
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const itemId = document.getElementById('itemId').value;
            const url = itemId ? '/admin/menus/update-item.php' : '/admin/menus/add-item.php';
            const data = {
                menu_id: document.getElementById('menuId').value,
                parent_id: document.getElementById('parentId').value,
                label: document.getElementById('itemLabel').value,
                link_type: document.getElementById('linkType').value,
                target: document.getElementById('itemTarget').value,
                css_class: document.getElementById('itemCssClass').value,
                icon_class: document.getElementById('itemIconClass').value,
                is_active: document.getElementById('itemIsActive').checked
            };
            
            if (data.link_type === 'page') {
                data.page_id = document.getElementById('pageId').value;
            } else if (data.link_type === 'url') {
                data.url = document.getElementById('itemUrl').value;
            } else if (data.link_type === 'anchor') {
                data.anchor = document.getElementById('itemAnchor').value;
            }
            
            if (itemId) {
                data.id = itemId;
            }
            
            fetch(url, {
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
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to save item', 'error');
                }
            });
        });
        
        // Expand/Collapse all
        function expandAll() {
            document.querySelectorAll('.nested-items').forEach(nested => {
                nested.style.display = 'block';
            });
        }
        
        function collapseAll() {
            document.querySelectorAll('.nested-items').forEach(nested => {
                nested.style.display = 'none';
            });
        }
        
        // Menu actions
        function duplicateMenu() {
            Swal.fire({
                title: 'Duplicate Menu',
                input: 'text',
                inputLabel: 'New Menu Name',
                inputValue: '<?php echo htmlspecialchars($menu->name); ?> (Copy)',
                showCancelButton: true,
                confirmButtonText: 'Duplicate',
                preConfirm: (name) => {
                    return fetch('/admin/menus/duplicate.php?id=<?php echo $menu->id; ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        },
                        body: JSON.stringify({ name: name })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '/admin/menus/edit.php?id=' + data.id;
                        } else {
                            throw new Error(data.error || 'Failed to duplicate menu');
                        }
                    })
                    .catch(error => {
                        Swal.showValidationMessage(error.message);
                    });
                }
            });
        }
        
        function deleteMenu() {
            Swal.fire({
                title: 'Delete Menu',
                html: 'Are you sure you want to delete this menu? This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete',
                preConfirm: () => {
                    return fetch('/admin/menus/delete.php?id=<?php echo $menu->id; ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '/admin/menus/';
                        } else {
                            throw new Error(data.error || 'Failed to delete menu');
                        }
                    })
                    .catch(error => {
                        Swal.showValidationMessage(error.message);
                    });
                }
            });
        }
        
        // Initialize toggle for link fields
        toggleLinkFields();
    </script>
</body>
</html>

<?php
// Helper functions for rendering menu tree
function renderMenuTree($items, $level = 0) {
    $html = '';
    foreach ($items as $item) {
        $badgeClass = 'badge-' . $item->link_type;
        $icon = $item->icon_class ? '<i class="' . htmlspecialchars($item->icon_class) . '"></i>' : '<i class="fas fa-link"></i>';
        
        $html .= '<div class="menu-item" data-id="' . $item->id . '">';
        $html .= '<div class="menu-item-header">';
        $html .= '<i class="fas fa-grip-vertical drag-handle"></i>';
        $html .= '<span class="item-icon">' . $icon . '</span>';
        $html .= '<span class="item-title">' . htmlspecialchars($item->label) . '</span>';
        $html .= '<span class="item-badge ' . $badgeClass . '">' . $item->getLinkTypeLabel() . '</span>';
        $html .= '<div class="item-actions">';
        $html .= '<button class="item-action edit" onclick="editItem(' . $item->id . ')"><i class="fas fa-edit"></i></button>';
        $html .= '<button class="item-action clone" onclick="cloneItem(' . $item->id . ')"><i class="fas fa-copy"></i></button>';
        $html .= '<button class="item-action delete" onclick="deleteItem(' . $item->id . ', \'' . htmlspecialchars(addslashes($item->label)) . '\')"><i class="fas fa-trash"></i></button>';
        $html .= '</div>';
        $html .= '</div>';
        
        if (!empty($item->children)) {
            $html .= '<div class="nested-items">';
            $html .= renderMenuTree($item->children, $level + 1);
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    return $html;
}

function renderPreviewMenu($items) {
    $html = '';
    foreach ($items as $item) {
        if ($item->isSeparator()) {
            $html .= '<li class="separator">---</li>';
        } else {
            $html .= '<li>';
            $html .= '<a href="' . htmlspecialchars($item->getUrl()) . '" target="' . $item->getTarget() . '">';
            if ($item->icon_class) {
                $html .= '<i class="' . htmlspecialchars($item->icon_class) . '"></i> ';
            }
            $html .= htmlspecialchars($item->label);
            $html .= '</a>';
            
            if (!empty($item->children)) {
                $html .= '<ul>';
                $html .= renderPreviewMenu($item->children);
                $html .= '</ul>';
            }
            
            $html .= '</li>';
        }
    }
    return $html;
}
?>