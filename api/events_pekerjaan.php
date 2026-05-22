<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

error_reporting(0);
ini_set('display_errors', 0);
ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    // 1. Parsing Tanggal
    $startParam = $_GET['start'] ?? date('Y-m-01');
    $endParam = $_GET['end'] ?? date('Y-m-t');

    $startParam = substr($startParam, 0, 10);
    $endParam = substr($endParam, 0, 10);

    $rangeStart = new DateTime($startParam . ' 00:00:00');
    $rangeEnd = new DateTime($endParam . ' 23:59:59');

    $nppSession = $_SESSION['npp'] ?? '';
    $roleName = $_SESSION['role_name'] ?? '';

    // Validasi Role jika session kosong
    if (empty($roleName) && !empty($nppSession)) {
        $resR = $conn->query("SELECT r.name FROM employee e JOIN roles r ON e.role_id = r.id WHERE e.npp = '$nppSession'");
        if ($resR && $rowR = $resR->fetch_assoc()) { $roleName = $rowR['name']; }
    }

    $isManager = (strtolower((string)$roleName) === 'manager' || strtolower((string)$roleName) === 'admin');
    $filterNpp = $_GET['filter_npp'] ?? null;
    $filterStatus = $_GET['status'] ?? null;

    // 2. TRACKING: Ambil SEMUA data yang sudah ada di tabel pekerjaan (Done maupun Revisi)
    // Ini kunci agar tidak muncul ganda (double entry)
    $existsInPekerjaan = [];
    $sqlTracking = "SELECT master_tugas_id, tgl_mulai, assigned_to_npp FROM pekerjaan 
                    WHERE master_tugas_id IS NOT NULL 
                    AND tgl_mulai >= '$startParam' AND tgl_mulai <= '$endParam'";
    $resTracking = $conn->query($sqlTracking);
    if ($resTracking) {
        while ($track = $resTracking->fetch_assoc()) {
            $existsInPekerjaan[$track['master_tugas_id']][$track['tgl_mulai']][$track['assigned_to_npp']] = true;
        }
    }

    // 3. Ambil Pekerjaan Nyata untuk ditampilkan
    $sqlPek = "SELECT p.*, e.nama_emp AS assigned_name, er.nama_emp AS reporter_name 
               FROM pekerjaan p 
               LEFT JOIN employee e ON e.npp = p.assigned_to_npp 
               LEFT JOIN employee er ON er.npp = p.created_by_npp WHERE 1=1";

    if ($filterNpp) { $sqlPek .= " AND p.assigned_to_npp = '" . $conn->real_escape_string($filterNpp) . "'"; }
    elseif (!$isManager) { $sqlPek .= " AND p.assigned_to_npp = '$nppSession'"; }

    if ($filterStatus === 'open') {
        $sqlPek .= " AND LOWER(TRIM(p.status)) NOT IN ('done', 'selesai', 'completed')";
    } elseif ($filterStatus === 'done') {
        $sqlPek .= " AND LOWER(TRIM(p.status)) IN ('done', 'selesai', 'completed')";
    } elseif ($filterStatus === 'revisi') {
        $sqlPek .= " AND LOWER(TRIM(p.status)) = 'revisi'";
    }

    $resP = $conn->query($sqlPek);
    $groupedEvents = [];

    if ($resP) {
        while ($r = $resP->fetch_assoc()) {
            $date = $r['tgl_mulai'];
            $masterId = $r['master_tugas_id'];
            $judul = $r['judul'];
            $groupKey = $masterId ? "master-{$masterId}-{$date}" : "manual-{$judul}-{$date}";

            if (!isset($groupedEvents[$groupKey])) {
                $groupedEvents[$groupKey] = [
                    'id' => $groupKey, 'title' => $judul, 'start' => $date, 'allDay' => true,
                    'extendedProps' => [
                        'description' => $r['deskripsi'], 'periode' => strtolower($r['periode'] ?? ''),
                        'tgl_mulai' => $date, 'tgl_selesai' => $r['tgl_selesai'],
                        'master_tugas_id' => $masterId, 'is_master' => !!$masterId,
                        'reporter_name' => $r['reporter_name'] ?: 'Manager', 'status' => $r['status'],
                        'assignees' => []
                    ]
                ];
            }
            if (strtolower(trim($r['status'])) === 'revisi') {
                $groupedEvents[$groupKey]['extendedProps']['status'] = 'revisi'; 
            }

            $groupedEvents[$groupKey]['extendedProps']['assignees'][] = [
                'id' => $r['id'], 'npp' => $r['assigned_to_npp'], 'nama' => $r['assigned_name'],
                'status' => $r['status'], 'lampiran' => $r['lampiran'],
                'catatan_revisi' => $r['catatan_revisi'], 'occ_token' => ''
            ];
        }
    }

    // 4. Ambil Master Tugas (Virtual)
    if ($filterStatus !== 'done' && $filterStatus !== 'revisi') {
        $masterSql = "SELECT mt.*, mtd.npp AS assignee_npp, e.nama_emp AS assignee_name, em.nama_emp AS nama_manager
                      FROM master_tugas mt
                      JOIN master_tugas_detail mtd ON mtd.master_tugas_id = mt.id
                      JOIN employee e ON e.npp = mtd.npp
                      LEFT JOIN employee em ON em.npp = mt.npp_manager WHERE 1=1";
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
                    } elseif ($periode === 'mingguan' && (int)$cursor->format('w') === $targetWDay) { 
                        $occDate = $dateToProcess; 
                    } elseif ($periode === 'bulanan' && (int)$cursor->format('j') === min($targetDay, (int)$cursor->format('t'))) { 
                        $occDate = $dateToProcess; 
                    } elseif ($periode === 'triwulan' && ((int)$cursor->format('n') - 1) % 3 === 0 && (int)$cursor->format('j') === min($targetDay, (int)$cursor->format('t'))) { 
                        $occDate = $dateToProcess; 
                    }

                    // CEK TRACKING: Jika pegawai sudah ada di tabel pekerjaan (Done/Revisi), JANGAN buatkan virtual Open.
                    if ($occDate && empty($existsInPekerjaan[$masterId][$occDate][$m['assignee_npp']])) {
                        $groupKey = "master-{$masterId}-{$occDate}";
                        if (!isset($groupedEvents[$groupKey])) {
                            $groupedEvents[$groupKey] = [
                                'id' => $groupKey, 'title' => $m['judul'], 'start' => $occDate, 'allDay' => true,
                                'extendedProps' => [
                                    'description' => $m['deskripsi'], 'status' => 'open', 'reporter_name' => $m['nama_manager'] ?: 'Manager',
                                    'tgl_mulai' => $occDate, 'master_tugas_id' => $masterId, 'is_master' => true,
                                    'periode' => $periode, 'assignees' => []
                                ]
                            ];
                        }
                        $groupedEvents[$groupKey]['extendedProps']['assignees'][] = [
                            'id' => '', 'npp' => $m['assignee_npp'], 'nama' => $m['assignee_name'],
                            'status' => 'open', 'lampiran' => '', 'catatan_revisi' => '',
                            'occ_token' => worklog_sign_master_occurrence($masterId, $occDate)
                        ];
                    }
                    $cursor->modify('+1 day');
                }
            }
        }
    }
    $events = array_values($groupedEvents);
}
echo json_encode($events, JSON_UNESCAPED_UNICODE);