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
          <!-- Filter Periode -->
          <div class="card mb-4 shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body py-3">
              <form action="index.php" method="GET" class="row g-3 align-items-center">
                <div class="col-auto"><label class="fw-bold text-muted small text-uppercase">Filter Periode :</label></div>
                <div class="col-md-2">
                  <select name="bulan" class="form-select form-select-sm">
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
                  <select name="tahun" class="form-select form-select-sm">
                    <?php 
                    $startYear = date('Y') - 2;
                    for($y = $startYear; $y <= date('Y') + 1; $y++): 
                    ?>
                      <option value="<?php echo $y; ?>" <?php echo ($selectedYear == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-2">
                  <select name="status_tugas" class="form-select form-select-sm">
                    <option value="semua" <?php echo ($selectedStatus == 'semua') ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="open" <?php echo ($selectedStatus == 'open') ? 'selected' : ''; ?>>Belum Selesai</option>
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
                    <span class="badge text-bg-light border ms-2 fw-normal" style="font-size: 0.6em;">
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
                    <thead><tr><th>Pegawai</th><th class="text-center">Total</th><th class="text-center">Open</th><th class="text-center">Selesai</th><th>Progres Pencapaian</th></tr></thead>
                    <tbody>
                      <?php if (empty($userProgress)): ?><tr><td colspan="5" class="text-center text-muted py-5">Tidak ada data untuk periode ini.</td></tr>
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
                            <a href="javascript:void(0)" class="badge text-bg-success btn-detail-task py-2 px-3" data-npp="<?php echo $up['npp']; ?>" data-status="done" data-name="<?php echo htmlspecialchars($up['nama_emp']); ?>" style="font-size: 14px;">
                                <?php echo $up['selesai']; ?> <i class="bi bi-search ms-1" style="font-size: 0.8em;"></i>
                            </a>
                          </td>
                          <td style="width: 250px;"><div class="d-flex align-items-center"><div class="progress flex-grow-1" style="height: 10px; border-radius: 5px;"><div class="progress-bar bg-success" style="width: <?php echo $up['persen']; ?>%"></div></div><span class="ms-2 fw-bold text-success"><?php echo $up['persen']; ?>%</span></div></td>
                        </tr>
                      <?php endforeach; endif; ?>
                    </tbody>
                  </table>
                </div>
                <?php if ($totalUserProgress > $itemsPerPage): ?><div class="card-footer bg-transparent border-0 py-3"><?php echo render_pagination($totalUserProgress, $itemsPerPage, $currentPage, site_url('index.php'), $_GET); ?></div><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalTaskDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content" style="border-radius: 16px; border: none;">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold">Rincian Tugas: <span id="dt_emp_name" class="text-primary"></span> (<span id="dt_status_text"></span>)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light"><tr><th>Judul Tugas & Deskripsi</th><th>Tipe</th><th>Tanggal</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
              <tbody id="dt_list_body"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer border-top py-3 d-flex justify-content-between align-items-center">
          <div id="dt_pagination" style="flex: 1; text-align: center;"></div>
          <button type="button" class="btn btn-light rounded-pill ms-2" data-bs-dismiss="modal">Tutup</button>
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
    const paginationEl = document.getElementById('dt_pagination');
    
    // State untuk track modal saat ini
    let currentModalData = {
        npp: null,
        status: null,
        bulan: null,
        tahun: null,
        page: 1
    };

    // Render table
    function renderTable(tasks) {
        if (!tasks || tasks.length === 0) {
            listBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data rincian untuk ditampilkan.</td></tr>';
            return;
        }
        
        let html = '';
        tasks.forEach(t => {
            const dateText = t.tgl_mulai + (t.tgl_selesai && t.tgl_selesai !== t.tgl_mulai ? ` s/d ${t.tgl_selesai}` : '');
            
            let actionBtn = '<span class="text-muted small">Tidak ada lampiran</span>';
            if (t.lampiran) {
              actionBtn = `<a href="uploads/${t.lampiran}" target="_blank" class="btn btn-xs btn-outline-info rounded-pill px-3"><i class="bi bi-file-earmark-text me-1"></i> Lihat Lampiran</a>`;
            }

            html += `<tr>
                <td class="ps-4">
                  <div class="fw-bold">${t.judul}</div>
                  <div class="small text-muted text-wrap" style="max-width:450px;">${t.deskripsi || '-'}</div>
                </td>
                <td><span class="badge text-bg-light border text-uppercase">${t.periode || 'Manual'}</span></td>
                <td><small class="text-muted">${dateText}</small></td>
                <td><span class="badge ${currentModalData.status === 'done' ? 'text-bg-success' : 'text-bg-primary'}">${t.status_label}</span></td>
                <td class="text-center">${actionBtn}</td>
            </tr>`;
        });
        listBody.innerHTML = html;
    }
    
    // Render pagination
    function renderPagination(pagination) {
        if (!pagination || pagination.totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }
        
        let html = '<nav aria-label="Task pagination" class="d-flex justify-content-center"><ul class="pagination pagination-sm mb-0">';
        
        // Previous
        if (pagination.page > 1) {
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadTaskPage(${pagination.page - 1})" title="Halaman sebelumnya">«</a></li>`;
        } else {
            html += '<li class="page-item disabled"><a class="page-link" href="javascript:void(0)" title="Halaman sebelumnya">«</a></li>';
        }
        
        // Page numbers
        const start = Math.max(1, pagination.page - 2);
        const end = Math.min(pagination.totalPages, start + 4);
        for (let i = start; i <= end; i++) {
            if (i === pagination.page) {
                html += `<li class="page-item active"><a class="page-link" href="javascript:void(0)">${i}</a></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadTaskPage(${i})">${i}</a></li>`;
            }
        }
        
        // Next
        if (pagination.page < pagination.totalPages) {
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="loadTaskPage(${pagination.page + 1})" title="Halaman berikutnya">»</a></li>`;
        } else {
            html += '<li class="page-item disabled"><a class="page-link" href="javascript:void(0)" title="Halaman berikutnya">»</a></li>';
        }
        
        html += '</ul></nav>';
        paginationEl.innerHTML = html;
    }
    
    // Load specific page
    window.loadTaskPage = function(page) {
        currentModalData.page = page;
        listBody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><br><small class="text-muted mt-2 d-block">Memuat data...</small></td></tr>';
        
        const url = `api/get_ongoing_tasks.php?npp=${currentModalData.npp}&status=${currentModalData.status}&bulan=${currentModalData.bulan}&tahun=${currentModalData.tahun}&page=${page}`;
        
        console.log('Loading tasks:', { url, data: currentModalData, page });
        
        fetch(url)
            .then(r => {
                console.log('Response status:', r.status, 'headers:', r.headers);
                if (!r.ok) {
                    throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                }
                return r.text();
            })
            .then(text => {
                console.log('Raw response text:', text);
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('JSON parse failed:', e);
                    throw new Error('Invalid JSON response: ' + e.message);
                }
            })
            .then(response => {
                console.log('Parsed response:', response);
                if (response && response.data !== undefined && response.pagination !== undefined) {
                    renderTable(response.data);
                    renderPagination(response.pagination);
                } else {
                    console.error('Invalid response structure. Expected data and pagination properties');
                    listBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data rincian untuk ditampilkan.</td></tr>';
                }
            })
            .catch(err => {
                console.error('Full error object:', err);
                console.error('Error message:', err.message);
                console.error('Error stack:', err.stack);
                const errorMsg = err.message || 'Kesalahan tidak diketahui';
                listBody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger"><small><strong>Kesalahan:</strong><br>' + escapeHtml(errorMsg) + '</small></td></tr>';
            });
    };
    
    // Helper untuk escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Button click handler
    document.querySelectorAll('.btn-detail-task').forEach(btn => {
        btn.addEventListener('click', function() {
            const npp = this.dataset.npp;
            const status = this.dataset.status;
            const empName = this.dataset.name;
            
            // Update state
            currentModalData.npp = npp;
            currentModalData.status = status;
            currentModalData.bulan = '<?php echo $selectedMonth; ?>';
            currentModalData.tahun = '<?php echo $selectedYear; ?>';
            currentModalData.page = 1;
            
            nameEl.textContent = empName;
            statusEl.textContent = (status === 'open' ? 'Tugas Belum Selesai' : 'Tugas Selesai');
            listBody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><br><small class="text-muted mt-2 d-block">Memuat data rincian...</small></td></tr>';
            modal.show();

            loadTaskPage(1);
        });
    });
});
</script>
