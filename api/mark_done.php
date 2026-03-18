<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();

header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
if (empty($npp)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare('SELECT assigned_to_npp FROM pekerjaan WHERE id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    http_response_code(500);
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    http_response_code(404);
    exit;
}

$assigned = $row['assigned_to_npp'];
if ($assigned !== $npp) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    http_response_code(403);
    exit;
}

$up = $conn->prepare("UPDATE pekerjaan SET status = 'done', updated_at = NOW() WHERE id = ?");
if (!$up) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    http_response_code(500);
    exit;
}
$up->bind_param('i', $id);
$ok = $up->execute();
$up->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed']);
    http_response_code(500);
}
