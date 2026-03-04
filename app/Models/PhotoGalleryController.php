<?php
// app/Controllers/PhotoGalleryController.php

namespace App\Controllers;

use App\Models\PhotoGallery;
use App\Models\Photo;
use App\Models\Media;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;

class PhotoGalleryController
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
     * List all photo galleries
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
        
        $query = PhotoGallery::where(['deleted_at' => null]);
        
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
        
        // Load photo counts and cover images
        foreach ($galleries as $gallery) {
            $gallery->photo_count = $gallery->getPhotoCount();
            $gallery->cover_url = $gallery->getCoverUrl('small');
            $gallery->creator = $gallery->creator();
        }
        
        $totalPages = ceil($totalGalleries / $perPage);
        
        // Get stats
        $stats = PhotoGallery::getStats();
        
        return [
            'view' => 'galleries/photo/index.php',
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
        $csrfToken = $this->csrf->generate('gallery_create');
        
        return [
            'view' => 'galleries/photo/create.php',
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
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'gallery_create')) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/photo/create.php');
            exit;
        }
        
        // Validate input
        $errors = $this->validateGalleryInput($_POST);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/galleries/photo/create.php');
            exit;
        }
        
        // Generate slug
        $slug = PhotoGallery::generateSlug($_POST['name']);
        
        // Create gallery
        $gallery = new PhotoGallery();
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
            'action' => 'gallery.create',
            'entity_type' => 'photo_gallery',
            'entity_id' => $gallery->id,
            'entity_label' => $gallery->name,
            'new_values' => json_encode($gallery->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Photo gallery created successfully';
        header('Location: /admin/galleries/photo/edit.php?id=' . $gallery->id);
        exit;
    }

    /**
     * Show edit gallery form
     */
    public function edit($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = PhotoGallery::find($id);
        
        if (!$gallery) {
            header('HTTP/1.0 404 Not Found');
            return ['view' => 'errors/404.php', 'data' => []];
        }
        
        // Get gallery photos
        $photos = $gallery->photos();
        
        // Load media for each photo
        foreach ($photos as $photo) {
            $photo->media = $photo->media();
            $photo->thumbnail = $photo->getThumbnailUrl('small');
        }
        
        $csrfToken = $this->csrf->generate('gallery_edit_' . $id);
        
        return [
            'view' => 'galleries/photo/edit.php',
            'data' => [
                'user' => $user,
                'gallery' => $gallery,
                'photos' => $photos,
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
        
        $gallery = PhotoGallery::find($id);
        
        if (!$gallery) {
            $_SESSION['error'] = 'Gallery not found';
            header('Location: /admin/galleries/photo/');
            exit;
        }
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'gallery_edit_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/photo/edit.php?id=' . $id);
            exit;
        }
        
        // Validate input
        $errors = $this->validateGalleryInput($_POST, $id);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/galleries/photo/edit.php?id=' . $id);
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
            'action' => 'gallery.update',
            'entity_type' => 'photo_gallery',
            'entity_id' => $gallery->id,
            'entity_label' => $gallery->name,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($gallery->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Gallery updated successfully';
        header('Location: /admin/galleries/photo/edit.php?id=' . $gallery->id);
        exit;
    }

    /**
     * Delete gallery
     */
    public function delete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'gallery_delete_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/photo/');
            exit;
        }
        
        $gallery = PhotoGallery::find($id);
        
        if (!$gallery) {
            $_SESSION['error'] = 'Gallery not found';
            header('Location: /admin/galleries/photo/');
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
            'action' => 'gallery.delete',
            'entity_type' => 'photo_gallery',
            'entity_id' => $id,
            'entity_label' => $name,
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Gallery deleted successfully';
        header('Location: /admin/galleries/photo/');
        exit;
    }

    /**
     * Add photos to gallery
     */
    public function addPhotos($galleryId)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = PhotoGallery::find($galleryId);
        
        if (!$gallery) {
            http_response_code(404);
            echo json_encode(['error' => 'Gallery not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'gallery_add_photos')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        $mediaIds = $input['media_ids'] ?? [];
        
        if (empty($mediaIds)) {
            http_response_code(400);
            echo json_encode(['error' => 'No media selected']);
            exit;
        }
        
        $added = [];
        $errors = [];
        
        // Get max sort order
        $sortOrder = Photo::getMaxSortOrder($galleryId);
        
        foreach ($mediaIds as $mediaId) {
            $media = Media::find($mediaId);
            
            if (!$media || !$media->isImage()) {
                $errors[] = "Media #{$mediaId} is not a valid image";
                continue;
            }
            
            // Check if already in gallery
            $existing = Photo::where(['gallery_id' => $galleryId, 'media_id' => $mediaId])->get();
            if (!empty($existing)) {
                $errors[] = "Image '{$media->original_name}' is already in the gallery";
                continue;
            }
            
            // Create photo
            $photo = new Photo();
            $photo->gallery_id = $galleryId;
            $photo->media_id = $mediaId;
            $photo->title = $media->title ?: $media->original_name;
            $photo->alt_text = $media->alt_text;
            $photo->sort_order = $sortOrder++;
            $photo->is_visible = true;
            $photo->uploaded_by = $user->id;
            $photo->save();
            
            $added[] = [
                'id' => $photo->id,
                'title' => $photo->title,
                'thumbnail' => $media->getThumbnail('small'),
                'url' => $media->public_url
            ];
        }
        
        // Update gallery cover if needed
        $gallery->updateCover();
        
        // Log activity
        if (!empty($added)) {
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'gallery.add_photos',
                'entity_type' => 'photo_gallery',
                'entity_id' => $galleryId,
                'entity_label' => $gallery->name,
                'new_values' => json_encode(['count' => count($added)]),
                'result' => 'success'
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'added' => $added,
            'errors' => $errors
        ]);
        exit;
    }

    /**
     * Update photo
     */
    public function updatePhoto($photoId)
    {
        $user = $this->auth->getCurrentUser();
        
        $photo = Photo::find($photoId);
        
        if (!$photo) {
            http_response_code(404);
            echo json_encode(['error' => 'Photo not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'photo_edit')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Update photo
        if (isset($input['title'])) {
            $photo->title = $input['title'];
        }
        
        if (isset($input['caption'])) {
            $photo->caption = $input['caption'];
        }
        
        if (isset($input['alt_text'])) {
            $photo->alt_text = $input['alt_text'];
        }
        
        if (isset($input['is_visible'])) {
            $photo->is_visible = (bool)$input['is_visible'];
        }
        
        $photo->save();
        
        echo json_encode([
            'success' => true,
            'photo' => [
                'id' => $photo->id,
                'title' => $photo->title,
                'caption' => $photo->caption,
                'alt_text' => $photo->alt_text,
                'is_visible' => $photo->is_visible
            ]
        ]);
        exit;
    }

    /**
     * Delete photo from gallery
     */
    public function deletePhoto($photoId)
    {
        $user = $this->auth->getCurrentUser();
        
        $photo = Photo::find($photoId);
        
        if (!$photo) {
            http_response_code(404);
            echo json_encode(['error' => 'Photo not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'photo_delete')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $galleryId = $photo->gallery_id;
        $gallery = PhotoGallery::find($galleryId);
        
        // Delete photo
        $photo->delete();
        
        // Update gallery cover if needed
        if ($gallery && $gallery->cover_media_id == $photo->media_id) {
            $gallery->cover_media_id = null;
            $gallery->updateCover();
            $gallery->save();
        }
        
        // Reorder remaining photos
        $this->reorderPhotos($galleryId);
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Reorder photos (drag and drop)
     */
    public function reorderPhotos($galleryId)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = PhotoGallery::find($galleryId);
        
        if (!$gallery) {
            http_response_code(404);
            echo json_encode(['error' => 'Gallery not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'gallery_reorder')) {
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
            foreach ($order as $index => $photoId) {
                $photo = Photo::find($photoId);
                if ($photo && $photo->gallery_id == $galleryId) {
                    $photo->sort_order = $index;
                    $photo->save();
                }
            }
            
            $db->commit();
            
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'gallery.reorder',
                'entity_type' => 'photo_gallery',
                'entity_id' => $galleryId,
                'entity_label' => $gallery->name,
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reorder photos']);
        }
        
        exit;
    }

    /**
     * Set gallery cover image
     */
    public function setCover($galleryId, $photoId)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = PhotoGallery::find($galleryId);
        $photo = Photo::find($photoId);
        
        if (!$gallery || !$photo || $photo->gallery_id != $galleryId) {
            http_response_code(404);
            echo json_encode(['error' => 'Gallery or photo not found']);
            exit;
        }
        
        // Verify CSRF for AJAX
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'gallery_set_cover')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $gallery->cover_media_id = $photo->media_id;
        $gallery->save();
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Duplicate gallery
     */
    public function duplicate($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'gallery_duplicate_' . $id)) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /admin/galleries/photo/');
            exit;
        }
        
        $gallery = PhotoGallery::find($id);
        
        if (!$gallery) {
            $_SESSION['error'] = 'Gallery not found';
            header('Location: /admin/galleries/photo/');
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
                'action' => 'gallery.duplicate',
                'entity_type' => 'photo_gallery',
                'entity_id' => $newGallery->id,
                'entity_label' => $newGallery->name,
                'result' => 'success'
            ]);
            
            $_SESSION['success'] = 'Gallery duplicated successfully';
            header('Location: /admin/galleries/photo/edit.php?id=' . $newGallery->id);
        } else {
            $_SESSION['error'] = 'Failed to duplicate gallery';
            header('Location: /admin/galleries/photo/');
        }
        
        exit;
    }

    /**
     * Get media browser for adding photos
     */
    public function mediaBrowser($galleryId)
    {
        $user = $this->auth->getCurrentUser();
        
        $gallery = PhotoGallery::find($galleryId);
        
        if (!$gallery) {
            http_response_code(404);
            echo json_encode(['error' => 'Gallery not found']);
            exit;
        }
        
        // Get existing media IDs in this gallery
        $existingMediaIds = [];
        $photos = $gallery->photos();
        foreach ($photos as $photo) {
            $existingMediaIds[] = $photo->media_id;
        }
        
        // Get available images
        $query = Media::where(['deleted_at' => null])
                     ->where('mime_type', 'LIKE', 'image/%')
                     ->orderBy('created_at', 'DESC');
        
        if (!empty($existingMediaIds)) {
            $query->whereNotIn('id', $existingMediaIds);
        }
        
        $media = $query->limit(50)->get();
        
        $html = '';
        foreach ($media as $item) {
            $html .= '<div class="media-item" data-id="' . $item->id . '">';
            $html .= '<div class="media-preview">';
            $html .= '<img src="' . $item->getThumbnail('small') . '" alt="' . htmlspecialchars($item->alt_text) . '">';
            $html .= '</div>';
            $html .= '<div class="media-info">';
            $html .= '<div class="media-name">' . htmlspecialchars($item->original_name) . '</div>';
            $html .= '</div>';
            $html .= '<div class="media-check">';
            $html .= '<input type="checkbox" class="form-check-input" value="' . $item->id . '">';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        echo json_encode([
            'html' => $html,
            'total' => count($media)
        ]);
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
        $sql = "SELECT MAX(sort_order) FROM photo_galleries WHERE deleted_at IS NULL";
        $stmt = $db->query($sql);
        $max = $stmt->fetchColumn();
        return $max ? (int)$max + 1 : 0;
    }

    /**
     * Reorder photos after deletion
     */
    private function reorderPhotos($galleryId)
    {
        $photos = Photo::where(['gallery_id' => $galleryId])
                      ->orderBy('sort_order')
                      ->get();
        
        foreach ($photos as $index => $photo) {
            if ($photo->sort_order != $index) {
                $photo->sort_order = $index;
                $photo->save();
            }
        }
    }
}