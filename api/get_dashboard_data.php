<?php
/**
 * api/get_dashboard_data.php
 * Endpoint untuk mengambil data ringkasan progres pegawai untuk dashboard via AJAX.
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DashboardModel.php';

ensure_session_started();
header('Content-Type: application/json; charset=utf-8');

$isManager = (strtolower($_SESSION['role_name'] ?? '') === 'manager' || strtolower($_SESSION['role_name'] ?? '') === 'admin');
$currentNpp = $_SESSION['npp'] ?? '';

$selectedMonth = $_GET['bulan'] ?? date('m');
$selectedYear = $_GET['tahun'] ?? date('Y');
$selectedStatus = $_GET['status_tugas'] ?? 'semua';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

$model = new DashboardModel($conn);
$userProgress = $model->getUserProgress($isManager, $currentNpp, $selectedMonth, $selectedYear, $itemsPerPage, $offset, $selectedStatus);
$totalItems = $model->getTotalEmployees($isManager, $currentNpp, $selectedStatus, $selectedMonth, $selectedYear);
$totalPages = ceil($totalItems / $itemsPerPage);

echo json_encode([
    'success' => true,
    'data' => $userProgress,
    'pagination' => [
        'page' => $page,
        'totalPages' => $totalPages,
        'totalItems' => $totalItems,
        'itemsPerPage' => $itemsPerPage
    ],
    'debug' => [
        'filter' => [
            'bulan' => $selectedMonth,
            'tahun' => $selectedYear,
            'status' => $selectedStatus
        ]
    ]
], JSON_UNESCAPED_UNICODE);
