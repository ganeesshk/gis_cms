<?php
// admin/audit/get-log.php

require_once __DIR__ . '/../../app/bootstrap.php';

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
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get log ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Log ID required']);
    exit;
}

// Get log
$log = AuditLog::find($id);
if (!$log) {
    http_response_code(404);
    echo json_encode(['error' => 'Log not found']);
    exit;
}

// Parse old/new values
$oldValues = $log->old_values ? json_decode($log->old_values, true) : null;
$newValues = $log->new_values ? json_decode($log->new_values, true) : null;

echo json_encode([
    'id' => $log->id,
    'user_id' => $log->user_id,
    'username' => $log->username,
    'ip_address' => $log->ip_address,
    'user_agent' => $log->user_agent,
    'session_id' => $log->session_id,
    'action' => $log->action,
    'entity_type' => $log->entity_type,
    'entity_id' => $log->entity_id,
    'entity_label' => $log->entity_label,
    'old_values' => $oldValues,
    'new_values' => $newValues,
    'result' => $log->result,
    'error_message' => $log->error_message,
    'created_at' => $log->created_at->format('Y-m-d H:i:s')
]);
exit;