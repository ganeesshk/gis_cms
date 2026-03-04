<?php
// app/Controllers/PageController.php

namespace App\Controllers;

use App\Models\Page;
use App\Models\Tag;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Security\CSRF;

class PageController
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
     * List all pages
     */
    public function index()
    {
        $user = $this->auth->getCurrentUser();
        
        // Build query based on filters
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $query = Page::where(['deleted_at' => null]);
        
        if ($status && $status !== 'all') {
            $query->where('status', '=', $status);
        }
        
        if ($search) {
            $query->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('content', 'ILIKE', "%{$search}%");
        }
        
        $totalPages = $query->count();
        $pages = $query->orderBy('updated_at', 'DESC')
                      ->limit($perPage)
                      ->offset($offset)
                      ->get();
        
        // Load authors
        foreach ($pages as $pageObj) {
            $pageObj->author = $pageObj->author();
        }
        
        $totalFiltered = $query->count();
        $totalPagesCount = ceil($totalFiltered / $perPage);
        
        return [
            'view' => 'pages/index.php',
            'data' => [
                'pages' => $pages,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'totalPagesCount' => $totalPagesCount,
                'status' => $status,
                'search' => $search,
                'statusOptions' => Page::getStatusOptions(),
                'user' => $user
            ]
        ];
    }

    /**
     * Show create page form
     */
    public function create()
    {
        $user = $this->auth->getCurrentUser();
        $tags = Tag::getAllWithCounts();
        $csrfToken = $this->csrf->generate('page_create');
        
        return [
            'view' => 'pages/create.php',
            'data' => [
                'user' => $user,
                'tags' => $tags,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Store new page
     */
    public function store()
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'page_create')) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: /admin/pages/create.php');
            exit;
        }
        
        // Validate input
        $errors = $this->validatePageInput($_POST);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: /admin/pages/create.php');
            exit;
        }
        
        // Generate slug if not provided
        //$slug = $_POST['slug'] ?: Page::generateSlug($_POST['title']);
        
		// Generate slug if not provided
		if (empty($_POST['slug'])) {
			$slug = Page::generateUniqueSlug($_POST['title']);
		} else {
			// Validate the provided slug
			$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
			$slug = trim($slug, '-');
			
			// Check if slug exists
			$existingPage = Page::findBySlug($slug);
			if ($existingPage) {
				// If slug exists, make it unique
				$slug = Page::generateUniqueSlug($_POST['title']);
				$_SESSION['warning'] = 'The requested slug was already taken. A unique slug has been generated.';
			}
		}
		
		
        // Create page
        $page = new Page();
        $page->title = $_POST['title'];
        $page->slug = $slug;
        $page->content = $_POST['content'] ?? '';
        $page->excerpt = $_POST['excerpt'] ?? '';
        $page->meta_title = $_POST['meta_title'] ?? $_POST['title'];
        $page->meta_description = $_POST['meta_description'] ?? '';
        $page->meta_keywords = $_POST['meta_keywords'] ?? '';
        $page->status = $_POST['status'] ?? Page::STATUS_DRAFT;
        $page->template = $_POST['template'] ?? 'default';
        $page->is_in_sitemap = isset($_POST['is_in_sitemap']);
        $page->author_id = $user->id;
        
        if (!empty($_POST['scheduled_at']) && $_POST['status'] === Page::STATUS_SCHEDULED) {
            $page->scheduled_at = $_POST['scheduled_at'];
        }
        
        $page->save();
        
        // Handle tags
        if (!empty($_POST['tags'])) {
            $tagIds = [];
            foreach ($_POST['tags'] as $tagName) {
                $tag = Tag::findOrCreate($tagName);
                $tagIds[] = $tag->id;
            }
            $page->syncTags($tagIds);
        }
        
        // Create initial revision
        $page->createRevision($user->id, 'Initial creation');
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'page.create',
            'entity_type' => 'page',
            'entity_id' => $page->id,
            'entity_label' => $page->title,
            'new_values' => json_encode($page->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Page created successfully.';
        
        // Redirect based on action
        if (isset($_POST['save_and_publish'])) {
            $page->publish($user->id);
            $_SESSION['success'] = 'Page published successfully.';
            header('Location: ../../admin/pages/edit.php?id=' . $page->id);
        } elseif (isset($_POST['save_and_preview'])) {
            header('Location: ' . $page->getPreviewUrl());
        } else {
            header('Location: ../../admin/pages/edit.php?id=' . $page->id);
        }
        exit;
    }

    /**
     * Show edit page form
     */
    public function edit($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $page = Page::find($id);
        
        if (!$page) {
            header('HTTP/1.0 404 Not Found');
            return ['view' => 'errors/404.php', 'data' => []];
        }
        
        $tags = Tag::getAllWithCounts();
        $pageTags = $page->tags();
        $revisions = $page->revisions();
        $csrfToken = $this->csrf->generate('page_edit_' . $id);
        
        return [
            'view' => '../pages/edit.php',
            'data' => [
                'page' => $page,
                'tags' => $tags,
                'pageTags' => $pageTags,
                'revisions' => $revisions,
                'user' => $user,
                'csrfToken' => $csrfToken
            ]
        ];
    }

    /**
     * Update page
     */
    public function update($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $page = Page::find($id);
        
        if (!$page) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        
        // Verify CSRF
        if (!$this->csrf->validate($_POST['csrf_token'] ?? '', 'page_edit_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: ../../admin/pages/edit.php?id=' . $id);
            exit;
        }
        
        // Validate input
        $errors = $this->validatePageInput($_POST, $id);
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            $_SESSION['form_data'] = $_POST;
            header('Location: ../../admin/pages/edit.php?id=' . $id);
            exit;
        }
        
        // Store old values for audit
        $oldValues = $page->toArray();
        
        // Update page
        $page->title = $_POST['title'];
        //$page->slug = $_POST['slug'] ?: Page::generateSlug($_POST['title'], $id);
		// Handle slug in update method (around line 280-300)
		$page->slug = $_POST['slug'] ?: Page::generateUniqueSlug($_POST['title'], $id);

		// If slug was manually provided, validate it
		if (!empty($_POST['slug'])) {
			$page->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
			$page->slug = trim($page->slug, '-');
			
			// Check if slug exists (excluding current page)
			$db = \App\Config\Database::getConnection();
			$sql = "SELECT id FROM pages WHERE slug = :slug AND id != :id AND deleted_at IS NULL";
			$stmt = $db->prepare($sql);
			$stmt->execute([':slug' => $page->slug, ':id' => $id]);
			
			if ($stmt->fetch()) {
				// Slug exists, generate unique one
				$slug = Page::generateUniqueSlug($_POST['title'], $id);
				$_SESSION['warning'] = 'The requested slug was already taken. A unique slug has been generated.';
			}
		}
		
		
        $page->content = $_POST['content'] ?? '';
        $page->excerpt = $_POST['excerpt'] ?? '';
        $page->meta_title = $_POST['meta_title'] ?? $_POST['title'];
        $page->meta_description = $_POST['meta_description'] ?? '';
        $page->meta_keywords = $_POST['meta_keywords'] ?? '';
        $page->template = $_POST['template'] ?? 'default';
        $page->is_in_sitemap = isset($_POST['is_in_sitemap']);
        
        // Handle status change
        $oldStatus = $page->status;
        $newStatus = $_POST['status'] ?? $oldStatus;
        
        if ($newStatus !== $oldStatus) {
            if ($newStatus === Page::STATUS_PUBLISHED) {
                $page->publish($user->id);
            } elseif ($newStatus === Page::STATUS_UNPUBLISHED) {
                $page->unpublish($user->id);
            } elseif ($newStatus === Page::STATUS_SCHEDULED && !empty($_POST['scheduled_at'])) {
                $page->schedule($_POST['scheduled_at'], $user->id);
            } else {
                $page->status = $newStatus;
            }
        }
        
        $page->save();
        
        // Handle tags
        if (isset($_POST['tags'])) {
            $tagIds = [];
            foreach ($_POST['tags'] as $tagName) {
                $tag = Tag::findOrCreate($tagName);
                $tagIds[] = $tag->id;
            }
            $page->syncTags($tagIds);
        }
        
        // Create revision if content changed
        if ($page->content !== $oldValues['content'] || $page->title !== $oldValues['title']) {
            $page->createRevision($user->id, $_POST['change_note'] ?? 'Updated page');
        }
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'page.update',
            'entity_type' => 'page',
            'entity_id' => $page->id,
            'entity_label' => $page->title,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($page->toArray()),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Page updated successfully.';
        
        // Redirect based on action
        if (isset($_POST['save_and_publish'])) {
            $page->publish($user->id);
            $_SESSION['success'] = 'Page published successfully.';
            header('Location: ../../admin/pages/edit.php?id=' . $page->id);
        } elseif (isset($_POST['save_and_preview'])) {
            header('Location: ' . $page->getPreviewUrl());
        } else {
            header('Location: ../../admin/pages/edit.php?id=' . $page->id);
        }
        exit;
    }

    /**
     * Delete page (soft delete)
     */
    public function delete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'page_delete_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        $page = Page::find($id);
        
        if (!$page) {
            $_SESSION['error'] = 'Page not found.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        // Store for audit
        $oldValues = $page->toArray();
        
        // Soft delete
        $page->trash();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'page.delete',
            'entity_type' => 'page',
            'entity_id' => $page->id,
            'entity_label' => $page->title,
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Page moved to trash.';
        header('Location: ../../admin/pages/');
        exit;
    }

    /**
     * Restore page from trash
     */
    public function restore($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'page_restore_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        $page = Page::find($id);
        
        if (!$page) {
            $_SESSION['error'] = 'Page not found.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        $page->restore();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'page.restore',
            'entity_type' => 'page',
            'entity_id' => $page->id,
            'entity_label' => $page->title,
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Page restored successfully.';
        header('Location: ../../admin/pages/edit.php?id=' . $page->id);
        exit;
    }

    /**
     * Permanently delete page
     */
    public function forceDelete($id)
    {
        $user = $this->auth->getCurrentUser();
        
        // Check permission (Super Admin only)
        if (!$user->isSuperAdmin()) {
            $_SESSION['error'] = 'You do not have permission to permanently delete pages.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'page_force_delete_' . $id)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        $page = Page::find($id);
        
        if (!$page) {
            $_SESSION['error'] = 'Page not found.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        // Store for audit
        $oldValues = $page->toArray();
        
        // Hard delete
        $page->forceDelete();
        
        // Log activity
        AuditLog::log([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'action' => 'page.permanent_delete',
            'entity_type' => 'page',
            'entity_id' => $id,
            'entity_label' => $oldValues['title'],
            'old_values' => json_encode($oldValues),
            'result' => 'success'
        ]);
        
        $_SESSION['success'] = 'Page permanently deleted.';
        header('Location: ../../admin/pages/');
        exit;
    }

    /**
     * Preview page
     */
    public function preview($id)
    {
        $page = Page::find($id);
        
        if (!$page) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        
        // Check preview token
        $token = $_GET['token'] ?? '';
        $expectedToken = md5($page->updated_at);
        
        if ($token !== $expectedToken) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
        
        return [
            'view' => '../pages/preview.php',
            'data' => [
                'page' => $page
            ],
            'layout' => 'public'
        ];
    }

    /**
     * View revisions
     */
    public function revisions($id)
    {
        $user = $this->auth->getCurrentUser();
        
        $page = Page::find($id);
        
        if (!$page) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }
        
        $revisions = $page->revisions();
        
        // Load authors for revisions
        foreach ($revisions as $revision) {
            $revision->author = $revision->author();
        }
        
        return [
            'view' => '../pages/revisions.php',
            'data' => [
                'page' => $page,
                'revisions' => $revisions,
                'user' => $user
            ]
        ];
    }

    /**
     * Restore revision
     */
    public function restoreRevision($pageId, $revisionId)
    {
        $user = $this->auth->getCurrentUser();
        
        // Verify CSRF
        if (!isset($_GET['token']) || !$this->csrf->validate($_GET['token'], 'revision_restore_' . $revisionId)) {
            $_SESSION['error'] = 'Invalid security token.';
            header('Location: ../../admin/pages/revisions.php?id=' . $pageId);
            exit;
        }
        
        $page = Page::find($pageId);
        
        if (!$page) {
            $_SESSION['error'] = 'Page not found.';
            header('Location: ../../admin/pages/');
            exit;
        }
        
        if ($page->revertToRevision($revisionId, $user->id)) {
            // Log activity
            AuditLog::log([
                'user_id' => $user->id,
                'username' => $user->username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'action' => 'page.revision_restore',
                'entity_type' => 'page',
                'entity_id' => $page->id,
                'entity_label' => $page->title,
                'result' => 'success'
            ]);
            
            $_SESSION['success'] = 'Revision restored successfully.';
        } else {
            $_SESSION['error'] = 'Failed to restore revision.';
        }
        
        header('Location: ../../admin/pages/edit.php?id=' . $page->id);
        exit;
    }

    /**
     * Validate page input
     */
    private function validatePageInput($data, $pageId = null)
    {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Page title is required.';
        } elseif (strlen($data['title']) > 500) {
            $errors[] = 'Page title must not exceed 500 characters.';
        }
        
        if (!empty($data['slug'])) {
            if (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
                $errors[] = 'Slug can only contain lowercase letters, numbers, and hyphens.';
            }
            
            // Check uniqueness
            $existing = Page::findBySlug($data['slug']);
            if ($existing && ($pageId === null || $existing->id != $pageId)) {
                $errors[] = 'This slug is already in use. Please choose another.';
            }
        }
        
        if (!empty($data['scheduled_at']) && $data['status'] === Page::STATUS_SCHEDULED) {
            $scheduled = strtotime($data['scheduled_at']);
            if ($scheduled < time()) {
                $errors[] = 'Scheduled date must be in the future.';
            }
        }
        
        if (strlen($data['meta_title'] ?? '') > 300) {
            $errors[] = 'Meta title must not exceed 300 characters.';
        }
        
        if (strlen($data['meta_description'] ?? '') > 500) {
            $errors[] = 'Meta description must not exceed 500 characters.';
        }
        
        return $errors;
    }
}