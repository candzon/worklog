<?php
/**
 * api/update_profile.php
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi berakhir, silakan login kembali.']);
    exit;
}

$npp = $_SESSION['npp'];
$telp = trim($_POST['telp'] ?? '');
$password = trim($_POST['password'] ?? '');

try {
    $conn->begin_transaction();

    // 1. Update Telepon
    $stmt = $conn->prepare("UPDATE employee SET telp = ? WHERE npp = ?");
    $stmt->bind_param('ss', $telp, $npp);
    $stmt->execute();

    // 2. Update Password (jika diisi)
    if (!empty($password)) {
        // Karena user meminta password TIDAK DIHASH (seperti sebelumnya)
        // Saya akan mengikuti pola password plain-text sesuai instruksi sebelumnya
        // meskipun sangat disarankan untuk hashing.
        $stmt = $conn->prepare("UPDATE employee SET password = ? WHERE npp = ?");
        $stmt->bind_param('ss', $password, $npp);
        $stmt->execute();
    }

    // 3. Update Foto (jika ada upload)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $fileType = $_FILES['foto']['type'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Format file tidak didukung (Gunakan: jpg, png, gif).");
        }
        
        if ($fileSize > 2 * 1024 * 1024) {
            throw new Exception("Ukuran file maksimal 2MB.");
        }

        $newFileName = 'avatar_' . $npp . '_' . time() . '.' . $fileExtension;
        $uploadFileDir = __DIR__ . '/../uploads/profile/';
        
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0777, true);
        }

        $destPath = $uploadFileDir . $newFileName;

        if(move_uploaded_file($fileTmpPath, $destPath)) {
            // Hapus foto lama jika ada
            $stmtOld = $conn->prepare("SELECT foto FROM employee WHERE npp = ?");
            $stmtOld->bind_param('s', $npp);
            $stmtOld->execute();
            $oldFoto = $stmtOld->get_result()->fetch_assoc()['foto'] ?? null;
            if ($oldFoto && file_exists($uploadFileDir . $oldFoto)) {
                unlink($uploadFileDir . $oldFoto);
            }

            // Update database
            $stmt = $conn->prepare("UPDATE employee SET foto = ? WHERE npp = ?");
            $stmt->bind_param('ss', $newFileName, $npp);
            $stmt->execute();
        } else {
            throw new Exception("Gagal mengunggah foto.");
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
