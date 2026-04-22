<!-- views/dashboard.php -->
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
            <div class="col-lg-3 col-6"><div class="small-box text-bg-primary"><div class="inner"><h3><?php echo $counts['open']; ?></h3><p>Task Open</p></div><i class="small-box-icon bi bi-list-task"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-light">More info <i class="bi bi-link-45deg"></i></a></div></div>
            <div class="col-lg-3 col-6"><div class="small-box text-bg-success"><div class="inner"><h3><?php echo $counts['done']; ?></h3><p>Task Done</p></div><i class="small-box-icon bi bi-check2-all"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-light">More info <i class="bi bi-link-45deg"></i></a></div></div>
            <div class="col-lg-3 col-6"><div class="small-box text-bg-warning"><div class="inner"><h3><?php echo count($recent); ?></h3><p>Task Terbaru</p></div><i class="small-box-icon bi bi-clock-history"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-dark">More info <i class="bi bi-link-45deg"></i></a></div></div>
            <div class="col-lg-3 col-6"><div class="small-box text-bg-danger"><div class="inner"><h3><?php echo $counts['near']; ?></h3><p>Deadline (7 hari)</p></div><i class="small-box-icon bi bi-calendar-x"></i><a href="daftar_pekerjaan.php" class="small-box-footer link-light">Lihat tugas <i class="bi bi-link-45deg"></i></a></div></div>
          </div>

          <div class="row mt-3">
            <div class="col-12 mb-3">
              <div class="card">
                <div class="card-header border-0"><h3 class="card-title"><?php echo ($isManager) ? 'Progres Pekerjaan Pegawai' : 'Progres Pekerjaan Saya'; ?></h3></div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-striped table-valign-middle">
                    <thead><tr><th>Pegawai</th><th>Total Tugas</th><th>Open</th><th>Selesai</th><th>Progres</th></tr></thead>
                    <tbody>
                      <?php if (empty($userProgress)): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>
                      <?php else: foreach ($userProgress as $up): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($up['nama_emp']); ?><br><small class="text-muted"><?php echo htmlspecialchars($up['npp']); ?></small></td>
                          <td><?php echo $up['total']; ?></td>
                          <td>
                            <a href="javascript:void(0)" class="badge text-bg-primary btn-detail-task" data-npp="<?php echo $up['npp']; ?>" data-status="open" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>">
                                <?php echo $up['open']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                            </a>
                          </td>
                          <td>
                            <a href="javascript:void(0)" class="badge text-bg-success btn-detail-task" data-npp="<?php echo $up['npp']; ?>" data-status="done" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>">
                                <?php echo $up['selesai']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                            </a>
                          </td>
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

  <div class="modal fade" id="modalTaskDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Rincian Tugas: <span id="dt_emp_name" class="fw-bold"></span> (<span id="dt_status_text"></span>)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light"><tr><th>Judul Tugas & Deskripsi</th><th>Tipe</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
              <tbody id="dt_list_body"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalTaskDetail'));
    const listBody = document.getElementById('dt_list_body');
    const nameEl = document.getElementById('dt_emp_name');
    const statusEl = document.getElementById('dt_status_text');

    document.querySelectorAll('.btn-detail-task').forEach(btn => {
        btn.addEventListener('click', function() {
            const npp = this.dataset.npp;
            const status = this.dataset.status;
            const empName = this.dataset.name;
            
            nameEl.textContent = empName;
            statusEl.textContent = (status === 'open' ? 'Tugas Open' : 'Tugas Selesai');
            listBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Memuat data...</td></tr>';
            modal.show();

            fetch(`api/get_ongoing_tasks.php?npp=${npp}&status=${status}`)
                .then(r => r.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        listBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data untuk ditampilkan.</td></tr>';
                        return;
                    }
                    let html = '';
                    data.forEach(t => {
                        const dateText = t.tgl_mulai + (t.tgl_selesai && t.tgl_selesai !== t.tgl_mulai ? ` s/d ${t.tgl_selesai}` : '');
                        const calendarUrl = `daftar_pekerjaan.php?filter_npp=${npp}&status=${status}`;
                        
                        html += `<tr>
                            <td><div class="fw-bold">${t.judul}</div><div class="small text-muted text-wrap" style="max-width:400px;">${t.deskripsi || '-'}</div></td>
                            <td><span class="badge text-bg-light border">${t.periode || 'Manual'}</span></td>
                            <td><small>${dateText}</small></td>
                            <td><span class="badge ${status === 'done' ? 'text-bg-success' : 'text-bg-primary'}">${t.status_label}</span></td>
                            <td><a href="${calendarUrl}" class="btn btn-xs btn-outline-primary"><i class="bi bi-calendar3"></i> Buka Kalender</a></td>
                        </tr>`;
                    });
                    listBody.innerHTML = html;
                });
        });
    });
});
</script>
