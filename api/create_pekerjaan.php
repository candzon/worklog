<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
ensure_session_started();

header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
$nama_emp = $_SESSION['nama_emp'] ?? null;

if (empty($npp)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

$judul = trim($_POST['judul'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');
$tglMulai = trim($_POST['tgl_mulai'] ?? '');
$tglSelesai = trim($_POST['tgl_selesai'] ?? '');
$assigned_to_npp = trim($_POST['assigned_to_npp'] ?? '');
$status = trim($_POST['status'] ?? 'open');

if ($judul === '' || $assigned_to_npp === '') {
    echo json_encode(['success' => false, 'message' => 'Judul dan Pegawai wajib diisi']);
    http_response_code(400);
    exit;
}

// Handle File Upload
$lampiran = null;
if (!empty($_FILES['lampiran']['name'])) {
    $file = $_FILES['lampiran'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedExt)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung.']);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (Maks 5MB).']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $newName = 'task_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
        $lampiran = $newName;
    }
}

$stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, status, lampiran) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param('sssssssss', $judul, $deskripsi, $npp, $nama_emp, $tglMulai, $tglSelesai, $assigned_to_npp, $status, $lampiran);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Tersimpan' : 'Gagal simpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
