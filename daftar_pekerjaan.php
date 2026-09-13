<?php
require_once __DIR__ . '/functions/helpers.php';
// Proteksi halaman sebelum output apapun: wajib login, jika tidak redirect ke login.php
require_login();
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
    if ($deskripsi === '')
        $errors[] = 'Deskripsi pekerjaan wajib diisi';
    if ($tglMulai === '')
        $errors[] = 'Tanggal mulai wajib diisi';
    if ($tglSelesai === '')
        $errors[] = 'Tanggal selesai wajib diisi';
    if ($ditugaskan === '')
        $errors[] = 'Pilih pegawai yang ditugaskan';


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
                while ($e = $res->fetch_assoc())
                    $employees[] = $e;
                $res->free();
            }
            $stmt->close();
        }
    } else {
        $r2 = $conn->query("SELECT npp, nama_emp FROM employee ORDER BY nama_emp ASC");
        if ($r2) {
            while ($e = $r2->fetch_assoc())
                $employees[] = $e;
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

            <style>
                /* Custom calendar event card: improved text fitting and truncation */
                .fc-custom-event {
                    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
                    color: var(--bs-body-color);
                    padding: 8px;
                    border-radius: 8px;
                    background: #fff;
                    border: 1px solid rgba(0, 0, 0, 0.04);
                    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.02);
                    display: flex;
                    flex-direction: column;
                    min-height: 44px;
                    overflow: hidden;
                }

                .fc-custom-event .fc-ce-header {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 8px;
                    min-height: 28px;
                }

                .fc-custom-event .fc-ce-title {
                    font-weight: 600;
                    font-size: 14px;
                    line-height: 1.15;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    word-break: break-word;
                    margin: 0;
                }

                .fc-custom-event .fc-ce-deadline {
                    font-size: 12px;
                    color: #6c757d;
                    margin-top: 4px;
                }

                .fc-custom-event .fc-ce-desc {
                    font-size: 13px;
                    color: #333;
                    margin-top: 6px;
                    max-height: 3.6em;
                    /* ~2 lines */
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    line-height: 1.6;
                }

                .fc-custom-event .fc-ce-assigned {
                    font-size: 12px;
                    color: #495057;
                    margin-top: 8px;
                    border-top: 1px dashed rgba(0, 0, 0, 0.04);
                    padding-top: 6px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .fc-custom-event .ec-open-btn {
                    font-size: 11px;
                    padding: 3px 8px;
                    flex: 0 0 auto;
                    white-space: nowrap;
                }

                /* Menyembunyikan isi secara visual, tapi TETAP mempertahankan tingginya */
                .fc-custom-event.is-continuation>* {
                    visibility: hidden;
                }

                /* Merapikan bentuk kotak kelanjutan agar terlihat menyambung dari tepi layar */
                .fc-custom-event.is-continuation {
                    border-left: none !important;
                    border-top-left-radius: 0 !important;
                    border-bottom-left-radius: 0 !important;
                    background-color: #ffffff !important;
                    /* <--- UBAH BAGIAN INI JADI PUTIH */
                    box-shadow: none !important;
                }

                /* Improve visuals in month/day grid where space is tight */
                .fc .fc-daygrid-event .fc-custom-event {
                    padding: 6px;
                    font-size: 12px;
                }

                @media (max-width:575.98px) {
                    .fc-custom-event {
                        padding: 6px;
                        border-radius: 6px
                    }

                    .fc-custom-event .fc-ce-title {
                        font-size: 13px;
                    }

                    .fc-custom-event .fc-ce-desc {
                        font-size: 12px;
                        -webkit-line-clamp: 2
                    }

                    .fc-custom-event .fc-ce-assigned {
                        font-size: 11px
                    }

                    .fc-custom-event .ec-open-btn {
                        font-size: 11px;
                        padding: 2px 6px
                    }

                    .fc-custom-event .fc-ce-header {
                        gap: 6px
                    }

                    .fc-custom-event .fc-ce-title {
                        max-width: calc(100% - 70px);
                    }

                    /* leave space for button */
                }
            </style>
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

                    function showEventDetail(ev) {
                        function escapeHtml(s) {
                            return String(s ?? '')
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;')
                                .replace(/'/g, '&#039;');
                        }

                        var props = ev.extendedProps || {};
                        var desc = props.description || '';
                        var status = props.status || '';
                        var assigned = props.ditugaskan || '';
                        var assignedName = props.assigned_name || '';
                        var reporter = props.reporter_name || props.reporter_npp || '';
                        var tglMulai = props.tgl_mulai || ev.startStr || '';
                        var tglSelesai = props.tgl_selesai || '';
                        var doneAt = props.done_at || '';

                        var assignedLabel = assigned ? (assignedName ? (assignedName + ' (' + assigned + ')') : assigned) : '-';
                        var reporterLabel = reporter ? reporter : '-';
                        var mulaiText = tglMulai;
                        var selesaiText = tglSelesai;
                        var tanggalText = tglSelesai ? (mulaiText + ' — ' + selesaiText) : mulaiText;

                        var titleEl = document.getElementById('pj_detail_title');
                        var tanggalEl = document.getElementById('pj_detail_tanggal');
                        var statusEl = document.getElementById('pj_detail_status');
                        var assignedEl = document.getElementById('pj_detail_assigned');
                        var reporterEl = document.getElementById('pj_detail_reporter');
                        var descEl = document.getElementById('pj_detail_desc');
                        var doneAtEl = document.getElementById('pj_detail_done_at');
                        var doneRow = document.getElementById('pj_detail_done_row');
                        var alertEl = document.getElementById('pj_detail_alert');
                        var doneBtn = document.getElementById('pj_detail_done');

                        if (titleEl) titleEl.textContent = ev.title || '';
                        if (tanggalEl) tanggalEl.textContent = tanggalText;

                        var statusText = status ? status : '-';
                        if (statusEl) {
                            statusEl.textContent = statusText;
                            statusEl.classList.remove('text-bg-secondary', 'text-bg-primary', 'text-bg-success');
                            if (status === 'done') statusEl.classList.add('text-bg-success');
                            else if (status && status !== '-') statusEl.classList.add('text-bg-primary');
                            else statusEl.classList.add('text-bg-secondary');
                        }

                        if (assignedEl) assignedEl.textContent = assignedLabel;
                        if (reporterEl) reporterEl.textContent = reporterLabel;
                        if (descEl) descEl.innerHTML = desc ? escapeHtml(desc).replace(/\n/g, '<br>') : '<span class="text-body-secondary">-</span>';

                        if (alertEl) {
                            alertEl.classList.add('d-none');
                            alertEl.classList.remove('alert-success', 'alert-danger');
                            alertEl.textContent = '';
                        }

                        var canDone = currentUserNpp && assigned && assigned === currentUserNpp && status !== 'done';
                        if (doneBtn) {
                            doneBtn.dataset.id = ev.id;
                            doneBtn.disabled = false;
                            doneBtn.classList.toggle('d-none', !canDone);
                        }

                        if (doneRow && doneAtEl) {
                            if (status === 'done' && doneAt) {
                                doneAtEl.textContent = doneAt;
                                doneRow.classList.remove('d-none');
                            } else {
                                doneAtEl.textContent = '-';
                                doneRow.classList.add('d-none');
                            }
                        }

                        if (window.openPekerjaanDetailModal) {
                            window.openPekerjaanDetailModal();
                        }
                    }

                    var calendar = new FullCalendar.Calendar(el, {
                        initialView: initialView,
                        customButtons: {
                            addPekerjaan: { text: 'Tambah Pekerjaan', click: function () { window.openPekerjaanModal(); } }
                        },
                        headerToolbar: { left: 'prev,next today addPekerjaan', center: 'title', right: headerRight },
                        events: '<?php echo site_url("api/events_pekerjaan.php"); ?>',
                        displayEventTime: false,
                        height: 'auto',
                        expandRows: true,
                        dayMaxEventRows: 3,
                        locale: 'id',
                        displayEventEnd: true,
                        nextDayThreshold: '00:00:00',
                        buttonText: {
                            today: 'Hari ini',
                            month: 'Bulan',
                            week: 'Minggu',
                            list: 'Daftar'
                        },
                        navLinks: true,
                        stickyHeaderDates: true,
                        dateClick: function (info) {
                            if (typeof window.openPekerjaanModal === 'function') {
                                window.openPekerjaanModal(info.dateStr);
                            }
                        },
                        eventClick: function (info) { showEventDetail(info.event); },
                        eventContent: function (arg) {
                            function escapeHtml(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }

                            var ev = arg.event;
                            var props = ev.extendedProps || {};
                            var title = ev.title || '';
                            // Prefer server-formatted dates from extendedProps (provided by API)
                            var tgl = props.tgl_selesai_fmt || props.tgl_mulai_fmt || props.tgl_selesai || props.tgl_mulai || '';
                            var desc = props.description || '';
                            var assigned = (props.assigned_name ? (props.assigned_name + ' (' + (props.ditugaskan || '') + ')') : (props.ditugaskan || '')) || '-';

                            function humanizeStatus(s) {
                                if (!s) return '';
                                try { return String(s).replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); }); } catch (e) { return String(s); }
                            }
                            var btnLabel = props.status ? humanizeStatus(props.status) : 'Open';
                            var statusVal = (props.status || '').toString().toLowerCase();
                            var isDone = (statusVal === 'done' || statusVal === 'selesai' || statusVal === 'completed');
                            var btnClass = isDone ? 'btn btn-sm btn-success ec-open-btn' : (props.status ? 'btn btn-sm btn-primary ec-open-btn' : 'btn btn-sm btn-outline-primary ec-open-btn');
                            var btnAttrs = isDone ? ' disabled' : '';

                            // Deteksi apakah ini potongan di minggu berikutnya
                            var isContinuation = !arg.isStart;

                            // Tambahkan class khusus jika ini adalah potongan kelanjutan
                            var wrapperClass = isContinuation ? 'fc-custom-event is-continuation' : 'fc-custom-event';

                            // RENDER SEMUA ELEMEN SAMA PERSIS agar tingginya tetap stabil
                            var html = '<div class="' + wrapperClass + '">';
                            html += '<div class="fc-ce-header"><div><div class="fc-ce-title">' + escapeHtml(title) + '</div></div>'
                                + '<div><button type="button" class="' + btnClass + '" data-eid="' + escapeHtml(ev.id) + '"' + btnAttrs + '>' + escapeHtml(btnLabel) + '</button></div></div>';
                            html += (tgl ? ('<div class="fc-ce-deadline">' + escapeHtml(tgl) + '</div>') : '');
                            html += (desc ? ('<div class="fc-ce-desc">' + escapeHtml(desc) + '</div>') : '');
                            html += '<div class="fc-ce-assigned">' + escapeHtml(assigned) + '</div>';
                            html += '</div>';

                            return { html: html };
                        },
                        eventDidMount: function (info) {
                            try {
                                var btn = info.el.querySelector('.ec-open-btn');
                                if (btn) {
                                    btn.addEventListener('click', function (e) {
                                        e.stopPropagation();
                                        showEventDetail(info.event);
                                    });
                                }

                                // CSS tambahan agar kotak event kelanjutan tidak memiliki border kiri/kanan
                                // sehingga terlihat seperti satu kesatuan bar panjang
                                if (!info.isStart) {
                                    var customEventEl = info.el.querySelector('.fc-custom-event');
                                    if (customEventEl) {
                                        customEventEl.style.borderLeft = 'none';
                                        customEventEl.style.borderTopLeftRadius = '0';
                                        customEventEl.style.borderBottomLeftRadius = '0';
                                    }
                                }
                                if (!info.isEnd) {
                                    var customEventEl = info.el.querySelector('.fc-custom-event');
                                    if (customEventEl) {
                                        customEventEl.style.borderRight = 'none';
                                        customEventEl.style.borderTopRightRadius = '0';
                                        customEventEl.style.borderBottomRightRadius = '0';
                                    }
                                }

                            } catch (e) { console.error(e); }
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
                                    <textarea name="deskripsi" id="pj_deskripsi" class="form-control form-control-lg"
                                        rows="4" required></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Tanggal Mulai</label>
                                        <input type="date" name="tgl_mulai" id="pj_mulai"
                                            class="form-control form-control-lg" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Tanggal Selesai</label>
                                        <input type="date" name="tgl_selesai" id="pj_selesai"
                                            class="form-control form-control-lg" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ditugaskan ke</label>
                                    <select name="ditugaskan" id="pj_ditugaskan" class="form-select form-select-lg"
                                        required>
                                        <option value="">-- Pilih Pegawai --</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo e($emp['npp']); ?>"><?php echo e($emp['nama_emp']); ?>
                                                (<?php echo e($emp['npp']); ?>)</option>
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
                                <div class="list-group-item px-0" id="pj_detail_done_row">
                                    <div class="text-uppercase text-body-secondary small">Waktu Tugas Selesai</div>
                                    <div class="fw-semibold" id="pj_detail_done_at">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Tutup</button>
                            <button type="button" class="btn btn-primary d-none" id="pj_detail_done">Tandai
                                Selesai</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // handle opening modal from calendar and submitting via fetch
                (function () {
                    var modalEl = document.getElementById('pekerjaanModal');
                    var bsModal = null;
                    function ensureModal() {
                        if (!bsModal && window.bootstrap && modalEl) bsModal = new bootstrap.Modal(modalEl);
                        return bsModal;
                    }

                    var detailModalEl = document.getElementById('pekerjaanDetailModal');
                    var bsDetailModal = null;
                    function ensureDetailModal() {
                        if (!bsDetailModal && window.bootstrap && detailModalEl) bsDetailModal = new bootstrap.Modal(detailModalEl);
                        return bsDetailModal;
                    }

                    window.openPekerjaanDetailModal = function () {
                        ensureDetailModal();
                        if (bsDetailModal) bsDetailModal.show();
                    };

                    // expose helper to calendar dateClick
                    window.openPekerjaanModal = function (dateStr) {
                        ensureModal();
                        document.getElementById('pj_mulai').value = dateStr || '';
                        document.getElementById('pj_selesai').value = '';
                        document.getElementById('pj_judul').value = '';
                        document.getElementById('pj_deskripsi').value = '';
                        if (bsModal) bsModal.show();
                        setTimeout(function () {
                            var el = document.getElementById('pj_judul');
                            if (el) el.focus();
                        }, 300);
                    };

                    // submit handler
                    document.getElementById('pj_save').addEventListener('click', function () {
                        var form = document.getElementById('pekerjaanForm');
                        // client-side validation: use HTML5 constraint validation
                        if (!form.checkValidity()) {
                            form.reportValidity();
                            return;
                        }
                        var fd = new FormData(form);
                        fetch('<?php echo site_url("api/create_pekerjaan.php"); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
                            .then(function (res) { return res.json(); })
                            .then(function (json) {
                                if (json && json.success) {
                                    if (bsModal) bsModal.hide();
                                    if (window.Swal) Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.message || 'Pekerjaan tersimpan' });
                                    // refetch events if calendar present
                                    if (window.pekerjaanCalendar && typeof window.pekerjaanCalendar.refetchEvents === 'function') {
                                        window.pekerjaanCalendar.refetchEvents();
                                    } else {
                                        // fallback reload
                                        setTimeout(function () { location.reload(); }, 700);
                                    }
                                } else {
                                    if (window.Swal) Swal.fire({ icon: 'error', title: 'Gagal', text: json.message || 'Gagal menyimpan' });
                                }
                            }).catch(function (err) {
                                console.error(err);
                                if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi error saat menyimpan.' });
                            });
                    });

                    // mark done handler (detail modal)
                    var doneBtn = document.getElementById('pj_detail_done');
                    if (doneBtn) {
                        doneBtn.addEventListener('click', function () {
                            var id = this.dataset.id;
                            if (!id) return;

                            var alertEl = document.getElementById('pj_detail_alert');
                            function showAlert(kind, text) {
                                if (!alertEl) return;
                                alertEl.classList.remove('d-none', 'alert-success', 'alert-danger');
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
                                .then(function (json) {
                                    if (json && json.success) {
                                        showAlert('success', 'Berhasil ditandai selesai.');
                                        var statusEl = document.getElementById('pj_detail_status');
                                        if (statusEl) {
                                            statusEl.textContent = 'done';
                                            statusEl.classList.remove('text-bg-secondary', 'text-bg-primary');
                                            statusEl.classList.add('text-bg-success');
                                        }
                                        doneBtn.classList.add('d-none');
                                        if (window.pekerjaanCalendar) window.pekerjaanCalendar.refetchEvents();
                                    } else {
                                        showAlert('error', (json && json.message) ? json.message : 'Gagal memperbarui.');
                                        doneBtn.disabled = false;
                                    }
                                })
                                .catch(function (err) {
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
                                            <td><?php echo e(($r['tgl_selesai'] ?? '') ? format_date_id($r['tgl_selesai']) : '-'); ?></td>
                                            <td><?php echo e($r['nama_emp'] ?: $r['npp']); ?></td>
                                            <td><?php echo e(format_datetime_id($r['created_at'])); ?></td>
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