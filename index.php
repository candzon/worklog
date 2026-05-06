<?php
/**
 * index.php (Controller)
 * Halaman utama Dashboard
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/DashboardModel.php';

ensure_session_started();

// 1. Otoritas & Identitas
$currentNpp = $_SESSION['npp'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;
$roleName = $_SESSION['role_name'] ?? null;

if (!$roleName && !empty($currentNpp) && isset($conn)) {
    $stmtR = $conn->prepare("SELECT r.name FROM employee e JOIN roles r ON e.role_id = r.id WHERE e.npp = ?");
    $stmtR->bind_param('s', $currentNpp);
    $stmtR->execute();
    if ($rowR = $stmtR->get_result()->fetch_assoc()) {
        $roleName = $rowR['name'];
        $_SESSION['role_name'] = $roleName;
    }
    $stmtR->close();
}

$isManager = ($roleId == 1 || $roleId == 2 || strtolower((string)$roleName) === 'manager' || strtolower((string)$roleName) === 'admin');

// 2. Pengambilan Data via Model (Aman dari error bool)
$model = new DashboardModel($conn);

// Filter Periode
$selectedMonth = $_GET['bulan'] ?? date('m');
$selectedYear = $_GET['tahun'] ?? date('Y');
$selectedStatus = $_GET['status_tugas'] ?? 'semua';
$currentMonth = "$selectedYear-$selectedMonth";

// Pagination untuk Tabel Progres
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$totalUserProgress = $model->getTotalEmployees($isManager, $currentNpp, $selectedStatus, $currentMonth);
$userProgress = $model->getUserProgress($isManager, $currentNpp, $currentMonth, $itemsPerPage, $offset, $selectedStatus);

// 3. Render Tampilan
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Masukkan file View Dashboard
require_once __DIR__ . '/views/dashboard.php';

require_once __DIR__ . '/includes/footer.php';
