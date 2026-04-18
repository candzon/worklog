<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

ensure_session_started();

// If the request was rewritten to index.php for an unknown path (pretty URL),
// show 404 instead of the dashboard.
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$base = function_exists('base_path') ? base_path() : '';
if (is_string($reqPath) && $base !== '' && strpos($reqPath, $base) === 0) {
  $reqPath = substr($reqPath, strlen($base));
}
$reqPath = trim((string) $reqPath, '/');
if ($reqPath !== '' && $reqPath !== 'index.php') {
  http_response_code(404);
  require_once __DIR__ . '/includes/404.php';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

$currentNpp = $_SESSION['npp'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;
$roleName = null;
if (isset($conn)) {
  if ($roleId) {
    $stmtR = $conn->prepare('SELECT name FROM roles WHERE id = ? LIMIT 1');
    if ($stmtR) {
      $stmtR->bind_param('i', $roleId);
      $stmtR->execute();
      $resR = $stmtR->get_result();
      $r = $resR->fetch_assoc();
      $roleName = $r['name'] ?? null;
      $stmtR->close();
    }
  } elseif ($currentNpp) {
    $stmtR = $conn->prepare('SELECT r.name FROM employee e LEFT JOIN roles r ON e.role_id = r.id WHERE e.npp = ? LIMIT 1');
    if ($stmtR) {
      $stmtR->bind_param('s', $currentNpp);
      $stmtR->execute();
      $resR = $stmtR->get_result();
      $r = $resR->fetch_assoc();
      $roleName = $r['name'] ?? null;
      $stmtR->close();
    }
  }

  // Build filters: if user role, restrict to tasks assigned to current NPP
  $assignedFilterSql = '';
  $assignedParams = [];
  if ($roleName === 'user' && $currentNpp) {
    $assignedFilterSql = "AND assigned_to_npp = ?";
    $assignedParams[] = $currentNpp;
  }

  // Count open tasks
  $openCount = 0;
  $sql = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) " . $assignedFilterSql;
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if (!empty($assignedParams)) $stmt->bind_param('s', $assignedParams[0]);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $openCount = intval($row['cnt'] ?? 0);
    $stmt->close();
  }

  // Count done tasks
  $doneCount = 0;
  $sql = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE LOWER(TRIM(status)) IN ('done','selesai','completed') " . $assignedFilterSql;
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if (!empty($assignedParams)) $stmt->bind_param('s', $assignedParams[0]);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $doneCount = intval($row['cnt'] ?? 0);
    $stmt->close();
  }

  // Recent tasks (latest 5)
  $recent = [];
  $sql = "SELECT id, judul, tgl_mulai, tgl_selesai, status, assigned_to_npp FROM pekerjaan WHERE 1 " . $assignedFilterSql . " ORDER BY created_at DESC LIMIT 5";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if (!empty($assignedParams)) $stmt->bind_param('s', $assignedParams[0]);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $recent[] = $r;
    $stmt->close();
  }

  // Near-deadline: tgl_selesai within next 7 days and not done
  $nearCount = 0;
  $sql = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) AND tgl_selesai IS NOT NULL AND tgl_selesai BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) " . $assignedFilterSql;
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    if (!empty($assignedParams)) $stmt->bind_param('s', $assignedParams[0]);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $nearCount = intval($row['cnt'] ?? 0);
    $stmt->close();
  }
} else {
  $openCount = $doneCount = $nearCount = 0;
  $recent = [];
}

// User progress logic
$userProgress = [];
$totalUserProgress = 0;
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

