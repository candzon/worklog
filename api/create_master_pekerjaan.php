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

$judul = trim($_POST['judul'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$periode = trim($_POST['periode'] ?? 'bulanan');
$target_tgl = !empty($_POST['target_tgl']) ? $_POST['target_tgl'] : null;
$bagian_id = !empty($_POST['bagian_id']) ? intval($_POST['bagian_id']) : null;

if ($judul === '' || empty($target_tgl) || empty($bagian_id)) {
    flash_swal('error', 'Gagal', 'Semua field wajib diisi.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

// 1. Simpan baris Master Utama
$stmt = $conn->prepare('INSERT INTO master_tugas (judul, deskripsi, periode, target_tgl, bagian_id, npp_manager, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
if ($stmt) {
    $stmt->bind_param('ssssis', $judul, $deskripsi, $periode, $target_tgl, $bagian_id, $npp_created);
    if ($stmt->execute()) {
        $masterId = $conn->insert_id;
        
        // 2. CARI PEGAWAI: Mencocokkan bagian_id dengan KOLOM nama_bagian (yang isinya ID)
        $sqlEmp = "SELECT npp FROM employee WHERE nama_bagian = ?";
        $stmtEmp = $conn->prepare($sqlEmp);
        $s_bagian_id = (string)$bagian_id; // Kolom nama_bagian bertipe varchar
        $stmtEmp->bind_param('s', $s_bagian_id);
        $stmtEmp->execute();
        $resEmp = $stmtEmp->get_result();
        
        // 3. Masukkan ke detail penugasan
        $stmtDetail = $conn->prepare('INSERT INTO master_tugas_detail (master_tugas_id, npp) VALUES (?, ?)');
        $count = 0;
        while ($row = $resEmp->fetch_assoc()) {
            $npp_to_add = $row['npp'];
            $stmtDetail->bind_param('is', $masterId, $npp_to_add);
            if ($stmtDetail->execute()) $count++;
        }
        $stmtDetail->close();
        $stmtEmp->close();

        if ($count > 0) {
            flash_swal('success', 'Tersimpan', "Master tugas berhasil ditugaskan ke $count pegawai.");
        } else {
            flash_swal('warning', 'Tersimpan (0 Pegawai)', "Master tersimpan, namun tidak ditemukan pegawai dengan ID Bagian $bagian_id di kolom 'nama_bagian'.");
        }
    } else {
        flash_swal('error', 'Gagal', 'DB Error: ' . $conn->error);
    }
    $stmt->close();
}

header('Location: ' . site_url('master_pekerjaan.php'));
exit;
