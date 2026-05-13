<?php
/**
 * api/mark_done.php
 * Menandai tugas manual selesai atau merealisasikan tugas rutin (master).
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
$nama_emp = $_SESSION['nama_emp'] ?? null;

if (!$npp) {
    echo json_encode(['success' => false, 'message' => 'Sesi habis, silakan login ulang.']);
    exit;
}

$id = $_POST['id'] ?? null; // ID untuk tugas manual
$masterId = $_POST['master_tugas_id'] ?? null; // ID untuk tugas rutin
$tglMulai = $_POST['tgl_mulai'] ?? null; // Tanggal kejadian tugas rutin
$lampiran = $_FILES['lampiran'] ?? null;

if (!$lampiran) {
    echo json_encode(['success' => false, 'message' => 'Lampiran bukti pekerjaan wajib diunggah.']);
    exit;
}

// Proses Upload Lampiran
$uploadDir = __DIR__ . '/../uploads/';
$fileExt = pathinfo($lampiran['name'], PATHINFO_EXTENSION);
$fileName = 'done_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $fileExt;
$targetPath = $uploadDir . $fileName;

if (!move_uploaded_file($lampiran['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengunggah lampiran.']);
    exit;
}

if ($masterId && $tglMulai) {
    // ALUR MASTER: Ambil data Master termasuk NPP & Nama Manager-nya
    $stmtM = $conn->prepare("SELECT mt.judul, mt.deskripsi, mt.periode, mt.npp_manager, e.nama_emp as nama_manager 
                             FROM master_tugas mt 
                             LEFT JOIN employee e ON e.npp = mt.npp_manager 
                             WHERE mt.id = ?");
    $stmtM->bind_param('i', $masterId);
    $stmtM->execute();
    $master = $stmtM->get_result()->fetch_assoc();
    $stmtM->close();

    if (!$master) {
        echo json_encode(['success' => false, 'message' => 'Master tugas tidak ditemukan.']);
        exit;
    }

    // Gunakan NPP dan Nama Manager sebagai pembuat tugas (Pemberi Tugas)
    $mgrNpp = $master['npp_manager'] ?: 'SYSTEM';
    $mgrName = $master['nama_manager'] ?: 'Manager';

    $sql = "INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, assigned_to_npp, tgl_mulai, tgl_selesai, status, lampiran, master_tugas_id, periode) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'done', ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssssis', $master['judul'], $master['deskripsi'], $mgrNpp, $mgrName, $npp, $tglMulai, $tglMulai, $fileName, $masterId, $master['periode']);
} else {
    // ALUR MANUAL
    $sql = "UPDATE pekerjaan SET status = 'done', lampiran = ?, tgl_selesai = CURDATE(), updated_at = NOW() WHERE id = ? AND assigned_to_npp = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sis', $fileName, $id, $npp);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pekerjaan berhasil diselesaikan.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status: ' . $conn->error]);
}
$stmt->close();
