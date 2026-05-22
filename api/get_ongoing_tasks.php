<?php
/**
 * api/get_ongoing_tasks.php
 * Memberikan daftar rincian tugas (Selesai atau Belum Selesai) untuk modal dashboard.
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$nppTarget = $_GET['npp'] ?? null;
$statusFilter = $_GET['status'] ?? 'open';
$selectedMonth = $_GET['bulan'] ?? date('m');
$selectedYear = $_GET['tahun'] ?? date('Y');
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

if (empty($nppTarget)) {
    echo json_encode(['data' => [], 'pagination' => ['page' => 1, 'totalPages' => 1]]);
    exit;
}

$tasks = [];
$isAllMonths = ($selectedMonth === 'semua_bulan');

/**
 * Ambil master tugas untuk pegawai:
 * 1) yang sudah termapping di master_tugas_detail, atau
 * 2) fallback untuk master tugas tanpa detail mapping tetapi bagian-nya sama.
 */
function fetchMasterTasksForEmployee($conn, $nppTarget)
{
    $sqlMaster = "SELECT DISTINCT mt.id as master_id, mt.judul, mt.deskripsi, mt.periode, mt.target_tgl, mt.created_at
                  FROM master_tugas mt
                  LEFT JOIN master_tugas_detail mtd_self
                    ON mtd_self.master_tugas_id = mt.id AND mtd_self.npp = ?
                  LEFT JOIN employee e
                    ON e.npp = ?
                  WHERE mtd_self.id IS NOT NULL
                     OR (
                        NOT EXISTS (
                            SELECT 1
                            FROM master_tugas_detail mtdx
                            WHERE mtdx.master_tugas_id = mt.id
                        )
                        AND e.bagian_id = mt.bagian_id
                     )";
    $stmtM = $conn->prepare($sqlMaster);
    if (!$stmtM) {
        return [];
    }
    $stmtM->bind_param('ss', $nppTarget, $nppTarget);
    $stmtM->execute();
    $rows = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtM->close();
    return $rows;
}

