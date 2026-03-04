<?php
// admin/media/upload.php

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

// Initialize controller
$controller = new MediaController($auth, $csrf, $config);
$result = $controller->upload();
extract($result['data']);

$maxSizeMB = $maxSize / 1048576;
$allowedExtensionsStr = implode(', ', $allowedTypes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Media - CMS Admin</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Dropzone CSS -->
    <link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet">
    
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
        
        /* Upload Container */
        .upload-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
            margin-bottom: 30px;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: var(--primary-color);
            background: #f0f2ff;
        }
        
        .upload-area i {
            font-size: 64px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .upload-area h3 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .upload-area p {
            color: #666;
            margin-bottom: 20px;
        }
        
        .upload-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }
        
        .upload-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .upload-info-item i {
            font-size: 16px;
            color: var(--primary-color);
        }
        
        /* File list */
        .file-list {
            margin-top: 30px;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
        }
        
        .file-meta {
            font-size: 12px;
            color: #666;
        }
        
        .file-progress {
            width: 200px;
            margin: 0 20px;
        }
        
        .progress {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 3px;
            transition: width 0.3s;
        }
        
        .file-status {
            width: 100px;
            text-align: right;
        }
        
        .status-success {
            color: #28a745;
        }
        
        .status-error {
            color: #dc3545;
        }
        
        .file-remove {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }
        
        .file-remove:hover {
            background: #dc3545;
            color: white;
        }
        
        /* Folder select */
        .folder-select {
            margin-bottom: 30px;
        }
        
        .folder-select label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .folder-select .form-select {
            max-width: 300px;
        }
        
        /* Upload actions */
        .upload-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
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
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            
            .upload-info {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
            
            .file-item {
                flex-wrap: wrap;
            }
            
            .file-progress {
                width: 100%;
                margin: 10px 0;
            }
            
            .file-status {
                width: auto;
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
                <h1>Upload Media</h1>
                <div class="breadcrumb">
                    <a href="/admin/dashboard.php" class="breadcrumb-item">Dashboard</a>
                    <a href="/admin/media/" class="breadcrumb-item">Media Library</a>
                    <span class="breadcrumb-item active">Upload</span>
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
        
        <!-- Upload Container -->
        <div class="upload-container">
            <!-- Folder Selection -->
            <div class="folder-select">
                <label for="folder">Upload to Folder:</label>
                <select class="form-select" id="folder">
                    <option value="/">Root</option>
                    <?php foreach ($folders as $folder): ?>
                        <?php if ($folder !== '/'): ?>
                            <option value="<?php echo htmlspecialchars($folder); ?>"><?php echo htmlspecialchars($folder); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Upload Area -->
            <div class="upload-area" id="uploadArea">
                <i class="fas fa-cloud-upload-alt"></i>
                <h3>Drag & Drop Files Here</h3>
                <p>or click to browse</p>
                
                <div class="upload-info">
                    <div class="upload-info-item">
                        <i class="fas fa-file"></i>
                        <span>Max file size: <?php echo $maxSizeMB; ?>MB</span>
                    </div>
                    <div class="upload-info-item">
                        <i class="fas fa-image"></i>
                        <span>Allowed: <?php echo $allowedExtensionsStr; ?></span>
                    </div>
                </div>
                
                <input type="file" id="fileInput" multiple style="display: none;" accept="<?php echo implode(',', array_map(function($ext) {
                    return '.' . $ext;
                }, $allowedTypes)); ?>">
            </div>
            
            <!-- CSRF Token -->
            <input type="hidden" id="csrfToken" value="<?php echo htmlspecialchars($csrfToken); ?>">
            
            <!-- File List -->
            <div class="file-list" id="fileList">
                <!-- Files will be added here dynamically -->
            </div>
            
            <!-- Upload Actions -->
            <div class="upload-actions">
                <a href="/admin/media/" class="btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
                <button class="btn-primary" id="startUploadBtn" disabled onclick="startUpload()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Start Upload
                </button>
            </div>
        </div>
    </div>
    
    <!-- Template for file item -->
    <template id="fileItemTemplate">
        <div class="file-item" data-file-id="">
            <div class="file-icon">
                <i class="fas fa-file"></i>
            </div>
            <div class="file-info">
                <div class="file-name"></div>
                <div class="file-meta"></div>
            </div>
            <div class="file-progress">
                <div class="progress">
                    <div class="progress-bar" style="width: 0%"></div>
                </div>
            </div>
            <div class="file-status">
                <span class="status-text">Pending</span>
            </div>
            <button class="file-remove" onclick="removeFile(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </template>
    
    <script>
        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const startUploadBtn = document.getElementById('startUploadBtn');
        const folderSelect = document.getElementById('folder');
        const csrfToken = document.getElementById('csrfToken').value;
        
        let filesToUpload = [];
        let uploadQueue = [];
        let uploading = false;
        
        // Click on upload area
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Drag and drop events
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            addFiles(files);
        });
        
        // File input change
        fileInput.addEventListener('change', () => {
            const files = Array.from(fileInput.files);
            addFiles(files);
            fileInput.value = ''; // Reset so same files can be selected again
        });
        
        // Add files to queue
        function addFiles(files) {
            const maxSize = <?php echo $maxSize; ?>;
            const allowedExtensions = <?php echo json_encode($allowedTypes); ?>;
            
            files.forEach(file => {
                // Check file size
                if (file.size > maxSize) {
                    showNotification(`File "${file.name}" exceeds size limit`, 'error');
                    return;
                }
                
                // Check file extension
                const extension = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(extension)) {
                    showNotification(`File type not allowed for "${file.name}"`, 'error');
                    return;
                }
                
                // Check for duplicates
                if (filesToUpload.some(f => f.name === file.name && f.size === file.size)) {
                    showNotification(`File "${file.name}" already in queue`, 'warning');
                    return;
                }
                
                filesToUpload.push(file);
                addFileToList(file);
            });
            
            updateUploadButton();
        }
        
        // Add file to list UI
        function addFileToList(file) {
            const template = document.getElementById('fileItemTemplate');
            const clone = template.content.cloneNode(true);
            const fileItem = clone.querySelector('.file-item');
            
            // Set file info
            fileItem.querySelector('.file-name').textContent = file.name;
            fileItem.querySelector('.file-meta').textContent = formatFileSize(file.size);
            
            // Set icon based on file type
            const icon = fileItem.querySelector('.file-icon i');
            if (file.type.startsWith('image/')) {
                icon.className = 'fas fa-image';
            } else if (file.type.startsWith('video/')) {
                icon.className = 'fas fa-video';
            } else if (file.type.startsWith('audio/')) {
                icon.className = 'fas fa-music';
            } else {
                icon.className = 'fas fa-file';
            }
            
            // Store file reference
            fileItem.dataset.fileName = file.name;
            fileItem.dataset.fileSize = file.size;
            fileItem.dataset.fileType = file.type;
            
            fileList.appendChild(clone);
        }
        
        // Remove file from queue
        function removeFile(button) {
            const fileItem = button.closest('.file-item');
            const fileName = fileItem.dataset.fileName;
            const fileSize = parseInt(fileItem.dataset.fileSize);
            
            // Remove from filesToUpload array
            filesToUpload = filesToUpload.filter(f => !(f.name === fileName && f.size === fileSize));
            
            // Remove from UI
            fileItem.remove();
            
            updateUploadButton();
        }
        
        // Start upload
        function startUpload() {
            if (uploading || filesToUpload.length === 0) return;
            
            uploading = true;
            startUploadBtn.disabled = true;
            
            uploadQueue = [...filesToUpload];
            uploadNext();
        }
        
        // Upload next file in queue
        function uploadNext() {
            if (uploadQueue.length === 0) {
                uploading = false;
                showNotification('All files uploaded successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/admin/media/';
                }, 2000);
                return;
            }
            
            const file = uploadQueue.shift();
            const folder = folderSelect.value;
            
            // Find file item in UI
            const fileItem = Array.from(document.querySelectorAll('.file-item')).find(item => 
                item.dataset.fileName === file.name && parseInt(item.dataset.fileSize) === file.size
            );
            
            if (!fileItem) {
                uploadNext();
                return;
            }
            
            const progressBar = fileItem.querySelector('.progress-bar');
            const statusText = fileItem.querySelector('.status-text');
            
            // Create FormData
            const formData = new FormData();
            formData.append('files[]', file);
            formData.append('folder', folder);
            formData.append('csrf_token', csrfToken);
            
            // Upload via AJAX
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });
            
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            statusText.innerHTML = '<i class="fas fa-check-circle status-success"></i> Complete';
                            
                            // Remove from filesToUpload
                            filesToUpload = filesToUpload.filter(f => !(f.name === file.name && f.size === file.size));
                            
                            // Upload next file
                            uploadNext();
                        } else {
                            throw new Error(response.error || 'Upload failed');
                        }
                    } catch (error) {
                        statusText.innerHTML = '<i class="fas fa-exclamation-circle status-error"></i> Failed';
                        showNotification(`Failed to upload ${file.name}: ${error.message}`, 'error');
                        uploadNext();
                    }
                } else {
                    statusText.innerHTML = '<i class="fas fa-exclamation-circle status-error"></i> Failed';
                    showNotification(`Failed to upload ${file.name}`, 'error');
                    uploadNext();
                }
            });
            
            xhr.addEventListener('error', () => {
                statusText.innerHTML = '<i class="fas fa-exclamation-circle status-error"></i> Failed';
                showNotification(`Failed to upload ${file.name}`, 'error');
                uploadNext();
            });
            
            statusText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            xhr.open('POST', '/admin/media/upload-handler.php');
            xhr.send(formData);
        }
        
        // Update upload button state
        function updateUploadButton() {
            startUploadBtn.disabled = filesToUpload.length === 0 || uploading;
        }
        
        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            // You can implement toast notifications here
            alert(message); // Simple alert for now
        }
        
        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            document.addEventListener(eventName, (e) => e.preventDefault());
            document.body.addEventListener(eventName, (e) => e.preventDefault());
        });
    </script>
</body>
</html>