<?php
/**
 * api/create_pekerjaan.php
 * Endpoint untuk membuat pekerjaan manual via AJAX
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TaskModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
if (empty($npp)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$model = new TaskModel($conn);

// Handle File Upload
$lampiran = null;
if (!empty($_FILES['lampiran']['name'])) {
    $file = $_FILES['lampiran'];
    if ($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File terlalu besar (Maks 2MB)']);
        exit;
    }
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = 'task_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
        $lampiran = $newName;
    }
}

$data = [
    'judul' => trim($_POST['judul'] ?? ''),
    'deskripsi' => trim($_POST['deskripsi'] ?? ''),
    'created_by_npp' => $npp,
    'nama_emp' => $_SESSION['nama_emp'] ?? $npp,
    'tgl_mulai' => $_POST['tgl_mulai'] ?? null,
    'tgl_selesai' => $_POST['tgl_selesai'] ?? null,
    'assigned_to_npp' => $_POST['assigned_to_npp'] ?? $npp,
    'master_tugas_id' => null,
    'periode' => 'manual',
    'status' => 'open',
    'lampiran' => $lampiran
];

if (empty($data['judul']) || empty($data['assigned_to_npp'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Judul dan Pegawai wajib diisi']);
    exit;
}

$insertId = $model->create($data);

if ($insertId) {
    echo json_encode(['success' => true, 'message' => 'Pekerjaan berhasil dibuat']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
}