if (isset($conn)) {
    $progressFilterSql = '';
    $progressParams = [];
    if ($roleName === 'user' && $currentNpp) {
        $progressFilterSql = " WHERE p.assigned_to_npp = ? ";
        $progressParams[] = $currentNpp;
    }

    $countSql = "SELECT COUNT(DISTINCT assigned_to_npp) as total FROM pekerjaan p" . $progressFilterSql;
    $stmtC = $conn->prepare($countSql);
    if ($stmtC) {
        if (!empty($progressParams)) $stmtC->bind_param('s', $progressParams[0]);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        $countRow = $resC->fetch_assoc();
        $totalUserProgress = intval($countRow['total'] ?? 0);
        $stmtC->close();
    }

    $sql = "SELECT 
                p.assigned_to_npp AS npp, 
                COALESCE(e.nama_emp, p.assigned_to_npp) AS nama_emp,
                COUNT(*) AS total,
                SUM(CASE WHEN LOWER(TRIM(p.status)) IN ('done','selesai','completed') THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN NOT (LOWER(TRIM(p.status)) IN ('done','selesai','completed')) THEN 1 ELSE 0 END) AS berjalan
            FROM pekerjaan p 
            LEFT JOIN employee e ON e.npp = p.assigned_to_npp " . $progressFilterSql . "
            GROUP BY p.assigned_to_npp, e.nama_emp 
            ORDER BY total DESC 
            LIMIT ? OFFSET ?";
    
    $stmtP = $conn->prepare($sql);
    if ($stmtP) {
        if (!empty($progressParams)) {
            $stmtP->bind_param('sii', $progressParams[0], $itemsPerPage, $offset);
        } else {
            $stmtP->bind_param('ii', $itemsPerPage, $offset);
        }
        $stmtP->execute();
        $resP = $stmtP->get_result();
        while ($r = $resP->fetch_assoc()) {
            $r['total'] = intval($r['total']);
            $r['selesai'] = intval($r['selesai']);
            $r['berjalan'] = intval($r['berjalan']);
            $r['persen'] = $r['total'] > 0 ? round(($r['selesai'] / $r['total']) * 100) : 0;
            $userProgress[] = $r;
        }
        $stmtP->close();
    }
}
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Dashboard</h3></div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </div>
      </div>

      <div class="app-content">
        <div class="container-fluid">
          <div class="row">
            <!-- Small Box 1 - Open -->
            <div class="col-lg-3 col-6">
              <div class="small-box text-bg-primary">
                <div class="inner"><h3><?php echo $openCount; ?></h3><p>Task Open</p></div>
                <i class="small-box-icon bi bi-list-task"></i>
                <a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0">More info <i class="bi bi-link-45deg"></i></a>
              </div>
            </div>
            <!-- Small Box 2 - Done -->
            <div class="col-lg-3 col-6">
              <div class="small-box text-bg-success">
                <div class="inner"><h3><?php echo $doneCount; ?></h3><p>Task Done</p></div>
                <i class="small-box-icon bi bi-check2-all"></i>
                <a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0">More info <i class="bi bi-link-45deg"></i></a>
              </div>
            </div>
            <!-- Small Box 3 - Recent -->
            <div class="col-lg-3 col-6">
              <div class="small-box text-bg-warning">
                <div class="inner"><h3><?php echo count($recent); ?></h3><p>Task Terbaru</p></div>
                <i class="small-box-icon bi bi-clock-history"></i>
                <a href="daftar_pekerjaan.php" class="small-box-footer link-dark link-underline-opacity-0">More info <i class="bi bi-link-45deg"></i></a>
              </div>
            </div>
            <!-- Small Box 4 - Near Deadline -->
            <div class="col-lg-3 col-6">
              <div class="small-box text-bg-danger">
                <div class="inner"><h3><?php echo $nearCount; ?></h3><p>Deadline (7 hari)</p></div>
                <i class="small-box-icon bi bi-calendar-x"></i>
                <a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0">Lihat tugas <i class="bi bi-link-45deg"></i></a>
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-12 mb-3">
              <div class="card">
                <div class="card-header border-0">
                  <h3 class="card-title"><?php echo ($roleName === 'user') ? 'Progres Pekerjaan Saya' : 'Progres Pekerjaan Pegawai'; ?></h3>
                </div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-striped table-valign-middle">
                    <thead>
                      <tr>
                        <th>Pegawai</th>
                        <th>Total Tugas</th>
                        <th>Sedang Berjalan</th>
                        <th>Selesai</th>
                        <th>Progres</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($userProgress)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data progres.</td></tr>
                      <?php else: ?>
                        <?php foreach ($userProgress as $up): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($up['nama_emp']); ?><br><small class="text-muted"><?php echo htmlspecialchars($up['npp']); ?></small></td>
                            <td><?php echo $up['total']; ?></td>
                            <td><span class="badge text-bg-primary"><?php echo $up['berjalan']; ?></span></td>
                            <td><span class="badge text-bg-success"><?php echo $up['selesai']; ?></span></td>
                            <td style="width: 200px;">
                              <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1" style="height: 8px;">
                                  <div class="progress-bar bg-success" style="width: <?php echo $up['persen']; ?>%"></div>
                                </div>
                                <span class="ms-2 fw-bold"><?php echo $up['persen']; ?>%</span>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
                <?php if ($totalUserProgress > $itemsPerPage): ?>
                  <div class="card-footer clearfix">
                    <?php echo render_pagination($totalUserProgress, $itemsPerPage, $currentPage, site_url('index.php'), $_GET); ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
