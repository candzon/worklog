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

// Enforce role-based create permission using existing schema: users with role 'user' cannot create
$roleName = null;
if (isset($conn)) {
    $stmtRole = $conn->prepare("SELECT r.name AS role_name FROM employee e LEFT JOIN roles r ON e.role_id = r.id WHERE e.npp = ? LIMIT 1");
    if ($stmtRole) {
        $stmtRole->bind_param('s', $npp);
        $stmtRole->execute();
        $resRole = $stmtRole->get_result();
        if ($resRole) {
            $r = $resRole->fetch_assoc();
            $roleName = $r['role_name'] ?? null;
        }
        $stmtRole->close();
    }
}

if ($roleName === 'user') {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki izin untuk membuat pekerjaan baru']);
    http_response_code(403);
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

// Validate required fields
if ($deskripsi === '') {
    echo json_encode(['success' => false, 'message' => 'Deskripsi wajib diisi']);
    http_response_code(400);
    exit;
}
if ($tglMulai === '') {
    echo json_encode(['success' => false, 'message' => 'Tanggal Mulai wajib diisi']);
    http_response_code(400);
    exit;
}
if ($tglSelesai === '') {
    echo json_encode(['success' => false, 'message' => 'Tanggal Selesai wajib diisi']);
    http_response_code(400);
    exit;
}
if ($ditugaskan === '') {
    echo json_encode(['success' => false, 'message' => 'Pegawai yang ditugaskan wajib dipilih']);
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
