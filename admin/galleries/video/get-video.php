<?php
// admin/galleries/video/get-video.php

require_once __DIR__ . '/../../../app/bootstrap.php';

use App\Models\Video;
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

// Get video ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Video ID required']);
    exit;
}

// Get video
$video = Video::find($id);
if (!$video) {
    http_response_code(404);
    echo json_encode(['error' => 'Video not found']);
    exit;
}

echo json_encode([
    'id' => $video->id,
    'title' => $video->title,
    'description' => $video->description,
    'is_visible' => $video->is_visible,
    'thumbnail' => $video->getThumbnailUrl(),
    'custom_thumbnail_id' => $video->custom_thumbnail_id,
    'youtube_id' => $video->youtube_id,
    'channel_name' => $video->channel_name,
    'duration' => $video->getDuration()
]);
exit;