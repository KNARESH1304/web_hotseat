<?php
// logout.php - wipes the session completely (POST + token only)
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('index.php');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $c = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
}
session_destroy();
setcookie('dond_can_resume', '', time() - 3600, '/');

session_start();               // fresh empty session just to carry the messages
session_regenerate_id(true);
flash_set('ok', 'You have been logged out.');
redirect('login.php');
