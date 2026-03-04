<?php
// app/Controllers/MediaController.php

namespace App\Controllers;

use App\Models\Media;
use App\Models\MediaThumbnail;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;
use App\Services\ImageService;

class MediaController
{
    private $auth;
    private $csrf;
    private $config;
    private $uploadDir;
    private $thumbSizes;

    public function __construct(AuthService $auth, CSRF $csrf, array $config)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->config = $config;
        $this->uploadDir = __DIR__ . '/../../public/uploads/';
        $this->thumbSizes = $config['uploads']['thumb_sizes'] ?? [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 400, 'height' => 300],
            'large' => ['width' => 800, 'height' => 600]
        ];
        
        // Create upload directories if they don't exist
        $this->ensureDirectories();
    }

    /**
     * List all media files
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Build query with filters
        $type = $_GET['type'] ?? 'all';
        $folder = $_GET['folder'] ?? '/';
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 24;
        $offset = ($page - 1) * $perPage;
        
        $query = Media::where(['deleted_at' => null]);
        
        if ($type !== 'all') {
            switch ($type) {
                case 'images':
                    $query->where('mime_type', 'LIKE', 'image/%');
                    break;
                case 'videos':
                    $query->where('mime_type', 'LIKE', 'video/%');
                    break;
                case 'documents':
                    $query->where('mime_type', 'LIKE', 'application/%');
                    break;
            }
        }
        
        if ($folder !== 'all') {
            $query->where('folder', '=', $folder);
        }
        
        if ($search) {
            $query->where('original_name', 'ILIKE', "%{$search}%")
                  ->orWhere('title', 'ILIKE', "%{$search}%")
                  ->orWhere('alt_text', 'ILIKE', "%{$search}%");
        }
        
        $totalFiles = $query->count();
        $files = $query->orderBy('created_at', 'DESC')
                      ->limit($perPage)
                      ->offset($offset)
                      ->get();
        
        $totalPages = ceil($totalFiles / $perPage);
        
        // Get folders for filter
        $folders = Media::getFolders();
        
        // Get usage stats
        $stats = Media::getUsageStats();
        
        return [
            'view' => 'media/index.php',
            'data' => [
                'user' => $user,
                'files' => $files,
                'folders' => $folders,
                'stats' => $stats,
                'currentType' => $type,
                'currentFolder' => $folder,
                'search' => $search,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'perPage' => $perPage,
                'totalFiles' => $totalFiles
            ]
        ];
    }

    /**
     * Show upload form
     */
    public function upload()
    {
        $user = $this->auth->getCurrentUser();
        $csrfToken = $this->csrf->generate('media_upload');
        $folders = Media::getFolders();
        
        return [
            'view' => 'media/upload.php',
            'data' => [
                'user' => $user,
                'csrfToken' => $csrfToken,
                'folders' => $folders,
                'maxSize' => $this->config['uploads']['max_size'],
                'allowedTypes' => $this->config['uploads']['allowed_extensions']
            ]
        ];
    }

    /**
     * Handle file upload
     */
    public function handleUpload()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_POST['csrf_token']) || !$this->csrf->validate($_POST['csrf_token'], 'media_upload')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Check if files were uploaded
        if (empty($_FILES['files'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No files uploaded']);
            exit;
        }
        
        $folder = $_POST['folder'] ?? '/';
        $uploaded = [];
        $errors = [];
        
        // Handle multiple files
        $files = $this->rearrangeFiles($_FILES['files']);
        
        foreach ($files as $file) {
            try {
                $result = $this->processUpload($file, $user->id, $folder);
                $uploaded[] = $result;
            } catch (\Exception $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }
        
        // Log activity
        if (!empty($uploaded)) {
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'media.upload',
                'entity_type' => 'media',
                'result' => 'success',
                'new_values' => json_encode(['count' => count($uploaded)])
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'uploaded' => $uploaded,
            'errors' => $errors
        ]);
        exit;
    }

    /**
     * Process single file upload
     */
    private function processUpload($file, $userId, $folder)
    {
        // Validate file
        $this->validateFile($file);
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        
        // Determine storage path based on file type
        $mimeType = mime_content_type($file['tmp_name']);
        $typeFolder = $this->getTypeFolder($mimeType);
        $yearMonth = date('Y/m');
        
        $relativePath = "/uploads/{$typeFolder}/{$yearMonth}";
        $fullPath = $this->uploadDir . $typeFolder . '/' . $yearMonth;
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        $destination = $fullPath . '/' . $storedName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('Failed to move uploaded file');
        }
        
        // Get image dimensions if applicable
        $width = null;
        $height = null;
        if (strpos($mimeType, 'image/') === 0) {
            list($width, $height) = getimagesize($destination);
        }
        
        // Create media record
        $media = new Media();
        $media->original_name = $file['name'];
        $media->stored_name = $storedName;
        $media->storage_path = $typeFolder . '/' . $yearMonth;
        $media->public_url = "/uploads/{$typeFolder}/{$yearMonth}/{$storedName}";
        $media->mime_type = $mimeType;
        $media->file_size = $file['size'];
        $media->width = $width;
        $media->height = $height;
        $media->folder = $folder;
        $media->uploaded_by = $userId;
        $media->save();
        
        // Generate thumbnails for images
        if (strpos($mimeType, 'image/') === 0 && !strpos($mimeType, 'svg')) {
            $this->generateThumbnails($media, $destination);
        }
        
        return [
            'id' => $media->id,
            'name' => $media->original_name,
            'url' => $media->public_url,
            'size' => $media->getFormattedSize(),
            'type' => $mimeType
        ];
    }

    /**
     * Generate thumbnails for image
     */
    private function generateThumbnails(Media $media, $sourcePath)
    {
        foreach ($this->thumbSizes as $size => $dimensions) {
            $thumbName = $this->thumbSizes[$size]['prefix'] ?? 'thumb_' . $size . '_';
            $thumbStoredName = $thumbName . $media->stored_name;
            $thumbPath = dirname($sourcePath) . '/' . $thumbStoredName;
            
            // Create thumbnail
            ImageService::resize($sourcePath, $thumbPath, $dimensions['width'], $dimensions['height'], true);
            
            // Create thumbnail record
            $thumbnail = new MediaThumbnail();
            $thumbnail->media_id = $media->id;
            $thumbnail->size_label = $size;
            $thumbnail->width = $dimensions['width'];
            $thumbnail->height = $dimensions['height'];
            $thumbnail->stored_name = $thumbStoredName;
            $thumbnail->public_url = dirname($media->public_url) . '/' . $thumbStoredName;
            $thumbnail->file_size = filesize($thumbPath);
            $thumbnail->save();
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->config['uploads']['max_size']) {
            $maxSize = $this->config['uploads']['max_size'] / 1048576;
            throw new \Exception("File exceeds maximum size of {$maxSize}MB");
        }
        
        // Check file type
        $mimeType = mime_content_type($file['tmp_name']);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm', 'video/ogg',
            'audio/mpeg', 'audio/ogg', 'audio/wav',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-zip-compressed'
        ];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception("File type not allowed: {$mimeType}");
        }
        
        // Additional security check for images
        if (strpos($mimeType, 'image/') === 0) {
            if (!getimagesize($file['tmp_name'])) {
                throw new \Exception('Invalid image file');
            }
        }
    }

    /**
     * Get type folder based on mime type
     */
    private function getTypeFolder($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'images';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'videos';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        } else {
            return 'documents';
        }
    }

    /**
     * Get user-friendly upload error message
     */
    private function getUploadErrorMessage($error)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$error] ?? 'Unknown upload error';
    }

    /**
     * Rearrange $_FILES array for multiple uploads
     */
    private function rearrangeFiles($files)
    {
        $result = [];
        
        if (is_array($files['name'])) {
            foreach ($files['name'] as $key => $name) {
                if ($files['error'][$key] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                $result[] = [
                    'name' => $name,
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
            }
        } else {
            $result[] = $files;
        }
        
        return $result;
    }

    /**
     * Edit media details
     */
    public function edit($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $media = Media::find($id);
        
        if (!$media) {
            header('HTTP/1.0 404 Not Found');
            return ['view' => 'errors/404.php', 'data' => []];
        }
        
        $csrfToken = $this->csrf->generate('media_edit_' . $id);
        $folders = Media::getFolders();
        
        return [
            'view' => 'media/edit.php',
            'data' => [
                'user' => $user,
                'media' => $media,
                'csrfToken' => $csrfToken,
                'folders' => $folders
            ]
        ];
    }

    /**
     * Update media details
     */
    public function update($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $media = Media::find($id);
        
        if (!$media) {
            $_SESSION['error'] = 'Media not found';
            header('Location: /admin/media/');
            exit;
        }
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'media_edit_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/media/edit.php?id=' . $id);
            exit;
        }
        
        // Store old values for audit
        $oldValues = $media->toArray();
        
        // Update fields
        $media->title = $_POST['title'] ?? '';
        $media->alt_text = $_POST['alt_text'] ?? '';
        $media->caption = $_POST['caption'] ?? '';
        $media->folder = $_POST['folder'] ?? '/';
        $media->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'media.update',
            'entity_type' => 'media',
            'entity_id' => $media->id,
            'entity_label' => $media->original_name,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($media->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Media updated successfully';
        header('Location: /admin/media/edit.php?id=' . $media->id);
        exit;
    }

    /**
     * Delete media
     */
    public function delete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF for AJAX
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $headers = getallheaders();
            $token = $headers['X-CSRF-Token'] ?? '';
            
            if (!$this->csrf->validate($token, 'media_delete')) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid security token']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $ids = $input['ids'] ?? [$id];
        } else {
            if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'media_delete_' . $id)) {
                $_SESSION['error'] = 'Invalid security token';
                header('Location: /admin/media/');
                exit;
            }
            $ids = [$id];
        }
        
        $deleted = [];
        $errors = [];
        
        foreach ($ids as $mediaId) {
            $media = Media::find($mediaId);
            
            if (!$media) {
                $errors[] = "Media #{$mediaId} not found";
                continue;
            }
            
            // Store for audit
            $oldValues = $media->toArray();
            $name = $media->original_name;
            
            // Delete media
            if ($media->delete()) {
                $deleted[] = $mediaId;
                
                // Log activity
                AuditLog::log([
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'action' => 'media.delete',
                    'entity_type' => 'media',
                    'entity_id' => $mediaId,
                    'entity_label' => $name,
                    'old_values' => json_encode($oldValues),
                    'result' => 'success'
                ]);
            } else {
                $errors[] = "Failed to delete {$name}";
            }
        }
        
        if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            echo json_encode([
                'success' => empty($errors),
                'deleted' => $deleted,
                'errors' => $errors
            ]);
            exit;
        } else {
            if (empty($errors)) {
                $_SESSION['success'] = 'Media deleted successfully';
            } else {
                $_SESSION['error'] = implode('<br>', $errors);
            }
            header('Location: /admin/media/');
            exit;
        }
    }

    /**
     * Get media details for modal
     */
    public function getDetails($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $media = Media::find($id);
        
        if (!$media) {
            http_response_code(404);
            echo json_encode(['error' => 'Media not found']);
            exit;
        }
        
        // Load thumbnails
        $thumbnails = $media->thumbnails();
        
        echo json_encode([
            'id' => $media->id,
            'name' => $media->original_name,
            'title' => $media->title,
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'url' => $media->public_url,
            'thumbnail' => $media->getThumbnail('medium'),
            'thumbnails' => array_map(function($t) {
                return [
                    'size' => $t->size_label,
                    'url' => $t->public_url,
                    'width' => $t->width,
                    'height' => $t->height
                ];
            }, $thumbnails),
            'type' => $media->mime_type,
            'size' => $media->getFormattedSize(),
            'dimensions' => $media->getDimensions(),
            'folder' => $media->folder,
            'uploaded_by' => $media->uploader() ? $media->uploader()->username : 'Unknown',
            'uploaded_at' => $media->created_at->format('Y-m-d H:i:s'),
            'icon' => $media->getIcon()
        ]);
        exit;
    }

    /**
     * Get media browser for page editor
     */
    public function browser()
    {
        $user = $this->auth->getCurrentUser();
        
        $type = $_GET['type'] ?? 'all';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 24;
        $offset = ($page - 1) * $perPage;
        
        $query = Media::where(['deleted_at' => null]);
        
        if ($type !== 'all') {
            if ($type === 'images') {
                $query->where('mime_type', 'LIKE', 'image/%');
            } elseif ($type === 'documents') {
                $query->where('mime_type', 'LIKE', 'application/%');
            }
        }
        
        if (!empty($_GET['search'])) {
            $query->where('original_name', 'ILIKE', '%' . $_GET['search'] . '%');
        }
        
        $total = $query->count();
        $files = $query->orderBy('created_at', 'DESC')
                      ->limit($perPage)
                      ->offset($offset)
                      ->get();
        
        $html = '';
        foreach ($files as $file) {
            $html .= '<div class="media-item" data-id="' . $file->id . '" data-url="' . $file->public_url . '">';
            $html .= '<div class="media-preview">';
            
            if ($file->isImage()) {
                $html .= '<img src="' . $file->getThumbnail('small') . '" alt="' . htmlspecialchars($file->alt_text) . '">';
            } else {
                $html .= '<i class="' . $file->getIcon() . ' fa-3x"></i>';
            }
            
            $html .= '</div>';
            $html .= '<div class="media-info">';
            $html .= '<div class="media-name">' . htmlspecialchars($file->original_name) . '</div>';
            $html .= '<div class="media-meta">' . $file->getFormattedSize() . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        echo json_encode([
            'html' => $html,
            'total' => $total,
            'page' => $page,
            'totalPages' => ceil($total / $perPage)
        ]);
        exit;
    }

    /**
     * Create folder
     */
    public function createFolder()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'media_folder')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $path = $input['path'] ?? '/';
        $name = $input['name'] ?? '';
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder name is required']);
            exit;
        }
        
        // Sanitize folder name
        $name = preg_replace('/[^a-zA-Z0-9\-_]/', '', $name);
        
        if ($path === '/') {
            $newPath = '/' . $name;
        } else {
            $newPath = $path . '/' . $name;
        }
        
        // Check if folder already exists
        $existing = Media::where(['folder' => $newPath])->get();
        if (!empty($existing)) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder already exists']);
            exit;
        }
        
        // Create a dummy record to represent the folder
        // Folders are virtual, we don't need to create a physical directory yet
        
        echo json_encode([
            'success' => true,
            'folder' => $newPath
        ]);
        exit;
    }

    /**
     * Ensure upload directories exist
     */
    private function ensureDirectories()
    {
        $dirs = ['images', 'videos', 'audio', 'documents'];
        
        foreach ($dirs as $dir) {
            $path = $this->uploadDir . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}