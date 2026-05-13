<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MasterModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp_session = $_SESSION['npp'] ?? null;
if (empty($npp_session)) { 
    http_response_code(401); echo json_encode(['success'=>false, 'message'=>'Sesi habis.']); exit; 
}

// Re-check Manager Role
$stmtR = $conn->prepare("SELECT role_id FROM employee WHERE npp = ?");
$stmtR->bind_param('s', $npp_session);
$stmtR->execute();
$resR = $stmtR->get_result()->fetch_assoc();
$isAuthorized = ($resR && ($resR['role_id'] == 1 || $resR['role_id'] == 2));

if (!$isAuthorized) {
    http_response_code(403); echo json_encode(['success'=>false, 'message'=>'Forbidden: Akses ditolak.']); exit;
}

$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
$bagian_id = !empty($_POST['bagian_id']) ? intval($_POST['bagian_id']) : null;

$data = [
    'judul' => trim($_POST['judul'] ?? ''),
    'deskripsi' => trim($_POST['deskripsi'] ?? ''),
    'periode' => $_POST['periode'] ?? 'bulanan',
    'target_tgl' => $_POST['target_tgl'] ?? null,
    'bagian_id' => $bagian_id
];

if (!$id || empty($data['judul']) || !$bagian_id) {
    http_response_code(400); echo json_encode(['success'=>false, 'message'=>'Data tidak lengkap.']); exit;
}

$model = new MasterModel($conn);
if ($model->update($id, $data)) {
    $model->syncDetails($id, $bagian_id);
    echo json_encode(['success' => true, 'message' => 'Master tugas berhasil diperbarui.']);
} else {
    http_response_code(500); echo json_encode(['success' => false, 'message' => 'Gagal memperbarui database.']);
}
exit;
