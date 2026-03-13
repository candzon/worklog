<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    $sql = "SELECT id, judul, deskripsi, due_date, created_at, ditugaskan AS assigned_npp, status, npp AS reporter_npp, nama_emp AS reporter_name FROM pekerjaan";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $start = $r['due_date'] ?: $r['created_at'];
            $startIso = $start ? date('Y-m-d\TH:i:s', strtotime($start)) : null;
            if ($startIso === null) continue;
            $events[] = [
                'id' => $r['id'],
                'title' => $r['judul'],
                'start' => $startIso,
                'color' => ($r['status'] === 'done') ? '#28a745' : '#007bff',
            ];
        }
        $res->free();
    }
}
echo json_encode($events, JSON_UNESCAPED_UNICODE);
