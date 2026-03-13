<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// handle form submission
require_app('config/database.php');
require_app('functions/helpers.php');

ensure_session_started();
$npp = $_SESSION['npp'] ?? null;
$nama_emp = $_SESSION['nama_emp'] ?? null;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tglMulai = trim($_POST['tgl_mulai'] ?? '');
    $tglSelesai = trim($_POST['tgl_selesai'] ?? '');
    $ditugaskan = trim($_POST['ditugaskan'] ?? '');

    if ($judul === '')
        $errors[] = 'Judul pekerjaan wajib diisi';


    if (isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, npp, nama_emp, tgl_mulai, tgl_selesai, ditugaskan) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))");
        if ($stmt) {
            $stmt->bind_param('sssssss', $judul, $deskripsi, $npp, $nama_emp, $tglMulai, $tglSelesai, $ditugaskan);
            $stmt->execute();
            $stmt->close();
            if (function_exists('flash_swal'))
                flash_swal('success', 'Tersimpan', 'Pekerjaan berhasil ditambahkan');
            header('Location: ' . site_url('daftar_pekerjaan.php'));
            exit;
        } else {
            $errors[] = 'Gagal menyiapkan penyimpanan.';
        }
    } else {
        $errors[] = 'Koneksi database tidak tersedia.';
    }
}

// fetch recent entries
$rows = [];
if (isset($conn)) {
    $res = $conn->query("SELECT * FROM pekerjaan ORDER BY created_at DESC LIMIT 50");
    if ($res) {
        while ($r = $res->fetch_assoc())
            $rows[] = $r;
        $res->free();
    }
}

// fetch employees for assignee selection
$employees = [];
if (isset($conn)) {
    $nama_bagian = $_SESSION['nama_bagian'] ?? null;
    if (!empty($nama_bagian)) {
        $stmt = $conn->prepare("SELECT npp, nama_emp FROM employee WHERE nama_bagian = ? ORDER BY nama_emp ASC");
        if ($stmt) {
            $stmt->bind_param('s', $nama_bagian);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($e = $res->fetch_assoc()) $employees[] = $e;
                $res->free();
            }
            $stmt->close();
        }
    } else {
        $r2 = $conn->query("SELECT npp, nama_emp FROM employee ORDER BY nama_emp ASC");
        if ($r2) {
            while ($e = $r2->fetch_assoc()) $employees[] = $e;
            $r2->free();
        }
    }
}

