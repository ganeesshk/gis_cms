<?php
// admin/pages/restore-revision.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\AuditLog;
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

// Get parameters
$pageId = (int)($_GET['page_id'] ?? 0);
$revisionId = (int)($_GET['revision_id'] ?? 0);

// Verify CSRF token
$token = $_GET['token'] ?? '';
if (!$csrf->validate($token, 'revision_restore_' . $revisionId)) {
    $_SESSION['error'] = 'Invalid security token.';
    header('Location: /admin/pages/revisions.php?id=' . $pageId);
    exit;
}

// Find page and revision
$page = Page::find($pageId);
$revision = PageRevision::find($revisionId);

if (!$page || !$revision || $revision->page_id != $page->id) {
    $_SESSION['error'] = 'Page or revision not found.';
    header('Location: /admin/pages/');
    exit;
}

// Check permission
if (!$user->hasPermission('pages.write') && $page->author_id !== $user->id) {
    $_SESSION['error'] = 'You do not have permission to restore revisions for this page.';
    header('Location: /admin/pages/revisions.php?id=' . $pageId);
    exit;
}

// Restore revision
if ($page->revertToRevision($revisionId, $user->id)) {
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
    
    $_SESSION['success'] = 'Revision #' . $revision->revision_number . ' restored successfully.';
} else {
    $_SESSION['error'] = 'Failed to restore revision.';
}

header('Location: /admin/pages/edit.php?id=' . $page->id);
exit;