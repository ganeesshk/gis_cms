<?php
// admin/pages/update.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\PageController;
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

// Get page ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /admin/pages/');
    exit;
}

// Handle the request
$controller = new PageController($auth, $csrf, $config);
$controller->update($id);