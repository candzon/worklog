<?php
/**
 * api/request_revision.php
 * Endpoint untuk Manager meminta revisi pada tugas yang sudah Done.
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

// Hanya Manager/Admin yang boleh meminta revisi
$roleName = strtolower((string) ($_SESSION['role_name'] ?? ''));
if ($roleName !== 'manager' && $roleName !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Hanya Manager yang dapat meminta revisi.']);
    exit;
}

$id = $_POST['id'] ?? null;
$catatan = trim($_POST['catatan'] ?? '');

if (!$id || !$catatan) {
    echo json_encode(['success' => false, 'message' => 'ID tugas dan catatan revisi wajib diisi.']);
    exit;
}

try {
    // Cari tugasnya (pastikan statusnya sudah done)
    $stmt = $conn->prepare("SELECT status FROM pekerjaan WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $task = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan.']);
        exit;
    }

    if (!in_array(strtolower($task['status']), ['done', 'selesai', 'completed'])) {
        echo json_encode(['success' => false, 'message' => 'Hanya tugas yang sudah selesai yang dapat direvisi.']);
        exit;
    }

    // Update status ke 'revisi' dan simpan catatan
    $stmt = $conn->prepare("UPDATE pekerjaan SET status = 'revisi', catatan_revisi = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $catatan, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Permintaan revisi berhasil dikirim ke pegawai.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data: ' . $conn->error]);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}
exit;
