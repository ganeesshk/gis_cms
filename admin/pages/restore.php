<?php
// admin/pages/restore.php

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

// Check permission
if (!$user->hasPermission('pages.write') && $page->author_id !== $user->id) {
    $_SESSION['error'] = 'You do not have permission to restore this page.';
    header('Location: /admin/pages/');
    exit;
}

// Restore page
if ($page->restore()) {
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
} else {
    $_SESSION['error'] = 'Failed to restore page.';
}

header('Location: /admin/pages/edit.php?id=' . $page->id);
exit;