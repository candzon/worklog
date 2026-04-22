<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
if (empty($npp)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$masterId = intval($_POST['master_tugas_id'] ?? 0);
$occDate = $_POST['tgl_mulai'] ?? null;
$token = $_POST['token'] ?? null;

// Handle File Upload
$lampiran = null;
if (!empty($_FILES['lampiran']['name'])) {
    $file = $_FILES['lampiran'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Validasi maksimal 2MB (2 * 1024 * 1024)
    if ($file['size'] <= 2 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $newName = 'done_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            $lampiran = $newName;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ukuran file bukti terlalu besar (Maks 2MB)']);
        exit;
    }
}

if ($masterId > 0 && $occDate) {
    // Logic for Master Recurring
    if (!function_exists('worklog_verify_master_occurrence') || !worklog_verify_master_occurrence($masterId, $occDate, $token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Token']);
        exit;
    }

    $stmtM = $conn->prepare('SELECT judul, deskripsi, periode, npp_manager FROM master_tugas WHERE id = ?');
    $stmtM->bind_param('i', $masterId);
    $stmtM->execute();
    $mt = $stmtM->get_result()->fetch_assoc();
    $stmtM->close();

    if (!$mt) {
        echo json_encode(['success' => false, 'message' => 'Master not found']);
        exit;
    }

    $coordinatorNpp = $mt['npp_manager'] ?? null;
    $coordinatorName = 'Manager';
    if ($coordinatorNpp) {
        $stC = $conn->prepare('SELECT nama_emp FROM employee WHERE npp = ? LIMIT 1');
        $stC->bind_param('s', $coordinatorNpp);
        $stC->execute();
        if ($rC = $stC->get_result()->fetch_assoc()) $coordinatorName = $rC['nama_emp'];
        $stC->close();
    }

    $ins = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, master_tugas_id, periode, status, lampiran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), 'done', ?)");
    if ($ins) {
        // sssssssiss = 7 strings, 1 int, 2 strings (Total 10 placeholders)
        $ins->bind_param('sssssssiss', $mt['judul'], $mt['deskripsi'], $coordinatorNpp, $coordinatorName, $occDate, $occDate, $npp, $masterId, $mt['periode'], $lampiran);
        $ok = $ins->execute();
        $ins->close();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan penyimpanan.']);
    }
} else {
    // Logic for direct task ID
    $up = $conn->prepare("UPDATE pekerjaan SET status = 'done', updated_at = NOW(), lampiran = COALESCE(?, lampiran) WHERE id = ? AND assigned_to_npp = ?");
    if ($up) {
        $up->bind_param('sis', $lampiran, $id, $npp);
        $ok = $up->execute();
        $up->close();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status.']);
    }
}
