<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    // If the current user has role 'user', only return events assigned to them
    $roleName = null;
    if (!empty($_SESSION['npp'])) {
        $stmtR = $conn->prepare("SELECT r.name AS role_name FROM employee e LEFT JOIN roles r ON e.role_id = r.id WHERE e.npp = ? LIMIT 1");
        if ($stmtR) {
            $stmtR->bind_param('s', $_SESSION['npp']);
            $stmtR->execute();
            $resR = $stmtR->get_result();
            if ($resR) {
                $rowR = $resR->fetch_assoc();
                $roleName = $rowR['role_name'] ?? null;
            }
            $stmtR->close();
        }
    }

    if ($roleName === 'user' && !empty($_SESSION['npp'])) {
        $sql = "SELECT p.id, p.judul, p.deskripsi, p.tgl_mulai, p.tgl_selesai, p.created_at, p.updated_at, p.status, p.ditugaskan, p.npp AS reporter_npp, p.nama_emp AS reporter_name, e.nama_emp AS assigned_name
                FROM pekerjaan p
                LEFT JOIN employee e ON e.npp = p.ditugaskan
                WHERE p.ditugaskan = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $_SESSION['npp']);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = false;
        }
    } else {
        $sql = "SELECT p.id, p.judul, p.deskripsi, p.tgl_mulai, p.tgl_selesai, p.created_at, p.updated_at, p.status, p.ditugaskan, p.npp AS reporter_npp, p.nama_emp AS reporter_name, e.nama_emp AS assigned_name
                FROM pekerjaan p
                LEFT JOIN employee e ON e.npp = p.ditugaskan";
        $res = $conn->query($sql);
    }

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
                    // formatted date strings (use PHP helpers)
                    'tgl_mulai_fmt' => (!empty($r['tgl_mulai']) ? format_date_id($r['tgl_mulai']) : ''),
                    'tgl_selesai_fmt' => (!empty($r['tgl_selesai']) ? format_date_id($r['tgl_selesai']) : ''),
                    'done_at' => $r['updated_at'] ?? null,
                    'created_at_fmt' => (!empty($r['created_at']) ? format_datetime_id($r['created_at']) : ''),
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
