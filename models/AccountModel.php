<?php
class AccountModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function getAll($limit, $offset) {
        $stmt = $this->db->prepare("SELECT e.*, r.name as role_name, b.nama_bagian as dept_name FROM employee e LEFT JOIN roles r ON e.role_id = r.id LEFT JOIN bagian b ON b.id_bagian = e.bagian_id ORDER BY e.nama_emp ASC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCount() {
        return (int)($this->db->query("SELECT COUNT(*) as cnt FROM employee")->fetch_assoc()['cnt'] ?? 0);
    }

    public function getRoles() { return $this->db->query("SELECT id, name FROM roles")->fetch_all(MYSQLI_ASSOC); }
    public function getDepts() { return $this->db->query("SELECT id_bagian, nama_bagian FROM bagian")->fetch_all(MYSQLI_ASSOC); }

    public function upsert($data) {
        $npp = $data['npp'];
        $check = $this->db->query("SELECT npp FROM employee WHERE npp = '$npp'");
        if ($check->num_rows > 0) {
            $stmt = $this->db->prepare("UPDATE employee SET nama_emp=?, jenis_kelamin=?, telp=?, nama_bagian=?, bagian_id=?, role_id=? WHERE npp=?");
            $stmt->bind_param('ssssiss', $data['nama_emp'], $data['jenis_kelamin'], $data['telp'], $data['bagian_id'], $data['bagian_id'], $data['role_id'], $npp);
        } else {
            $stmt = $this->db->prepare("INSERT INTO employee (npp, nama_emp, jenis_kelamin, telp, nama_bagian, bagian_id, role_id, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $pass = $npp;
            $stmt->bind_param('sssssiss', $npp, $data['nama_emp'], $data['jenis_kelamin'], $data['telp'], $data['bagian_id'], $data['bagian_id'], $data['role_id'], $pass);
        }
        return $stmt->execute();
    }

    public function delete($npp) {
        $stmt = $this->db->prepare("DELETE FROM employee WHERE npp = ?");
        $stmt->bind_param('s', $npp);
        return $stmt->execute();
    }
}
