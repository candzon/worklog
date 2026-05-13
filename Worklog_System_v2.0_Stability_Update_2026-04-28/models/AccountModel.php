<?php
class AccountModel {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function getAll($limit, $offset) {
        // Ambil data dengan JOIN ke tabel bagian untuk mendapatkan NAMA bagiannya
        $sql = "SELECT e.*, r.name as role_name, b.nama_bagian as dept_name 
                FROM employee e 
                LEFT JOIN roles r ON e.role_id = r.id 
                LEFT JOIN bagian b ON b.id_bagian = e.nama_bagian
                ORDER BY e.nama_emp ASC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Prepare Failed in AccountModel: " . $this->db->error);
            return [];
        }
        
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCount() {
        $res = $this->db->query("SELECT COUNT(*) as cnt FROM employee");
        return $res ? (int)($res->fetch_assoc()['cnt'] ?? 0) : 0;
    }

    public function getRoles() { 
        return $this->db->query("SELECT id, name FROM roles WHERE name = 'user'")->fetch_all(MYSQLI_ASSOC); 
    }
    
    public function getDepts() { 
        return $this->db->query("SELECT id_bagian, nama_bagian FROM bagian WHERE nama_bagian IN ('Apoteker', 'TTK') ORDER BY nama_bagian")->fetch_all(MYSQLI_ASSOC); 
    }

    public function upsert($data) {
        $npp = $data['npp'];
        $check = $this->db->query("SELECT npp FROM employee WHERE npp = '" . $this->db->real_escape_string($npp) . "'");
        
        if ($check && $check->num_rows > 0) {
            // Update: sesuaikan kolom dengan struktur asli (nama_bagian berisi ID)
            $stmt = $this->db->prepare("UPDATE employee SET nama_emp=?, jenis_kelamin=?, telp=?, nama_bagian=?, role_id=? WHERE npp=?");
            $stmt->bind_param('ssssis', $data['nama_emp'], $data['jenis_kelamin'], $data['telp'], $data['bagian_id'], $data['role_id'], $npp);
        } else {
            // Insert
            $stmt = $this->db->prepare("INSERT INTO employee (npp, nama_emp, jenis_kelamin, telp, nama_bagian, role_id, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $pass = $npp; // Default pass = NPP
            $stmt->bind_param('sssssis', $npp, $data['nama_emp'], $data['jenis_kelamin'], $data['telp'], $data['bagian_id'], $data['role_id'], $pass);
        }
        return $stmt ? $stmt->execute() : false;
    }

    public function delete($npp) {
        $stmt = $this->db->prepare("DELETE FROM employee WHERE npp = ?");
        $stmt->bind_param('s', $npp);
        return $stmt->execute();
    }
}
