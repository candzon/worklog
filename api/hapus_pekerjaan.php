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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID wajib']);
    exit;
}

if (!isset($conn)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB unavailable']);
    exit;
}

// permission: only creator or manager can delete (adjust as needed)
$stmt = $conn->prepare('SELECT created_by_npp FROM pekerjaan WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

$canDelete = false;
if ($row['created_by_npp'] === $npp)
    $canDelete = true;
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
    $canDelete = true;

if (!$canDelete) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM pekerjaan WHERE id = ?');
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
$stmt->close();
if ($ok)
    echo json_encode(['success' => true]);
else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal hapus', 'error' => $conn->error]);
}

