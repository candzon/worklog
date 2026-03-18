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

// Ensure nama_emp is available (fallback to DB if session is missing)
if (empty($nama_emp) && isset($conn)) {
    $stmtEmp = $conn->prepare('SELECT nama_emp FROM employee WHERE npp = ? LIMIT 1');
    if ($stmtEmp) {
        $stmtEmp->bind_param('s', $npp);
        $stmtEmp->execute();
        $resEmp = $stmtEmp->get_result();
        if ($resEmp) {
            $rowEmp = $resEmp->fetch_assoc();
            $nama_emp = $rowEmp['nama_emp'] ?? $nama_emp;
            if (!empty($nama_emp)) {
                $_SESSION['nama_emp'] = $nama_emp;
            }
        }
        $stmtEmp->close();
    }
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

if (strtolower(trim((string) $roleName)) !== 'manager') {
    echo json_encode(['success' => false, 'message' => 'Hanya role manager yang dapat membuat pekerjaan baru']);
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
$ditugaskan = trim($input['assigned_to_npp'] ?? $input['ditugaskan_npp'] ?? $input['ditugaskan'] ?? '');
$master_tugas_id = trim($input['master_tugas_id'] ?? '');
$periode = trim($input['periode'] ?? '');

// If master_tugas_id is provided, use master_tugas as the source of truth for
// judul/deskripsi/assigned_to_npp/periode so users don't need to fill them manually.
if ($master_tugas_id !== '') {
    if (!isset($conn)) {
        echo json_encode(['success' => false, 'message' => 'Database tidak tersedia']);
        http_response_code(500);
        exit;
    }
    if (!ctype_digit((string) $master_tugas_id)) {
        echo json_encode(['success' => false, 'message' => 'Master Tugas tidak valid']);
        http_response_code(400);
        exit;
    }
    $mid = (int) $master_tugas_id;
    $stmtMt = $conn->prepare('SELECT judul, deskripsi, npp, periode FROM master_tugas WHERE id = ? LIMIT 1');
    if (!$stmtMt) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query master']);
        http_response_code(500);
        exit;
    }
    $stmtMt->bind_param('i', $mid);
    $stmtMt->execute();
    $resMt = $stmtMt->get_result();
    $mt = $resMt ? $resMt->fetch_assoc() : null;
    $stmtMt->close();

    if (!$mt) {
        echo json_encode(['success' => false, 'message' => 'Master Tugas tidak ditemukan']);
        http_response_code(400);
        exit;
    }

    $judul = trim($mt['judul'] ?? '');
    $deskripsi = trim($mt['deskripsi'] ?? '');
    $ditugaskan = trim($mt['npp'] ?? '');
    if ($periode === '') {
        $periode = trim($mt['periode'] ?? '');
    }
}

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

// Insert using updated schema: created_by_npp, nama_emp, assigned_to_npp, optional master_tugas_id and periode
$stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, master_tugas_id, periode) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?,''), NULLIF(?,''))");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query']);
    http_response_code(500);
    exit;
}
$stmt->bind_param('sssssssss', $judul, $deskripsi, $npp, $nama_emp, $tglMulai, $tglSelesai, $ditugaskan, $master_tugas_id, $periode);
$ok = $stmt->execute();
$insertId = $stmt->insert_id;
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true, 'id' => $insertId, 'message' => 'Tersimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan', 'error' => $conn->error]);
    http_response_code(500);
}
