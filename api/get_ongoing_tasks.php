<?php
/**
 * api/get_ongoing_tasks.php
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$nppTarget = $_GET['npp'] ?? null;
$statusFilter = $_GET['status'] ?? 'open'; 

if (empty($nppTarget)) {
    echo json_encode([]);
    exit;
}

$tasks = [];
$currentMonth = date('Y-m');

if (isset($conn)) {
    if ($statusFilter === 'done') {
        // AMBIL TUGAS SELESAI
        $sql = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, 'Selesai' as status_label 
                FROM pekerjaan 
                WHERE assigned_to_npp = ? AND LOWER(TRIM(status)) IN ('done','selesai','completed')
                ORDER BY tgl_selesai DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $nppTarget);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // AMBIL TUGAS OPEN (STANDARISASI STATUS)
        // 1. Manual Open
        $sqlPek = "SELECT id, judul, deskripsi, periode, tgl_mulai, tgl_selesai, 'Belum Selesai' as status_label 
                   FROM pekerjaan 
                   WHERE assigned_to_npp = ? AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))";
        $stmtP = $conn->prepare($sqlPek);
        $stmtP->bind_param('s', $nppTarget);
        $stmtP->execute();
        $tasks = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtP->close();

        // 2. Master Rutin Open (Belum disentuh)
        $sqlMaster = "SELECT mt.id as master_id, mt.judul, mt.deskripsi, mt.periode, mt.target_tgl 
                      FROM master_tugas_detail mtd
                      JOIN master_tugas mt ON mt.id = mtd.master_tugas_id
                      WHERE mtd.npp = ? AND mt.periode = 'bulanan'
                      AND NOT EXISTS (
                          SELECT 1 FROM pekerjaan p 
                          WHERE p.master_tugas_id = mtd.master_tugas_id 
                          AND p.assigned_to_npp = mtd.npp 
                          AND DATE_FORMAT(p.tgl_mulai, '%Y-%m') = ?
                      )";
        $stmtM = $conn->prepare($sqlMaster);
        $stmtM->bind_param('ss', $nppTarget, $currentMonth);
        $stmtM->execute();
        $resM = $stmtM->get_result();
        while ($r = $resM->fetch_assoc()) {
            $tasks[] = [
                'id' => 'master-'.$r['master_id'],
                'judul' => $r['judul'],
                'deskripsi' => $r['deskripsi'],
                'periode' => $r['periode'],
                'tgl_mulai' => date('Y-m-') . date('j', strtotime($r['target_tgl'] ?: 'today')),
                'status_label' => 'Belum Selesai' // STANDARISASI
            ];
        }
        $stmtM->close();
    }
}

echo json_encode($tasks, JSON_UNESCAPED_UNICODE);
