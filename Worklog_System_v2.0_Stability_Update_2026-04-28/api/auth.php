<?php
/**
 * api/auth.php
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuthModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp = trim($_POST['npp'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($npp) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'NPP dan Password wajib diisi.']);
    exit;
}

$auth = new AuthModel($conn);
$user = $auth->verify($npp, $password);

if ($user) {
    // Set Session via helper
    $_SESSION['npp'] = $user['npp'];
    $_SESSION['nama_emp'] = $user['nama_emp'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['nama_bagian'] = $user['nama_bagian'];

    echo json_encode(['success' => true, 'message' => 'Login Berhasil.']);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'NPP atau Password salah.']);
}
exit;