if (isset($conn)) {
    if ($statusFilter === 'done') {
        // 1. AMBIL TUGAS REALISASI (SELESAI)
        if ($isAllMonths) {
            $sql = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, updated_at, catatan_revisi, status, 'Selesai' as status_label 
                    FROM pekerjaan 
                    WHERE assigned_to_npp = ? 
                    AND LOWER(TRIM(status)) IN ('done','selesai','completed')
                    AND (YEAR(tgl_selesai) = ? OR YEAR(updated_at) = ?)
                    ORDER BY tgl_selesai DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $nppTarget, $selectedYear, $selectedYear);
        } else {
            $currentMonth = "$selectedYear-$selectedMonth";
            $sql = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, updated_at, catatan_revisi, status, 'Selesai' as status_label 
                    FROM pekerjaan 
                    WHERE assigned_to_npp = ? 
                    AND LOWER(TRIM(status)) IN ('done','selesai','completed')
                    AND (DATE_FORMAT(tgl_selesai, '%Y-%m') = ? OR DATE_FORMAT(updated_at, '%Y-%m') = ?)
                    ORDER BY tgl_selesai DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $nppTarget, $currentMonth, $currentMonth);
        }
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } elseif ($statusFilter === 'revisi') {
        // 2. AMBIL TUGAS REVISI
        if ($isAllMonths) {
            $sqlRevisi = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, updated_at, catatan_revisi, status, 'Revisi' as status_label 
                          FROM pekerjaan 
                          WHERE assigned_to_npp = ? 
                          AND LOWER(TRIM(status)) = 'revisi'
                          AND (YEAR(tgl_mulai) = ? OR YEAR(tgl_selesai) = ? OR YEAR(updated_at) = ?)
                          ORDER BY updated_at DESC";
            $stmtR = $conn->prepare($sqlRevisi);
            $stmtR->bind_param('ssss', $nppTarget, $selectedYear, $selectedYear, $selectedYear);
        } else {
            $currentMonth = "$selectedYear-$selectedMonth";
            $sqlRevisi = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, updated_at, catatan_revisi, status, 'Revisi' as status_label 
                          FROM pekerjaan 
                          WHERE assigned_to_npp = ? 
                          AND LOWER(TRIM(status)) = 'revisi'
                          AND (DATE_FORMAT(tgl_mulai, '%Y-%m') = ? OR DATE_FORMAT(tgl_selesai, '%Y-%m') = ? OR DATE_FORMAT(updated_at, '%Y-%m') = ?)
                          ORDER BY updated_at DESC";
            $stmtR = $conn->prepare($sqlRevisi);
            $stmtR->bind_param('ssss', $nppTarget, $currentMonth, $currentMonth, $currentMonth);
        }
        $stmtR->execute();
        $tasks = $stmtR->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtR->close();
    } else {
        // 3. AMBIL TUGAS BELUM SELESAI (OPEN)
        if ($isAllMonths) {
            // A. Manual Open
            $sqlPek = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, catatan_revisi, status, 'Belum Selesai' as status_label 
                       FROM pekerjaan 
                       WHERE assigned_to_npp = ? 
                       AND master_tugas_id IS NULL
                       AND (status IS NULL OR NOT (LOWER(TRIM(status)) IN ('done','selesai','completed','revisi')))
                       AND YEAR(tgl_mulai) = ?
                       ORDER BY tgl_mulai DESC";
            $stmtP = $conn->prepare($sqlPek);
            $stmtP->bind_param('ss', $nppTarget, $selectedYear);
            $stmtP->execute();
            $tasks = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtP->close();

            // B. Master Rutin Virtual
            $masterRows = fetchMasterTasksForEmployee($conn, $nppTarget);
            $now = new DateTime();
            $yearFilter = (int)$selectedYear;

            foreach ($masterRows as $r) {
                $masterId = $r['master_id'];
                $periode = strtolower($r['periode']);
                $targetDay = !empty($r['target_tgl']) ? (int) date('j', strtotime($r['target_tgl'])) : 1;
                $targetWDay = !empty($r['target_tgl']) ? (int) date('w', strtotime($r['target_tgl'])) : 1;
                $createdAt = new DateTime($r['created_at']);
                
                $cursor = new DateTime("$yearFilter-01-01 00:00:00");
                if ((int)$createdAt->format('Y') > $yearFilter) continue;
                if ((int)$createdAt->format('Y') === $yearFilter) { $cursor = clone $createdAt; }
                
                $yearEnd = new DateTime("$yearFilter-12-31 23:59:59");
                $limitDate = ($now < $yearEnd) ? $now : $yearEnd;

                while ($cursor <= $limitDate) {
                    $occDate = null;
                    if ($periode === 'harian') { $occDate = $cursor->format('Y-m-d'); $cursor->modify('+1 day'); }
                    elseif ($periode === 'mingguan') { if ((int)$cursor->format('w') === $targetWDay) { $occDate = $cursor->format('Y-m-d'); } $cursor->modify('+1 day'); }
                    elseif ($periode === 'bulanan') { $useDay = min($targetDay, (int)$cursor->format('t')); $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay); $cursor->modify('first day of next month'); }
                    elseif ($periode === 'triwulan') { if (((int)$cursor->format('n') - 1) % 3 === 0) { $useDay = min($targetDay, (int)$cursor->format('t')); $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay); } $cursor->modify('first day of next month'); }
                    else { $cursor->modify('+1 day'); }

                    if ($occDate && $occDate >= $createdAt->format('Y-m-d') && (int)date('Y', strtotime($occDate)) == $yearFilter && $occDate <= $limitDate->format('Y-m-d')) {
                        $sqlCheck = "SELECT 1 FROM pekerjaan WHERE master_tugas_id = ? AND assigned_to_npp = ? AND tgl_mulai = ? AND LOWER(TRIM(status)) IN ('done','selesai','completed','revisi')";
                        $stmtCheck = $conn->prepare($sqlCheck);
                        $stmtCheck->bind_param('iss', $masterId, $nppTarget, $occDate);
                        $stmtCheck->execute();
                        if ($stmtCheck->get_result()->num_rows == 0) {
                            $tasks[] = [
                                'id' => 'master-' . $masterId . '-' . $occDate,
                                'judul' => $r['judul'],
                                'deskripsi' => $r['deskripsi'],
                                'periode' => $r['periode'],
                                'tgl_mulai' => $occDate,
                                'tgl_selesai' => null,
                                'lampiran' => null,
                                'status_label' => 'Belum Selesai'
                            ];
                        }
                        $stmtCheck->close();
                    }
                }
            }
        } else {
            // BULAN SPESIFIK
            $currentMonth = "$selectedYear-$selectedMonth";

            // A. Manual Open
            $sqlPek = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, catatan_revisi, status, 'Belum Selesai' as status_label 
                       FROM pekerjaan 
                       WHERE assigned_to_npp = ? 
                       AND master_tugas_id IS NULL
                       AND (status IS NULL OR NOT (LOWER(TRIM(status)) IN ('done','selesai','completed','revisi')))
                       AND (DATE_FORMAT(tgl_mulai, '%Y-%m') = ? OR DATE_FORMAT(updated_at, '%Y-%m') = ?)
                       ORDER BY tgl_mulai DESC";
            $stmtP = $conn->prepare($sqlPek);
            $stmtP->bind_param('sss', $nppTarget, $currentMonth, $currentMonth);
            $stmtP->execute();
            $tasks = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtP->close();

            // B. Master Rutin Virtual
            $masterRows = fetchMasterTasksForEmployee($conn, $nppTarget);
            $startOfMonth = new DateTime("$currentMonth-01 00:00:00");
            $endOfMonth = new DateTime($startOfMonth->format('Y-m-t 23:59:59'));

            foreach ($masterRows as $r) {
                $masterId = $r['master_id'];
                $periode = strtolower($r['periode']);
                $targetDay = !empty($r['target_tgl']) ? (int) date('j', strtotime($r['target_tgl'])) : 1;
                $targetWDay = !empty($r['target_tgl']) ? (int) date('w', strtotime($r['target_tgl'])) : 1;
                $createdAt = new DateTime($r['created_at']);
                $createdAtMonth = $createdAt->format('Y-m');

                if ($currentMonth < $createdAtMonth) continue;

                $cursor = clone $startOfMonth;
                if (($periode === 'harian' || $periode === 'mingguan') && $currentMonth === $createdAtMonth) {
                    $cursor = new DateTime($createdAt->format('Y-m-d 00:00:00'));
                }

                while ($cursor <= $endOfMonth) {
                    $occDate = null;
                    if ($periode === 'harian') { $occDate = $cursor->format('Y-m-d'); $cursor->modify('+1 day'); }
                    elseif ($periode === 'mingguan') { if ((int) $cursor->format('w') === $targetWDay) { $occDate = $cursor->format('Y-m-d'); } $cursor->modify('+1 day'); }
                    elseif ($periode === 'bulanan') { $useDay = min($targetDay, (int) $cursor->format('t')); $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay); $cursor->modify('first day of next month'); }
                    elseif ($periode === 'triwulan') { if (((int) $cursor->format('n') - 1) % 3 === 0) { $useDay = min($targetDay, (int) $cursor->format('t')); $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay); } $cursor->modify('first day of next month'); }
                    else { $cursor->modify('+1 day'); }

                    if ($occDate && $occDate >= $startOfMonth->format('Y-m-d') && $occDate <= $endOfMonth->format('Y-m-d')) {
                        $sqlCheck = "SELECT 1 FROM pekerjaan WHERE master_tugas_id = ? AND assigned_to_npp = ? AND tgl_mulai = ? AND LOWER(TRIM(status)) IN ('done','selesai','completed','revisi')";
                        $stmtCheck = $conn->prepare($sqlCheck);
                        $stmtCheck->bind_param('iss', $masterId, $nppTarget, $occDate);
                        $stmtCheck->execute();
                        if ($stmtCheck->get_result()->num_rows == 0) {
                            $tasks[] = [
                                'id' => 'master-' . $masterId . '-' . $occDate,
                                'judul' => $r['judul'], 'deskripsi' => $r['deskripsi'], 'periode' => $r['periode'],
                                'tgl_mulai' => $occDate, 'tgl_selesai' => null, 'lampiran' => null, 'status_label' => 'Belum Selesai'
                            ];
                        }
                        $stmtCheck->close();
                    }
                }
            }
        }
    }
}

// PAGINATION
$totalItems = count($tasks);
$totalPages = max(1, ceil($totalItems / $itemsPerPage));
$paginatedTasks = array_slice($tasks, $offset, $itemsPerPage);

$response = [
    'data' => $paginatedTasks,
    'pagination' => [
        'page' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'itemsPerPage' => $itemsPerPage
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
