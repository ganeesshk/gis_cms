<?php
// admin/galleries/photo/set-cover.php

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
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get gallery ID and photo ID
$galleryId = (int)($_GET['gallery_id'] ?? 0);
$photoId = (int)($_GET['photo_id'] ?? 0);

if (!$galleryId || !$photoId) {
    http_response_code(400);
    echo json_encode(['error' => 'Gallery ID and Photo ID required']);
    exit;
}

// Handle the request
$controller = new PhotoGalleryController($auth, $csrf, $config);
$controller->setCover($galleryId, $photoId);