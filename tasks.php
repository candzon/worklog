<?php
/**
 * tasks.php (Controller)
 * Halaman Kalender Pekerjaan
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';

ensure_session_started();
require_login();

$npp = $_SESSION['npp'] ?? null;

// Fetch employees for dropdown selection
$employees = [];
if (isset($conn)) {
    // Ambil data karyawan satu bagian (opsional, atau ambil semua jika admin/manager)
    $res = $conn->query("SELECT npp, nama_emp FROM employee ORDER BY nama_emp ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $employees[] = $row;
        }
    }
}

// Render Tampilan
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Masukkan file View Tasks
require_once __DIR__ . '/views/tasks.php';

require_once __DIR__ . '/includes/footer.php';
