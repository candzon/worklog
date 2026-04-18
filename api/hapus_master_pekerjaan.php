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

// Cek apakah ada pekerjaan yang masih merujuk ke master_tugas ini
$check = $conn->prepare('SELECT COUNT(*) FROM pekerjaan WHERE master_tugas_id = ?');
if (!$check) {
    flash_swal('error','Gagal','Terjadi kesalahan pada pengecekan dependensi.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}
$check->bind_param('i', $id);
$check->execute();
$check->bind_result($cnt);
$check->fetch();
$check->close();

if ($cnt > 0) {
    // Pesan untuk user awam, jelas dan singkat
    $msg = "Master tugas ini tidak dapat dihapus karena ada {$cnt} pekerjaan yang menggunakan tugas ini. Silakan hapus atau ubah tugas pada pekerjaan terkait terlebih dahulu.";
    flash_swal('error', 'Tidak Bisa Dihapus', $msg);
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

// Jika tidak ada dependensi, lanjut hapus
$del = $conn->prepare('DELETE FROM master_tugas WHERE id = ?');
if ($del) {
    $del->bind_param('i', $id);
    if ($del->execute()) {
        $del->close();
        flash_swal('success','Dihapus','Master tugas berhasil dihapus.');
    } else {
        $del->close();
        flash_swal('error','Gagal','Gagal menghapus master tugas. Silakan coba lagi.');
    }
} else {
    flash_swal('error','Gagal','Query hapus gagal dibuat.');
}

header('Location: ' . site_url('master_pekerjaan.php'));
exit;