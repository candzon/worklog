<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
ensure_session_started();

$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
$judul = trim($_POST['judul'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$periode = $_POST['periode'] ?? 'bulanan';
$target_tgl = !empty($_POST['target_tgl']) ? $_POST['target_tgl'] : null;
$bagian_id = !empty($_POST['bagian_id']) ? intval($_POST['bagian_id']) : null;

if (!$id || $judul === '' || !$target_tgl || !$bagian_id) {
    flash_swal('error','Gagal','Semua field wajib diisi.');
    header('Location: ' . site_url('master_pekerjaan.php'));
    exit;
}

// 1. Update baris Master Utama
$stmt = $conn->prepare("UPDATE master_tugas SET judul = ?, deskripsi = ?, periode = ?, target_tgl = ?, bagian_id = ?, npp = NULL WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('ssssii', $judul, $deskripsi, $periode, $target_tgl, $bagian_id, $id);
    if ($stmt->execute()) {
        
        // 2. Hapus detail penugasan lama
        $stmtDel = $conn->prepare("DELETE FROM master_tugas_detail WHERE master_tugas_id = ?");
        $stmtDel->bind_param('i', $id);
        $stmtDel->execute();
        $stmtDel->close();

        // 3. Cari NPP baru: Mencocokkan bagian_id dengan KOLOM nama_bagian
        $sqlEmp = "SELECT npp FROM employee WHERE nama_bagian = ?";
        $stmtEmp = $conn->prepare($sqlEmp);
        $s_bagian_id = (string)$bagian_id;
        $stmtEmp->bind_param('s', $s_bagian_id);
        $stmtEmp->execute();
        $resEmp = $stmtEmp->get_result();

        // 4. Masukkan kembali detail
        $stmtDetail = $conn->prepare('INSERT INTO master_tugas_detail (master_tugas_id, npp) VALUES (?, ?)');
        $count = 0;
        while ($row = $resEmp->fetch_assoc()) {
            $npp_to_add = $row['npp'];
            $stmtDetail->bind_param('is', $id, $npp_to_add);
            if ($stmtDetail->execute()) $count++;
        }
        $stmtDetail->close();
        $stmtEmp->close();

        flash_swal('success','Tersimpan','Master tugas diperbarui untuk ' . $count . ' pegawai.');
    } else {
        flash_swal('error','Gagal','DB Error: ' . $conn->error);
    }
    $stmt->close();
}

header('Location: ' . site_url('master_pekerjaan.php'));
exit;
