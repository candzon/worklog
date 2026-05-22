<?php
/**
 * models/DashboardModel.php
 */
class DashboardModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    private function querySafe($sql) {
        $res = $this->db->query($sql);
        if (!$res) {
            error_log("Database Query Failed: " . $this->db->error . " | SQL: " . $sql);
            return null;
        }
        return $res;
    }

    /**
     * Ambil master tugas untuk pegawai:
     * 1) yang sudah termapping di master_tugas_detail, atau
     * 2) fallback untuk master tugas tanpa detail mapping tetapi bagian-nya sama.
     */
    private function getMasterTasksForEmployee($npp) {
        $sql = "SELECT DISTINCT mt.id, mt.periode, mt.target_tgl, mt.created_at
                FROM master_tugas mt
                LEFT JOIN master_tugas_detail mtd_self
                    ON mtd_self.master_tugas_id = mt.id AND mtd_self.npp = '$npp'
                LEFT JOIN employee e
                    ON e.npp = '$npp'
                WHERE mtd_self.id IS NOT NULL
                   OR (
                        NOT EXISTS (
                            SELECT 1
                            FROM master_tugas_detail mtdx
                            WHERE mtdx.master_tugas_id = mt.id
                        )
                        AND e.bagian_id = mt.bagian_id
                   )";

        return $this->querySafe($sql);
    }

    public function getCounts($isManager, $currentNpp, $currentMonth) {
        // Task Done
        $sqlDone = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE LOWER(TRIM(status)) IN ('done','selesai','completed')";
        if (!$isManager) { $sqlDone .= " AND assigned_to_npp = '$currentNpp'"; }
        $resDone = $this->querySafe($sqlDone);
        $doneCount = $resDone ? (int)($resDone->fetch_assoc()['cnt'] ?? 0) : 0;

        // Task Open (Manual)
        $sqlOpenPek = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))";
        if (!$isManager) { $sqlOpenPek .= " AND assigned_to_npp = '$currentNpp'"; }
        $resOpenPek = $this->querySafe($sqlOpenPek);
        $openCount = $resOpenPek ? (int)($resOpenPek->fetch_assoc()['cnt'] ?? 0) : 0;

        return ['open' => $openCount, 'done' => $doneCount, 'near' => 0];
    }

    public function getRecentTasks($isManager, $currentNpp) {
        $sql = "SELECT id, judul, tgl_mulai, status FROM pekerjaan";
        if (!$isManager) { $sql .= " WHERE assigned_to_npp = '$currentNpp'"; }
        $sql .= " ORDER BY created_at DESC LIMIT 5";
        $res = $this->querySafe($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getUserProgress($isManager, $currentNpp, $selectedMonth, $selectedYear, $limit, $offset, $statusFilter = 'semua') {
        // Jika selectedMonth adalah 'semua_bulan', gunakan method khusus
        if ($selectedMonth === 'semua_bulan') {
            return $this->getUserProgressAllMonths($isManager, $currentNpp, $selectedYear, $limit, $offset, $statusFilter);    
        }

        $currentMonth = "$selectedYear-$selectedMonth";
        $sqlBase = "SELECT npp, nama_emp FROM employee WHERE role_id = 3";
        if (!$isManager) { $sqlBase .= " AND npp = '$currentNpp'"; }

        $resProg = $this->querySafe("$sqlBase ORDER BY nama_emp ASC");
        if (!$resProg) return [];

        $results = [];
        // Window waktu untuk filter bulan
        $startOfMonth = new DateTime("$currentMonth-01 00:00:00");
        $endOfMonth = new DateTime($startOfMonth->format('Y-m-t 23:59:59'));

        while ($emp = $resProg->fetch_assoc()) {
            $npp = $emp['npp'];

            // 1. HITUNG SELESAI (Realisasi di bulan tersebut)
            $resD = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND LOWER(TRIM(status)) IN ('done','selesai','completed') AND (DATE_FORMAT(tgl_selesai, '%Y-%m') = '$currentMonth' OR DATE_FORMAT(updated_at, '%Y-%m') = '$currentMonth')"); 
            $done = $resD ? (int)($resD->fetch_assoc()['cnt'] ?? 0) : 0;

            // 2. HITUNG REVISI (Ada aktivitas revisi di bulan tersebut)
            $resR = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND status = 'revisi' AND (DATE_FORMAT(tgl_mulai, '%Y-%m') = '$currentMonth' OR DATE_FORMAT(tgl_selesai, '%Y-%m') = '$currentMonth' OR DATE_FORMAT(updated_at, '%Y-%m') = '$currentMonth')");
            $revisi = $resR ? (int)($resR->fetch_assoc()['cnt'] ?? 0) : 0;

            // 3. HITUNG OPEN MANUAL (Dibuat di bulan tersebut dan belum selesai/revisi)
            $resP = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND master_tugas_id IS NULL AND status != 'revisi' AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) AND (DATE_FORMAT(tgl_mulai, '%Y-%m') = '$currentMonth')");
            $openP = $resP ? (int)($resP->fetch_assoc()['cnt'] ?? 0) : 0;

            // 4. HITUNG OPEN MASTER (VIRTUAL untuk bulan tersebut)
            $openM = 0;
            $resM = $this->getMasterTasksForEmployee($npp);
            if ($resM) {
                while ($mt = $resM->fetch_assoc()) {
                    $masterId = $mt['id'];
                    $periode = strtolower($mt['periode']);
                    $targetDay = !empty($mt['target_tgl']) ? (int)date('j', strtotime($mt['target_tgl'])) : 1;  
                    $targetWDay = !empty($mt['target_tgl']) ? (int)date('w', strtotime($mt['target_tgl'])) : 1; 

                    $createdAt = new DateTime($mt['created_at']);
                    $limitStartMonth = $createdAt->format('Y-m');

                    if (strcmp($currentMonth, $limitStartMonth) < 0) continue;

                    $cursor = clone $startOfMonth;
                    if (($periode === 'harian' || $periode === 'mingguan') && $currentMonth === $limitStartMonth) {
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
                            $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay);
                            $cursor->modify('first day of next month');
                        } elseif ($periode === 'triwulan') {
                            if (((int)$cursor->format('n') - 1) % 3 === 0) {
                                $useDay = min($targetDay, (int)$cursor->format('t'));
                                $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay);
                            }
                            $cursor->modify('first day of next month');
                        } else { $cursor->modify('+1 day'); }

                        if ($occDate && $occDate >= $startOfMonth->format('Y-m-d') && $occDate <= $endOfMonth->format('Y-m-d')) {
                            // Cek Realisasi
                            $resCheck = $this->querySafe("SELECT 1 FROM pekerjaan WHERE master_tugas_id = '$masterId' AND assigned_to_npp = '$npp' AND tgl_mulai = '$occDate' AND LOWER(TRIM(status)) IN ('done','selesai','completed','revisi')");
                            if (!$resCheck || $resCheck->num_rows == 0) {
                                $openM++;
                            }
                        }
                    }
                }
            }

            $openTotal = $openP + $openM;
            $total = $done + $openTotal + $revisi;

            $include = true;
            if ($statusFilter === 'open' && $openTotal == 0) $include = false;
            elseif ($statusFilter === 'done' && $done == 0) $include = false;
            elseif ($statusFilter === 'revisi' && $revisi == 0) $include = false;
            if ($statusFilter === 'semua') $include = true;

            if ($include) {
                $results[] = [
                    'npp' => $npp, 'nama_emp' => $emp['nama_emp'], 'total' => $total,
                    'selesai' => $done, 'open' => $openTotal, 'revisi' => $revisi,
                    'persen' => ($total > 0 ? round(($done / $total) * 100) : 0)
                ];
            }
        }

        $this->lastFilteredCount = count($results);
        return array_slice($results, $offset, $limit);
    }

    // METHOD UNTUK SEMUA BULAN (Dibatasi Tahun)
    private function getUserProgressAllMonths($isManager, $currentNpp, $selectedYear, $limit, $offset, $statusFilter = 'semua') {
        $sqlBase = "SELECT npp, nama_emp FROM employee WHERE role_id = 3";
        if (!$isManager) { $sqlBase .= " AND npp = '$currentNpp'"; }

        $resProg = $this->querySafe("$sqlBase ORDER BY nama_emp ASC");
        if (!$resProg) return [];

        $results = [];
        $now = new DateTime();
        $yearFilter = (int)$selectedYear;

        while ($emp = $resProg->fetch_assoc()) {
            $npp = $emp['npp'];

            // 1. HITUNG SEMUA TUGAS SELESAI di Tahun tsb
            $resD = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND LOWER(TRIM(status)) IN ('done','selesai','completed') AND YEAR(tgl_selesai) = $yearFilter");
            $doneManual = $resD ? (int)($resD->fetch_assoc()['cnt'] ?? 0) : 0;

            // 2. HITUNG SEMUA REVISI di Tahun tsb
            $resR = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND status = 'revisi' AND (YEAR(tgl_mulai) = $yearFilter OR YEAR(updated_at) = $yearFilter)");
            $revisiCount = $resR ? (int)($resR->fetch_assoc()['cnt'] ?? 0) : 0;

            // 3. HITUNG SEMUA TUGAS OPEN MANUAL di Tahun tsb
            $resP = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND master_tugas_id IS NULL AND status != 'revisi' AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) AND YEAR(tgl_mulai) = $yearFilter");
            $openManual = $resP ? (int)($resP->fetch_assoc()['cnt'] ?? 0) : 0;

            // 4. HITUNG VIRTUAL MASTER TASKS DARI TAHUN TERSEBUT
            $openVirtualMaster = 0;
            $resM = $this->getMasterTasksForEmployee($npp);
            if ($resM) {
                while ($mt = $resM->fetch_assoc()) {
                    $masterId = $mt['id'];
                    $periode = strtolower($mt['periode']);
                    $targetDay = !empty($mt['target_tgl']) ? (int)date('j', strtotime($mt['target_tgl'])) : 1;  
                    $targetWDay = !empty($mt['target_tgl']) ? (int)date('w', strtotime($mt['target_tgl'])) : 1; 

                    $createdAt = new DateTime($mt['created_at']);
                    
                    // Tentukan rentang waktu untuk tahun ini
                    $cursor = new DateTime("$yearFilter-01-01 00:00:00");
                    if ((int)$createdAt->format('Y') > $yearFilter) continue; // Belum dibuat di tahun ini
                    if ((int)$createdAt->format('Y') === $yearFilter) {
                        $cursor = clone $createdAt;
                    }
                    
                    $yearEnd = new DateTime("$yearFilter-12-31 23:59:59");
                    $limitDate = ($now < $yearEnd) ? $now : $yearEnd;

                    while ($cursor <= $limitDate) {
                        $occDate = null;
                        if ($periode === 'harian') {
                            $occDate = $cursor->format('Y-m-d');
                            $cursor->modify('+1 day');
                        } elseif ($periode === 'mingguan') {
                            if ((int)$cursor->format('w') === $targetWDay) { $occDate = $cursor->format('Y-m-d'); }
                            $cursor->modify('+1 day');
                        } elseif ($periode === 'bulanan') {
                            $useDay = min($targetDay, (int)$cursor->format('t'));
                            $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay);
                            $cursor->modify('first day of next month');
                        } elseif ($periode === 'triwulan') {
                            if (((int)$cursor->format('n') - 1) % 3 === 0) {
                                $useDay = min($targetDay, (int)$cursor->format('t'));
                                $occDate = sprintf('%s-%02d', $cursor->format('Y-m'), $useDay);
                            }
                            $cursor->modify('first day of next month');
                        } else { $cursor->modify('+1 day'); }

                        if ($occDate && $occDate >= $createdAt->format('Y-m-d') && (int)date('Y', strtotime($occDate)) == $yearFilter && $occDate <= $limitDate->format('Y-m-d')) {
                            $resCheck = $this->querySafe("SELECT 1 FROM pekerjaan WHERE master_tugas_id = '$masterId' AND assigned_to_npp = '$npp' AND tgl_mulai = '$occDate' AND LOWER(TRIM(status)) IN ('done','selesai','completed','revisi')");
                            if (!$resCheck || $resCheck->num_rows == 0) {
                                $openVirtualMaster++;
                            }
                        }
                    }
                }
            }

            $openTotal = $openManual + $openVirtualMaster;
            $total = $doneManual + $openTotal + $revisiCount;

            $include = true;
            if ($statusFilter === 'open' && $openTotal == 0) $include = false;
            elseif ($statusFilter === 'done' && $doneManual == 0) $include = false;
            elseif ($statusFilter === 'revisi' && $revisiCount == 0) $include = false;
            if ($statusFilter === 'semua') $include = true;

            if ($include) {
                $results[] = [
                    'npp' => $npp, 'nama_emp' => $emp['nama_emp'], 'total' => $total,
                    'selesai' => $doneManual, 'open' => $openTotal, 'revisi' => $revisiCount,
                    'persen' => ($total > 0 ? round(($doneManual / $total) * 100) : 0)
                ];
            }
        }

        $this->lastFilteredCount = count($results);
        return array_slice($results, $offset, $limit);
    }

    private $lastFilteredCount = 0;

    public function getTotalEmployees($isManager, $currentNpp, $statusFilter = 'semua', $selectedMonth = '', $selectedYear = '') {   
        $all = $this->getUserProgress($isManager, $currentNpp, $selectedMonth, $selectedYear, 999999, 0, $statusFilter);
        return count($all);
    }
}
