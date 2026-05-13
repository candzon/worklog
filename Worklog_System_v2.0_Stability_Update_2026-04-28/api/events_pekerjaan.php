<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    // 1. Parsing Tanggal yang lebih aman
    $startParam = $_GET['start'] ?? date('Y-m-01');
    $endParam = $_GET['end'] ?? date('Y-m-t');
    
    // Bersihkan format FullCalendar (ambil Y-m-d saja)
    $startParam = substr($startParam, 0, 10);
    $endParam = substr($endParam, 0, 10);

    $rangeStart = new DateTime($startParam . ' 00:00:00');
    $rangeEnd = new DateTime($endParam . ' 23:59:59');

    $nppSession = $_SESSION['npp'] ?? '';
    // Ambil Role Name dari DB jika sesi tidak lengkap
    $roleName = $_SESSION['role_name'] ?? '';
    if (empty($roleName) && !empty($nppSession)) {
        $resR = $conn->query("SELECT r.name FROM employee e JOIN roles r ON e.role_id = r.id WHERE e.npp = '$nppSession'");
        if ($resR && $rowR = $resR->fetch_assoc()) {
            $roleName = $rowR['name'];
        }
    }
    
    $isManager = (strtolower((string)$roleName) === 'manager' || strtolower((string)$roleName) === 'admin');

    $filterNpp = $_GET['filter_npp'] ?? null;
    $filterStatus = $_GET['status'] ?? null;

    // 2. Ambil Pekerjaan Nyata
    $completions = [];
    $sqlPek = "SELECT p.*, e.nama_emp AS assigned_name, er.nama_emp AS reporter_name 
               FROM pekerjaan p 
               LEFT JOIN employee e ON e.npp = p.assigned_to_npp
               LEFT JOIN employee er ON er.npp = p.created_by_npp WHERE 1=1";
    if ($filterNpp) { $sqlPek .= " AND p.assigned_to_npp = '" . $conn->real_escape_string($filterNpp) . "'"; }
    elseif (!$isManager) { $sqlPek .= " AND p.assigned_to_npp = '$nppSession'"; }
    
    $resP = $conn->query($sqlPek);
    if ($resP) {
        while ($r = $resP->fetch_assoc()) {
            if ($r['master_tugas_id']) {
                $completions[$r['master_tugas_id']][$r['tgl_mulai']][$r['assigned_to_npp']] = true;
            }
            $isDone = in_array(strtolower(trim($r['status'])), ['done', 'selesai', 'completed']);
            $events[] = [
                'id' => 'real-' . $r['id'], 'title' => $r['judul'], 'start' => $r['tgl_mulai'], 'allDay' => true,
                'color' => $isDone ? '#28a745' : '#007bff',
                'extendedProps' => [
                    'description' => $r['deskripsi'], 
                    'status' => $r['status'],
                    'ditugaskan' => $r['assigned_to_npp'], 
                    'assigned_name' => $r['assigned_name'],
                    'reporter_name' => $r['nama_emp'] ?: 'Manager', 
                    'lampiran' => $r['lampiran'],
                    'tgl_mulai' => $r['tgl_mulai'], 
                    'tgl_selesai' => $r['tgl_selesai'],
                    'master_tugas_id' => $r['master_tugas_id'],
                    'is_master' => false
                ]
            ];
        }
    }

    // 3. Ambil Master Tugas (Virtual)
    if ($filterStatus !== 'done') {
        $masterSql = "SELECT mt.*, mtd.npp AS assignee_npp, e.nama_emp AS assignee_name, em.nama_emp AS nama_manager
                      FROM master_tugas mt
                      JOIN master_tugas_detail mtd ON mtd.master_tugas_id = mt.id
                      JOIN employee e ON e.npp = mtd.npp
                      LEFT JOIN employee em ON em.npp = mt.npp_manager
                      WHERE 1=1";
        if ($filterNpp) { $masterSql .= " AND mtd.npp = '" . $conn->real_escape_string($filterNpp) . "'"; }
        elseif (!$isManager) { $masterSql .= " AND mtd.npp = '$nppSession'"; }

        $resM = $conn->query($masterSql);
        if ($resM) {
            while ($m = $resM->fetch_assoc()) {
                $masterId = (int)$m['id'];
                $periode = strtolower($m['periode']);
                $targetDay = !empty($m['target_tgl']) ? (int)date('j', strtotime($m['target_tgl'])) : 1;
                $targetWDay = !empty($m['target_tgl']) ? (int)date('w', strtotime($m['target_tgl'])) : 1;

                $cursor = clone $rangeStart;
                while ($cursor <= $rangeEnd) {
                    $occDate = null;
                    $dateToProcess = $cursor->format('Y-m-d');

                    if ($periode === 'harian') {
                        $occDate = $dateToProcess;
                    } elseif ($periode === 'mingguan') {
                        if ((int)$cursor->format('w') === $targetWDay) { $occDate = $dateToProcess; }
                    } elseif ($periode === 'bulanan') {
                        $daysInMonth = (int)$cursor->format('t');
                        if ((int)$cursor->format('j') === min($targetDay, $daysInMonth)) { $occDate = $dateToProcess; }
                    } elseif ($periode === 'triwulan') {
                        $monthNum = (int)$cursor->format('n');
                        if (($monthNum - 1) % 3 === 0) {
                            $daysInMonth = (int)$cursor->format('t');
                            if ((int)$cursor->format('j') === min($targetDay, $daysInMonth)) { $occDate = $dateToProcess; }
                        }
                    }

                    if ($occDate && empty($completions[$masterId][$occDate][$m['assignee_npp']])) {
                        $events[] = [
                            'id' => 'master-' . $masterId . '-' . $occDate,
                            'title' => "[" . strtoupper($periode) . "] " . $m['judul'],
                            'start' => $occDate, 'allDay' => true, 'color' => '#17a2b8',
                            'extendedProps' => [
                                'description' => $m['deskripsi'], 'status' => 'open',
                                'ditugaskan' => $m['assignee_npp'], 'assigned_name' => $m['assignee_name'],
                                'reporter_name' => $m['nama_manager'] ?: 'Manager',
                                'tgl_mulai' => $occDate, 'master_tugas_id' => $masterId, 'is_master' => true,
                                'occ_token' => worklog_sign_master_occurrence($masterId, $occDate)
                            ]
                        ];
                    }
                    $cursor->modify('+1 day');
                }
            }
        }
    }
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
