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

$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
$judul = trim($_POST['judul'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$npp = trim($_POST['npp'] ?? '');
$bagian_id = (isset($_POST['bagian_id']) && $_POST['bagian_id'] !== '') ? intval($_POST['bagian_id']) : null;
$periode = $_POST['periode'] ?? 'bulanan';

if ($judul === '') {
    flash_swal('error','Gagal','Judul wajib diisi.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

if (!isset($conn)) {
    flash_swal('error','DB Error','Koneksi database tidak tersedia.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

$hasMasterNppManagerCol = false;
$colRes = $conn->query("SHOW COLUMNS FROM master_tugas LIKE 'npp_manager'");
if ($colRes) { $hasMasterNppManagerCol = ($colRes->num_rows > 0); $colRes->free(); }

if ($id) {
    $stmt = $conn->prepare("UPDATE master_tugas SET judul = ?, deskripsi = ?, npp = ?, bagian_id = ?, periode = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('sssisi', $judul, $deskripsi, $npp, $bagian_id, $periode, $id);
        $stmt->execute();
        $stmt->close();
        flash_swal('success','Tersimpan','Master tugas diperbarui.');
    } else {
        flash_swal('error','Gagal','Query update gagal.');
    }
} else {
    if ($hasMasterNppManagerCol) {
        $npp_manager = $_SESSION['npp'] ?? null;
        $stmt = $conn->prepare("INSERT INTO master_tugas (judul, deskripsi, npp, bagian_id, periode, npp_manager, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('sssiss', $judul, $deskripsi, $npp, $bagian_id, $periode, $npp_manager);
            $stmt->execute();
            $stmt->close();
            flash_swal('success','Tersimpan','Master tugas ditambahkan.');
        } else {
            flash_swal('error','Gagal','Query insert gagal.');
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO master_tugas (judul, deskripsi, npp, bagian_id, periode, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('sssis', $judul, $deskripsi, $npp, $bagian_id, $periode);
            $stmt->execute();
            $stmt->close();
            flash_swal('success','Tersimpan','Master tugas ditambahkan.');
        } else {
            flash_swal('error','Gagal','Query insert gagal.');
        }
    }
}

flash_swal('success', 'Edit Berhasil', 'Master tugas berhasil disimpan.');
header('Location: ' . site_url('master_pekerjaan.php'));
exit;