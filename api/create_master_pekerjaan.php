<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MasterModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp_created = $_SESSION['npp'] ?? null;
if (empty($npp_created)) { 
    http_response_code(401); 
    echo json_encode(['success'=>false, 'message'=>'Sesi habis, silakan login kembali.']); 
    exit; 
}

// Re-check Manager Role
$isManager = false;
$stmtR = $conn->prepare("SELECT role_id FROM employee WHERE npp = ?");
$stmtR->bind_param('s', $npp_created);
$stmtR->execute();
$resR = $stmtR->get_result()->fetch_assoc();
if ($resR && ($resR['role_id'] == 1 || $resR['role_id'] == 2)) {
    $isManager = true;
}
$stmtR->close();

if (!$isManager) {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Akses Ditolak: Hanya Manager/Admin yang diizinkan.']);
    exit;
}

$model = new MasterModel($conn);
$data = [
    'judul' => trim($_POST['judul'] ?? ''),
    'deskripsi' => trim($_POST['deskripsi'] ?? ''),
    'periode' => trim($_POST['periode'] ?? 'bulanan'),
    'target_tgl' => $_POST['target_tgl'] ?? null,
    'bagian_id' => !empty($_POST['bagian_id']) ? intval($_POST['bagian_id']) : null,
    'npp_manager' => $npp_created
];

try {
    $masterId = $model->create($data);
    if ($masterId) {
        $model->syncDetails($masterId, $data['bagian_id']);
        echo json_encode(['success' => true, 'message' => 'Master tugas berhasil dibuat.']);
    } else {
        throw new Exception("Gagal menyimpan ke database.");
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
