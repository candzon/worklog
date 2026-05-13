<?php
class TaskModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, master_tugas_id, periode, status, lampiran) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?,''), NULLIF(?,''), ?, ?)");
        $stmt->bind_param('sssssssssss', $data['judul'], $data['deskripsi'], $data['created_by_npp'], $data['nama_emp'], $data['tgl_mulai'], $data['tgl_selesai'], $data['assigned_to_npp'], $data['master_tugas_id'], $data['periode'], $data['status'], $data['lampiran']);
        if ($stmt->execute()) return $this->db->insert_id;
        return false;
    }

    public function markDone($id, $npp, $lampiran = null) {
        $up = $this->db->prepare("UPDATE pekerjaan SET status = 'done', updated_at = NOW(), lampiran = COALESCE(?, lampiran) WHERE id = ? AND assigned_to_npp = ?");
        $up->bind_param('sis', $lampiran, $id, $npp);
        return $up->execute();
    }

    public function getEmployees() {
        $res = $this->db->query("SELECT npp, nama_emp FROM employee ORDER BY nama_emp ASC");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}
