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

    public function getUserProgress($isManager, $currentNpp, $currentMonth, $limit, $offset, $statusFilter = 'semua') {
        $sqlBase = "SELECT npp, nama_emp FROM employee WHERE role_id = 3";
        if (!$isManager) { $sqlBase .= " AND npp = '$currentNpp'"; }
        
        $resProg = $this->querySafe("$sqlBase ORDER BY nama_emp ASC");
        if (!$resProg) return [];

        $results = [];
        // Pastikan currentMonth berformat Y-m (misal 2026-04)
        $startOfMonth = new DateTime("$currentMonth-01 00:00:00");
        $endOfMonth = new DateTime($startOfMonth->format('Y-m-t 23:59:59'));

        while ($emp = $resProg->fetch_assoc()) {
            $npp = $emp['npp'];
            
            // 1. HITUNG SELESAI (Realisasi)
            $resD = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND LOWER(TRIM(status)) IN ('done','selesai','completed') AND DATE_FORMAT(tgl_mulai, '%Y-%m') = '$currentMonth'");
            $done = $resD ? (int)($resD->fetch_assoc()['cnt'] ?? 0) : 0;

            // 2. HITUNG OPEN MANUAL
            $resP = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND master_tugas_id IS NULL AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) AND DATE_FORMAT(tgl_mulai, '%Y-%m') = '$currentMonth'");
            $openP = $resP ? (int)($resP->fetch_assoc()['cnt'] ?? 0) : 0;

            // 3. HITUNG OPEN MASTER (VIRTUAL)
            $openM = 0;
            $resM = $this->querySafe("SELECT mt.id, mt.periode, mt.target_tgl, mt.created_at FROM master_tugas_detail mtd JOIN master_tugas mt ON mt.id = mtd.master_tugas_id WHERE mtd.npp = '$npp'");
            if ($resM) {
                while ($mt = $resM->fetch_assoc()) {
                    $masterId = $mt['id'];
                    $periode = strtolower($mt['periode']);
                    $targetDay = !empty($mt['target_tgl']) ? (int)date('j', strtotime($mt['target_tgl'])) : 1;
                    $targetWDay = !empty($mt['target_tgl']) ? (int)date('w', strtotime($mt['target_tgl'])) : 1;
                    
                    $createdAt = new DateTime($mt['created_at']);
                    $limitStartMonth = $createdAt->format('Y-m');

                    // KEAMANAN: Jangan hitung jika bulan filter adalah MASA LALU dibanding bulan pembuatan
                    if (strcmp($currentMonth, $limitStartMonth) < 0) continue;

                    $cursor = clone $startOfMonth;
                    // Untuk Harian/Mingguan di bulan pembuatan, jangan hitung hari sebelum dibuat
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
                            // Cek Realisasi 'done'
                            $resCheck = $this->querySafe("SELECT 1 FROM pekerjaan WHERE master_tugas_id = '$masterId' AND assigned_to_npp = '$npp' AND tgl_mulai = '$occDate' AND LOWER(TRIM(status)) IN ('done','selesai','completed')");
                            if (!$resCheck || $resCheck->num_rows == 0) {
                                $openM++;
                            }
                        }
                    }
                }
            }

            $openTotal = $openP + $openM;
            $total = $done + $openTotal;
            
            $include = true;
            if ($statusFilter === 'open' && $openTotal == 0) $include = false;
            elseif ($statusFilter === 'done' && $done == 0) $include = false;
            if ($statusFilter === 'semua') $include = true;

            if ($include) {
                $results[] = [
                    'npp' => $npp, 'nama_emp' => $emp['nama_emp'], 'total' => $total,
                    'selesai' => $done, 'open' => $openTotal,
                    'persen' => ($total > 0 ? round(($done / $total) * 100) : 0)
                ];
            }
        }

        $this->lastFilteredCount = count($results);
        return array_slice($results, $offset, $limit);
    }

    private $lastFilteredCount = 0;

    public function getTotalEmployees($isManager, $currentNpp, $statusFilter = 'semua', $currentMonth = '') {
        $all = $this->getUserProgress($isManager, $currentNpp, $currentMonth, 999999, 0, $statusFilter);
        return count($all);
    }
}
