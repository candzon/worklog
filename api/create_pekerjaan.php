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

// Ambil input: form-urlencoded atau JSON
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $input = $json;
}

$judul = trim($input['judul'] ?? '');
$deskripsi = trim($input['deskripsi'] ?? '');
$tglMulai = trim($input['tgl_mulai'] ?? ''); // YYYY-MM-DD atau ''
$tglSelesai = trim($input['tgl_selesai'] ?? ''); // YYYY-MM-DD atau ''
// Terima kedua kemungkinan nama field dari form/JS
$ditugaskan = trim($input['ditugaskan_npp'] ?? $input['ditugaskan'] ?? '');

if ($judul === '') {
    echo json_encode(['success' => false, 'message' => 'Judul wajib diisi']);
    http_response_code(400);
    exit;
}

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Database tidak tersedia']);
    http_response_code(500);
    exit;
}

$stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, npp, nama_emp, tgl_mulai, tgl_selesai, ditugaskan) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query']);
    http_response_code(500);
    exit;
}

$stmt->bind_param('sssssss', $judul, $deskripsi, $npp, $nama_emp, $tglMulai, $tglSelesai, $ditugaskan);
$ok = $stmt->execute();
$insertId = $stmt->insert_id;
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true, 'id' => $insertId, 'message' => 'Tersimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan', 'error' => $conn->error]);
    http_response_code(500);
}
