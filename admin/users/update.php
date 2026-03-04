<?php
// admin/users/update.php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\UserController;
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

// Get user ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /admin/users/');
    exit;
}

// Handle the request
$controller = new UserController($auth, $csrf, $config);
$controller->update($id);