?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Pengisian Daftar Pekerjaan</h3>

            <div class="card mb-4">
                <div class="card-body">
                    <div id="calendar" style="max-width:100%; min-height:480px;"></div>
                </div>
            </div>

            <script>
            var currentUserNpp = '<?php echo e($npp ?? ''); ?>';
            window.addEventListener('load', function () {
                var el = document.getElementById('calendar');
                if (!el || !window.FullCalendar) {
                    console.warn('Calendar element or FullCalendar is not available.');
                    return;
                }

                var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                var isMobile = viewportWidth < 576; // match CSS mobile breakpoint
                var initialView = isMobile ? 'listWeek' : 'dayGridMonth';
                var headerRight = isMobile ? 'listWeek,dayGridMonth' : 'dayGridMonth,timeGridWeek,listWeek';

                var calendar = new FullCalendar.Calendar(el, {
                    initialView: initialView,
                    customButtons: {
                        addPekerjaan: { text: 'Tambah Pekerjaan', click: function(){ window.openPekerjaanModal(); } }
                    },
                    headerToolbar: { left: 'prev,next today addPekerjaan', center: 'title', right: headerRight },
                    events: '<?php echo site_url("api/events_pekerjaan.php"); ?>',
                    displayEventTime: false,
                    height: 'auto',
                    expandRows: true,
                    dayMaxEventRows: 3,
                    locale: 'id',
                    buttonText: {
                        today: 'Hari ini',
                        month: 'Bulan',
                        week: 'Minggu',
                        list: 'Daftar'
                    },
                    navLinks: true,
                    stickyHeaderDates: true,
                    dateClick: function(info){
                        if (typeof window.openPekerjaanModal === 'function'){
                            window.openPekerjaanModal(info.dateStr);
                        }
                    },
                    eventClick: function(info){
                        function escapeHtml(s){
                            return String(s ?? '')
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }
                        function formatDateId(yyyyMmDd){
                            if (!yyyyMmDd) return '-';
                            // Expect YYYY-MM-DD
                            var d = new Date(yyyyMmDd + 'T00:00:00');
                            if (isNaN(d.getTime())) return String(yyyyMmDd);
                            return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
                        }

                        var props = info.event.extendedProps || {};
                        var desc = props.description || '';
                        var status = props.status || '';
                        var assigned = props.ditugaskan || '';
                        var assignedName = props.assigned_name || '';
                        var reporter = props.reporter_name || props.reporter_npp || '';
                        var tglMulai = props.tgl_mulai || info.event.startStr || '';
                        var tglSelesai = props.tgl_selesai || '';

                        var assignedLabel = assigned ? (assignedName ? (assignedName + ' (' + assigned + ')') : assigned) : '-';
                        var reporterLabel = reporter ? reporter : '-';
                        var mulaiText = formatDateId(tglMulai);
                        var selesaiText = formatDateId(tglSelesai);
                        var tanggalText = tglSelesai ? (mulaiText + ' — ' + selesaiText) : mulaiText;

                        var titleEl = document.getElementById('pj_detail_title');
                        var tanggalEl = document.getElementById('pj_detail_tanggal');
                        var statusEl = document.getElementById('pj_detail_status');
                        var assignedEl = document.getElementById('pj_detail_assigned');
                        var reporterEl = document.getElementById('pj_detail_reporter');
                        var descEl = document.getElementById('pj_detail_desc');
                        var alertEl = document.getElementById('pj_detail_alert');
                        var doneBtn = document.getElementById('pj_detail_done');

                        if (titleEl) titleEl.textContent = info.event.title || '';
                        if (tanggalEl) tanggalEl.textContent = tanggalText;

                        var statusText = status ? status : '-';
                        if (statusEl) {
                            statusEl.textContent = statusText;
                            statusEl.classList.remove('text-bg-secondary','text-bg-primary','text-bg-success');
                            if (status === 'done') statusEl.classList.add('text-bg-success');
                            else if (status && status !== '-') statusEl.classList.add('text-bg-primary');
                            else statusEl.classList.add('text-bg-secondary');
                        }

                        if (assignedEl) assignedEl.textContent = assignedLabel;
                        if (reporterEl) reporterEl.textContent = reporterLabel;
                        if (descEl) descEl.innerHTML = desc ? escapeHtml(desc).replace(/\n/g,'<br>') : '<span class="text-body-secondary">-</span>';

                        if (alertEl) {
                            alertEl.classList.add('d-none');
                            alertEl.classList.remove('alert-success','alert-danger');
                            alertEl.textContent = '';
                        }

                        var canDone = currentUserNpp && assigned && assigned === currentUserNpp && status !== 'done';
                        if (doneBtn) {
                            doneBtn.dataset.id = info.event.id;
                            doneBtn.disabled = false;
                            doneBtn.classList.toggle('d-none', !canDone);
                        }

                        if (window.openPekerjaanDetailModal) {
                            window.openPekerjaanDetailModal();
                        }
                    },
                    views: {
                        dayGridMonth: { dayMaxEventRows: 3 },
                        listWeek: { noEventMessage: 'Tidak ada pekerjaan minggu ini' }
                    },
                    eventDisplay: 'block'
                });
                calendar.render();
                window.pekerjaanCalendar = calendar;

                if (isMobile) el.classList.add('mobile-calendar');
            });
            </script>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $err)
                        echo '<li>' . e($err) . '</li>'; ?></ul>
                </div>
            <?php endif; ?>

                        <!-- Form removed: use calendar modal to create tasks -->

                        <!-- Modal for creating pekerjaan -->
                        <div class="modal fade" id="pekerjaanModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Buat Pekerjaan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="pekerjaanForm">
                                            <div class="mb-3">
                                                <label class="form-label">Judul</label>
                                                <input name="judul" id="pj_judul" class="form-control form-control-lg" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea name="deskripsi" id="pj_deskripsi" class="form-control form-control-lg" rows="4"></textarea>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">Tanggal Mulai</label>
                                                    <input type="date" name="tgl_mulai" id="pj_mulai" class="form-control form-control-lg">
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">Tanggal Selesai</label>
                                                    <input type="date" name="tgl_selesai" id="pj_selesai" class="form-control form-control-lg">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ditugaskan ke</label>
                                                <select name="ditugaskan" id="pj_ditugaskan" class="form-select form-select-lg" required>
                                                    <option value="">-- Pilih Pegawai --</option>
                                                    <?php foreach ($employees as $emp): ?>
                                                        <option value="<?php echo e($emp['npp']); ?>"><?php echo e($emp['nama_emp']); ?> (<?php echo e($emp['npp']); ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <!-- <div class="mb-3">
                                                <label class="form-label">Dilaporkan Oleh</label>
                                                <input class="form-control" value="<?php echo e($nama_emp ?? $npp ?? ''); ?>" disabled>
                                            </div> -->
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="button" id="pj_save" class="btn btn-primary">Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal detail pekerjaan (custom, mirip task app) -->
                        <div class="modal fade" id="pekerjaanDetailModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <div class="w-100">
                                            <h5 class="modal-title mb-1" id="pj_detail_title">Detail Pekerjaan</h5>
                                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                                <div class="text-body-secondary" id="pj_detail_tanggal">-</div>
                                                <span class="badge text-bg-secondary" id="pj_detail_status">-</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div id="pj_detail_alert" class="alert d-none" role="alert"></div>

                                        <div class="list-group list-group-flush">
                                            <div class="list-group-item px-0">
                                                <div class="text-uppercase text-body-secondary small">Ditugaskan</div>
                                                <div class="fw-semibold" id="pj_detail_assigned">-</div>
                                            </div>
                                            <div class="list-group-item px-0">
                                                <div class="text-uppercase text-body-secondary small">Koordinator</div>
                                                <div class="fw-semibold" id="pj_detail_reporter">-</div>
                                            </div>
                                            <div class="list-group-item px-0">
                                                <div class="text-uppercase text-body-secondary small">Deskripsi</div>
                                                <div id="pj_detail_desc" class="mt-1">-</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <button type="button" class="btn btn-primary d-none" id="pj_detail_done">Tandai Selesai</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                        // handle opening modal from calendar and submitting via fetch
                        (function(){
                                var modalEl = document.getElementById('pekerjaanModal');
                                var bsModal = null;
                                function ensureModal(){
                                        if (!bsModal && window.bootstrap && modalEl) bsModal = new bootstrap.Modal(modalEl);
                                        return bsModal;
                                }

                            var detailModalEl = document.getElementById('pekerjaanDetailModal');
                            var bsDetailModal = null;
                            function ensureDetailModal(){
                                if (!bsDetailModal && window.bootstrap && detailModalEl) bsDetailModal = new bootstrap.Modal(detailModalEl);
                                return bsDetailModal;
                            }

                            window.openPekerjaanDetailModal = function(){
                                ensureDetailModal();
                                if (bsDetailModal) bsDetailModal.show();
                            };

                                // expose helper to calendar dateClick
                                window.openPekerjaanModal = function(dateStr){
                                    ensureModal();
                                    document.getElementById('pj_mulai').value = dateStr || '';
                                    document.getElementById('pj_selesai').value = '';
                                    document.getElementById('pj_judul').value = '';
                                    document.getElementById('pj_deskripsi').value = '';
                                    if (bsModal) bsModal.show();
                                    setTimeout(function(){
                                        var el = document.getElementById('pj_judul');
                                        if (el) el.focus();
                                    }, 300);
                                };

                                // submit handler
                            document.getElementById('pj_save').addEventListener('click', function(){
                                var form = document.getElementById('pekerjaanForm');
                                var fd = new FormData(form);
                                        fetch('<?php echo site_url("api/create_pekerjaan.php"); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                                        .then(function(res){ return res.json(); })
                                        .then(function(json){
                                                if (json && json.success){
                                                        if (bsModal) bsModal.hide();
                                                        if (window.Swal) Swal.fire({icon:'success', title:'Tersimpan', text: json.message || 'Pekerjaan tersimpan'});
                                                        // refetch events if calendar present
                                                        if (window.pekerjaanCalendar && typeof window.pekerjaanCalendar.refetchEvents === 'function'){
                                                                window.pekerjaanCalendar.refetchEvents();
                                                        } else {
                                                                // fallback reload
                                                                setTimeout(function(){ location.reload(); }, 700);
                                                        }
                                                } else {
                                                        if (window.Swal) Swal.fire({icon:'error', title:'Gagal', text: json.message || 'Gagal menyimpan'});
                                                }
                                        }).catch(function(err){
                                                console.error(err);
                                                if (window.Swal) Swal.fire({icon:'error', title:'Error', text: 'Terjadi error saat menyimpan.'});
                                        });
                                });

                                // mark done handler (detail modal)
                                var doneBtn = document.getElementById('pj_detail_done');
                                if (doneBtn) {
                                    doneBtn.addEventListener('click', function(){
                                        var id = this.dataset.id;
                                        if (!id) return;

                                        var alertEl = document.getElementById('pj_detail_alert');
                                        function showAlert(kind, text){
                                            if (!alertEl) return;
                                            alertEl.classList.remove('d-none','alert-success','alert-danger');
                                            alertEl.classList.add(kind === 'success' ? 'alert-success' : 'alert-danger');
                                            alertEl.textContent = text;
                                        }

                                        this.disabled = true;
                                        fetch('<?php echo site_url("api/mark_done.php"); ?>', {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                                            body: new URLSearchParams({ id: id })
                                        })
                                        .then(r => r.json())
                                        .then(function(json){
                                            if (json && json.success){
                                                showAlert('success', 'Berhasil ditandai selesai.');
                                                var statusEl = document.getElementById('pj_detail_status');
                                                if (statusEl) {
                                                    statusEl.textContent = 'done';
                                                    statusEl.classList.remove('text-bg-secondary','text-bg-primary');
                                                    statusEl.classList.add('text-bg-success');
                                                }
                                                doneBtn.classList.add('d-none');
                                                if (window.pekerjaanCalendar) window.pekerjaanCalendar.refetchEvents();
                                            } else {
                                                showAlert('error', (json && json.message) ? json.message : 'Gagal memperbarui.');
                                                doneBtn.disabled = false;
                                            }
                                        })
                                        .catch(function(err){
                                            console.error(err);
                                            showAlert('error', 'Terjadi kesalahan.');
                                            doneBtn.disabled = false;
                                        });
                                    });
                                }
                        })();
                        </script>

            <!-- <div class="card">
                <div class="card-header">Pekerjaan Terbaru</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Judul</th>
                                    <th>Deskripsi</th>
                                    <th>Selesai</th>
                                    <th>Pelapor</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center small text-muted">Belum ada pekerjaan.</td>
                                    </tr>
                                <?php else:
                                    foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?php echo e($r['id']); ?></td>
                                            <td><?php echo e($r['judul']); ?></td>
                                            <td><?php echo e(mb_strimwidth($r['deskripsi'], 0, 120, '...')); ?></td>
                                            <td><?php echo e($r['tgl_selesai'] ?? ''); ?></td>
                                            <td><?php echo e($r['nama_emp'] ?: $r['npp']); ?></td>
                                            <td><?php echo e($r['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> -->

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>