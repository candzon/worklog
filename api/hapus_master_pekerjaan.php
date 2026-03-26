<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
ensure_session_started();

$nppSession = $_SESSION['npp'] ?? null;
if (empty($nppSession)) {
    flash_swal('error','Unauthorized','Anda harus login.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

$roleName = function_exists('get_current_role_name') ? get_current_role_name($conn ?? null) : null;
$isManager = function_exists('role_is') ? role_is($roleName, 'manager') : (strtolower((string)$roleName) === 'manager');
if (!$isManager) {
    flash_swal('error','Forbidden','Akses ditolak.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    flash_swal('error','Invalid','ID tidak valid.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

if (!isset($conn)) {
    flash_swal('error','DB Error','Koneksi database tidak tersedia.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

$stmt = $conn->prepare('DELETE FROM master_tugas WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    flash_swal('success','Dihapus','Master tugas dihapus.');
} else {
    flash_swal('error','Gagal','Query hapus gagal.');
}

flash_swal('success', 'Hapus Berhasil', 'Master tugas berhasil terimpan.');
header('Location: ' . site_url('master_pekerjaan.php'));
exit;