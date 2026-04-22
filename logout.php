<?php
/**
 * logout.php
 */
require_once __DIR__ . '/functions/helpers.php';

ensure_session_started();
clear_user_session();

header('Location: login.php');
exit;
