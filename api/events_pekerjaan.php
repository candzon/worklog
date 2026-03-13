<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    $sql = "SELECT p.id, p.judul, p.deskripsi, p.tgl_mulai, p.tgl_selesai, p.created_at, p.status, p.ditugaskan, p.npp AS reporter_npp, p.nama_emp AS reporter_name, e.nama_emp AS assigned_name
            FROM pekerjaan p
            LEFT JOIN employee e ON e.npp = p.ditugaskan";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $startDate = $r['tgl_mulai'] ?: null;
            if (!$startDate && !empty($r['created_at'])) {
                $startDate = date('Y-m-d', strtotime($r['created_at']));
            }

            if (empty($startDate)) continue;

            // Calendar currently hides time, so treat as all-day event
            $event = [
                'id' => $r['id'],
                'title' => $r['judul'],
                'start' => $startDate,
                'allDay' => true,
                'color' => ($r['status'] === 'done') ? '#28a745' : '#007bff',
                'extendedProps' => [
                    'description' => $r['deskripsi'],
                    'status' => $r['status'],
                    'ditugaskan' => $r['ditugaskan'],
                    'assigned_name' => $r['assigned_name'],
                    'reporter_npp' => $r['reporter_npp'],
                    'reporter_name' => $r['reporter_name'],
                    'tgl_mulai' => $r['tgl_mulai'],
                    'tgl_selesai' => $r['tgl_selesai'],
                ],
            ];

            // If end date exists, FullCalendar expects all-day end to be exclusive (+1 day)
            if (!empty($r['tgl_selesai'])) {
                $endExclusive = date('Y-m-d', strtotime($r['tgl_selesai'] . ' +1 day'));
                $event['end'] = $endExclusive;
            }

            $events[] = $event;
        }
        $res->free();
    }
}
echo json_encode($events, JSON_UNESCAPED_UNICODE);
