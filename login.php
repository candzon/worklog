<?php
/**
 * login.php (Controller)
 */
require_once __DIR__ . '/functions/helpers.php';

ensure_session_started();

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Load Login View
require_once __DIR__ . '/views/login.php';
