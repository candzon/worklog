<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';

// Clear session and redirect to login
clear_user_session();
$login = site_url('login.php');
header('Location: ' . $login);
exit;
