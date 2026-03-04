<?php
// admin/galleries/photo/delete.php

require_once __DIR__ . '/../../../app/bootstrap.php';

use App\Controllers\PhotoGalleryController;
use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../../../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Require authentication
$user = $auth->getCurrentUser();
if (!$user) {
    header('Location: /admin/login.php');
    exit;
}

// Get gallery ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /admin/galleries/photo/');
    exit;
}

// Handle the request
$controller = new PhotoGalleryController($auth, $csrf, $config);
$controller->delete($id);