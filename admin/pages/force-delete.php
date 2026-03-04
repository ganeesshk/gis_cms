<?php
// admin/pages/force-delete.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\Page;
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

// Check permission (Super Admin only)
if (!$user->isSuperAdmin()) {
    $_SESSION['error'] = 'You do not have permission to permanently delete pages.';
    header('Location: /admin/pages/');
    exit;
}

// Verify CSRF token
$token = $_GET['token'] ?? '';
if (!$csrf->validate($token, 'page_actions')) {
    $_SESSION['error'] = 'Invalid security token.';
    header('Location: /admin/pages/');
    exit;
}

// Get page ID
$id = (int)($_GET['id'] ?? 0);
$page = Page::find($id);

if (!$page) {
    $_SESSION['error'] = 'Page not found.';
    header('Location: /admin/pages/');
    exit;
}

// Store for audit
$oldValues = $page->toArray();
$title = $page->title;

// Hard delete
if ($page->forceDelete()) {
    AuditLog::log([
        'user_id' => $user->id,
        'username' => $user->username,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'action' => 'page.permanent_delete',
        'entity_type' => 'page',
        'entity_id' => $id,
        'entity_label' => $title,
        'old_values' => json_encode($oldValues),
        'result' => 'success'
    ]);
    
    $_SESSION['success'] = 'Page permanently deleted.';
} else {
    $_SESSION['error'] = 'Failed to delete page.';
}

header('Location: /admin/pages/');
exit;