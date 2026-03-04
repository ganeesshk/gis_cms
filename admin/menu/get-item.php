<?php
// admin/menus/get-item.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\MenuItem;
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
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get item ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID required']);
    exit;
}

// Get item
$item = MenuItem::find($id);
if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
    exit;
}

// Format response
echo json_encode([
    'id' => $item->id,
    'parent_id' => $item->parent_id,
    'label' => $item->label,
    'link_type' => $item->link_type,
    'page_id' => $item->page_id,
    'url' => $item->url,
    'anchor' => $item->anchor,
    'target' => $item->target,
    'css_class' => $item->css_class,
    'icon_class' => $item->icon_class,
    'is_active' => $item->is_active
]);