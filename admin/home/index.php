<?php
// admin/home/index.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\HomeController;
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
$controller = new HomeController($auth, $csrf, $config);
$result = $controller->index();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page Editor - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- CodeMirror for HTML/CSS/JS editing -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    
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
        
        /* Editor layout */
        .editor-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
        }
        
        /* Sections panel */
        .sections-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .panel-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .sections-list {
            min-height: 400px;
        }
        
        .section-item {
            background: #f8f9fa;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .section-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(102,126,234,0.1);
        }
        
        .section-item.dragging {
            opacity: 0.5;
            transform: scale(0.98);
        }
        
        .section-item.inactive {
            opacity: 0.7;
            background: #f1f1f1;
            border-color: #ddd;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            padding: 15px;
            cursor: move;
        }
        
        .drag-handle {
            color: #999;
            margin-right: 15px;
            font-size: 18px;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .section-info {
            flex: 1;
        }
        
        .section-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .section-type {
            font-size: 12px;
            color: #666;
        }
        
        .section-type i {
            margin-right: 3px;
        }
        
        .section-status {
            margin-left: 10px;
        }
        
        .status-toggle {
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .status-toggle.active {
            background: #28a745;
        }
        
        .status-toggle:after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: left 0.3s;
        }
        
        .status-toggle.active:after {
            left: 22px;
        }
        
        .section-actions {
            display: flex;
            gap: 5px;
            margin-left: 15px;
        }
        
        .section-action {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .section-action:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .section-action.edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .section-action.delete:hover {
            background: #dc3545;
            color: white;
        }
        
        .section-action.clone:hover {
            background: #28a745;
            color: white;
        }
        
        .section-preview {
            padding: 15px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            font-size: 13px;
            color: #666;
            display: none;
        }
        
        .section-item.expanded .section-preview {
            display: block;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            border: 2px dashed #e1e1e1;
            border-radius: 10px;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        /* Toolbox panel */
        .toolbox-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
            position: sticky;
            top: 30px;
        }
        
        .toolbox-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .toolbox-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .toolbox-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .section-types {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .section-type-btn {
            background: #f8f9fa;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .section-type-btn:hover {
            border-color: var(--primary-color);
            background: #f0f2ff;
            transform: translateY(-2px);
        }
        
        .section-type-btn i {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 5px;
            display: block;
        }
        
        .section-type-btn span {
            font-size: 12px;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-action {
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(40,167,69,0.4);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-outline-primary {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
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
        
        .draft-badge {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
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
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            display: block;
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
        
        .CodeMirror {
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            height: auto;
            min-height: 200px;
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
            
            .editor-layout {
                grid-template-columns: 1fr;
            }
            
            .toolbox-panel {
                position: static;
            }
            
            .section-types {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .section-types {
                grid-template-columns: repeat(2, 1fr);
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
                <li class="active">
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
                    Home Page Editor
                    <span class="badge bg-warning draft-badge">DRAFT MODE</span>
                </h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <span class="breadcrumb-item active">Home Page</span>
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
        
        <!-- Editor Layout -->
        <div class="editor-layout">
            <!-- Sections Panel -->
            <div class="sections-panel">
                <div class="panel-header">
                    <h2>Page Sections</h2>
                    <span class="text-muted">Drag to reorder</span>
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
                    <strong>Draft Mode:</strong> Changes are saved as draft. Use the Publish button to make them live.
                </div>
                
                <div id="sectionsList" class="sections-list">
                    <?php if (empty($sections)): ?>
                        <div class="empty-state">
                            <i class="fas fa-layer-group"></i>
                            <h3>No Sections Yet</h3>
                            <p>Add your first section from the toolbox on the right.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections as $section): ?>
                            <div class="section-item <?php echo !$section->is_visible ? 'inactive' : ''; ?>" 
                                 data-id="<?php echo $section->id; ?>"
                                 data-type="<?php echo $section->section_type; ?>">
                                <div class="section-header">
                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                    
                                    <div class="section-icon">
                                        <i class="<?php echo $section->getIcon(); ?>"></i>
                                    </div>
                                    
                                    <div class="section-info">
                                        <div class="section-title">
                                            <?php echo htmlspecialchars($section->title ?: $section->getSectionTypeLabel()); ?>
                                        </div>
                                        <div class="section-type">
                                            <i class="<?php echo $section->getIcon(); ?>"></i>
                                            <?php echo $section->getSectionTypeLabel(); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="section-status">
                                        <div class="status-toggle <?php echo $section->is_visible ? 'active' : ''; ?>"
                                             onclick="toggleSection(<?php echo $section->id; ?>)"></div>
                                    </div>
                                    
                                    <div class="section-actions">
                                        <button class="section-action edit" onclick="editSection(<?php echo $section->id; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="section-action clone" onclick="cloneSection(<?php echo $section->id; ?>)">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="section-action delete" onclick="deleteSection(<?php echo $section->id; ?>, '<?php echo htmlspecialchars(addslashes($section->title ?: $section->getSectionTypeLabel())); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="section-preview">
                                    <?php echo $section->getSectionTypeLabel(); ?> configuration
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Toolbox Panel -->
            <div class="toolbox-panel">
                <!-- Add Section -->
                <div class="toolbox-section">
                    <h3>Add Section</h3>
                    <div class="section-types">
                        <?php foreach ($availableTypes as $type => $label): ?>
                            <div class="section-type-btn" onclick="addSection('<?php echo $type; ?>')">
                                <?php
                                $tempSection = new \App\Models\HomeSection();
                                $tempSection->section_type = $type;
                                ?>
                                <i class="<?php echo $tempSection->getIcon(); ?>"></i>
                                <span><?php echo $label; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="toolbox-section">
                    <h3>Actions</h3>
                    <div class="action-buttons">
                        <button class="btn-action btn-primary" onclick="previewHome()">
                            <i class="fas fa-eye"></i>
                            Preview Home Page
                        </button>
                        
                        <button class="btn-action btn-success" onclick="publishHome()">
                            <i class="fas fa-check-circle"></i>
                            Publish Changes
                        </button>
                        
                        <button class="btn-action btn-warning" onclick="discardChanges()">
                            <i class="fas fa-undo"></i>
                            Discard Draft
                        </button>
                        
                        <button class="btn-action btn-outline-primary" onclick="viewLiveHome()">
                            <i class="fas fa-external-link-alt"></i>
                            View Live Home
                        </button>
                    </div>
                </div>
                
                <!-- Tips -->
                <div class="toolbox-section">
                    <h3>Tips</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-arrows-alt text-primary"></i> Drag sections to reorder</li>
                        <li class="mb-2"><i class="fas fa-toggle-on text-success"></i> Toggle visibility on/off</li>
                        <li class="mb-2"><i class="fas fa-copy text-info"></i> Clone sections to reuse</li>
                        <li class="mb-2"><i class="fas fa-save text-warning"></i> Changes auto-save to draft</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section Configuration Modal -->
    <div class="modal fade" id="sectionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Configure Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body" id="modalBody">
                    <!-- Content loaded dynamically -->
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Loading configuration form...</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSection()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CSRF Token for AJAX -->
    <input type="hidden" id="csrfToken" value="<?php echo htmlspecialchars($csrfToken); ?>">
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Initialize Sortable
        const sectionsList = document.getElementById('sectionsList');
        
        new Sortable(sectionsList, {
            animation: 150,
            handle: '.drag-handle',
            draggable: '.section-item',
            onEnd: function() {
                saveOrder();
            }
        });
        
        // Modal instance
        const sectionModal = new bootstrap.Modal(document.getElementById('sectionModal'));
        let currentSectionId = null;
        let currentEditor = null;
        
        // Save section order
        function saveOrder() {
            const order = [];
            document.querySelectorAll('.section-item').forEach(item => {
                order.push(item.dataset.id);
            });
            
            fetch('/admin/home/reorder.php', {
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
                    // Show subtle success indicator
                } else {
                    Swal.fire('Error', data.error || 'Failed to save order', 'error');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Add new section
        function addSection(type) {
            Swal.fire({
                title: 'Add Section',
                html: 'Enter a title for this section (optional):',
                input: 'text',
                inputPlaceholder: 'Section title',
                showCancelButton: true,
                confirmButtonText: 'Add',
                preConfirm: (title) => {
                    return fetch('/admin/home/add-section.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        },
                        body: JSON.stringify({ type: type, title: title })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to add section');
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
        
        // Edit section
        function editSection(id) {
            currentSectionId = id;
            
            // Show modal with loading indicator
            document.getElementById('modalBody').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading configuration form...</p>
                </div>
            `;
            sectionModal.show();
            
            // Load section data
            fetch('/admin/home/get-section.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        Swal.fire('Error', data.error, 'error');
                        sectionModal.hide();
                        return;
                    }
                    
                    // Load configuration form based on section type
                    loadConfigForm(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to load section data', 'error');
                    sectionModal.hide();
                });
        }
        
        // Load configuration form based on section type
        function loadConfigForm(data) {
            const type = data.section_type;
            const config = data.config;
            
            let formHtml = '';
            
            switch(type) {
                case 'hero_banner':
                    formHtml = getHeroBannerForm(data);
                    break;
                case 'featured_blocks':
                    formHtml = getFeaturedBlocksForm(data);
                    break;
                case 'latest_pages':
                    formHtml = getLatestPagesForm(data);
                    break;
                case 'photo_gallery_preview':
                    formHtml = getPhotoGalleryForm(data);
                    break;
                case 'video_gallery_preview':
                    formHtml = getVideoGalleryForm(data);
                    break;
                case 'custom_html':
                    formHtml = getCustomHtmlForm(data);
                    break;
                case 'contact_bar':
                    formHtml = getContactBarForm(data);
                    break;
                case 'stats_bar':
                    formHtml = getStatsBarForm(data);
                    break;
                case 'testimonials':
                    formHtml = getTestimonialsForm(data);
                    break;
                case 'cta_banner':
                    formHtml = getCtaBannerForm(data);
                    break;
                default:
                    formHtml = '<p class="text-danger">Unknown section type</p>';
            }
            
            document.getElementById('modalBody').innerHTML = formHtml;
            
            // Initialize CodeMirror for custom HTML sections
            if (type === 'custom_html') {
                initCodeMirror();
            }
        }
        
        // Save section changes
        function saveSection() {
            const form = document.getElementById('sectionConfigForm');
            const formData = new FormData(form);
            const config = {};
            
            for (let [key, value] of formData.entries()) {
                // Handle nested keys (e.g., blocks[0][title])
                if (key.includes('[')) {
                    const matches = key.match(/([^[]+)\[(\d+)\]\[([^)]+)\]/);
                    if (matches) {
                        const prefix = matches[1];
                        const index = matches[2];
                        const field = matches[3];
                        
                        if (!config[prefix]) config[prefix] = [];
                        if (!config[prefix][index]) config[prefix][index] = {};
                        config[prefix][index][field] = value;
                    }
                } else {
                    config[key] = value;
                }
            }
            
            // Handle checkboxes
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                if (!checkbox.checked) {
                    config[checkbox.name] = false;
                }
            });
            
            // Get CodeMirror content for custom HTML
            if (currentEditor) {
                config.html = currentEditor.getValue();
            }
            
            fetch('/admin/home/update-section.php?id=' + currentSectionId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify({ config: config })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    sectionModal.hide();
                    location.reload();
                } else {
                    Swal.fire('Error', data.error || 'Failed to save changes', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to save changes', 'error');
            });
        }
        
        // Toggle section visibility
        function toggleSection(id) {
            fetch('/admin/home/update-section.php?id=' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.getElementById('csrfToken').value
                },
                body: JSON.stringify({ is_visible: false }) // Will be toggled by server
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        // Clone section
        function cloneSection(id) {
            Swal.fire({
                title: 'Clone Section',
                text: 'Create a copy of this section?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Clone'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Implement clone functionality
                    location.reload();
                }
            });
        }
        
        // Delete section
        function deleteSection(id, title) {
            Swal.fire({
                title: 'Delete Section',
                html: `Are you sure you want to delete "<strong>${title}</strong>"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/home/delete-section.php?id=' + id, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            Swal.fire('Error', data.error || 'Failed to delete section', 'error');
                        }
                    });
                }
            });
        }
        
        // Preview home page
        function previewHome() {
            const token = '<?php echo md5('home_preview_' . date('Y-m-d')); ?>';
            window.open('/admin/home/preview.php?token=' + token, '_blank');
        }
        
        // Publish changes
        function publishHome() {
            Swal.fire({
                title: 'Publish Home Page',
                text: 'This will make your draft changes live on the website. Continue?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Publish'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/home/publish.php', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-Token': document.getElementById('csrfToken').value
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success', 'Home page published successfully!', 'success');
                        } else {
                            Swal.fire('Error', data.error || 'Failed to publish', 'error');
                        }
                    });
                }
            });
        }
        
        // Discard draft changes
        function discardChanges() {
            Swal.fire({
                title: 'Discard Changes',
                text: 'This will revert all draft changes to the last published version. Continue?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Discard'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/admin/home/discard.php', {
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
                            Swal.fire('Error', data.error || 'Failed to discard changes', 'error');
                        }
                    });
                }
            });
        }
        
        // View live home page
        function viewLiveHome() {
            window.open('/', '_blank');
        }
        
        // Initialize CodeMirror for custom HTML
        function initCodeMirror() {
            const textarea = document.getElementById('customHtml');
            if (textarea) {
                currentEditor = CodeMirror.fromTextArea(textarea, {
                    mode: 'htmlmixed',
                    theme: 'monokai',
                    lineNumbers: true,
                    lineWrapping: true,
                    autoCloseTags: true,
                    autoCloseBrackets: true
                });
            }
        }
        
        // Form generators for each section type
        function getHeroBannerForm(data) {
            const config = data.config;
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Headline</label>
                        <input type="text" class="form-control" name="headline" value="${config.headline || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subheadline</label>
                        <textarea class="form-control" name="subheadline" rows="2">${config.subheadline || ''}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Background Image</label>
                        <input type="text" class="form-control" name="background_image" value="${config.background_image || ''}" placeholder="/uploads/image.jpg">
                        <small class="text-muted">Path to background image (optional)</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control" name="background_color" value="${config.background_color || '#667eea'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Text Color</label>
                                <input type="color" class="form-control" name="text_color" value="${config.text_color || '#ffffff'}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Overlay Opacity (0-1)</label>
                        <input type="number" class="form-control" name="overlay_opacity" step="0.1" min="0" max="1" value="${config.overlay_opacity || 0.5}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Height</label>
                        <input type="text" class="form-control" name="height" value="${config.height || '500px'}" placeholder="500px or 100vh">
                    </div>
                    
                    <h4 class="mt-4">Primary Button</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_primary_text" value="${config.button_primary_text || 'Get Started'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="button_primary_link" value="${config.button_primary_link || '#'}">
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mt-4">Secondary Button</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_secondary_text" value="${config.button_secondary_text || 'Learn More'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="button_secondary_link" value="${config.button_secondary_link || '#'}">
                            </div>
                        </div>
                    </div>
                </form>
            `;
        }
        
        function getFeaturedBlocksForm(data) {
            const config = data.config;
            let blocksHtml = '';
            
            if (config.blocks) {
                config.blocks.forEach((block, index) => {
                    blocksHtml += `
                        <div class="card mb-3 p-3">
                            <h5>Block ${index + 1}</h5>
                            <div class="form-group">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="blocks[${index}][title]" value="${block.title || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="blocks[${index}][description]">${block.description || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Icon Class</label>
                                <input type="text" class="form-control" name="blocks[${index}][icon]" value="${block.icon || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="blocks[${index}][button_text]" value="${block.button_text || 'Read More'}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="blocks[${index}][link]" value="${block.link || '#'}">
                            </div>
                        </div>
                    `;
                });
            }
            
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Number of Columns</label>
                        <select class="form-control" name="columns">
                            <option value="2" ${config.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${config.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${config.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control" name="background_color" value="${config.background_color || '#ffffff'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Text Color</label>
                                <input type="color" class="form-control" name="text_color" value="${config.text_color || '#333333'}">
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mt-4">Featured Blocks</h4>
                    ${blocksHtml}
                    
                    <button type="button" class="btn btn-sm btn-primary" onclick="addBlock()">
                        <i class="fas fa-plus"></i> Add Block
                    </button>
                </form>
            `;
        }
        
        function getLatestPagesForm(data) {
            const config = data.config;
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Number of Pages to Show</label>
                        <input type="number" class="form-control" name="count" min="1" max="12" value="${config.count || 3}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <select class="form-control" name="layout">
                            <option value="grid" ${config.layout == 'grid' ? 'selected' : ''}>Grid</option>
                            <option value="list" ${config.layout == 'list' ? 'selected' : ''}>List</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Columns (for grid layout)</label>
                        <select class="form-control" name="columns">
                            <option value="2" ${config.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${config.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${config.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_excerpt" id="showExcerpt" ${config.show_excerpt ? 'checked' : ''}>
                        <label class="form-check-label" for="showExcerpt">Show Excerpt</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_date" id="showDate" ${config.show_date ? 'checked' : ''}>
                        <label class="form-check-label" for="showDate">Show Date</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_author" id="showAuthor" ${config.show_author ? 'checked' : ''}>
                        <label class="form-check-label" for="showAuthor">Show Author</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_featured_image" id="showFeaturedImage" ${config.show_featured_image ? 'checked' : ''}>
                        <label class="form-check-label" for="showFeaturedImage">Show Featured Image</label>
                    </div>
                </form>
            `;
        }
        
        function getPhotoGalleryForm(data) {
            const config = data.config;
            let galleryOptions = '<option value="">Select a gallery...</option>';
            
            <?php foreach ($photoGalleries as $gallery): ?>
                galleryOptions += `<option value="<?php echo $gallery->id; ?>" ${config.gallery_id == <?php echo $gallery->id; ?> ? 'selected' : ''}>${escapeHtml('<?php echo addslashes($gallery->name); ?>')}</option>`;
            <?php endforeach; ?>
            
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Select Gallery</label>
                        <select class="form-control" name="gallery_id">
                            ${galleryOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Number of Thumbnails to Show</label>
                        <input type="number" class="form-control" name="thumbnail_count" min="1" max="12" value="${config.thumbnail_count || 6}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <select class="form-control" name="layout">
                            <option value="grid" ${config.layout == 'grid' ? 'selected' : ''}>Grid</option>
                            <option value="carousel" ${config.layout == 'carousel' ? 'selected' : ''}>Carousel</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Columns (for grid layout)</label>
                        <select class="form-control" name="columns">
                            <option value="2" ${config.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${config.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${config.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_title" id="showTitle" ${config.show_title ? 'checked' : ''}>
                        <label class="form-check-label" for="showTitle">Show Gallery Title</label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Link Text</label>
                        <input type="text" class="form-control" name="link_text" value="${config.link_text || 'View All Photos'}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Link URL</label>
                        <input type="text" class="form-control" name="link_url" value="${config.link_url || '/galleries/photo'}">
                    </div>
                </form>
            `;
        }
        
        function getVideoGalleryForm(data) {
            const config = data.config;
            let galleryOptions = '<option value="">Select a gallery...</option>';
            
            <?php foreach ($videoGalleries as $gallery): ?>
                galleryOptions += `<option value="<?php echo $gallery->id; ?>" ${config.gallery_id == <?php echo $gallery->id; ?> ? 'selected' : ''}>${escapeHtml('<?php echo addslashes($gallery->name); ?>')}</option>`;
            <?php endforeach; ?>
            
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Select Gallery</label>
                        <select class="form-control" name="gallery_id">
                            ${galleryOptions}
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Number of Videos to Show</label>
                        <input type="number" class="form-control" name="video_count" min="1" max="12" value="${config.video_count || 3}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <select class="form-control" name="layout">
                            <option value="grid" ${config.layout == 'grid' ? 'selected' : ''}>Grid</option>
                            <option value="carousel" ${config.layout == 'carousel' ? 'selected' : ''}>Carousel</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Columns (for grid layout)</label>
                        <select class="form-control" name="columns">
                            <option value="2" ${config.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${config.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${config.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_title" id="showTitle" ${config.show_title ? 'checked' : ''}>
                        <label class="form-check-label" for="showTitle">Show Video Title</label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Link Text</label>
                        <input type="text" class="form-control" name="link_text" value="${config.link_text || 'View All Videos'}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Link URL</label>
                        <input type="text" class="form-control" name="link_url" value="${config.link_url || '/galleries/video'}">
                    </div>
                </form>
            `;
        }
        
        function getCustomHtmlForm(data) {
            const config = data.config;
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">HTML Content</label>
                        <textarea class="form-control" id="customHtml" name="html" rows="10">${escapeHtml(config.html || '')}</textarea>
                        <small class="text-muted">You can use HTML, CSS, and JavaScript</small>
                    </div>
                </form>
            `;
        }
        
        function getContactBarForm(data) {
            const config = data.config;
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" value="${config.address || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="${config.phone || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="${config.email || ''}">
                    </div>
                    
                    <h4 class="mt-4">Social Media</h4>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_social" id="showSocial" ${config.show_social ? 'checked' : ''}>
                        <label class="form-check-label" for="showSocial">Show Social Media Icons</label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Facebook URL</label>
                        <input type="url" class="form-control" name="social_facebook" value="${config.social_facebook || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Twitter URL</label>
                        <input type="url" class="form-control" name="social_twitter" value="${config.social_twitter || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" class="form-control" name="social_instagram" value="${config.social_instagram || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="url" class="form-control" name="social_linkedin" value="${config.social_linkedin || ''}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control" name="background_color" value="${config.background_color || '#f8f9fa'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Text Color</label>
                                <input type="color" class="form-control" name="text_color" value="${config.text_color || '#333333'}">
                            </div>
                        </div>
                    </div>
                </form>
            `;
        }
        
        function getStatsBarForm(data) {
            const config = data.config;
            let statsHtml = '';
            
            if (config.stats) {
                config.stats.forEach((stat, index) => {
                    statsHtml += `
                        <div class="card mb-3 p-3">
                            <h5>Stat ${index + 1}</h5>
                            <div class="form-group">
                                <label class="form-label">Label</label>
                                <input type="text" class="form-control" name="stats[${index}][label]" value="${stat.label || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Value</label>
                                <input type="text" class="form-control" name="stats[${index}][value]" value="${stat.value || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Icon Class</label>
                                <input type="text" class="form-control" name="stats[${index}][icon]" value="${stat.icon || ''}">
                            </div>
                        </div>
                    `;
                });
            }
            
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Number of Columns</label>
                        <select class="form-control" name="columns">
                            <option value="2" ${config.columns == 2 ? 'selected' : ''}>2 Columns</option>
                            <option value="3" ${config.columns == 3 ? 'selected' : ''}>3 Columns</option>
                            <option value="4" ${config.columns == 4 ? 'selected' : ''}>4 Columns</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control" name="background_color" value="${config.background_color || '#667eea'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Text Color</label>
                                <input type="color" class="form-control" name="text_color" value="${config.text_color || '#ffffff'}">
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mt-4">Statistics</h4>
                    ${statsHtml}
                    
                    <button type="button" class="btn btn-sm btn-primary" onclick="addStat()">
                        <i class="fas fa-plus"></i> Add Stat
                    </button>
                </form>
            `;
        }
        
        function getTestimonialsForm(data) {
            const config = data.config;
            let testimonialsHtml = '';
            
            if (config.testimonials) {
                config.testimonials.forEach((testimonial, index) => {
                    testimonialsHtml += `
                        <div class="card mb-3 p-3">
                            <h5>Testimonial ${index + 1}</h5>
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="testimonials[${index}][name]" value="${testimonial.name || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" name="testimonials[${index}][position]" value="${testimonial.position || ''}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Content</label>
                                <textarea class="form-control" name="testimonials[${index}][content]">${testimonial.content || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Rating (1-5)</label>
                                <input type="number" class="form-control" name="testimonials[${index}][rating]" min="1" max="5" value="${testimonial.rating || 5}">
                            </div>
                        </div>
                    `;
                });
            }
            
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="autoplay" id="autoplay" ${config.autoplay ? 'checked' : ''}>
                        <label class="form-check-label" for="autoplay">Autoplay Carousel</label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Autoplay Speed (ms)</label>
                        <input type="number" class="form-control" name="autoplay_speed" min="1000" step="500" value="${config.autoplay_speed || 5000}">
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_arrows" id="showArrows" ${config.show_arrows ? 'checked' : ''}>
                        <label class="form-check-label" for="showArrows">Show Navigation Arrows</label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="show_dots" id="showDots" ${config.show_dots ? 'checked' : ''}>
                        <label class="form-check-label" for="showDots">Show Navigation Dots</label>
                    </div>
                    
                    <h4 class="mt-4">Testimonials</h4>
                    ${testimonialsHtml}
                    
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTestimonial()">
                        <i class="fas fa-plus"></i> Add Testimonial
                    </button>
                </form>
            `;
        }
        
        function getCtaBannerForm(data) {
            const config = data.config;
            return `
                <form id="sectionConfigForm">
                    <div class="form-group">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" value="${data.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Main Title</label>
                        <input type="text" class="form-control" name="title" value="${config.title || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2">${config.description || ''}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Button Text</label>
                                <input type="text" class="form-control" name="button_text" value="${config.button_text || 'Get Started'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Button Link</label>
                                <input type="text" class="form-control" name="button_link" value="${config.button_link || '#'}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Button Style</label>
                        <select class="form-control" name="button_style">
                            <option value="primary" ${config.button_style == 'primary' ? 'selected' : ''}>Primary</option>
                            <option value="secondary" ${config.button_style == 'secondary' ? 'selected' : ''}>Secondary</option>
                            <option value="outline" ${config.button_style == 'outline' ? 'selected' : ''}>Outline</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Alignment</label>
                        <select class="form-control" name="alignment">
                            <option value="left" ${config.alignment == 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${config.alignment == 'center' ? 'selected' : ''}>Center</option>
                            <option value="right" ${config.alignment == 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Background Image</label>
                        <input type="text" class="form-control" name="background_image" value="${config.background_image || ''}" placeholder="/uploads/image.jpg">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control" name="background_color" value="${config.background_color || '#667eea'}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Text Color</label>
                                <input type="color" class="form-control" name="text_color" value="${config.text_color || '#ffffff'}">
                            </div>
                        </div>
                    </div>
                </form>
            `;
        }
        
        // Helper function to escape HTML
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        // Dynamic form functions
        function addBlock() {
            // Implementation for adding a new block to featured blocks
        }
        
        function addStat() {
            // Implementation for adding a new stat to stats bar
        }
        
        function addTestimonial() {
            // Implementation for adding a new testimonial
        }
    </script>
</body>
</html>