<?php
/**
 * master_pekerjaan.php (Controller)
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/MasterModel.php';

ensure_session_started();

// 1. Role Check
$roleName = function_exists('get_current_role_name') ? get_current_role_name($conn ?? null) : null;
$isManager = (strtolower((string)$roleName) === 'manager' || strtolower((string)$roleName) === 'admin');
if (!$isManager) {
    http_response_code(403);
    require_once __DIR__ . '/includes/403.php';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 2. Fetch Data
$model = new MasterModel($conn);
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalRows = $model->getCount();
$totalPages = ceil(max(1, $totalRows) / $perPage);
$offset = ($page - 1) * $perPage;

$masters = $model->getAll($perPage, $offset);
$bagians = $model->getBagians();

// 3. Render View
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Load View (HTML & AJAX Logic)
require_once __DIR__ . '/views/master.php';

require_once __DIR__ . '/includes/footer.php';
