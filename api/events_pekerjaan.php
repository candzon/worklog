<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$events = [];
if (isset($conn)) {
    // 1. Definisikan Range Waktu
    $rangeStart = null; $rangeEnd = null;
    try { if (!empty($_GET['start'])) $rangeStart = new DateTimeImmutable($_GET['start']); } catch (Exception $e) {}
    try { if (!empty($_GET['end'])) $rangeEnd = new DateTimeImmutable($_GET['end']); } catch (Exception $e) {}
    if (!$rangeStart) $rangeStart = new DateTimeImmutable('first day of this month 00:00:00');
    if (!$rangeEnd) $rangeEnd = new DateTimeImmutable('last day of this month 23:59:59');

    // 2. Identifikasi User & Role
    $nppSession = $_SESSION['npp'] ?? '';
    $roleId = $_SESSION['role_id'] ?? null;
    $roleName = $_SESSION['role_name'] ?? null;
    
    if (!$roleName && !empty($nppSession)) {
        $stmtR = $conn->prepare("SELECT r.name, e.role_id FROM employee e JOIN roles r ON e.role_id = r.id WHERE e.npp = ?");
        $stmtR->bind_param('s', $nppSession);
        $stmtR->execute();
        $resR = $stmtR->get_result();
        if ($rowR = $resR->fetch_assoc()) {
            $roleName = $rowR['name'];
            $roleId = $rowR['role_id'];
        }
        $stmtR->close();
    }
    
    $isManager = ($roleId == 1 || $roleId == 2 || strtolower((string)$roleName) === 'manager' || strtolower((string)$roleName) === 'admin');

    // 3. Tangkap Filter dari Dashboard
    $filterNpp = $_GET['filter_npp'] ?? null;
    $filterStatus = $_GET['status'] ?? null;

    // 4. AMBIL TUGAS NYATA (PEKERJAAN)
    $monthlyCompletions = [];
    $sqlPek = "SELECT p.*, e.nama_emp AS assigned_name, er.nama_emp AS reporter_name 
               FROM pekerjaan p 
               LEFT JOIN employee e ON e.npp = p.assigned_to_npp
               LEFT JOIN employee er ON er.npp = p.created_by_npp WHERE 1=1";
    
    if ($filterNpp) {
        $sqlPek .= " AND p.assigned_to_npp = '" . $conn->real_escape_string($filterNpp) . "'";
    } elseif (!$isManager) {
        $sqlPek .= " AND p.assigned_to_npp = '$nppSession'";
    }

    if ($filterStatus === 'done') {
        $sqlPek .= " AND LOWER(TRIM(p.status)) IN ('done','selesai','completed')";
    }

    $resP = $conn->query($sqlPek);
    if ($resP) {
        while ($r = $resP->fetch_assoc()) {
            $startDate = $r['tgl_mulai'] ?: (!empty($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : null);
            if (!$startDate) continue;

            if (!empty($r['master_tugas_id'])) {
                $mid = (int)$r['master_tugas_id'];
                $monthYear = date('Y-m', strtotime($startDate));
                $monthlyCompletions[$mid][$monthYear][$r['assigned_to_npp']] = true;
            }

            $isDone = in_array(strtolower(trim($r['status'] ?? '')), ['done', 'selesai', 'completed']);
            $event = [
                'id' => $r['id'],
                'title' => $r['judul'],
                'start' => $startDate,
                'allDay' => true,
                'color' => $isDone ? '#28a745' : '#007bff',
                'extendedProps' => [
                    'description' => $r['deskripsi'], 'status' => $r['status'],
                    'ditugaskan' => $r['assigned_to_npp'], 'assigned_name' => $r['assigned_name'],
                    'reporter_name' => $r['reporter_name'] ?: 'Manager', 'lampiran' => $r['lampiran'],
                    'tgl_mulai' => $r['tgl_mulai'], 'tgl_selesai' => $r['tgl_selesai'],
                    'master_tugas_id' => $r['master_tugas_id'], 'is_master' => false
                ]
            ];
            if (!empty($r['tgl_selesai'])) $event['end'] = date('Y-m-d', strtotime($r['tgl_selesai'] . ' +1 day'));
            $events[] = $event;
        }
    }

    // 5. AMBIL TUGAS RUTIN (MASTER)
    if ($filterStatus !== 'done') {
        $masterSql = "SELECT mt.*, mtd.npp AS assignee_npp, e.nama_emp AS assignee_name, em.nama_emp AS manager_name
                      FROM master_tugas mt
                      JOIN master_tugas_detail mtd ON mtd.master_tugas_id = mt.id
                      JOIN employee e ON e.npp = mtd.npp
                      LEFT JOIN employee em ON em.npp = mt.npp_manager
                      WHERE mt.periode = 'bulanan'";
        
        if ($filterNpp) {
            $masterSql .= " AND mtd.npp = '" . $conn->real_escape_string($filterNpp) . "'";
        } elseif (!$isManager) {
            $masterSql .= " AND mtd.npp = '$nppSession'";
        }

        $resM = $conn->query($masterSql);
        if ($resM) {
            while ($m = $resM->fetch_assoc()) {
                $masterId = (int)$m['id'];
                $targetDay = !empty($m['target_tgl']) ? (int)date('j', strtotime($m['target_tgl'])) : 28;
                $monthCursor = new DateTimeImmutable($rangeStart->format('Y-m-01 00:00:00'));
                
                while ($monthCursor < $rangeEnd) {
                    $monthYearKey = $monthCursor->format('Y-m');
                    $daysInMonth = (int)$monthCursor->format('t');
                    $useDay = min($targetDay, $daysInMonth);
                    $occDate = $monthCursor->format("Y-m-$useDay");

                    if ($monthCursor <= $rangeEnd) {
                        if (empty($monthlyCompletions[$masterId][$monthYearKey][$m['assignee_npp']])) {
                            $events[] = [
                                'id' => 'master-' . $masterId . '-' . $monthYearKey . '-' . $m['assignee_npp'],
                                'title' => "[RUTIN] " . $m['judul'],
                                'start' => $occDate,
                                'allDay' => true,
                                'color' => '#17a2b8',
                                'extendedProps' => [
                                    'description' => $m['deskripsi'], 'status' => 'open',
                                    'ditugaskan' => $m['assignee_npp'], 'assigned_name' => $m['assignee_name'],
                                    'reporter_name' => $m['manager_name'] ?: 'Manager',
                                    'tgl_mulai' => $occDate, 'tgl_selesai' => $occDate,
                                    'master_tugas_id' => $masterId, 'is_master' => true,
                                    'occ_token' => worklog_sign_master_occurrence($masterId, $occDate)
                                ]
                            ];
                        }
                    }
                    $monthCursor = $monthCursor->modify('+1 month');
                }
            }
        }
    }
}

echo json_encode($events, JSON_UNESCAPED_UNICODE);
