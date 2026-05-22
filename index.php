<?php
/**
 * index.php (Controller)
 * Halaman utama Dashboard
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/DashboardModel.php';

ensure_session_started();

// Cek Login
if (!isset($_SESSION['npp'])) {
    header('Location: ' . site_url('login.php'));
    exit;
}

$isManager = (strtolower($_SESSION['role_name'] ?? '') === 'manager' || strtolower($_SESSION['role_name'] ?? '') === 'admin');
$currentNpp = $_SESSION['npp'];

// Filter & Pagination (Initial Load)
$selectedMonth = $_GET['bulan'] ?? date('m');
$selectedYear = $_GET['tahun'] ?? date('Y');
$selectedStatus = $_GET['status_tugas'] ?? 'semua';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;

$model = new DashboardModel($conn);
$userProgress = $model->getUserProgress($isManager, $currentNpp, $selectedMonth, $selectedYear, $itemsPerPage, $offset, $selectedStatus);
$totalUserProgress = $model->getTotalEmployees($isManager, $currentNpp, $selectedStatus, $selectedMonth, $selectedYear);

// Get counts for small boxes (Current month only)
$counts = $model->getCounts($isManager, $currentNpp, date('Y-m'));

// Hitung total revisi untuk alert (khusus user ybs jika bukan manager, atau semua jika manager)
$sqlAlert = "SELECT COUNT(*) as cnt FROM pekerjaan WHERE status = 'revisi'";
if (!$isManager) { $sqlAlert .= " AND assigned_to_npp = '$currentNpp'"; }
$resAlert = $conn->query($sqlAlert);
$revisiAlertCount = $resAlert ? ($resAlert->fetch_assoc()['cnt'] ?? 0) : 0;

// Hitung jumlah agenda unik (Grouping sesuai logika kalender)
$sqlAgenda = "SELECT COUNT(*) as cnt FROM (
    SELECT 1 FROM pekerjaan 
    WHERE status = 'revisi' " . (!$isManager ? " AND assigned_to_npp = '$currentNpp' " : "") . "
    GROUP BY COALESCE(master_tugas_id, 0), tgl_mulai, (CASE WHEN master_tugas_id IS NULL THEN judul ELSE '' END)
) as grouped";
$resAgenda = $conn->query($sqlAgenda);
$revisiAgendaCount = $resAgenda ? ($resAgenda->fetch_assoc()['cnt'] ?? 0) : 0;

// Render Tampilan
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Masukkan file View Dashboard
require_once __DIR__ . '/views/dashboard.php';

require_once __DIR__ . '/includes/footer.php';
