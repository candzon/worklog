<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
ensure_session_started();

$npp_created = $_SESSION['npp'] ?? null;
if (empty($npp_created)) {
    flash_swal('error', 'Unauthorized', 'Anda harus login.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

// only managers can create masters
$role = null;
if (isset($conn)) {
    $stmt = $conn->prepare('SELECT r.name FROM employee e LEFT JOIN roles r ON e.role_id=r.id WHERE e.npp = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $npp_created);
        $stmt->execute();
        $res = $stmt->get_result();
        $r = $res ? $res->fetch_assoc() : null;
        $role = $r['name'] ?? null;
        $stmt->close();
    }
}
if (strtolower(trim((string) $role)) !== 'manager') {
    flash_swal('error', 'Forbidden', 'Akses ditolak.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json))
        $input = $json;
}

$judul = trim($input['judul'] ?? '');
$deskripsi = trim($input['deskripsi'] ?? '');
$npp_owner = trim($input['npp'] ?? '');
$bagian_id = isset($input['bagian_id']) && $input['bagian_id'] !== '' ? intval($input['bagian_id']) : null;
$periode = trim($input['periode'] ?? 'bulanan');
$create_instance = !empty($input['create_instance']);
$instance_tgl_mulai = trim($input['tgl_mulai'] ?? '');
$instance_tgl_selesai = trim($input['tgl_selesai'] ?? '');

// basic validation
if ($judul === '') {
    flash_swal('error', 'Gagal', 'Judul wajib.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

$hasNppManager = false;
if (isset($conn)) {
    $col = $conn->query("SHOW COLUMNS FROM master_tugas LIKE 'npp_manager'");
    if ($col) {
        $hasNppManager = ($col->num_rows > 0);
        $col->free();
    }
}

if (!isset($conn)) {
    flash_swal('error', 'DB Error', 'Koneksi database tidak tersedia.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

if ($hasNppManager) {
    $npp_manager = $_SESSION['npp'] ?? null;
    $stmt = $conn->prepare('INSERT INTO master_tugas (judul, deskripsi, npp, bagian_id, periode, npp_manager, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    if ($stmt) {
        $stmt->bind_param('sssiss', $judul, $deskripsi, $npp_owner, $bagian_id, $periode, $npp_manager);
        $ok = $stmt->execute();
        $masterId = $stmt->insert_id;
        $stmt->close();
    } else {
        $ok = false;
    }
} else {
    $stmt = $conn->prepare('INSERT INTO master_tugas (judul, deskripsi, npp, bagian_id, periode, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    if ($stmt) {
        $stmt->bind_param('sssis', $judul, $deskripsi, $npp_owner, $bagian_id, $periode);
        $ok = $stmt->execute();
        $masterId = $stmt->insert_id;
        $stmt->close();
    } else {
        $ok = false;
    }
}

if (!$ok) {
    flash_swal('error', 'Gagal', 'Gagal menyimpan master: ' . ($conn->error ?? ''));
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

// optionally create a pekerjaan row with status 'open' tied to this master
$createdPekerjaanId = null;
if ($create_instance) {
    $nama_emp = $_SESSION['nama_emp'] ?? null;
    $assigned = $npp_owner ?: null;
    $stmt2 = $conn->prepare('INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, master_tugas_id, periode, status) VALUES (?, ?, ?, ?, NULLIF(?,""), NULLIF(?,""), ?, ?, ?, "open")');
    if ($stmt2) {
        $stmt2->bind_param('sssssssi', $judul, $deskripsi, $npp_created, $nama_emp, $instance_tgl_mulai, $instance_tgl_selesai, $assigned, $masterId, $periode);
        $ok2 = $stmt2->execute();
        $createdPekerjaanId = $stmt2->insert_id;
        $stmt2->close();
        if (!$ok2) {
            flash_swal('warning', 'Perhatian', 'Master tersimpan tapi pembuatan pekerjaan gagal: ' . ($conn->error ?? ''));
            header('Location: ' . site_url('master_pekerjaan.php'));
            exit;
        }
    }
}

flash_swal('success', 'Tersimpan', 'Master tugas berhasil disimpan.');
header('Location: ' . site_url('master_pekerjaan.php'));
exit;