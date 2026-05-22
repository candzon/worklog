<?php
/**
 * daftar_pekerjaan.php (Controller & View)
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';

ensure_session_started();
require_login();

// Detect Protocol for Base URL (for Google Viewer)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

$npp = $_SESSION['npp'] ?? null;
$nama_emp = $_SESSION['nama_emp'] ?? null;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['judul'])) {
    $roleName = function_exists('get_current_role_name') ? get_current_role_name($conn ?? null) : null;
    $isManager = (strtolower((string) $roleName) === 'manager' || strtolower((string) $roleName) === 'admin');

    if (!$isManager) {
        $errors[] = 'Hanya role manager yang dapat membuat pekerjaan baru.';
    }

    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $tglMulai = trim($_POST['tgl_mulai'] ?? '');
    $tglSelesai = trim($_POST['tgl_selesai'] ?? '');
    $assigned_to_npp = trim($_POST['assigned_to_npp'] ?? $_POST['ditugaskan'] ?? '');
    $master_tugas_id = trim($_POST['master_tugas_id'] ?? '');
    $periode = trim($_POST['periode'] ?? '');

    if ($judul === '')
        $errors[] = 'Judul pekerjaan wajib diisi';
    if ($tglMulai === '')
        $errors[] = 'Tanggal mulai wajib diisi';
    if ($tglSelesai === '')
        $errors[] = 'Tanggal selesai wajib diisi';
    if ($assigned_to_npp === '')
        $errors[] = 'Pilih pegawai yang ditugaskan';

    if (empty($errors) && isset($conn)) {
        $stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, master_tugas_id, periode) VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?,''), NULLIF(?,''))");
        if ($stmt) {
            $stmt->bind_param('sssssssss', $judul, $deskripsi, $npp, $nama_emp, $tglMulai, $tglSelesai, $assigned_to_npp, $master_tugas_id, $periode);
            $stmt->execute();
            $stmt->close();
            if (function_exists('flash_swal'))
                flash_swal('success', 'Tersimpan', 'Pekerjaan berhasil ditambahkan');
            header('Location: ' . site_url('daftar_pekerjaan.php'));
            exit;
        } else {
            $errors[] = 'Gagal menyiapkan penyimpanan.';
        }
    }
}

// Fetch employees for selection
$employees = [];
if (isset($conn)) {
    $res = $conn->query("SELECT npp, nama_emp FROM employee ORDER BY nama_emp ASC");
    if ($res) {
        while ($e = $res->fetch_assoc())
            $employees[] = $e;
        $res->free();
    }
}

// Render Layout
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Document Viewer Modal -->
<div class="modal fade" id="docViewerModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0 shadow-none" style="background: #1a1a1a;">
            <div class="modal-header border-0 py-3 px-4"
                style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-3 p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px;">
                        <i class="bi bi-file-earmark-text text-white fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title text-white fw-bold mb-0" id="docViewerTitle">Pratinjau Dokumen</h6>
                        <small class="text-white-50"
                            style="font-size: 10px; letter-spacing: 1px; text-transform: uppercase;">Worklog Document
                            Viewer</small>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <a href="#" id="btnDownloadActual" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm"
                        target="_blank">
                        <i class="bi bi-download me-2"></i> Unduh
                    </a>
                    <button type="button"
                        class="btn btn-outline-light border-0 rounded-circle d-flex align-items-center justify-content-center"
                        data-bs-dismiss="modal" style="width: 40px; height: 40px; background: rgba(255,255,255,0.1);">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex justify-content-center align-items-center position-relative"
                style="background: #262626;">
                <div id="docLoader" class="text-white text-center position-absolute" style="z-index: 5;">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
                    <div class="mt-3 fw-semibold opacity-75">Menyiapkan Dokumen...</div>
                </div>
                <iframe id="docViewerIframe" src="" width="100%" height="100%" style="border:none; background: #fff;"
                    onload="document.getElementById('docLoader').classList.add('d-none')"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="app-main">
    <div class="app-content p-2 p-md-4">
        <div class="container-fluid px-0 px-md-2">
            <h3 class="mb-3 px-2">Pengisian Daftar Pekerjaan</h3>

            <div class="card mb-4 border-0 shadow-sm card-calendar">
                <div class="card-body p-2 p-md-4">
                    <div id="calendar" class="calendar-wrap"></div>
                </div>
            </div>

            <style>
                img {
                    max-width: 100%;
                    height: auto;
                }

                #calendar {
                    background: #fff;
                    border-radius: 1.25rem;
                    padding: 0.625rem;
                    border: none;
                }

                .card-calendar {
                    border-radius: 1.5rem;
                    width: 100%;
                }

                .card-calendar .calendar-wrap {
                    width: 100%;
                    min-height: 56.25rem;
                }

                .fc .fc-toolbar-title {
                    font-weight: 700;
                    color: #1c1c1e;
                    font-size: 1.8rem !important;
                }

                .fc .fc-button-primary {
                    background: #f2f2f7;
                    border: none;
                    color: #007aff;
                    border-radius: 0.75rem;
                    padding: 0.625rem 1.25rem;
                    font-weight: 700;
                    font-size: 1rem;
                    transition: all 0.2s;
                }

                .fc .fc-button-primary:hover {
                    background: #e5e5ea;
                }

                .fc .fc-button-active {
                    background: #007aff !important;
                    color: #fff !important;
                }

                .fc .fc-theme-standard td,
                .fc .fc-theme-standard th {
                    border-color: #f2f2f7;
                }

                .fc .fc-daygrid-day-number {
                    color: #8e8e93;
                    font-weight: 700;
                    padding: 0.75rem;
                    font-size: 1.1rem;
                }

                .fc .fc-col-header-cell-cushion {
                    color: #8e8e93;
                    font-size: 0.9rem;
                    text-transform: uppercase;
                    letter-spacing: 0.0625rem;
                    padding: 0.9375rem;
                    font-weight: 700;
                }

                .fc-custom-event {
                    padding: 0.5rem 0.8rem;
                    border-radius: 0.75rem;
                    margin: 0.2rem 0.3rem;
                    display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    transition: all 0.2s;
                    cursor: pointer;
                    border-left: 0.4rem solid !important;
                    width: auto !important;
                    min-height: 2rem;
                    box-shadow: 0 0.125rem 0.4rem rgba(0, 0, 0, 0.06);
                }

                .fc-custom-event:hover {
                    filter: brightness(0.94);
                    transform: scale(1.02);
                    box-shadow: 0 0.3rem 0.8rem rgba(0, 0, 0, 0.1);
                }

                .fc-ce-title {
                    font-weight: 700;
                    font-size: 0.85rem;
                    color: #1e293b;
                    line-height: 1.3;
                    white-space: normal !important;
                    word-break: break-word;
                }

                /* Sembunyikan judul di tampilan Grid Bulan agar tetap rapi seperti bar */
                .fc-daygrid-event .fc-ce-title {
                    display: none !important;
                }

                /* Sembunyikan judul di tampilan List/Daftar (Hanya tampil warna) */
                .fc-list-event .fc-ce-title {
                    display: none !important;
                }

                .ev-harian {
                    background: #e8f5e9 !important;
                    border-color: #4caf50 !important;
                }

                .ev-mingguan {
                    background: #fffde7 !important;
                    border-color: #fbc02d !important;
                }

                .ev-bulanan {
                    background: #e3f2fd !important;
                    border-color: #2196f3 !important;
                }

                .ev-triwulan {
                    background: #fff3e0 !important;
                    border-color: #ff9800 !important;
                }

                .ev-manual {
                    background: #f1f5f9 !important;
                    border-color: #94a3b8 !important;
                }

                .ev-revisi {
                    background: #ffebee !important;
                    border-color: #f44336 !important;
                    color: #b71c1c !important;
                }

                .fc-daygrid-event {
                    background: transparent !important;
                    border: none !important;
                    box-shadow: none !important;
                }

                .fc-daygrid-event-h-harness {
                    margin-bottom: 0.375rem !important;
                }

                .fc .fc-daygrid-day-frame {
                    min-height: 11.25rem !important;
                }

                .modal-content {
                    border-radius: 1.75rem;
                    border: none;
                    box-shadow: 0 1.5625rem 3.125rem -0.75rem rgba(0, 0, 0, 0.2);
                }

                .modal-header {
                    border-bottom: 0.0625rem solid #f2f2f7;
                    padding: 1.25rem 1.5rem;
                }

                .modal-footer {
                    border-top: none;
                    padding: 0.75rem 1.5rem 1.5rem;
                    gap: 0.75rem;
                }

                .btn-app-primary {
                    background: linear-gradient(135deg, #007aff 0%, #0056b3 100%);
                    color: #fff;
                    border: none;
                    border-radius: 1rem;
                    padding: 0.875rem 1.75rem;
                    font-weight: 700;
                    transition: all 0.3s;
                    box-shadow: 0 0.25rem 0.75rem rgba(0, 122, 255, 0.3);
                }

                .btn-app-secondary {
                    background: #f2f2f7;
                    color: #1c1c1e;
                    border: none;
                    border-radius: 1rem;
                    padding: 0.875rem 1.75rem;
                    font-weight: 600;
                    transition: all 0.2s;
                }

                .btn-app-primary:hover {
                    transform: translateY(-0.125rem);
                    box-shadow: 0 0.375rem 1.25rem rgba(0, 122, 255, 0.4);
                }

                .btn-app-secondary:hover {
                    background: #e5e5ea;
                }

                .fab-add {
                    position: fixed;
                    bottom: 1.875rem;
                    right: 1.5625rem;
                    width: 4rem;
                    height: 4rem;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #007aff 0%, #0056b3 100%);
                    color: #fff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.75rem;
                    border: none;
                    box-shadow: 0 0.5rem 1.5625rem rgba(0, 122, 255, 0.5);
                    z-index: 1050;
                    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                }

                .fab-add:hover {
                    transform: scale(1.1) rotate(90deg);
                }

                @media (max-width:575.98px) {
                    .fc .fc-toolbar {
                        flex-direction: column !important;
                        gap: 1rem !important;
                        align-items: center !important;
                    }

                    .fc .fc-toolbar-title {
                        font-size: 1.1rem !important;
                        order: -1;
                        width: 100%;
                        text-align: center;
                        margin: 0 !important;
                    }

                    .fc .fc-toolbar-chunk {
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        gap: 0.3rem;
                        width: 100%;
                    }

                    .fc-button {
                        padding: 0.5rem 0.6rem !important;
                        font-size: 0.75rem !important;
                        border-radius: 8px !important;
                        flex: 1 1 auto;
                    }

                    .fc-addPekerjaan-button {
                        display: none !important;
                    }

                    .fc-custom-event {
                        padding: 1rem 1rem 1rem 1rem !important;
                        border-radius: 0.5rem !important;
                        min-height: 5rem !important;
                    }

                    .fc-ce-title {
                        font-size: 0.7rem !important;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                    }

                    .fc .fc-daygrid-day-frame {
                        min-height: 6.25rem !important;
                    }

                    .modal-footer {
                        flex-direction: column-reverse;
                        padding: 1.25rem;
                    }

                    .modal-footer .btn {
                        width: 100%;
                        margin: 0 !important;
                    }
                }
            </style>

            <script>
                var currentUserNpp = '<?php echo e($npp ?? ''); ?>';
                window.addEventListener('load', function () {
                    var el = document.getElementById('calendar');
                    if (!el || !window.FullCalendar) return;

                    var isMobile = window.innerWidth < 576;
                    var hLeft = isMobile ? 'prev,next today filterRevisi' : 'prev,next today addPekerjaan filterRevisi';
                    var hRight = isMobile ? 'listMonth,dayGridMonth' : 'dayGridMonth,listMonth';
                    var initialDate = new Date().toISOString().split('T')[0];

                    // Helper untuk escape JS string
                    function escapeJs(str) {
                        return String(str || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
                    }

                    // Fungsi Preview Dokumen (Universal)
                    window.viewDocument = function (fileName, title) {
                        const viewerModal = new bootstrap.Modal(document.getElementById('docViewerModal'));
                        const iframe = document.getElementById('docViewerIframe');
                        const titleEl = document.getElementById('docViewerTitle');
                        const downloadBtn = document.getElementById('btnDownloadActual');
                        const loader = document.getElementById('docLoader');

                        const fileUrl = `<?php echo site_url('uploads/'); ?>${fileName}`;
                        const encodedUrl = encodeURIComponent(fileUrl);
                        const ext = fileName.split('.').pop().toLowerCase();

                        titleEl.textContent = `Pratinjau: ${title}`;
                        downloadBtn.href = fileUrl;
                        loader.classList.remove('d-none');
                        iframe.src = '';

                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                            iframe.src = fileUrl;
                        } else if (ext === 'pdf') {
                            iframe.src = fileUrl;
                        } else if (['xlsx', 'xls', 'doc', 'docx', 'ppt', 'pptx'].includes(ext)) {
                            iframe.src = `https://docs.google.com/viewer?url=${encodedUrl}&embedded=true`;
                        } else {
                            iframe.src = fileUrl;
                        }
                        viewerModal.show();
                    };

                    function showEventDetail(ev) {
                        function escapeHtml(s) {
                            return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                        }

                        var props = ev.extendedProps || {};
                        var tglMulai = props.tgl_mulai || ev.startStr || '';
                        var tglSelesai = props.tgl_selesai || '';
                        var tanggalText = tglSelesai ? (tglMulai + ' — ' + tglSelesai) : tglMulai;

                        var titleEl = document.getElementById('pj_detail_title');
                        var tanggalEl = document.getElementById('pj_detail_tanggal');
                        var reporterEl = document.getElementById('pj_detail_reporter');
                        var descEl = document.getElementById('pj_detail_desc');
                        var assigneesBody = document.getElementById('pj_detail_assignees_body');
                        var uploadContainer = document.getElementById('pj_detail_upload_container');
                        var doneBtn = document.getElementById('pj_detail_done');
                        var alertEl = document.getElementById('pj_detail_alert');

                        var rawTitle = ev.title || '';
                        var cleanTitle = rawTitle.replace(/^\[.*?\]\s*/, '');
                        if (titleEl) titleEl.textContent = cleanTitle;
                        if (tanggalEl) tanggalEl.textContent = tanggalText;
                        if (reporterEl) reporterEl.textContent = props.reporter_name || 'Manager';
                        if (descEl) descEl.innerHTML = (props.description || '').replace(/\n/g, '<br>') || '<em class="text-muted">Tidak ada deskripsi</em>';

                        if (alertEl) {
                            alertEl.classList.add('d-none');
                            alertEl.classList.remove('alert-danger', 'alert-success', 'alert-warning');
                            alertEl.textContent = '';
                        }

                        // Render Assignees Table
                        var assigneesHtml = '';
                        var currentUserNeedsToUpload = false;
                        var currentUserTaskData = null;

                        var assignees = props.assignees || [];
                        if (assignees.length === 0) {
                            assigneesHtml = '<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data pelaksana</td></tr>';
                        } else {
                            assignees.forEach(function (a) {
                                var statLower = (a.status || '').toLowerCase();
                                var isDone = ['done', 'selesai', 'completed'].includes(statLower);
                                var isRevisi = statLower === 'revisi';

                                var badgeClass = isDone ? 'text-bg-success' : (isRevisi ? 'text-bg-danger' : 'text-bg-primary');
                                var statText = isDone ? 'Selesai' : (isRevisi ? 'Revisi' : 'Open');

                                var catatanRevisi = (isRevisi && a.catatan_revisi) ? escapeHtml(a.catatan_revisi) : '-';

                                var actionHtml = '<span class="text-muted small">Belum ada</span>';
                                if (a.lampiran) {
                                    actionHtml = '<button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 fw-bold" onclick="viewDocument(\'' + escapeJs(a.lampiran) + '\', \'' + escapeJs(cleanTitle) + '\')"><i class="bi bi-eye me-1"></i> Bukti</button>';
                                }

                                assigneesHtml += '<tr>' +
                                    '<td><div class="fw-bold text-dark">' + escapeHtml(a.nama) + '</div><small class="text-muted">' + escapeHtml(a.npp) + '</small></td>' +
                                    '<td class="text-center"><span class="badge ' + badgeClass + '">' + statText + '</span></td>' +
                                    '<td class="text-center small text-danger fw-semibold">' + catatanRevisi + '</td>' +
                                    '<td class="text-center">' + actionHtml + '</td>' +
                                    '</tr>';

                                // Cek apakah user yang login perlu upload
                                if (currentUserNpp && a.npp === currentUserNpp && !isDone) {
                                    currentUserNeedsToUpload = true;
                                    currentUserTaskData = a;
                                }
                            });
                        }

                        if (assigneesBody) assigneesBody.innerHTML = assigneesHtml;

                        // Handle Upload Form Visibility for Current User
                        if (uploadContainer) {
                            if (currentUserNeedsToUpload && currentUserTaskData) {
                                uploadContainer.classList.remove('d-none');

                                if (doneBtn) {
                                    doneBtn.onclick = function () {
                                        var fd = new FormData();
                                        fd.append('id', currentUserTaskData.id ? String(currentUserTaskData.id).replace('real-', '') : '');

                                        var fileInput = document.getElementById('pj_detail_file');
                                        if (!fileInput || !fileInput.files[0]) {
                                            Swal.fire('Perhatian', 'Mohon pilih file bukti pekerjaan terlebih dahulu.', 'warning');
                                            return;
                                        }
                                        if (fileInput.files[0].size > 5 * 1024 * 1024) {
                                            Swal.fire('Perhatian', 'Ukuran file maksimal 5MB.', 'warning');
                                            return;
                                        }

                                        fd.append('lampiran', fileInput.files[0]);

                                        if (props.is_master) {
                                            fd.append('master_tugas_id', props.master_tugas_id || '');
                                            fd.append('tgl_mulai', tglMulai);
                                            fd.append('token', currentUserTaskData.occ_token || '');
                                        }

                                        this.disabled = true;
                                        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

                                        fetch('api/mark_done.php', { method: 'POST', body: fd })
                                            .then(r => r.json()).then(json => {
                                                if (json.success) location.reload();
                                                else {
                                                    Swal.fire('Gagal', json.message, 'error');
                                                    this.disabled = false;
                                                    this.innerHTML = 'Upload & Selesai';
                                                }
                                            }).catch(err => {
                                                Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                                                this.disabled = false;
                                                this.innerHTML = 'Upload & Selesai';
                                            });
                                    };
                                }

                                // Tampilkan Peringatan Revisi Pribadi
                                if (currentUserTaskData.status === 'revisi' && currentUserTaskData.catatan_revisi) {
                                    if (alertEl) {
                                        alertEl.classList.remove('d-none');
                                        alertEl.classList.add('alert-danger');
                                        alertEl.innerHTML = '<strong><i class="bi bi-exclamation-octagon-fill me-2"></i> TUGAS ANDA DITOLAK:</strong><br>' + escapeHtml(currentUserTaskData.catatan_revisi);
                                    }
                                }

                                var fileInput = document.getElementById('pj_detail_file');
                                if (fileInput) fileInput.value = '';

                            } else {
                                uploadContainer.classList.add('d-none');
                            }
                        }

                        var bsDetailModal = new bootstrap.Modal(document.getElementById('pekerjaanDetailModal'));
                        bsDetailModal.show();
                    }

                    var calendar = new FullCalendar.Calendar(el, {
                        initialView: isMobile ? 'listMonth' : 'dayGridMonth',
                        initialDate: initialDate,
                        customButtons: {
                            addPekerjaan: { text: 'Tambah Pekerjaan', click: function () { window.openPekerjaanModal(); } },
                            filterRevisi: {
                                text: 'Cek Revisi',
                                click: function (e) {
                                    var currentSrc = calendar.getEventSources()[0];
                                    if (!currentSrc) return;
                                    var isRevisiMode = currentSrc.url.includes('status=revisi');
                                    currentSrc.remove();
                                    if (isRevisiMode) {
                                        calendar.changeView(isMobile ? 'listMonth' : 'dayGridMonth');
                                        calendar.addEventSource('api/events_pekerjaan.php?status=open');
                                        e.target.style.backgroundColor = ''; e.target.innerHTML = 'Cek Revisi';
                                    } else {
                                        calendar.changeView('listYear');
                                        calendar.addEventSource('api/events_pekerjaan.php?status=revisi');
                                        e.target.style.backgroundColor = '#f44336'; e.target.style.color = '#fff'; e.target.innerHTML = 'Kembali';
                                    }
                                }
                            }
                        },
                        headerToolbar: { left: hLeft, center: 'title', right: hRight },
                        events: 'api/events_pekerjaan.php?status=open',
                        dayMaxEventRows: isMobile ? 2 : 6,
                        locale: 'id',
                        buttonText: {
                            today: 'Hari ini',
                            month: 'Bulan',
                            week: 'Minggu',
                            list: 'Daftar'
                        },
                        eventClick: function (info) { showEventDetail(info.event); },
                        eventContent: function (arg) {
                            var ev = arg.event;
                            var props = ev.extendedProps || {};
                            var periode = (props.periode || '').toLowerCase();
                            var statusVal = (props.status || '').toLowerCase();

                            var colorClass = 'ev-manual';
                            if (periode === 'harian') colorClass = 'ev-harian';
                            else if (periode === 'mingguan') colorClass = 'ev-mingguan';
                            else if (periode === 'bulanan') colorClass = 'ev-bulanan';
                            else if (periode === 'triwulan') colorClass = 'ev-triwulan';

                            if (statusVal === 'revisi') colorClass = 'ev-revisi';

                            // Logika dot dihapus agar selalu merender kartu (Status Bar)
                            var titleHtml = '<div class="fc-ce-title">' + (ev.title || '') + '</div>';
                            return { html: '<div class="fc-custom-event ' + colorClass + '">' + titleHtml + '</div>' };
                        }
                    });
                    calendar.render();
                    window.pekerjaanCalendar = calendar;

                    var saveBtn = document.getElementById('pj_save');
                    if (saveBtn) {
                        saveBtn.onclick = function () {
                            var form = document.getElementById('pekerjaanForm');
                            if (!form.checkValidity()) { form.reportValidity(); return; }
                            fetch('api/create_pekerjaan.php', { method: 'POST', body: new FormData(form) })
                                .then(r => r.json()).then(json => {
                                    if (json.success) { location.reload(); }
                                    else { Swal.fire('Gagal', json.message, 'error'); }
                                }).catch(err => {
                                    Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
                                });
                        };
                    }
                });

                window.openPekerjaanModal = function (dateStr) {
                    var modalEl = document.getElementById('pekerjaanModal');
                    var bsModal = new bootstrap.Modal(modalEl);
                    document.getElementById('pj_mulai').value = dateStr || '';
                    document.getElementById('pj_selesai').value = dateStr || '';
                    bsModal.show();
                };
            </script>

            <!-- Modal for creating pekerjaan -->
            <div class="modal fade" id="pekerjaanModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Pekerjaan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="pekerjaanForm">
                                <div class="mb-3"><label class="form-label">Judul</label><input name="judul"
                                        class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi"
                                        class="form-control" rows="3"></textarea></div>
                                <div class="mb-3"><label class="form-label">Ditugaskan ke</label>
                                    <select name="assigned_to_npp" class="form-select" required>
                                        <option value="">-- Pilih Pegawai --</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?php echo e($emp['npp']); ?>"><?php echo e($emp['nama_emp']); ?>
                                            </option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6"><label class="form-label">Mulai</label><input type="date"
                                            name="tgl_mulai" id="pj_mulai" class="form-control" required></div>
                                    <div class="col-6"><label class="form-label">Selesai</label><input type="date"
                                            name="tgl_selesai" id="pj_selesai" class="form-control" required></div>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label class="form-label fw-bold"><i class="bi bi-palette me-2"></i>Kategori & Warna
                                        Kalender</label>
                                    <select name="periode" class="form-select border-primary" required>
                                        <option value="manual">Manual (Default - Abu-abu)</option>
                                        <option value="harian">Harian (Hijau)</option>
                                        <option value="mingguan">Mingguan (Kuning)</option>
                                        <option value="bulanan">Bulanan (Biru)</option>
                                        <option value="triwulan">Triwulan (Oranye)</option>
                                    </select>
                                    <small class="text-muted">Pilihan ini menentukan warna kartu di kalender dan label
                                        di dashboard.</small>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-app-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" id="pj_save" class="btn btn-app-primary px-5">Simpan</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail -->
            <div class="modal fade" id="pekerjaanDetailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="w-100">
                                <h5 class="modal-title mb-1" id="pj_detail_title"></h5>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><i class="bi bi-calendar-event me-1"></i><span
                                            id="pj_detail_tanggal"></span></span>
                                    <span><i class="bi bi-person-badge me-1"></i><span
                                            id="pj_detail_reporter"></span></span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div id="pj_detail_alert" class="alert d-none"></div>

                            <div class="mb-4">
                                <h6 class="text-uppercase text-muted small fw-bold mb-2">Deskripsi Tugas</h6>
                                <div id="pj_detail_desc" class="p-3 bg-light rounded" style="font-size: 0.95rem;"></div>
                            </div>

                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Daftar Pelaksana</h6>
                            <div class="table-responsive rounded border">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Pegawai</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Catatan Revisi</th>
                                            <th class="text-center">Aksi / Dokumen</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pj_detail_assignees_body">
                                        <!-- Render via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- File Upload Section (Only visible if current user needs to upload) -->
                            <div id="pj_detail_upload_container" class="mt-4 d-none">
                                <div class="card border-primary shadow-sm">
                                    <div class="card-body">
                                        <h6 class="text-primary fw-bold mb-3"><i
                                                class="bi bi-cloud-arrow-up me-2"></i>Tandai Tugas Saya Selesai</h6>
                                        <label class="form-label small">Upload Bukti Pekerjaan (Semua Tipe File, Maks
                                            5MB)</label>
                                        <input type="file" id="pj_detail_file" class="form-control mb-3">
                                        <button type="button" class="btn btn-primary w-100 fw-bold"
                                            id="pj_detail_done">Upload & Selesai</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-app-secondary w-100"
                                data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="fab-add d-md-none shadow-lg" onclick="window.openPekerjaanModal()"><i
                    class="bi bi-plus-lg"></i></button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>