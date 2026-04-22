<?php
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

ensure_session_started();

$currentNpp = $_SESSION['npp'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;
$roleName = $_SESSION['role_name'] ?? null;

if (!$roleName && !empty($currentNpp) && isset($conn)) {
  $stmtR = $conn->prepare("SELECT r.name FROM employee e JOIN roles r ON e.role_id = r.id WHERE e.npp = ?");
  $stmtR->bind_param('s', $currentNpp);
  $stmtR->execute();
  $resR = $stmtR->get_result();
  if ($rowR = $resR->fetch_assoc()) {
      $roleName = $rowR['name'];
      $_SESSION['role_name'] = $roleName;
  }
  $stmtR->close();
}

$isManager = ($roleId == 1 || $roleId == 2 || strtolower((string)$roleName) === 'manager' || strtolower((string)$roleName) === 'admin');

$openCount = 0; $doneCount = 0; $nearCount = 0; $recent = [];
$currentMonth = date('Y-m');

if (isset($conn)) {
    $sqlDone = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE LOWER(TRIM(status)) IN ('done','selesai','completed')";
    if (!$isManager) { $sqlDone .= " AND assigned_to_npp = '$currentNpp'"; }
    $resDone = $conn->query($sqlDone);
    $doneCount = (int)($resDone->fetch_assoc()['cnt'] ?? 0);

    $sqlOpenPek = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))";
    if (!$isManager) { $sqlOpenPek .= " AND assigned_to_npp = '$currentNpp'"; }
    $resOpenPek = $conn->query($sqlOpenPek);
    $openCount = (int)($resOpenPek->fetch_assoc()['cnt'] ?? 0);

    $sqlOpenMaster = "SELECT COUNT(*) AS cnt FROM master_tugas_detail mtd JOIN master_tugas mt ON mt.id = mtd.master_tugas_id WHERE mt.periode = 'bulanan' AND NOT EXISTS (SELECT 1 FROM pekerjaan p WHERE p.master_tugas_id = mtd.master_tugas_id AND p.assigned_to_npp = mtd.npp AND DATE_FORMAT(p.tgl_mulai, '%Y-%m') = '$currentMonth')";
    if (!$isManager) { $sqlOpenMaster .= " AND mtd.npp = '$currentNpp'"; }
    $resOpenMaster = $conn->query($sqlOpenMaster);
    $openCount += (int)($resOpenMaster->fetch_assoc()['cnt'] ?? 0);

    $sqlRecent = "SELECT id, judul, tgl_mulai, status FROM pekerjaan";
    if (!$isManager) { $sqlRecent .= " WHERE assigned_to_npp = '$currentNpp'"; }
    $sqlRecent .= " ORDER BY created_at DESC LIMIT 5";
    $resRecent = $conn->query($sqlRecent);
    while($r = $resRecent->fetch_assoc()) { $recent[] = $r; }

    $sqlNear = "SELECT COUNT(*) AS cnt FROM pekerjaan WHERE NOT (LOWER(TRIM(status)) IN ('done','selesai','completed')) AND tgl_selesai BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    if (!$isManager) { $sqlNear .= " AND assigned_to_npp = '$currentNpp'"; }
    $resNear = $conn->query($sqlNear);
    $nearCount = (int)($resNear->fetch_assoc()['cnt'] ?? 0);
}

$userProgress = [];
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;
$totalUserProgress = 0;

