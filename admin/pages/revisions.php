<?php
// admin/pages/revisions.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\Page;
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

// Get page ID
$id = (int)($_GET['id'] ?? 0);
$page = Page::find($id);

if (!$page) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Check permission
if (!$user->hasPermission('pages.write') && $page->author_id !== $user->id) {
    $_SESSION['error'] = 'You do not have permission to view revisions for this page.';
    header('Location: /admin/pages/');
    exit;
}

// Get revisions with author info
$revisions = $page->revisions();
foreach ($revisions as $revision) {
    $revision->author = $revision->author();
}

// Compare revisions if requested
$compare = isset($_GET['compare']);
$rev1Id = (int)($_GET['rev1'] ?? 0);
$rev2Id = (int)($_GET['rev2'] ?? 0);

$rev1 = null;
$rev2 = null;

if ($compare && $rev1Id && $rev2Id) {
    foreach ($revisions as $revision) {
        if ($revision->id == $rev1Id) $rev1 = $revision;
        if ($revision->id == $rev2Id) $rev2 = $revision;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Revisions: <?php echo htmlspecialchars($page->title); ?> - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Diff2Html CSS -->
    <link href="https://cdn.jsdelivr.net/npm/diff2html@3.4.45/bundles/css/diff2html.min.css" rel="stylesheet">
    
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
        
        /* Revisions */
        .revisions-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .revisions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .revisions-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .revision-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .revision-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
            background: white;
        }
        
        .revision-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .revision-item.selected {
            border-color: var(--primary-color);
            background: #f8f9ff;
        }
        
        .revision-checkbox {
            margin-right: 20px;
        }
        
        .revision-checkbox input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .revision-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 20px;
        }
        
        .revision-content {
            flex: 1;
        }
        
        .revision-number {
            font-weight: 600;
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 16px;
        }
        
        .revision-title {
            font-weight: 500;
            color: #333;
        }
        
        .revision-meta {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .revision-meta i {
            margin-right: 3px;
            width: 16px;
        }
        
        .revision-note {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            display: inline-block;
        }
        
        .revision-actions {
            display: flex;
            gap: 10px;
        }
        
        .badge-current {
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Compare panel */
        .compare-panel {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .compare-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .compare-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .compare-selectors {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .compare-selector {
            flex: 1;
        }
        
        .compare-selector label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            display: block;
        }
        
        .compare-versus {
            font-size: 24px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .compare-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .compare-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .compare-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Diff view */
        .diff-view {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
        }
        
        .diff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .diff-title {
            font-weight: 600;
            color: #333;
        }
        
        .diff-meta {
            font-size: 13px;
            color: #666;
        }
        
        .diff-stats {
            display: flex;
            gap: 20px;
        }
        
        .diff-stat {
            text-align: center;
        }
        
        .diff-stat .count {
            font-size: 20px;
            font-weight: 600;
            display: block;
        }
        
        .diff-stat .label {
            font-size: 12px;
            color: #666;
        }
        
        .stat-insert {
            color: #28a745;
        }
        
        .stat-delete {
            color: #dc3545;
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
            
            .compare-selectors {
                flex-direction: column;
                gap: 10px;
            }
            
            .compare-versus {
                display: none;
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
                <h1>Page Revisions: <?php echo htmlspecialchars($page->title); ?></h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/pages/" class="breadcrumb-item">Pages</a>
                    <a href="/admin/pages/edit.php?id=<?php echo $page->id; ?>" class="breadcrumb-item">
                        <?php echo htmlspecialchars($page->title); ?>
                    </a>
                    <span class="breadcrumb-item active">Revisions</span>
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
        
        <!-- Revisions Content -->
        <div class="revisions-container">
            <!-- Compare Panel -->
            <div class="compare-panel">
                <div class="compare-header">
                    <h3>Compare Revisions</h3>
                    <span class="text-muted">Select two revisions to compare</span>
                </div>
                
                <form method="GET" action="">
                    <input type="hidden" name="id" value="<?php echo $page->id; ?>">
                    
                    <div class="compare-selectors">
                        <div class="compare-selector">
                            <label>Revision A (older)</label>
                            <select class="form-select" name="rev1" id="rev1" required>
                                <option value="">Select revision...</option>
                                <?php foreach ($revisions as $rev): ?>
                                    <option value="<?php echo $rev->id; ?>" <?php echo $rev1Id == $rev->id ? 'selected' : ''; ?>>
                                        #<?php echo $rev->revision_number; ?> - <?php echo date('M j, Y H:i', strtotime($rev->created_at)); ?> 
                                        (<?php echo htmlspecialchars($rev->author ? $rev->author->username : 'Unknown'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="compare-versus">VS</div>
                        
                        <div class="compare-selector">
                            <label>Revision B (newer)</label>
                            <select class="form-select" name="rev2" id="rev2" required>
                                <option value="">Select revision...</option>
                                <?php foreach ($revisions as $rev): ?>
                                    <option value="<?php echo $rev->id; ?>" <?php echo $rev2Id == $rev->id ? 'selected' : ''; ?>>
                                        #<?php echo $rev->revision_number; ?> - <?php echo date('M j, Y H:i', strtotime($rev->created_at)); ?> 
                                        (<?php echo htmlspecialchars($rev->author ? $rev->author->username : 'Unknown'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="compare" value="1" class="compare-btn" 
                                <?php echo empty($revisions) ? 'disabled' : ''; ?>>
                            <i class="fas fa-code-compare"></i>
                            Compare Revisions
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if ($compare && $rev1 && $rev2): ?>
                <!-- Diff View -->
                <div class="diff-view">
                    <div class="diff-header">
                        <div>
                            <div class="diff-title">
                                Comparing #<?php echo $rev1->revision_number; ?> (older) with #<?php echo $rev2->revision_number; ?> (newer)
                            </div>
                            <div class="diff-meta">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($rev1->author ? $rev1->author->username : 'Unknown'); ?> 
                                vs <?php echo htmlspecialchars($rev2->author ? $rev2->author->username : 'Unknown'); ?>
                                <i class="fas fa-clock ms-3"></i> 
                                <?php echo date('M j, Y H:i', strtotime($rev1->created_at)); ?> 
                                vs <?php echo date('M j, Y H:i', strtotime($rev2->created_at)); ?>
                            </div>
                        </div>
                        
                        <div class="diff-stats">
                            <div class="diff-stat">
                                <span class="count stat-insert" id="insertCount">0</span>
                                <span class="label">Additions</span>
                            </div>
                            <div class="diff-stat">
                                <span class="count stat-delete" id="deleteCount">0</span>
                                <span class="label">Deletions</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Title Diff -->
                    <?php if ($rev1->title !== $rev2->title): ?>
                        <div class="alert alert-info mb-3">
                            <strong>Title changed:</strong><br>
                            <span class="text-danger">- <?php echo htmlspecialchars($rev1->title); ?></span><br>
                            <span class="text-success">+ <?php echo htmlspecialchars($rev2->title); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content Diff -->
                    <div id="contentDiff"></div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/diff@5.1.0/lib/index.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/diff2html@3.4.45/bundles/js/diff2html.min.js"></script>
                    
                    <script>
                        // Generate diff
                        const oldContent = <?php echo json_encode($rev1->content); ?>;
                        const newContent = <?php echo json_encode($rev2->content); ?>;
                        
                        const diff = Diff.createTwoFilesPatch(
                            'Revision #<?php echo $rev1->revision_number; ?>',
                            'Revision #<?php echo $rev2->revision_number; ?>',
                            oldContent,
                            newContent
                        );
                        
                        const targetElement = document.getElementById('contentDiff');
                        const configuration = {
                            drawFileList: false,
                            matching: 'lines',
                            outputFormat: 'side-by-side',
                            renderNothingWhenEmpty: true
                        };
                        
                        const html = Diff2Html.html(diff, configuration);
                        targetElement.innerHTML = html;
                        
                        // Update stats
                        const insertCount = (html.match(/class="d2h-ins"/g) || []).length;
                        const deleteCount = (html.match(/class="d2h-del"/g) || []).length;
                        
                        document.getElementById('insertCount').textContent = insertCount;
                        document.getElementById('deleteCount').textContent = deleteCount;
                    </script>
                </div>
            <?php endif; ?>
            
            <!-- Revision List -->
            <div class="revisions-header">
                <h2>All Revisions (<?php echo count($revisions); ?>)</h2>
                <a href="/admin/pages/edit.php?id=<?php echo $page->id; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Edit
                </a>
            </div>
            
            <?php if (empty($revisions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No revisions yet.</p>
                </div>
            <?php else: ?>
                <ul class="revision-list">
                    <?php foreach ($revisions as $index => $revision): ?>
                        <li class="revision-item <?php echo ($rev1Id == $revision->id || $rev2Id == $revision->id) ? 'selected' : ''; ?>">
                            <div class="revision-checkbox">
                                <input type="checkbox" class="revision-select" 
                                       value="<?php echo $revision->id; ?>"
                                       <?php echo ($rev1Id == $revision->id || $rev2Id == $revision->id) ? 'checked' : ''; ?>>
                            </div>
                            
                            <img src="<?php echo htmlspecialchars($revision->author ? $revision->author->getAvatarUrl() : '/admin/assets/img/default-avatar.png'); ?>" 
                                 alt="" class="revision-avatar">
                            
                            <div class="revision-content">
                                <div>
                                    <span class="revision-number">#<?php echo $revision->revision_number; ?></span>
                                    <span class="revision-title"><?php echo htmlspecialchars($revision->title); ?></span>
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
                            
                            <div class="revision-actions">
                                <?php if ($index === 0): ?>
                                    <span class="badge-current">Current</span>
                                <?php else: ?>
                                    <a href="/admin/pages/restore-revision.php?page_id=<?php echo $page->id; ?>&revision_id=<?php echo $revision->id; ?>&token=<?php echo $csrf->generate('revision_restore_' . $revision->id); ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       onclick="return confirm('Restore this revision? Current content will be saved as a new revision.')">
                                        <i class="fas fa-undo"></i>
                                        Restore
                                    </a>
                                <?php endif; ?>
                                
                                <a href="?id=<?php echo $page->id; ?>&compare=1&rev1=<?php echo $revision->id; ?>&rev2=<?php echo $revisions[0]->id; ?>" 
                                   class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-code-compare"></i>
                                    Compare with current
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Handle revision selection for comparison
        const checkboxes = document.querySelectorAll('.revision-select');
        const rev1Select = document.getElementById('rev1');
        const rev2Select = document.getElementById('rev2');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selected = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value)
                    .sort((a, b) => a - b); // Sort by ID (older first)
                
                if (selected.length > 2) {
                    this.checked = false;
                    alert('You can only select up to 2 revisions for comparison.');
                    return;
                }
                
                if (selected.length >= 1) {
                    rev1Select.value = selected[0];
                } else {
                    rev1Select.value = '';
                }
                
                if (selected.length >= 2) {
                    rev2Select.value = selected[1];
                } else {
                    rev2Select.value = '';
                }
            });
        });
    </script>
</body>
</html>