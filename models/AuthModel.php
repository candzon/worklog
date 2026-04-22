<?php
/**
 * models/AuthModel.php
 */
class AuthModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function verify($npp, $password) {
        $stmt = $this->db->prepare("SELECT e.*, r.name as role_name 
                                    FROM employee e 
                                    LEFT JOIN roles r ON r.id = e.role_id 
                                    WHERE e.npp = ? LIMIT 1");
        $stmt->bind_param('s', $npp);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Jika password di database belum di-hash, kita bandingkan langsung.
            // Jika sudah di-hash (disarankan), gunakan password_verify($password, $user['password'])
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }
}
