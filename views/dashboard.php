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
          
          <?php if (isset($revisiAlertCount) && $revisiAlertCount > 0): ?>
          <!-- Alert Revisi Dashboard -->
          <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4" style="border-radius: 12px;" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div>
                <strong>Perhatian!</strong>
                <?php if ($isManager): ?>
                  Ada <strong><?php echo $revisiAlertCount; ?> item tugas</strong> (dalam <strong><?php echo $revisiAgendaCount ?? $revisiAlertCount; ?> agenda kalender</strong>) yang masih menunggu revisi dari pegawai.
                <?php else: ?>
                  Anda memiliki <strong><?php echo $revisiAlertCount; ?> item tugas</strong> yang perlu direvisi/diperbaiki.
                <?php endif; ?>
                <a href="<?php echo site_url('daftar_pekerjaan.php'); ?>" class="alert-link text-decoration-underline ms-1">Cek Kalender Pekerjaan</a>.
            </div>
          </div>
          <?php endif; ?>

          <!-- Filter Periode -->
          <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body py-3">
              <form id="formFilterDashboard" class="row g-3 align-items-center">
                <div class="col-auto"><label class="fw-bold text-muted small text-uppercase">Filter Periode :</label></div>
                <div class="col-md-2">
                  <select name="bulan" id="filterBulan" class="form-select form-select-sm">
                    <option value="semua_bulan" <?php echo ($selectedMonth == 'semua_bulan') ? 'selected' : ''; ?>>Semua Bulan</option>
                    <?php 
                    $months = [
                      '01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni',
                      '07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'
                    ];
                    foreach($months as $mVal => $mName): 
                    ?>
                      <option value="<?php echo $mVal; ?>" <?php echo ($selectedMonth == $mVal) ? 'selected' : ''; ?>><?php echo $mName; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <select name="tahun" id="filterTahun" class="form-select form-select-sm">
                    <?php 
                    $startYear = date('Y') - 2;
                    for($y = $startYear; $y <= date('Y') + 1; $y++): 
                    ?>
                      <option value="<?php echo $y; ?>" <?php echo ($selectedYear == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <select name="status_tugas" id="filterStatus" class="form-select form-select-sm">
                    <option value="semua" <?php echo ($selectedStatus == 'semua') ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="open" <?php echo ($selectedStatus == 'open') ? 'selected' : ''; ?>>Belum Selesai</option>
                    <option value="revisi" <?php echo ($selectedStatus == 'revisi') ? 'selected' : ''; ?>>Revisi</option>
                    <option value="done" <?php echo ($selectedStatus == 'done') ? 'selected' : ''; ?>>Selesai</option>
                  </select>
                </div>
                <div class="col-auto">
                  <button type="submit" class="btn btn-sm btn-primary px-3"><i class="bi bi-filter me-1"></i> Terapkan</button>
                </div>
              </form>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-12 mb-3">
              <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header border-0 bg-transparent py-3">
                  <h3 class="card-title fw-bold">
                    <?php echo ($isManager) ? 'Progres Pekerjaan Pegawai' : 'Progres Pekerjaan Saya'; ?>
                    <span id="labelPeriode" class="badge text-bg-light border ms-2 fw-normal" style="font-size: 0.6em;">
                      <?php 
                        if ($selectedMonth === 'semua_bulan') {
                          echo 'Semua Bulan - ' . $selectedYear;
                        } else {
                          echo $months[$selectedMonth] . ' ' . $selectedYear;
                        }
                      ?>
                    </span>
                  </h3>
                </div>
                <div class="card-body table-responsive p-0">
                  <table class="table table-striped table-valign-middle">
                    <thead><tr><th>Pegawai</th><th class="text-center">Total</th><th class="text-center">Open</th><th class="text-center">Revisi</th><th class="text-center">Selesai</th><th>Progres Pencapaian</th></tr></thead>
                    <tbody id="dashboardTableBody">
                      <?php if (empty($userProgress)): ?><tr><td colspan="6" class="text-center text-muted py-5">Tidak ada data untuk periode ini.</td></tr>
                      <?php else: foreach ($userProgress as $up): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($up['nama_emp']); ?><br><small class="text-muted"><?php echo htmlspecialchars($up['npp']); ?></small></td>
                          <td class="text-center fw-bold"><?php echo $up['total']; ?></td>
                          <td class="text-center">
                            <a href="javascript:void(0)" class="badge text-bg-primary btn-detail-task py-2 px-3" data-npp="<?php echo $up['npp']; ?>" data-status="open" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>" style="font-size: 14px;">
                                <?php echo $up['open']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                            </a>
                          </td>
                          <td class="text-center">
                            <a href="javascript:void(0)" class="badge text-bg-warning btn-detail-task py-2 px-3" data-npp="<?php echo $up['npp']; ?>" data-status="revisi" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>" style="font-size: 14px;">
                                <?php echo $up['revisi']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                            </a>
                          </td>
                          <td class="text-center">
                            <a href="javascript:void(0)" class="badge text-bg-success btn-detail-task py-2 px-3" data-npp="<?php echo $up['npp']; ?>" data-status="done" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>" style="font-size: 14px;">
                                <?php echo $up['selesai']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                            </a>
                          </td>
                          <td style="width: 25%; min-width: 150px;"><div class="d-flex align-items-center"><div class="progress flex-grow-1" style="height: 0.625rem; border-radius: 0.3125rem;"><div class="progress-bar bg-success" style="width: <?php echo $up['persen']; ?>%"></div></div><span class="ms-2 fw-bold text-success"><?php echo $up['persen']; ?>%</span></div></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
                <div id="dashboardPagination" class="card-footer bg-transparent border-0 py-3">
                  <?php if ($totalUserProgress > $itemsPerPage): ?>
                    <?php echo render_pagination($totalUserProgress, $itemsPerPage, $currentPage, site_url('index.php'), $_GET); ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalTaskDetail" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
        <div class="modal-header border-0 pb-0 px-4 pt-4">
          <div>
            <h5 class="modal-title fw-800 mb-1" style="color: #1e3a8a; font-size: 1.25rem;">Rincian Tugas</h5>
            <p class="text-muted small mb-0">
              <span id="dt_emp_name" class="fw-bold text-primary"></span> • <span id="dt_status_text"></span>
            </p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          
          <?php if (isset($revisiAlertCount) && $revisiAlertCount > 0): ?>
          <!-- Alert Revisi Dalam Modal (Hanya Dirender Jika Session Punya Revisi) -->
          <div id="revisiAlertInModal" class="alert alert-warning border-0 shadow-sm d-none mb-3" style="border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-circle-fill fs-4 me-3 text-warning"></i>
                <div>
                  <h6 class="fw-bold mb-0">Tugas Perlu Perbaikan</h6>
                  <small class="text-muted">Silakan lakukan revisi melalui halaman kalender pekerjaan.</small>
                </div>
              </div>
              <a href="<?php echo site_url('daftar_pekerjaan.php'); ?>" class="btn btn-sm btn-warning fw-bold rounded-pill px-3">
                <i class="bi bi-calendar-event me-1"></i> Buka Kalender
              </a>
            </div>
          </div>
          <?php endif; ?>

          <div class="table-responsive" style="border-radius: 16px;">
            <table class="table table-hover align-middle mb-0 custom-task-table">
              <thead>
                <tr>
                  <th class="ps-4 py-3">Tugas & Deskripsi</th>
                  <th class="text-center py-3">Kategori</th>
                  <th class="py-3">Jadwal (Target)</th>
                  <th class="py-3" id="dt_th_upload">Tanggal Upload</th>
                  <th class="text-center py-3" id="dt_th_action">Dokumen</th>
                </tr>
              </thead>
              <tbody id="dt_list_body"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center">
          <div id="dt_pagination"></div>
          <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Document Viewer Modal -->
  <div class="modal fade" id="docViewerModal" aria-hidden="true" style="z-index: 2000;">
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content border-0 shadow-none" style="background: #1a1a1a;">
        <div class="modal-header border-0 py-3 px-4" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
          <div class="d-flex align-items-center">
            <div class="bg-primary rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-file-earmark-text text-white fs-5"></i>
            </div>
            <div>
                <h6 class="modal-title text-white fw-bold mb-0" id="docViewerTitle">Pratinjau Dokumen</h6>
                <small class="text-white-50" style="font-size: 10px; letter-spacing: 1px; text-transform: uppercase;">Worklog Document Viewer</small>
            </div>
          </div>
          <div class="d-flex gap-3">
            <a href="#" id="btnDownloadActual" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
              <i class="bi bi-download me-2"></i> Download
            </a>
            <button type="button" class="btn btn-outline-light border-0 rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="modal" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
                <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
        <div class="modal-body p-0 d-flex justify-content-center align-items-center position-relative" style="background: #262626;">
          <div id="docLoader" class="text-white text-center position-absolute" style="z-index: 5;">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
            <div class="mt-3 fw-semibold opacity-75">Menyiapkan Dokumen...</div>
          </div>
          <iframe id="docViewerIframe" src="" width="100%" height="100%" style="border:none; background: #fff;" onload="document.getElementById('docLoader').classList.add('d-none')"></iframe>
        </div>
      </div>
    </div>
  </div>
</main>

<style>
  /* Fluid Layout & Flexible Images */
  img { max-width: 100%; height: auto; }
  .app-main { width: 100%; overflow-x: hidden; }
  .container-fluid { width: 100%; padding-right: 1rem; padding-left: 1rem; }
  
  .fw-800 { font-weight: 800; }
  .custom-task-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-top: none;
    border-bottom: 2px solid #f1f5f9;
  }
  .task-row { transition: all 0.2s; }
  .task-row:hover { background-color: #f8faff !important; }
  .task-title { color: #1e293b; font-size: 14px; margin-bottom: 2px; }
  .task-desc { font-size: 12px; color: #64748b; line-height: 1.5; }
  .badge-category { 
    font-size: 10px; font-weight: 700; padding: 5px 10px; border-radius: 8px; 
    letter-spacing: 0.5px; background: #f1f5f9; color: #475569;
  }
  /* Period Specific Colors - VIBRANT */
  .badge-harian { background: #dcfce7 !important; color: #15803d !important; border: 1px solid #86efac; }
  .badge-mingguan { background: #fef9c3 !important; color: #a16207 !important; border: 1px solid #fde047; }
  .badge-bulanan { background: #dbeafe !important; color: #1d4ed8 !important; border: 1px solid #93c5fd; }
  .badge-triwulan { background: #ffedd5 !important; color: #c2410c !important; border: 1px solid #fdba74; }
  .badge-manual { background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #cbd5e1; }

  .period-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
  }
  .dot-harian { background-color: #22c55e; box-shadow: 0 0 8px rgba(34, 197, 94, 0.4); }
  .dot-mingguan { background-color: #eab308; box-shadow: 0 0 8px rgba(234, 179, 8, 0.4); }
  .dot-bulanan { background-color: #3b82f6; box-shadow: 0 0 8px rgba(59, 130, 246, 0.4); }
  .dot-triwulan { background-color: #f97316; box-shadow: 0 0 8px rgba(249, 115, 22, 0.4); }
  .dot-manual { background-color: #94a3b8; }

  .upload-timestamp { 
    display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; 
    background: #ecfdf5; color: #059669; border-radius: 6px; font-size: 10px; font-weight: 600;
  }
  .btn-view-doc {
    font-size: 12px; font-weight: 700; border-radius: 10px; padding: 6px 14px;
    transition: all 0.3s; border: 1.5px solid #e2e8f0; color: #475569; background: white;
  }
  .btn-view-doc:hover { background: #3b82f6; color: white; border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); }
  
  .empty-state { padding: 60px 0; text-align: center; }
  .empty-state i { font-size: 3rem; color: #e2e8f0; margin-bottom: 15px; display: block; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ---------------------------------------------------------
    // 1. LOGIKA DASHBOARD AJAX (FILTER & PAGINATION)
    // ---------------------------------------------------------
    const formFilter = document.getElementById('formFilterDashboard');
    const tableBody = document.getElementById('dashboardTableBody');
    const labelPeriode = document.getElementById('labelPeriode');
    const paginationContainer = document.getElementById('dashboardPagination');

    const monthsMap = {
        '01':'Januari','02':'Februari','03':'Maret','04':'April','05':'Mei','06':'Juni',
        '07':'Juli','08':'Agustus','09':'September','10':'Oktober','11':'November','12':'Desember'
    };

    function loadDashboardData(page = 1) {
        const formData = new FormData(formFilter);
        const params = new URLSearchParams(formData);
        params.append('page', page);

        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><br><small class="text-muted mt-2 d-block">Memperbarui data dashboard...</small></td></tr>';

        fetch(`api/get_dashboard_data.php?${params.toString()}`)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    renderDashboardTable(res.data);
                    renderDashboardPagination(res.pagination);
                    updatePeriodeLabel(res.debug.filter);
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-5">Gagal memuat data: ${res.message || 'Error tidak diketahui'}</td></tr>`;
                }
            })
            .catch(err => {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Terjadi kesalahan koneksi sistem.</td></tr>';
            });
    }

    function renderDashboardTable(data) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Tidak ada data untuk periode ini.</td></tr>';
            return;
        }

        let html = '';
        data.forEach(up => {
            html += `
                <tr>
                    <td>${escapeHtml(up.nama_emp)}<br><small class="text-muted">${escapeHtml(up.npp)}</small></td>
                    <td class="text-center fw-bold">${up.total}</td>
                    <td class="text-center">
                        <a href="javascript:void(0)" class="badge text-bg-primary btn-detail-task py-2 px-3" data-npp="${up.npp}" data-status="open" data-name="${escapeHtml(up.nama_emp)}" style="font-size: 14px;">
                            ${up.open} <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0)" class="badge text-bg-warning btn-detail-task py-2 px-3" data-npp="${up.npp}" data-status="revisi" data-name="${escapeHtml(up.nama_emp)}" style="font-size: 14px;">
                            ${up.revisi} <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                        </a>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0)" class="badge text-bg-success btn-detail-task py-2 px-3" data-npp="${up.npp}" data-status="done" data-name="${escapeHtml(up.nama_emp)}" style="font-size: 14px;">
                            ${up.selesai} <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                        </a>
                    </td>
                    <td style="width: 25%; min-width: 150px;">
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1" style="height: 0.625rem; border-radius: 0.3125rem;">
                                <div class="progress-bar bg-success" style="width: ${up.persen}%"></div>
                            </div>
                            <span class="ms-2 fw-bold text-success">${up.persen}%</span>
                        </div>
                    </td>
                </tr>`;
        });
        tableBody.innerHTML = html;
        attachDetailListeners();
    }

    function renderDashboardPagination(p) {
        if (!p || p.totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        let html = '<nav><ul class="pagination pagination-sm mb-0">';
        html += `<li class="page-item ${p.page <= 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="window.changeDashboardPage(${p.page - 1})">«</a></li>`;
        for (let i = 1; i <= p.totalPages; i++) {
            html += `<li class="page-item ${i === p.page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="window.changeDashboardPage(${i})">${i}</a></li>`;
        }
        html += `<li class="page-item ${p.page >= p.totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="window.changeDashboardPage(${p.page + 1})">»</a></li>`;
        html += '</ul></nav>';
        paginationContainer.innerHTML = html;
    }

    function updatePeriodeLabel(filter) {
        if (filter.bulan === 'semua_bulan') {
            labelPeriode.textContent = `Semua Bulan - ${filter.tahun}`;
        } else {
            labelPeriode.textContent = `${monthsMap[filter.bulan]} ${filter.tahun}`;
        }
    }

    window.changeDashboardPage = function(page) {
        loadDashboardData(page);
    };

    formFilter.addEventListener('submit', function(e) {
        e.preventDefault();
        loadDashboardData(1);
    });

    // ---------------------------------------------------------
    // 2. LOGIKA MODAL DETAIL (Eksisting Terintegrasi)
    // ---------------------------------------------------------
    const modal = new bootstrap.Modal(document.getElementById('modalTaskDetail'), { focus: false });
    const listBody = document.getElementById('dt_list_body');
    const nameEl = document.getElementById('dt_emp_name');
    const statusEl = document.getElementById('dt_status_text');
    const detailPaginationEl = document.getElementById('dt_pagination');
    
    let currentModalData = { npp: null, status: null, bulan: null, tahun: null, page: 1 };

    function attachDetailListeners() {
        document.querySelectorAll('.btn-detail-task').forEach(btn => {
            btn.addEventListener('click', function() {
                const npp = this.dataset.npp;
                const status = this.dataset.status;
                const empName = this.dataset.name;
                
                currentModalData.npp = npp;
                currentModalData.status = status;
                currentModalData.bulan = document.getElementById('filterBulan').value;
                currentModalData.tahun = document.getElementById('filterTahun').value;
                currentModalData.page = 1;

                const showDocs = (status === 'done' || status === 'revisi');
                document.getElementById('dt_th_upload').style.display = showDocs ? 'table-cell' : 'none';
                document.getElementById('dt_th_action').style.display = showDocs ? 'table-cell' : 'none';

                nameEl.textContent = empName;
                
                let statusText = 'Tugas Belum Selesai';
                if (status === 'done') statusText = 'Tugas Selesai';
                else if (status === 'revisi') statusText = 'Tugas Butuh Perbaikan (Revisi)';
                statusEl.textContent = statusText;

                const cols = showDocs ? 5 : 3;
                listBody.innerHTML = `<tr><td colspan="${cols}" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><br><small class="text-muted mt-2 d-block">Memuat data rincian...</small></td></tr>`;
                modal.show();

                loadTaskPage(1);
            });
        });
    }

    // Modal detail rendering functions
    function renderTable(tasks) {
      const isDone = currentModalData.status === 'done';
      const isRevisi = currentModalData.status === 'revisi';
      const showDocs = isDone || isRevisi;
      
      document.getElementById('dt_th_upload').style.display = showDocs ? 'table-cell' : 'none';
      document.getElementById('dt_th_action').style.display = showDocs ? 'table-cell' : 'none';

      // Toggle Revisi Alert di dalam modal (Hanya untuk user, bukan manager)
      const revAlert = document.getElementById('revisiAlertInModal');
      const isManager = <?php echo $isManager ? 'true' : 'false'; ?>;
      if (revAlert) {
          if (isRevisi && !isManager) {
              revAlert.classList.remove('d-none');
          } else {
              revAlert.classList.add('d-none');
          }
      }

      const cols = showDocs ? 5 : 3;

      if (!tasks || tasks.length === 0) {
        listBody.innerHTML = `<tr><td colspan="${cols}"><div class="empty-state"><i class="bi bi-clipboard-x"></i><h6 class="fw-bold text-secondary">Tidak Ada Data</h6><p class="small text-muted mb-0">Belum ada rincian tugas untuk filter ini.</p></div></td></tr>`;
        return;
      }
        
      let html = '';
      tasks.forEach(t => {
        const cleanTitle = (t.judul || '').replace(/^\[.*?\]\s*/, '');
        const dateText = t.tgl_mulai;
        let uploadInfo = t.updated_at ? `<span class="upload-timestamp"><i class="bi bi-clock-history"></i> ${t.updated_at}</span>` : '<em class="text-muted small">Belum realisasi</em>';
        let actionBtn = t.lampiran ? `<button type="button" class="btn btn-view-doc me-1" onclick="viewDocument('${t.lampiran}', '${escapeJs(cleanTitle)}')"><i class="bi bi-eye me-1"></i> Lihat Bukti</button>` : '<span class="text-muted small"><i class="bi bi-info-circle me-1"></i> Tanpa Lampiran</span>';
        
        if (t.lampiran && isDone && (<?php echo $isManager ? 'true' : 'false'; ?>)) {
          actionBtn += `<button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold" onclick="requestRevision('${t.id}')"><i class="bi bi-exclamation-triangle-fill me-1"></i> Revisi</button>`;
        }

        let revisionInfo = t.catatan_revisi ? `<div class="mt-2 p-2 border-start border-4 border-warning rounded" style="font-size: 11px; background-color: #fffbeb;"><strong class="text-warning">CATATAN REVISI:</strong><br><span class="text-dark">${t.catatan_revisi}</span></div>` : '';

        const p = (t.periode || '').toLowerCase();
        let pClass = 'badge-manual';
        if (p === 'harian') pClass = 'badge-harian';
        else if (p === 'mingguan') pClass = 'badge-mingguan';
        else if (p === 'bulanan') pClass = 'badge-bulanan';
        else if (p === 'triwulan') pClass = 'badge-triwulan';

        html += `<tr class="task-row">
          <td class="ps-4 py-3" style="max-width: 30rem;"><div class="task-title fw-bold">${cleanTitle}</div></td>
          <td class="text-center"><span class="badge-category ${pClass}">${(t.periode || 'MANUAL').toUpperCase()}</span></td>
          <td class="py-3"><div class="d-flex align-items-center gap-2"><i class="bi bi-calendar3 text-muted"></i><span class="fw-semibold text-dark">${dateText}</span></div></td>
          ${showDocs ? `<td class="py-3">${uploadInfo}</td><td class="text-center">${actionBtn}</td>` : ''}
        </tr>`;
      });
      listBody.innerHTML = html;
    }

    function renderPagination(p) {
        if (!p || p.totalPages <= 1) { detailPaginationEl.innerHTML = ''; return; }
        let html = '<nav><ul class="pagination pagination-sm mb-0">';
        html += `<li class="page-item ${p.page <= 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadTaskPage(${p.page - 1})">«</a></li>`;
        for (let i = 1; i <= p.totalPages; i++) {
            html += `<li class="page-item ${i === p.page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadTaskPage(${i})">${i}</a></li>`;
        }
        html += `<li class="page-item ${p.page >= p.totalPages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadTaskPage(${p.page + 1})">»</a></li>`;
        html += '</ul></nav>';
        detailPaginationEl.innerHTML = html;
    }

    window.loadTaskPage = function(page) {
        currentModalData.page = page;
        const url = `api/get_ongoing_tasks.php?npp=${currentModalData.npp}&status=${currentModalData.status}&bulan=${currentModalData.bulan}&tahun=${currentModalData.tahun}&page=${page}`;
        fetch(url).then(r => r.json()).then(res => {
            renderTable(res.data);
            renderPagination(res.pagination);
        }).catch(err => {
            const cols = ['done', 'revisi'].includes(currentModalData.status) ? 5 : 3;
            listBody.innerHTML = `<tr><td colspan="${cols}" class="text-center py-5 text-danger">Gagal memuat data rincian.</td></tr>`;
        });
    };

    window.viewDocument = function(fileName, title) {
        const viewerModal = new bootstrap.Modal(document.getElementById('docViewerModal'));
        const iframe = document.getElementById('docViewerIframe');
        const titleEl = document.getElementById('docViewerTitle');
        const downloadBtn = document.getElementById('btnDownloadActual');
        const loader = document.getElementById('docLoader');
        const fileUrl = `<?php echo site_url('uploads/'); ?>${fileName}`;
        titleEl.textContent = `Pratinjau: ${title}`;
        downloadBtn.href = fileUrl;
        loader.classList.remove('d-none');
        iframe.src = '';
        const ext = fileName.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'].includes(ext)) iframe.src = fileUrl;
        else if (['xlsx', 'xls', 'doc', 'docx', 'ppt', 'pptx'].includes(ext)) iframe.src = `https://docs.google.com/viewer?url=${encodeURIComponent(fileUrl)}&embedded=true`;
        else { loader.classList.add('d-none'); iframe.srcdoc = '<h3>Pratinjau Tidak Tersedia</h3>'; }
        viewerModal.show();
    };

    window.requestRevision = function(taskId) {
        Swal.fire({
            title: 'Minta Revisi Tugas',
            input: 'textarea',
            target: document.getElementById('modalTaskDetail'),
            showCancelButton: true,
            confirmButtonText: 'Kirim',
            showLoaderOnConfirm: true,
            preConfirm: (catatan) => {
                if (!catatan) { Swal.showValidationMessage('Wajib diisi!'); return false; }
                const fd = new FormData(); fd.append('id', taskId); fd.append('catatan', catatan);
                return fetch('api/request_revision.php', { method: 'POST', body: fd }).then(r => r.json());
            }
        }).then((res) => { if (res.isConfirmed && res.value.success) { Swal.fire('Berhasil', '', 'success'); loadTaskPage(currentModalData.page); loadDashboardData(1); } });
    };

    function escapeHtml(t) { return t.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
    function escapeJs(s) { return s.replace(/'/g, "\\'").replace(/"/g, '\\"'); }

    // Initial listener attach
    attachDetailListeners();
});
</script>