<?php
// admin/logout.php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Security\Session;
use App\Security\CSRF;

$config = require __DIR__ . '/../app/Config/config.php';
$session = Session::getInstance($config['security']);
$csrf = new CSRF($session, $config['security']);
$auth = new AuthService($session, $csrf, $config);

// Perform logout
$auth->logout();

// Redirect to login page
header('Location: login.php');
exit;