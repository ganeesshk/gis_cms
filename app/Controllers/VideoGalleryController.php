<?php
// app/Controllers/VideoGalleryController.php

namespace App\Controllers;

use App\Models\VideoGallery;
use App\Models\Video;
use App\Models\Media;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;

class VideoGalleryController
{
    private $auth;
    private $csrf;
    private $config;

    public function __construct(AuthService $auth, CSRF $csrf, array $config)
    {
        $this->auth = $auth;
        $this->csrf = $csrf;
        $this->config = $config;
    }

    /**
     * List all video galleries
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Build query with filters
        $visibility = $_GET['visibility'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        
        $query = VideoGallery::where(['deleted_at' => null]);
        
        if ($visibility !== 'all') {
            $isPublic = $visibility === 'public';
            $query->where('is_public', '=', $isPublic);
        }
        
        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
        }
        
        $totalGalleries = $query->count();
        $galleries = $query->orderBy('sort_order')
                          ->limit($perPage)
                          ->offset($offset)
                          ->get();
        
        // Load video counts and cover images
        foreach ($galleries as $gallery) {
            $gallery->video_count = $gallery->getVideoCount();
            $gallery->cover_url = $gallery->getCoverUrl();
            $gallery->creator = $gallery->creator();
        }
        
        $totalPages = ceil($totalGalleries / $perPage);
        
        // Get stats
        $stats = VideoGallery::getStats();
        
        return [
            'view' => 'galleries/video/index.php',
            'data' => [
                'user' => $user,
                'galleries' => $galleries,
                'stats' => $stats,
                'currentVisibility' => $visibility,
                'search' => $search,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalGalleries' => $totalGalleries
            ]
        ];
    }

    /**
     * Show create gallery form
     */
    public function create()
    {
        $user = $this->auth->getCurrentUser();
        $csrfToken = $this->csrf->generate('video_gallery_create');
        
        return [
            'view' => 'galleries/video/create.php',
            'data' => [
                'user' => $user,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Store new gallery
     */
    public function store()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'video_gallery_create')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/video/create.php');
            exit;
        }
        
        // Validate input
        $errors = $this->validateGalleryInput($_POST);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/galleries/video/create.php');
            exit;
        }
        
        // Generate slug
        $slug = VideoGallery::generateSlug($_POST['name']);
        
        // Create gallery
        $gallery = new VideoGallery();
        $gallery->name = $_POST['name'];
        $gallery->slug = $slug;
        $gallery->description = $_POST['description'] ?? '';
        $gallery->is_public = isset($_POST['is_public']);
        $gallery->sort_order = $this->getNextSortOrder();
        $gallery->created_by = $user->id;
        $gallery->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'video_gallery.create',
            'entity_type' => 'video_gallery',
            'entity_id' => $gallery->id,
            'entity_label' => $gallery->name,
            'new_values' => json_encode($gallery->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Video gallery created successfully';
        header('Location: /admin/galleries/video/edit.php?id=' . $gallery->id);
        exit;
    }

