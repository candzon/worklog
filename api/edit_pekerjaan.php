<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
if (empty($npp)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json))
        $input = $json;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID pekerjaan wajib']);
    exit;
}

$judul = trim($input['judul'] ?? '');
$deskripsi = trim($input['deskripsi'] ?? '');
$assigned = trim($input['assigned_to_npp'] ?? '');
$tgl_mulai = trim($input['tgl_mulai'] ?? '');
$tgl_selesai = trim($input['tgl_selesai'] ?? '');
$status = trim($input['status'] ?? '');

if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB unavailable']);
    exit;
}

// permission: manager or creator or assigned can edit (adjust to your policy)
$stmt = $conn->prepare('SELECT created_by_npp, assigned_to_npp FROM pekerjaan WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Pekerjaan tidak ditemukan']);
    exit;
}
$canEdit = false;
if ($row['created_by_npp'] === $npp || $row['assigned_to_npp'] === $npp)
    $canEdit = true;
// allow managers
$roleName = null;
$stmtR = $conn->prepare('SELECT r.name FROM employee e LEFT JOIN roles r ON e.role_id=r.id WHERE e.npp = ? LIMIT 1');
if ($stmtR) {
    $stmtR->bind_param('s', $npp);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    $rr = $resR ? $resR->fetch_assoc() : null;
    $roleName = $rr['name'] ?? null;
    $stmtR->close();
}
if (strtolower(trim((string) $roleName)) === 'manager')
    $canEdit = true;

if (!$canEdit) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Build update dynamically
$fields = [];
$params = [];
$types = '';
if ($judul !== '') {
    $fields[] = 'judul = ?';
    $params[] = $judul;
    $types .= 's';
}
if ($deskripsi !== '') {
    $fields[] = 'deskripsi = ?';
    $params[] = $deskripsi;
    $types .= 's';
}
if ($assigned !== '') {
    $fields[] = 'assigned_to_npp = ?';
    $params[] = $assigned;
    $types .= 's';
}
if ($tgl_mulai !== '') {
    $fields[] = 'tgl_mulai = NULLIF(?, \'\')';
    $params[] = $tgl_mulai;
    $types .= 's';
}
if ($tgl_selesai !== '') {
    $fields[] = 'tgl_selesai = NULLIF(?, \'\')';
    $params[] = $tgl_selesai;
    $types .= 's';
}
if ($status !== '') {
    $fields[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}

if (empty($fields)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada perubahan']);
    exit;
}

$sql = 'UPDATE pekerjaan SET ' . implode(', ', $fields) . ' WHERE id = ?';
$params[] = $id;
$types .= 'i';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$ok = $stmt->execute();
$stmt->close();
if ($ok)
    echo json_encode(['success' => true]);
else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal update', 'error' => $conn->error]);
}

