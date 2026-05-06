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
$currentMonth = "$selectedYear-$selectedMonth";

if (empty($nppTarget)) {
    echo json_encode([]);
    exit;
}

$tasks = [];

if (isset($conn)) {
    if ($statusFilter === 'done') {
        // 1. AMBIL TUGAS REALISASI (SELESAI)
        $sql = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, 'Selesai' as status_label 
                FROM pekerjaan 
                WHERE assigned_to_npp = ? 
                AND LOWER(TRIM(status)) IN ('done','selesai','completed')
                AND DATE_FORMAT(tgl_selesai, '%Y-%m') = ?
                ORDER BY tgl_selesai DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $nppTarget, $currentMonth);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // 2. AMBIL TUGAS BELUM SELESAI
        // A. Manual Open
        $sqlPek = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, lampiran, 'Belum Selesai' as status_label 
                   FROM pekerjaan 
                   WHERE assigned_to_npp = ? 
                   AND master_tugas_id IS NULL
                   AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))
                   AND DATE_FORMAT(tgl_mulai, '%Y-%m') = ?";
        $stmtP = $conn->prepare($sqlPek);
        $stmtP->bind_param('ss', $nppTarget, $currentMonth);
        $stmtP->execute();
        $tasks = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtP->close();

        // B. Master Rutin Virtual
        $sqlMaster = "SELECT mt.id as master_id, mt.judul, mt.deskripsi, mt.periode, mt.target_tgl, mt.created_at 
                      FROM master_tugas_detail mtd
                      JOIN master_tugas mt ON mt.id = mtd.master_tugas_id
                      WHERE mtd.npp = ?";
        $stmtM = $conn->prepare($sqlMaster);
        $stmtM->bind_param('s', $nppTarget);
        $stmtM->execute();
        $resM = $stmtM->get_result();
        
        $startOfMonth = new DateTime("$currentMonth-01 00:00:00");
        $endOfMonth = new DateTime($startOfMonth->format('Y-m-t 23:59:59'));

        while ($r = $resM->fetch_assoc()) {
            $masterId = $r['master_id'];
            $periode = strtolower($r['periode']);
            $targetDay = !empty($r['target_tgl']) ? (int)date('j', strtotime($r['target_tgl'])) : 1;
            $targetWDay = !empty($r['target_tgl']) ? (int)date('w', strtotime($r['target_tgl'])) : 1;
            $createdAt = new DateTime($r['created_at']);
            $createdAtMonth = $createdAt->format('Y-m');

            // Skip jika bulan filter sebelum bulan pembuatan
            if ($currentMonth < $createdAtMonth) continue;

            $cursor = clone $startOfMonth;
            // Jika bulan filter sama dengan bulan pembuatan, mulai dari tanggal dibuat (Khusus Harian/Mingguan)
            if (($periode === 'harian' || $periode === 'mingguan') && $currentMonth === $createdAtMonth) {
                $cursor = new DateTime($createdAt->format('Y-m-d 00:00:00'));
            }

            while ($cursor <= $endOfMonth) {
                $occDate = null;
                if ($periode === 'harian') {
                    $occDate = $cursor->format('Y-m-d');
                    $cursor->modify('+1 day');
                } elseif ($periode === 'mingguan') {
                    if ((int)$cursor->format('w') === $targetWDay) { $occDate = $cursor->format('Y-m-d'); }
                    $cursor->modify('+1 day');
                } elseif ($periode === 'bulanan') {
                    $useDay = min($targetDay, (int)$cursor->format('t'));
                    $occDate = $cursor->format("Y-m-$useDay");
                    $cursor->modify('first day of next month');
                } elseif ($periode === 'triwulan') {
                    if (((int)$cursor->format('n') - 1) % 3 === 0) {
                        $useDay = min($targetDay, (int)$cursor->format('t'));
                        $occDate = $cursor->format("Y-m-$useDay");
                    }
                    $cursor->modify('first day of next month');
                } else { $cursor->modify('+1 day'); }

                if ($occDate && $occDate >= $startOfMonth->format('Y-m-d') && $occDate <= $endOfMonth->format('Y-m-d')) {
                    // Cek realisasi
                    $sqlCheck = "SELECT 1 FROM pekerjaan WHERE master_tugas_id = ? AND assigned_to_npp = ? AND tgl_mulai = ? AND LOWER(TRIM(status)) IN ('done','selesai','completed')";
                    $stmtCheck = $conn->prepare($sqlCheck);
                    $stmtCheck->bind_param('iss', $masterId, $nppTarget, $occDate);
                    $stmtCheck->execute();
                    if ($stmtCheck->get_result()->num_rows == 0) {
                        $tasks[] = [
                            'id' => 'master-'.$masterId.'-'.$occDate,
                            'judul' => "[".strtoupper($periode)."] ".$r['judul'],
                            'deskripsi' => $r['deskripsi'],
                            'periode' => $r['periode'],
                            'tgl_mulai' => $occDate,
                            'lampiran' => null,
                            'status_label' => 'Belum Selesai'
                        ];
                    }
                    $stmtCheck->close();
                }
            }
        }
        $stmtM->close();
    }
}

echo json_encode($tasks, JSON_UNESCAPED_UNICODE);
