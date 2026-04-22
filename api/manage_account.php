<?php
/**
 * api/manage_account.php
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AccountModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$model = new AccountModel($conn);
$action = $_GET['action'] ?? '';

try {
    if ($action === 'upsert') {
        if (empty($_POST['npp']) || empty($_POST['nama_emp'])) {
            throw new Exception("NPP dan Nama wajib diisi.");
        }
        $data = [
            'npp' => $_POST['npp'],
            'nama_emp' => $_POST['nama_emp'],
            'telp' => $_POST['telp'],
            'jenis_kelamin' => $_POST['jenis_kelamin'],
            'bagian_id' => $_POST['bagian_id'],
            'role_id' => $_POST['role_id']
        ];
        if($model->upsert($data)) {
            echo json_encode(['success'=>true, 'message'=>'Data pegawai berhasil disimpan.']);
        } else {
            throw new Exception("Gagal menyimpan ke database.");
        }
    } 
    elseif ($action === 'delete') {
        $npp = $_POST['npp'] ?? '';
        if(empty($npp)) throw new Exception("NPP tidak valid.");
        if($model->delete($npp)) {
            echo json_encode(['success'=>true, 'message'=>'Akun berhasil dihapus.']);
        } else {
            throw new Exception("Gagal menghapus data.");
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
exit;
