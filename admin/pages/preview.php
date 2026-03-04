<?php
// admin/pages/preview.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\Page;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Get page ID and token
$id = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

// Find page
$page = Page::find($id);

// Verify preview token
if (!$page || md5($page->updated_at) !== $token) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Get author info
$author = $page->author();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page->title); ?> - Preview</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Preview bar */
        .preview-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .preview-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .preview-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .preview-badge i {
            margin-right: 5px;
        }
        
        .preview-actions {
            display: flex;
            gap: 10px;
        }
        
        .preview-actions .btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .btn-edit {
            background: white;
            color: #667eea;
            border: none;
        }
        
        .btn-edit:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .btn-close-preview {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .btn-close-preview:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Page content */
        .preview-content {
            padding: 40px 0;
            background: #f8f9fa;
            min-height: calc(100vh - 60px);
        }
        
        .preview-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        /* Page header */
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        
        .page-meta {
            color: #666;
            font-size: 14px;
        }
        
        .page-meta i {
            margin-right: 5px;
            color: #667eea;
        }
        
        .page-meta span {
            margin-right: 20px;
        }
        
        /* Featured image */
        .featured-image {
            margin-bottom: 30px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .featured-image img {
            width: 100%;
            height: auto;
        }
        
        /* Page content */
        .page-content {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
        }
        
        .page-content h1,
        .page-content h2,
        .page-content h3,
        .page-content h4,
        .page-content h5,
        .page-content h6 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
        }
        
        .page-content p {
            margin-bottom: 20px;
        }
        
        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .page-content blockquote {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-style: italic;
        }
        
        .page-content pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
        
        .page-content table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        
        .page-content th,
        .page-content td {
            padding: 10px;
            border: 1px solid #dee2e6;
        }
        
        .page-content th {
            background: #f8f9fa;
        }
        
        /* Tags */
        .page-tags {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .tag {
            display: inline-block;
            padding: 5px 15px;
            background: #f0f0f0;
            color: #666;
            border-radius: 20px;
            font-size: 13px;
            margin-right: 8px;
            margin-bottom: 8px;
            text-decoration: none;
        }
        
        .tag:hover {
            background: #667eea;
            color: white;
        }
        
        /* Device preview */
        .device-preview {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }
        
        .device-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .device-btn:hover,
        .device-btn.active {
            background: white;
            color: #667eea;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .preview-container {
                padding: 20px;
                margin: 0 15px;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .preview-bar .container {
                flex-direction: column;
                gap: 10px;
            }
            
            .device-preview {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Preview Bar -->
    <div class="preview-bar">
        <div class="container">
            <div class="d-flex align-items-center">
                <span class="preview-badge">
                    <i class="fas fa-eye"></i>
                    PREVIEW MODE
                </span>
                <span class="ms-3">
                    <i class="fas fa-file-alt"></i>
                    <?php echo htmlspecialchars($page->title); ?>
                    <?php if ($page->status !== 'published'): ?>
                        <span class="badge bg-warning text-dark ms-2"><?php echo ucfirst($page->status); ?></span>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="device-preview">
                    <button class="device-btn active" onclick="setPreviewWidth('100%')" title="Desktop">
                        <i class="fas fa-desktop"></i>
                    </button>
                    <button class="device-btn" onclick="setPreviewWidth('768px')" title="Tablet">
                        <i class="fas fa-tablet-alt"></i>
                    </button>
                    <button class="device-btn" onclick="setPreviewWidth('375px')" title="Mobile">
                        <i class="fas fa-mobile-alt"></i>
                    </button>
                </div>
                
                <div class="preview-actions">
                    <a href="/admin/pages/edit.php?id=<?php echo $page->id; ?>" class="btn btn-edit">
                        <i class="fas fa-edit"></i>
                        Edit Page
                    </a>
                    <a href="/admin/pages/" class="btn btn-close-preview">
                        <i class="fas fa-times"></i>
                        Close Preview
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview Content -->
    <div class="preview-content">
        <div class="preview-container" id="previewContainer">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title"><?php echo htmlspecialchars($page->title); ?></h1>
                
                <div class="page-meta">
                    <span>
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($author ? $author->getFullName() : 'Unknown'); ?>
                    </span>
                    <span>
                        <i class="fas fa-calendar"></i>
                        <?php echo date('F j, Y', strtotime($page->updated_at)); ?>
                    </span>
                    <?php if ($page->published_at && $page->status === 'published'): ?>
                        <span>
                            <i class="fas fa-clock"></i>
                            Published: <?php echo date('F j, Y', strtotime($page->published_at)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Featured Image -->
            <?php if ($page->featured_image_path): ?>
                <div class="featured-image">
                    <img src="<?php echo htmlspecialchars($page->featured_image_path); ?>" 
                         alt="<?php echo htmlspecialchars($page->title); ?>">
                </div>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="page-content">
                <?php echo $page->content; ?>
            </div>
            
            <!-- Tags -->
            <?php 
            $tags = $page->tags();
            if (!empty($tags)): 
            ?>
                <div class="page-tags">
                    <strong><i class="fas fa-tags"></i> Tags:</strong>
                    <?php foreach ($tags as $tag): ?>
                        <span class="tag"><?php echo htmlspecialchars($tag->name); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function setPreviewWidth(width) {
            const container = document.getElementById('previewContainer');
            container.style.width = width;
            container.style.margin = width === '100%' ? '0 auto' : '0 auto';
            
            // Update active button
            document.querySelectorAll('.device-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
        
        // Add transition for smooth width change
        document.getElementById('previewContainer').style.transition = 'width 0.3s ease';
    </script>
</body>
</html>