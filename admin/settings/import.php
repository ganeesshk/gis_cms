<?php
// admin/settings/import.php

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
    header('Location: /admin/login.php');
    exit;
}

// Handle the request
$controller = new SettingsController($auth, $csrf, $config);
$controller->import();