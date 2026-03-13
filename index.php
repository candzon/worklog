<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

ensure_session_started();
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
    $assignedFilterSql = "AND ditugaskan = ?";
    $assignedParams[] = $currentNpp;
  }

  // Count open tasks
  $openCount = 0;
  $sql = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE status != 'done' " . $assignedFilterSql;
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
  $sql = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE status = 'done' " . $assignedFilterSql;
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
  $sql = "SELECT id, judul, tgl_mulai, tgl_selesai, status, ditugaskan FROM pekerjaan WHERE 1 " . $assignedFilterSql . " ORDER BY created_at DESC LIMIT 5";
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
  $sql = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE status != 'done' AND tgl_selesai IS NOT NULL AND tgl_selesai BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) " . $assignedFilterSql;
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
?>

<main class="app-main">
  <!--begin::App Content Header-->
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h3 class="mb-0">Dashboard</h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <!--end::App Content Header-->

  <!--begin::App Content-->
  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <!--begin::Small Box 1 - Open Tasks-->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-primary">
            <div class="inner">
              <h3><?php echo htmlspecialchars($openCount); ?></h3>
              <p>Task Open</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"></path>
            </svg>
            <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>
        <!--end::Small Box 1-->

        <!--begin::Small Box 2 - Done Tasks-->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-success">
            <div class="inner">
              <h3><?php echo htmlspecialchars($doneCount); ?></h3>
              <p>Task Done</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
            </svg>
            <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>
        <!--end::Small Box 2-->

        <!--begin::Small Box 3 - Recent Tasks-->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-warning">
            <div class="inner">
              <h3><?php echo count($recent); ?></h3>
              <p>Task Terbaru</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z"></path>
            </svg>
            <a href="#" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>
        <!--end::Small Box 3-->

        <!--begin::Small Box 4 - Near Deadline-->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-danger">
            <div class="inner">
              <h3><?php echo htmlspecialchars($nearCount); ?></h3>
              <p>Mendekati Deadline (7 hari)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path clip-rule="evenodd" fill-rule="evenodd" d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z"></path>
              <path clip-rule="evenodd" fill-rule="evenodd" d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z"></path>
            </svg>
            <a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">Lihat tugas <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>
        <!--end::Small Box 4-->
      </div>
    </div>
  </div>
  <!--end::App Content-->
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
