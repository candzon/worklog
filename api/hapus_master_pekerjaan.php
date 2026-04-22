<?php
/**
 * api/hapus_master_pekerjaan.php
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MasterModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp_session = $_SESSION['npp'] ?? null;
if (empty($npp_session)) { 
    http_response_code(401); echo json_encode(['success'=>false, 'message'=>'Sesi habis.']); exit; 
}

$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
if (!$id) {
    http_response_code(400); echo json_encode(['success'=>false, 'message'=>'ID tidak valid.']); exit;
}

$model = new MasterModel($conn);

try {
    // 1. Cek apakah sudah digunakan di tabel pekerjaan
    if ($model->isUsed($id)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'success' => false, 
            'type' => 'restricted',
            'message' => 'Anda tidak bisa menghapus master pekerjaan ini karena sudah ada pegawai yang menyelesaikannya.'
        ]);
        exit;
    }

    // 2. Jika aman, hapus
    if ($model->delete($id)) {
        echo json_encode(['success' => true, 'message' => 'Berhasil dihapus.']);
    } else {
        throw new Exception("Gagal menghapus data dari database.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
