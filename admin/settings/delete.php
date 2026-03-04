<?php
// admin/settings/delete.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\SettingsController;
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

// Get setting ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Setting ID required']);
    exit;
}

// Handle the request
$controller = new SettingsController($auth, $csrf, $config);
$controller->delete($id);