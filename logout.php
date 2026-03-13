<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';

// Clear session and redirect to login
// enqueue a SweetAlert message so login page can show it after redirect
if (function_exists('flash_swal')) {
	flash_swal('success', 'Logout berhasil', 'Anda telah keluar dari sistem');
}
clear_user_session();
$login = site_url('login.php');
header('Location: ' . $login);
exit;
