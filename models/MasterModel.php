<?php
class MasterModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function getAll($limit, $offset) {
        $stmt = $this->db->prepare('SELECT m.*, b.nama_bagian FROM master_tugas m LEFT JOIN bagian b ON m.bagian_id = b.id_bagian ORDER BY m.id DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCount() {
        $res = $this->db->query('SELECT COUNT(*) AS cnt FROM master_tugas');
        return (int)($res->fetch_assoc()['cnt'] ?? 0);
    }

    public function getBagians() {
        $res = $this->db->query("SELECT id_bagian, nama_bagian FROM bagian WHERE nama_bagian IN ('Apoteker', 'TTK') ORDER BY nama_bagian");
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare('INSERT INTO master_tugas (judul, deskripsi, periode, target_tgl, bagian_id, npp_manager, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssssis', $data['judul'], $data['deskripsi'], $data['periode'], $data['target_tgl'], $data['bagian_id'], $data['npp_manager']);
        if ($stmt->execute()) return $this->db->insert_id;
        return false;
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE master_tugas SET judul = ?, deskripsi = ?, periode = ?, target_tgl = ?, bagian_id = ?, npp = NULL WHERE id = ?");
        $stmt->bind_param('ssssii', $data['judul'], $data['deskripsi'], $data['periode'], $data['target_tgl'], $data['bagian_id'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM master_tugas WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function isUsed($id) {
        $stmt = $this->db->prepare("SELECT id FROM pekerjaan WHERE master_tugas_id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($res->num_rows > 0);
    }

    public function syncDetails($masterId, $bagianId) {
        $this->db->query("DELETE FROM master_tugas_detail WHERE master_tugas_id = $masterId");
        $stmtEmp = $this->db->prepare("SELECT npp FROM employee WHERE nama_bagian = ?");
        $sBagId = (string)$bagianId;
        $stmtEmp->bind_param('s', $sBagId);
        $stmtEmp->execute();
        $resEmp = $stmtEmp->get_result();
        $stmtIns = $this->db->prepare('INSERT INTO master_tugas_detail (master_tugas_id, npp) VALUES (?, ?)');
        while ($row = $resEmp->fetch_assoc()) {
            $stmtIns->bind_param('is', $masterId, $row['npp']);
            $stmtIns->execute();
        }
        return true;
    }
}
