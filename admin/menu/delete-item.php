<?php
// admin/menus/delete-item.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\MenuController;
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

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$itemId = (int)($input['id'] ?? 0);

if (!$itemId) {
    http_response_code(400);
    echo json_encode(['error' => 'Item ID required']);
    exit;
}

// Handle the request
$controller = new MenuController($auth, $csrf, $config);
$controller->deleteItem($itemId);