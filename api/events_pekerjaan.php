<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    // Optional column support: npp_manager on master_tugas
    $hasMasterNppManagerCol = false;
    $colRes = $conn->query("SHOW COLUMNS FROM master_tugas LIKE 'npp_manager'");
    if ($colRes) {
        $hasMasterNppManagerCol = ($colRes->num_rows > 0);
        $colRes->free();
    }

    // FullCalendar passes start/end range as query params (end is typically exclusive)
    $rangeStart = null;
    $rangeEnd = null;
    try {
        if (!empty($_GET['start'])) $rangeStart = new DateTimeImmutable($_GET['start']);
    } catch (Exception $e) {
        $rangeStart = null;
    }
    try {
        if (!empty($_GET['end'])) $rangeEnd = new DateTimeImmutable($_GET['end']);
    } catch (Exception $e) {
        $rangeEnd = null;
    }
    if (!$rangeStart) $rangeStart = new DateTimeImmutable('first day of this month 00:00:00');
    if (!$rangeEnd) $rangeEnd = new DateTimeImmutable('first day of next month 00:00:00');

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
        $sql = "SELECT p.id, p.judul, p.deskripsi, p.tgl_mulai, p.tgl_selesai, p.created_at, p.updated_at, p.status, p.assigned_to_npp AS ditugaskan, p.created_by_npp AS reporter_npp, COALESCE(NULLIF(p.nama_emp,''), er.nama_emp) AS reporter_name,
                   e.nama_emp AS assigned_name, p.master_tugas_id, mt.judul AS master_judul,
                   CASE WHEN p.master_tugas_id IS NOT NULL THEN 'manager' ELSE rr.name END AS reporter_role
                FROM pekerjaan p
                LEFT JOIN employee e ON e.npp = p.assigned_to_npp
                LEFT JOIN master_tugas mt ON mt.id = p.master_tugas_id
                LEFT JOIN employee er ON er.npp = p.created_by_npp
                LEFT JOIN roles rr ON rr.id = er.role_id
                WHERE p.assigned_to_npp = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $_SESSION['npp']);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = false;
        }
    } else {
        $sql = "SELECT p.id, p.judul, p.deskripsi, p.tgl_mulai, p.tgl_selesai, p.created_at, p.updated_at, p.status, p.assigned_to_npp AS ditugaskan, p.created_by_npp AS reporter_npp, COALESCE(NULLIF(p.nama_emp,''), er.nama_emp) AS reporter_name,
                   e.nama_emp AS assigned_name, p.master_tugas_id, mt.judul AS master_judul,
                   CASE WHEN p.master_tugas_id IS NOT NULL THEN 'manager' ELSE rr.name END AS reporter_role
                FROM pekerjaan p
                LEFT JOIN employee e ON e.npp = p.assigned_to_npp
                LEFT JOIN master_tugas mt ON mt.id = p.master_tugas_id
                LEFT JOIN employee er ON er.npp = p.created_by_npp
                LEFT JOIN roles rr ON rr.id = er.role_id";
        $res = $conn->query($sql);
    }

    $existingMasterDates = []; // [master_tugas_id][YYYY-MM-DD] => true

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $startDate = $r['tgl_mulai'] ?: null;
            if (!$startDate && !empty($r['created_at'])) {
                $startDate = date('Y-m-d', strtotime($r['created_at']));
            }

            if (empty($startDate)) continue;

            // Calendar currently hides time, so treat as all-day event
            $isDone = in_array(strtolower(trim($r['status'] ?? '')), ['done','selesai','completed']);

            if (!empty($r['master_tugas_id']) && !empty($startDate)) {
                $mid = (int) $r['master_tugas_id'];
                if (!isset($existingMasterDates[$mid])) $existingMasterDates[$mid] = [];
                $existingMasterDates[$mid][$startDate] = true;
            }

            $event = [
                'id' => $r['id'],
                'title' => $r['judul'],
                'start' => $startDate,
                'allDay' => true,
                'color' => $isDone ? '#28a745' : '#007bff',
                'extendedProps' => [
                    'description' => $r['deskripsi'],
                    'status' => $r['status'],
                    'ditugaskan' => $r['ditugaskan'],
                    'assigned_name' => $r['assigned_name'],
                    'reporter_npp' => $r['reporter_npp'],
                    'reporter_name' => $r['reporter_name'],
                    'reporter_role' => $r['reporter_role'] ?? null,
                    'tgl_mulai' => $r['tgl_mulai'],
                    'tgl_selesai' => $r['tgl_selesai'],
                    'master_tugas_id' => $r['master_tugas_id'] ?? null,
                    'master_judul' => $r['master_judul'] ?? null,
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

    // Opsi 1: Recurring monthly tasks derived from master_tugas (templates)
    // We generate concrete monthly events inside the requested calendar range.
    $masterSql = "SELECT mt.id, mt.judul, mt.deskripsi, mt.npp, mt.periode, mt.created_at, e.nama_emp AS assigned_name";
    if ($hasMasterNppManagerCol) {
        $masterSql .= ", mt.npp_manager, em.nama_emp AS manager_name";
    }
    $masterSql .= "
                  FROM master_tugas mt
                  LEFT JOIN employee e ON e.npp = mt.npp";
    if ($hasMasterNppManagerCol) {
        $masterSql .= "
                  LEFT JOIN employee em ON em.npp = mt.npp_manager";
    }
    $masterSql .= "
                  WHERE mt.periode = 'bulanan'";
    if ($roleName === 'user' && !empty($_SESSION['npp'])) {
        $masterSql .= ' AND mt.npp = ?';
        $stmtM = $conn->prepare($masterSql);
        if ($stmtM) {
            $stmtM->bind_param('s', $_SESSION['npp']);
            $stmtM->execute();
            $resM = $stmtM->get_result();
        } else {
            $resM = false;
        }
    } else {
        $resM = $conn->query($masterSql);
    }

    if ($resM) {
        $mastersBulanan = [];
        while ($m = $resM->fetch_assoc()) {
            $mastersBulanan[] = $m;
        }

        if (isset($stmtM) && $stmtM) {
            $stmtM->close();
        } else {
            $resM->free();
        }

        // Baseline range per master: ambil pekerjaan TERAWAL yang terkait master_tugas_id
        // agar recurring mengikuti tanggal awal & akhir (bukan created_at master)
        $baselineByMaster = []; // [masterId] => ['start' => 'Y-m-d', 'end' => 'Y-m-d']
        $masterIds = [];
        foreach ($mastersBulanan as $m) {
            $mid = (int) ($m['id'] ?? 0);
            if ($mid > 0) $masterIds[] = $mid;
        }
        $masterIds = array_values(array_unique($masterIds));

        if (!empty($masterIds)) {
            $idList = implode(',', array_map('intval', $masterIds));
            $sqlBase = "SELECT p.id, p.master_tugas_id, p.tgl_mulai, p.tgl_selesai, p.created_at
                        FROM pekerjaan p
                        WHERE p.master_tugas_id IN ($idList)
                        ORDER BY COALESCE(p.tgl_mulai, DATE(p.created_at)) ASC, p.id ASC";
            $resBase = $conn->query($sqlBase);
            if ($resBase) {
                while ($p = $resBase->fetch_assoc()) {
                    $mid = (int) ($p['master_tugas_id'] ?? 0);
                    if ($mid <= 0) continue;
                    if (isset($baselineByMaster[$mid])) continue;

                    $baseStart = $p['tgl_mulai'] ?: null;
                    if (!$baseStart && !empty($p['created_at'])) {
                        $baseStart = date('Y-m-d', strtotime($p['created_at']));
                    }
                    if (empty($baseStart)) continue;

                    $baseEnd = $p['tgl_selesai'] ?: $baseStart;
                    $baselineByMaster[$mid] = ['start' => $baseStart, 'end' => $baseEnd];
                }
                $resBase->free();
            }
        }

        foreach ($mastersBulanan as $m) {
            $masterId = (int) ($m['id'] ?? 0);
            if ($masterId <= 0) continue;

            $base = $baselineByMaster[$masterId] ?? null;
            $day = 1;
            $durationDays = 0;
            if ($base && !empty($base['start'])) {
                $ts = strtotime($base['start']);
                if ($ts !== false) {
                    $day = (int) date('j', $ts);
                    if ($day <= 0) $day = 1;
                }
                if (!empty($base['end'])) {
                    try {
                        $dStart = new DateTimeImmutable($base['start']);
                        $dEnd = new DateTimeImmutable($base['end']);
                        $durationDays = (int) $dEnd->diff($dStart)->days;
                        if ($durationDays < 0) $durationDays = 0;
                    } catch (Exception $e) {
                        $durationDays = 0;
                    }
                }
            }

            // iterate months within range
            $monthCursor = new DateTimeImmutable($rangeStart->format('Y-m-01 00:00:00'));
            while ($monthCursor < $rangeEnd) {
                $daysInMonth = (int) $monthCursor->format('t');
                $useDay = min(max(1, $day), $daysInMonth);
                $occStart = $monthCursor->setDate((int) $monthCursor->format('Y'), (int) $monthCursor->format('m'), $useDay);

                if ($occStart >= $rangeStart && $occStart < $rangeEnd) {
                    $occStartDate = $occStart->format('Y-m-d');
                    if (!empty($existingMasterDates[$masterId]) && !empty($existingMasterDates[$masterId][$occStartDate])) {
                        // If there is an actual pekerjaan row for this master+date, don't duplicate it.
                    } else {
                        $occEndDate = '';
                        $endExclusive = null;
                        if ($durationDays > 0) {
                            $occEnd = $occStart->modify('+' . $durationDays . ' days');
                            $occEndDate = $occEnd->format('Y-m-d');
                            $endExclusive = date('Y-m-d', strtotime($occEndDate . ' +1 day'));
                        }

                        $event = [
                            'id' => 'master-' . $masterId . '-' . $occStartDate,
                            'title' => $m['judul'] ?? '',
                            'start' => $occStartDate,
                            'allDay' => true,
                            'color' => '#17a2b8',
                            'extendedProps' => [
                                'description' => $m['deskripsi'] ?? '',
                                'status' => 'open',
                                'ditugaskan' => $m['npp'] ?? '',
                                'assigned_name' => $m['assigned_name'] ?? null,
                                'reporter_npp' => ($hasMasterNppManagerCol ? ($m['npp_manager'] ?? null) : null),
                                'reporter_name' => ($hasMasterNppManagerCol ? ($m['manager_name'] ?? 'Manager') : 'Manager'),
                                'reporter_role' => 'manager',
                                'tgl_mulai' => $occStartDate,
                                'tgl_selesai' => $occEndDate,
                                'tgl_mulai_fmt' => format_date_id($occStartDate),
                                'tgl_selesai_fmt' => ($occEndDate ? format_date_id($occEndDate) : ''),
                                'master_tugas_id' => $masterId,
                                'master_judul' => $m['judul'] ?? '',
                                'is_master' => true,
                                'occ_token' => worklog_sign_master_occurrence($masterId, $occStartDate),
                            ],
                        ];
                        if ($endExclusive) {
                            $event['end'] = $endExclusive;
                        }

                        $events[] = $event;
                    }
                }

                $monthCursor = $monthCursor->modify('+1 month');
            }
        }
    }
}
echo json_encode($events, JSON_UNESCAPED_UNICODE);
