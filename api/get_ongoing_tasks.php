<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$nppTarget = $_GET['npp'] ?? null;
if (empty($nppTarget)) {
    echo json_encode([]);
    exit;
}

$tasks = [];
$currentMonth = date('Y-m');

if (isset($conn)) {
    // 1. Ambil tugas manual yang masih open
    $sqlPek = "SELECT judul, deskripsi, periode, tgl_mulai, status FROM pekerjaan 
               WHERE assigned_to_npp = ? AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))";
    $stmtP = $conn->prepare($sqlPek);
    $stmtP->bind_param('s', $nppTarget);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    while ($r = $resP->fetch_assoc()) {
        $tasks[] = $r;
    }
    $stmtP->close();

    // 2. Ambil tugas master bulanan yang belum dikerjakan
    $sqlMaster = "SELECT mt.judul, mt.deskripsi, mt.periode, mt.target_tgl 
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
            'judul' => $r['judul'],
            'deskripsi' => $r['deskripsi'],
            'periode' => $r['periode'],
            'tgl_mulai' => date('Y-m-') . date('j', strtotime($r['target_tgl'] ?: 'today')), // Estimasi tgl target
            'status' => 'Belum Dikerjakan'
        ];
    }
    $stmtM->close();
}

echo json_encode($tasks, JSON_UNESCAPED_UNICODE);
