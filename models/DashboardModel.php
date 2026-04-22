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

        // Task Open (Master Rutin)
        $sqlOpenMaster = "SELECT COUNT(*) AS cnt FROM master_tugas_detail mtd JOIN master_tugas mt ON mt.id = mtd.master_tugas_id WHERE mt.periode = 'bulanan' AND NOT EXISTS (SELECT 1 FROM pekerjaan p WHERE p.master_tugas_id = mtd.master_tugas_id AND p.assigned_to_npp = mtd.npp AND DATE_FORMAT(p.tgl_mulai, '%Y-%m') = '$currentMonth')";
        if (!$isManager) { $sqlOpenMaster .= " AND mtd.npp = '$currentNpp'"; }
        $resOpenMaster = $this->querySafe($sqlOpenMaster);
        $openCount += $resOpenMaster ? (int)($resOpenMaster->fetch_assoc()['cnt'] ?? 0) : 0;

        // Near Deadline
        $sqlNear = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) AND tgl_selesai BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        if (!$isManager) { $sqlNear .= " AND assigned_to_npp = '$currentNpp'"; }
        $resNear = $this->querySafe($sqlNear);
        $nearCount = $resNear ? (int)($resNear->fetch_assoc()['cnt'] ?? 0) : 0;

        return ['open' => $openCount, 'done' => $doneCount, 'near' => $nearCount];
    }

    public function getRecentTasks($isManager, $currentNpp) {
        $sql = "SELECT id, judul, tgl_mulai, status FROM pekerjaan";
        if (!$isManager) { $sql .= " WHERE assigned_to_npp = '$currentNpp'"; }
        $sql .= " ORDER BY created_at DESC LIMIT 5";
        $res = $this->querySafe($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getUserProgress($isManager, $currentNpp, $currentMonth, $limit, $offset) {
        $sqlBase = "SELECT npp, nama_emp FROM employee WHERE role_id = 3";
        if (!$isManager) { $sqlBase .= " AND npp = '$currentNpp'"; }
        $resProg = $this->querySafe("$sqlBase ORDER BY nama_emp ASC LIMIT $limit OFFSET $offset");
        if (!$resProg) return [];

        $results = [];
        while ($emp = $resProg->fetch_assoc()) {
            $npp = $emp['npp'];
            $resD = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND LOWER(TRIM(status)) IN ('done','selesai','completed')");
            $done = $resD ? (int)($resD->fetch_assoc()['cnt'] ?? 0) : 0;

            $resP = $this->querySafe("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$npp' AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))");
            $openP = $resP ? (int)($resP->fetch_assoc()['cnt'] ?? 0) : 0;

            $resM = $this->querySafe("SELECT COUNT(*) as cnt FROM master_tugas_detail mtd JOIN master_tugas mt ON mt.id = mtd.master_tugas_id WHERE mtd.npp = '$npp' AND mt.periode = 'bulanan' AND NOT EXISTS (SELECT 1 FROM pekerjaan p WHERE p.master_tugas_id = mtd.master_tugas_id AND p.assigned_to_npp = mtd.npp AND DATE_FORMAT(p.tgl_mulai, '%Y-%m') = '$currentMonth')");
            $openM = $resM ? (int)($resM->fetch_assoc()['cnt'] ?? 0) : 0;

            $total = $done + $openP + $openM;
            $results[] = [
                'npp' => $npp, 'nama_emp' => $emp['nama_emp'], 'total' => $total, 
                'selesai' => $done, 'open' => $openP + $openM,
                'persen' => ($total > 0 ? round(($done / $total) * 100) : 0)
            ];
        }
        return $results;
    }

    public function getTotalEmployees($isManager, $currentNpp) {
        $sql = "SELECT COUNT(*) as total FROM employee WHERE role_id = 3";
        if (!$isManager) { $sql .= " AND npp = '$currentNpp'"; }
        $res = $this->querySafe($sql);
        return $res ? (int)($res->fetch_assoc()['total'] ?? 0) : 0;
    }
}