if (isset($conn)) {
    $sqlBase = "SELECT npp, nama_emp FROM employee WHERE role_id = 3";
    if (!$isManager) { $sqlBase .= " WHERE npp = '$currentNpp'"; }
    $resCount = $conn->query("SELECT COUNT(*) as total FROM ($sqlBase) as t");
    $totalUserProgress = (int)($resCount->fetch_assoc()['total'] ?? 0);
    $sqlProg = "$sqlBase ORDER BY nama_emp ASC LIMIT $itemsPerPage OFFSET $offset";
    $resProg = $conn->query($sqlProg);
    while ($emp = $resProg->fetch_assoc()) {
        $empNpp = $emp['npp'];
        $sDone = $conn->query("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$empNpp' AND LOWER(TRIM(status)) IN ('done','selesai','completed')");
        $doneVal = (int)($sDone->fetch_assoc()['cnt'] ?? 0);
        $sOpenP = $conn->query("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = '$empNpp' AND NOT (LOWER(TRIM(status)) IN ('done','selesai','completed'))");
        $openPVal = (int)($sOpenP->fetch_assoc()['cnt'] ?? 0);
        $sOpenM = $conn->query("SELECT COUNT(*) as cnt FROM master_tugas_detail mtd JOIN master_tugas mt ON mt.id = mtd.master_tugas_id WHERE mtd.npp = '$empNpp' AND mt.periode = 'bulanan' AND NOT EXISTS (SELECT 1 FROM pekerjaan p WHERE p.master_tugas_id = mtd.master_tugas_id AND p.assigned_to_npp = mtd.npp AND DATE_FORMAT(p.tgl_mulai, '%Y-%m') = '$currentMonth')");
        $openMVal = (int)($sOpenM->fetch_assoc()['cnt'] ?? 0);
        $totalTugas = $doneVal + $openPVal + $openMVal;
        $userProgress[] = [
            'npp' => $empNpp, 'nama_emp' => $emp['nama_emp'], 'total' => $totalTugas,
            'selesai' => $doneVal, 'berjalan' => $openPVal + $openMVal,
            'persen' => ($totalTugas > 0) ? round(($doneVal / $totalTugas) * 100) : 0
        ];
    }
}
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6"><h3 class="mb-0">Dashboard</h3></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-end"><li class="breadcrumb-item"><a href="#">Home</a></li><li class="breadcrumb-item active">Dashboard</li></ol></div>
      </div>
      <div class="app-content">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-3 col-6"><div class="small-box text-bg-primary"><div class="inner"><h3><?php echo $openCount; ?></h3><p>Task Open</p></div><i class="small-box-icon bi bi-list-task"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0">More info <i class="bi bi-link-45deg"></i></a></div></div>
            <div class="col-lg-3 col-6"><div class="small-box text-bg-success"><div class="inner"><h3><?php echo $doneCount; ?></h3><p>Task Done</p></div><i class="small-box-icon bi bi-check2-all"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0">More info <i class="bi bi-link-45deg"></i></a></div></div>
            <div class="col-lg-3 col-6"><div class="small-box text-bg-warning"><div class="inner"><h3><?php echo count($recent); ?></h3><p>Task Terbaru</p></div><i class="small-box-icon bi bi-clock-history"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-dark link-underline-opacity-0">More info <i class="bi bi-link-45deg"></i></a></div></div>
            <div class="col-lg-3 col-6"><div class="small-box text-bg-danger"><div class="inner"><h3><?php echo $nearCount; ?></h3><p>Deadline (7 hari)</p></div><i class="small-box-icon bi bi-calendar-x"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-light link-underline-opacity-0">Lihat tugas <i class="bi bi-link-45deg"></i></a></div></div>
          </div>

          <div class="row mt-3">
            <div class="col-12 mb-3">
              <div class="card">
                <div class="card-header border-0"><h3 class="card-title"><?php echo ($isManager) ? 'Progres Pekerjaan Pegawai' : 'Progres Pekerjaan Saya'; ?></h3></div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-striped table-valign-middle">
                    <thead><tr><th>Pegawai</th><th>Total Tugas</th><th>Berjalan</th><th>Selesai</th><th>Progres</th></tr></thead>
                    <tbody>
                      <?php if (empty($userProgress)): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>
                      <?php else: foreach ($userProgress as $up): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($up['nama_emp']); ?><br><small class="text-muted"><?php echo htmlspecialchars($up['npp']); ?></small></td>
                          <td><?php echo $up['total']; ?></td>
                          <td>
                            <?php if ($isManager && $up['berjalan'] > 0): ?>
                              <a href="javascript:void(0)" class="badge text-bg-primary btn-view-ongoing" data-npp="<?php echo $up['npp']; ?>" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>">
                                <?php echo $up['berjalan']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                              </a>
                            <?php else: ?>
                              <span class="badge text-bg-primary"><?php echo $up['berjalan']; ?></span>
                            <?php endif; ?>
                          </td>
                          <td><span class="badge text-bg-success"><?php echo $up['selesai']; ?></span></td>
                          <td style="width: 200px;"><div class="d-flex align-items-center"><div class="progress flex-grow-1" style="height: 8px;"><div class="progress-bar bg-success" style="width: <?php echo $up['persen']; ?>%"></div></div><span class="ms-2 fw-bold"><?php echo $up['persen']; ?>%</span></div></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
                <?php if ($totalUserProgress > $itemsPerPage): ?><div class="card-footer clearfix"><?php echo render_pagination($totalUserProgress, $itemsPerPage, $currentPage, site_url('index.php'), $_GET); ?></div><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Detail Ongoing -->
  <div class="modal fade" id="modalOngoing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tugas Berjalan: <span id="ongoing_emp_name"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light"><tr><th>Judul Tugas</th><th>Periode</th><th>Mulai</th><th>Status</th></tr></thead>
              <tbody id="ongoing_list_body"><tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Memuat data...</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalOngoing'));
    const listBody = document.getElementById('ongoing_list_body');
    const nameEl = document.getElementById('ongoing_emp_name');

    document.querySelectorAll('.btn-view-ongoing').forEach(btn => {
        btn.addEventListener('click', function() {
            const npp = this.dataset.npp;
            nameEl.textContent = this.dataset.name;
            listBody.innerHTML = '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Memuat data...</td></tr>';
            modal.show();

            fetch('api/get_ongoing_tasks.php?npp=' + npp)
                .then(r => r.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        listBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada tugas berjalan.</td></tr>';
                        return;
                    }
                    let html = '';
                    data.forEach(t => {
                        html += `<tr>
                            <td><strong>${t.judul}</strong><br><small class="text-muted">${t.deskripsi || ''}</small></td>
                            <td><span class="badge text-bg-light border text-uppercase" style="font-size: 0.75em;">${t.periode || 'Manual'}</span></td>
                            <td>${t.tgl_mulai || '-'}</td>
                            <td><span class="badge text-bg-info">${t.status}</span></td>
                        </tr>`;
                    });
                    listBody.innerHTML = html;
                }).catch(err => {
                    listBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>';
                });
        });
    });
});
</script>