    /**
     * Show edit gallery form
     */
    public function edit($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = VideoGallery::find($id);
        
        if (!$gallery) {
            header('HTTP/1.0 404 Not Found');
            return ['view' => 'errors/404.php', 'data' => []];
        }
        
        // Get gallery videos
        $videos = $gallery->videos();
        
        // Load thumbnail info for each video
        foreach ($videos as $video) {
            $video->thumbnail_url = $video->getThumbnailUrl();
            $video->duration = $video->getDuration();
            $video->adder = $video->adder();
        }
        
        $csrfToken = $this->csrf->generate('video_gallery_edit_' . $id);
        
        return [
            'view' => 'galleries/video/edit.php',
            'data' => [
                'user' => $user,
                'gallery' => $gallery,
                'videos' => $videos,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Update gallery
     */
    public function update($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = VideoGallery::find($id);
        
        if (!$gallery) {
            $_SESSION['error'] = 'Gallery not found';
            header('Location: /admin/galleries/video/');
            exit;
        }
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'video_gallery_edit_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/video/edit.php?id=' . $id);
            exit;
        }
        
        // Validate input
        $errors = $this->validateGalleryInput($_POST, $id);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/galleries/video/edit.php?id=' . $id);
            exit;
        }
        
        // Store old values for audit
        $oldValues = $gallery->toArray();
        
        // Update gallery
        $gallery->name = $_POST['name'];
        $gallery->description = $_POST['description'] ?? '';
        $gallery->is_public = isset($_POST['is_public']);
        $gallery->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'video_gallery.update',
            'entity_type' => 'video_gallery',
            'entity_id' => $gallery->id,
            'entity_label' => $gallery->name,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($gallery->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Gallery updated successfully';
        header('Location: /admin/galleries/video/edit.php?id=' . $gallery->id);
        exit;
    }

    /**
     * Delete gallery
     */
    public function delete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'video_gallery_delete_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/video/');
            exit;
        }
        
        $gallery = VideoGallery::find($id);
        
        if (!$gallery) {
            $_SESSION['error'] = 'Gallery not found';
            header('Location: /admin/galleries/video/');
            exit;
        }
        
        // Store for audit
        $oldValues = $gallery->toArray();
        $name = $gallery->name;
        
        // Soft delete
        $gallery->deleted_at = date('Y-m-d H:i:s');
        $gallery->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'video_gallery.delete',
            'entity_type' => 'video_gallery',
            'entity_id' => $id,
            'entity_label' => $name,
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Gallery deleted successfully';
        header('Location: /admin/galleries/video/');
        exit;
    }

    /**
     * Add video to gallery
     */
    public function addVideo($galleryId)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = VideoGallery::find($galleryId);
        
        if (!$gallery) {
            http_response_code(404);
            echo json_encode(['error' => 'Gallery not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'video_gallery_add')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        $youtubeUrl = $input['youtube_url'] ?? '';
        
        if (empty($youtubeUrl)) {
            http_response_code(400);
            echo json_encode(['error' => 'YouTube URL is required']);
            exit;
        }
        
        // Validate YouTube URL
        if (!Video::validateYoutubeUrl($youtubeUrl)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid YouTube URL']);
            exit;
        }
        
        // Extract YouTube ID
        $youtubeId = Video::extractYoutubeId($youtubeUrl);
        
        // Check if already in gallery
        $existing = Video::where(['gallery_id' => $galleryId, 'youtube_id' => $youtubeId])->get();
        if (!empty($existing)) {
            http_response_code(400);
            echo json_encode(['error' => 'This video is already in the gallery']);
            exit;
        }
        
        // Get max sort order
        $sortOrder = Video::getMaxSortOrder($galleryId);
        
        // Create video
        $video = new Video();
        $video->gallery_id = $galleryId;
        $video->youtube_url = $youtubeUrl;
        $video->youtube_id = $youtubeId;
        $video->sort_order = $sortOrder;
        $video->is_visible = true;
        $video->added_by = $user->id;
        
        // Fetch metadata from YouTube
        $video->fetchYouTubeData();
        $video->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'video_gallery.add_video',
            'entity_type' => 'video',
            'entity_id' => $video->id,
            'entity_label' => $video->title,
            'result' => 'success'
        ]);
        
        echo json_encode([
            'success' => true,
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'youtube_id' => $video->youtube_id,
                'thumbnail' => $video->getThumbnailUrl(),
                'duration' => $video->getDuration(),
                'channel' => $video->channel_name
            ]
        ]);
        exit;
    }

    /**
     * Update video
     */
    public function updateVideo($videoId)
    {
        $user = $this->auth->getCurrentUser();
        
        $video = Video::find($videoId);
        
        if (!$video) {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'video_edit')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Update video
        if (isset($input['title'])) {
            $video->title = $input['title'];
        }
        
        if (isset($input['description'])) {
            $video->description = $input['description'];
        }
        
        if (isset($input['is_visible'])) {
            $video->is_visible = (bool)$input['is_visible'];
        }
        
        // Handle custom thumbnail
        if (isset($input['custom_thumbnail_id'])) {
            if ($input['custom_thumbnail_id']) {
                $media = Media::find($input['custom_thumbnail_id']);
                if ($media && $media->isImage()) {
                    $video->custom_thumbnail_id = $input['custom_thumbnail_id'];
                }
            } else {
                $video->custom_thumbnail_id = null;
            }
        }
        
        $video->save();
        
        echo json_encode([
            'success' => true,
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'is_visible' => $video->is_visible,
                'thumbnail' => $video->getThumbnailUrl()
            ]
        ]);
        exit;
    }

    /**
     * Delete video from gallery
     */
    public function deleteVideo($videoId)
    {
        $user = $this->auth->getCurrentUser();
        
        $video = Video::find($videoId);
        
        if (!$video) {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'video_delete')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $galleryId = $video->gallery_id;
        
        // Delete video
        $video->delete();
        
        // Reorder remaining videos
        $this->reorderVideos($galleryId);
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Reorder videos (drag and drop)
     */
    public function reorderVideos($galleryId)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = VideoGallery::find($galleryId);
        
        if (!$gallery) {
            http_response_code(404);
            echo json_encode(['error' => 'Gallery not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'video_gallery_reorder')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get order data
        $input = json_decode(file_get_contents('php://input'), true);
        $order = $input['order'] ?? [];
        
        if (empty($order)) {
            http_response_code(400);
            echo json_encode(['error' => 'No order data provided']);
            exit;
        }
        
        $db = \App\Config\Database::getConnection();
        $db->beginTransaction();
        
        try {
            foreach ($order as $index => $videoId) {
                $video = Video::find($videoId);
                if ($video && $video->gallery_id == $galleryId) {
                    $video->sort_order = $index;
                    $video->save();
                }
            }
            
            $db->commit();
            
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'video_gallery.reorder',
                'entity_type' => 'video_gallery',
                'entity_id' => $galleryId,
                'entity_label' => $gallery->name,
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reorder videos']);
        }
        
        exit;
    }

    /**
     * Refresh video metadata from YouTube
     */
    public function refreshVideo($videoId)
    {
        $user = $this->auth->getCurrentUser();
        
        $video = Video::find($videoId);
        
        if (!$video) {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'video_refresh')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Fetch fresh metadata
        if ($video->fetchYouTubeData()) {
            $video->save();
            
            echo json_encode([
                'success' => true,
                'video' => [
                    'title' => $video->title,
                    'description' => $video->description,
                    'channel' => $video->channel_name,
                    'thumbnail' => $video->getThumbnailUrl()
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch video data from YouTube']);
        }
        
        exit;
    }

    /**
     * Duplicate gallery
     */
    public function duplicate($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'video_gallery_duplicate_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/video/');
            exit;
        }
        
        $gallery = VideoGallery::find($id);
        
        if (!$gallery) {
            $_SESSION['error'] = 'Gallery not found';
            header('Location: /admin/galleries/video/');
            exit;
        }
        
        // Duplicate gallery
        $newGallery = $gallery->duplicate($_GET['name'] ?? null);
        
        if ($newGallery) {
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'video_gallery.duplicate',
                'entity_type' => 'video_gallery',
                'entity_id' => $newGallery->id,
                'entity_label' => $newGallery->name,
                'result' => 'success'
            ]);
            
            $_SESSION['success'] = 'Gallery duplicated successfully';
            header('Location: /admin/galleries/video/edit.php?id=' . $newGallery->id);
        } else {
            $_SESSION['error'] = 'Failed to duplicate gallery';
            header('Location: /admin/galleries/video/');
        }
        
        exit;
    }

    /**
     * Validate gallery input
     */
    private function validateGalleryInput($data, $id = null)
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Gallery name is required';
        } elseif (strlen($data['name']) > 300) {
            $errors[] = 'Gallery name must not exceed 300 characters';
        }
        
        return $errors;
    }

    /**
     * Get next sort order for new gallery
     */
    private function getNextSortOrder()
    {
        $db = \App\Config\Database::getConnection();
        $sql = "SELECT MAX(sort_order) FROM video_galleries WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $max = $stmt->fetchColumn();
        return $max ? (int)$max + 1 : 0;
    }

    /**
     * Reorder videos after deletion
     */
    private function reorderVideos($galleryId)
    {
        $videos = Video::where(['gallery_id' => $galleryId])
                      ->orderBy('sort_order')
                      ->get();
        
        foreach ($videos as $index => $video) {
            if ($video->sort_order != $index) {
                $video->sort_order = $index;
                $video->save();
            }
        }
    }
}