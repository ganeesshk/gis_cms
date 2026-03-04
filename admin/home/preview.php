<?php
// admin/home/preview.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\HomeController;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Initialize controller and get preview
$controller = new HomeController($auth, $csrf, $config);
$result = $controller->preview();
extract($result['data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page Preview</title>
    
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
            text-decoration: none;
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
        
        /* Preview container */
        .preview-container {
            transition: all 0.3s ease;
            margin: 0 auto;
            background: #f8f9fa;
        }
        
        .preview-container.desktop {
            max-width: 1200px;
        }
        
        .preview-container.tablet {
            max-width: 768px;
        }
        
        .preview-container.mobile {
            max-width: 375px;
        }
        
        /* Section highlight */
        .section-highlight {
            position: relative;
        }
        
        .section-highlight:hover {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        
        .section-info {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 100;
            display: none;
        }
        
        .section-highlight:hover .section-info {
            display: block;
        }
        
        /* Loading indicator */
        .preview-loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 2000;
            display: none;
        }
        
        /* Section styles (these would normally be in separate CSS files) */
        .hero-banner {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            padding: 0 20px;
        }
        
        .featured-block {
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .featured-block:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .testimonial-card {
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin: 10px;
        }
        
        .rating {
            color: #ffc107;
        }
        
        .cta-banner {
            padding: 60px 0;
            text-align: center;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .social-link:hover {
            transform: translateY(-3px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .preview-bar .container {
                flex-direction: column;
                gap: 10px;
            }
            
            .contact-info {
                flex-direction: column;
                align-items: center;
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
                    <i class="fas fa-home"></i>
                    Home Page Preview
                </span>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="device-preview">
                    <button class="device-btn active" onclick="setDevice('desktop')" title="Desktop">
                        <i class="fas fa-desktop"></i>
                    </button>
                    <button class="device-btn" onclick="setDevice('tablet')" title="Tablet">
                        <i class="fas fa-tablet-alt"></i>
                    </button>
                    <button class="device-btn" onclick="setDevice('mobile')" title="Mobile">
                        <i class="fas fa-mobile-alt"></i>
                    </button>
                </div>
                
                <div class="preview-actions">
                    <a href="/admin/home/" class="btn btn-edit">
                        <i class="fas fa-edit"></i>
                        Back to Editor
                    </a>
                    <button class="btn btn-close-preview" onclick="window.close()">
                        <i class="fas fa-times"></i>
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div class="preview-loading" id="loading">
        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
        <p class="mt-2 mb-0">Loading preview...</p>
    </div>
    
    <!-- Preview Container -->
    <div class="preview-container desktop" id="previewContainer">
        <?php if (empty($sections)): ?>
            <div class="container py-5 text-center">
                <i class="fas fa-layer-group fa-4x text-muted mb-3"></i>
                <h3>No Sections Added Yet</h3>
                <p class="text-muted">Add sections to your home page in the editor.</p>
                <a href="/admin/home/" class="btn btn-primary mt-3">
                    <i class="fas fa-edit"></i>
                    Go to Editor
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($sections as $section): ?>
                <div class="section-highlight position-relative">
                    <div class="section-info">
                        <i class="<?php echo $section->getIcon(); ?>"></i>
                        <?php echo htmlspecialchars($section->title ?: $section->getSectionTypeLabel()); ?>
                    </div>
                    <?php echo $section->render(); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        let currentDevice = 'desktop';
        
        function setDevice(device) {
            const container = document.getElementById('previewContainer');
            const buttons = document.querySelectorAll('.device-btn');
            
            // Remove active class from all buttons
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            event.currentTarget.classList.add('active');
            
            // Update container class
            container.className = 'preview-container ' + device;
            currentDevice = device;
            
            // Trigger resize event for any responsive components
            window.dispatchEvent(new Event('resize'));
        }
        
        // Simulate different screen sizes
        function setPreviewWidth(width) {
            const container = document.getElementById('previewContainer');
            container.style.maxWidth = width;
        }
        
        // Show loading indicator for async operations
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close preview
            if (e.key === 'Escape') {
                window.close();
            }
            
            // Ctrl+E to go to editor
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = '/admin/home/';
            }
            
            // Device shortcuts
            if (e.ctrlKey && e.shiftKey) {
                if (e.key === '1') {
                    setDevice('desktop');
                } else if (e.key === '2') {
                    setDevice('tablet');
                } else if (e.key === '3') {
                    setDevice('mobile');
                }
            }
        });
        
        // Lazy load images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
                img.src = img.dataset.src;
            });
        });
    </script>
</body>
</html>