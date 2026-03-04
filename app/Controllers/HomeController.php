<?php
// app/Controllers/HomeController.php

namespace App\Controllers;

use App\Models\HomeSection;
use App\Models\HomeDraft;
use App\Models\PhotoGallery;
use App\Models\VideoGallery;
use App\Models\Page;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;

class HomeController
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
     * Show home page editor
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Check if draft exists, otherwise load from live
        $draftSections = HomeDraft::where([])->orderBy('sort_order')->get();
        
        if (empty($draftSections)) {
            HomeDraft::syncFromLive();
            $draftSections = HomeDraft::where([])->orderBy('sort_order')->get();
        }
        
        // Load additional data for section configs
        foreach ($draftSections as $section) {
            $this->enrichSectionData($section);
        }
        
        $availableTypes = HomeSection::getAvailableTypes();
        $photoGalleries = PhotoGallery::where(['deleted_at' => null])->orderBy('name')->get();
        $videoGalleries = VideoGallery::where(['deleted_at' => null])->orderBy('name')->get();
        $pages = Page::where(['status' => 'published', 'deleted_at' => null])
                     ->orderBy('title')
                     ->get();
        
        $csrfToken = $this->csrf->generate('home_editor');
        
        return [
            'view' => 'home/index.php',
            'data' => [
                'user' => $user,
                'sections' => $draftSections,
                'availableTypes' => $availableTypes,
                'photoGalleries' => $photoGalleries,
                'videoGalleries' => $videoGalleries,
                'pages' => $pages,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Add new section to draft
     */
    public function addSection()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'home_editor')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        $type = $input['type'] ?? '';
        
        if (!array_key_exists($type, HomeSection::getAvailableTypes())) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid section type']);
            exit;
        }
        
        // Get max sort order
        $db = \App\Config\Database::getConnection();
        $sql = "SELECT MAX(sort_order) FROM home_page_draft";
        $stmt = $db->query($sql);
        $maxSort = (int)$stmt->fetchColumn();
        
        // Create temporary section to get default config
        $tempSection = new HomeSection();
        $tempSection->section_type = $type;
        
        // Create draft section
        $section = new HomeDraft();
        $section->section_type = $type;
        $section->title = $input['title'] ?? $tempSection->getSectionTypeLabel();
        $section->config = $tempSection->getDefaultConfig();
        $section->sort_order = $maxSort + 1;
        $section->is_visible = true;
        $section->saved_by = $user->id;
        $section->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'home.section_add',
            'entity_type' => 'home_draft',
            'entity_id' => $section->id,
            'entity_label' => $section->title,
            'result' => 'success'
        ]);
        
        // Return section data
        echo json_encode([
            'success' => true,
            'section' => $this->formatSection($section)
        ]);
        exit;
    }

    /**
     * Update section
     */
    public function updateSection($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'home_editor')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $section = HomeDraft::find($id);
        
        if (!$section) {
            http_response_code(404);
            echo json_encode(['error' => 'Section not found']);
            exit;
        }
        
        // Get input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Store old values for audit
        $oldValues = $section->toArray();
        
        // Update section
        if (isset($input['title'])) {
            $section->title = $input['title'];
        }
        
        if (isset($input['config'])) {
            // Merge with existing config
            $config = $section->config;
            foreach ($input['config'] as $key => $value) {
                $config[$key] = $value;
            }
            $section->config = $config;
        }
        
        if (isset($input['is_visible'])) {
            $section->is_visible = (bool)$input['is_visible'];
        }
        
        $section->saved_by = $user->id;
        $section->save();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'home.section_update',
            'entity_type' => 'home_draft',
            'entity_id' => $section->id,
            'entity_label' => $section->title,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($section->toArray()),
            'result' => 'success'
        ]);
        
        echo json_encode([
            'success' => true,
            'section' => $this->formatSection($section)
        ]);
        exit;
    }

    /**
     * Delete section
     */
    public function deleteSection($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'home_editor')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        $section = HomeDraft::find($id);
        
        if (!$section) {
            http_response_code(404);
            echo json_encode(['error' => 'Section not found']);
            exit;
        }
        
        // Store for audit
        $oldValues = $section->toArray();
        $title = $section->title;
        
        // Delete section
        $section->delete();
        
        // Reorder remaining sections
        $this->reorderSections();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'home.section_delete',
            'entity_type' => 'home_draft',
            'entity_id' => $id,
            'entity_label' => $title,
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Reorder sections
     */
    public function reorderSections()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'home_editor')) {
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
            foreach ($order as $index => $sectionId) {
                $section = HomeDraft::find($sectionId);
                if ($section) {
                    $section->sort_order = $index;
                    $section->saved_by = $user->id;
                    $section->save();
                }
            }
            
            $db->commit();
            
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'home.reorder',
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to reorder sections']);
        }
        
        exit;
    }

    /**
     * Publish draft to live
     */
    public function publish()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'home_editor')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        if (HomeDraft::publish()) {
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'home.publish',
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to publish home page']);
        }
        
        exit;
    }

    /**
     * Discard draft changes
     */
    public function discard()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? '';
        
        if (!$this->csrf->validate($token, 'home_editor')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid security token']);
            exit;
        }
        
        if (HomeDraft::discard()) {
            // Reload from live
            HomeDraft::syncFromLive();
            
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'home.discard',
                'result' => 'success'
            ]);
            
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to discard changes']);
        }
        
        exit;
    }

    /**
     * Preview home page
     */
    public function preview()
    {
        $user = $this->auth->getCurrentUser();
        
        // Check preview token
        $token = $_GET['token'] ?? '';
        $expectedToken = md5('home_preview_' . date('Y-m-d'));
        
        if ($token !== $expectedToken && !$user) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        // Get draft sections
        $sections = HomeDraft::where([])->orderBy('sort_order')->get();
        
        // Enrich section data
        foreach ($sections as $section) {
            $this->enrichSectionData($section);
        }
        
        return [
            'view' => 'home/preview.php',
            'data' => [
                'sections' => $sections,
                'isPreview' => true
            ],
            'layout' => 'public'
        ];
    }

    /**
     * Get section data for editing
     */
    public function getSection($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $section = HomeDraft::find($id);
        
        if (!$section) {
            http_response_code(404);
            echo json_encode(['error' => 'Section not found']);
            exit;
        }
        
        $this->enrichSectionData($section);
        
        echo json_encode($this->formatSection($section));
        exit;
    }

    /**
     * Enrich section data with related models
     */
    private function enrichSectionData($section)
    {
        $config = $section->config;
        
        switch ($section->section_type) {
            case HomeSection::TYPE_PHOTO_GALLERY_PREVIEW:
                if (!empty($config['gallery_id'])) {
                    $config['gallery'] = PhotoGallery::find($config['gallery_id']);
                }
                break;
                
            case HomeSection::TYPE_VIDEO_GALLERY_PREVIEW:
                if (!empty($config['gallery_id'])) {
                    $config['gallery'] = VideoGallery::find($config['gallery_id']);
                }
                break;
                
            case HomeSection::TYPE_LATEST_PAGES:
                $query = Page::where(['status' => 'published', 'deleted_at' => null])
                             ->orderBy('published_at', 'DESC')
                             ->limit($config['count'] ?? 3);
                
                if (!empty($config['category'])) {
                    // Add category filter if implemented
                }
                
                $config['pages'] = $query->get();
                break;
        }
        
        $section->enriched_config = $config;
    }

    /**
     * Format section for JSON response
     */
    private function formatSection($section)
    {
        $data = $section->toArray();
        $data['type_label'] = $section->getSectionTypeLabel();
        $data['icon'] = $section->getIcon();
        $data['config'] = $section->enriched_config ?? $section->config;
        
        return $data;
    }
}