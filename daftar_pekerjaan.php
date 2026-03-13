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
    $due = trim($_POST['due_date'] ?? '');

    if ($judul === '')
        $errors[] = 'Judul pekerjaan wajib diisi';


    if (isset($conn)) {
        $conn->query($createSql);

        $stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, npp, nama_emp, due_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $dueParam = $due !== '' ? $due : null;
            $stmt->bind_param('sssss', $judul, $deskripsi, $npp, $nama_emp, $dueParam);
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
            document.addEventListener('DOMContentLoaded', function () {
                var attempts = 0;
                function tryInit() {
                    attempts++;
                    var el = document.getElementById('calendar');
                    if (window.FullCalendar && el) {
                        var calendar = new FullCalendar.Calendar(el, {
                            initialView: 'dayGridMonth',
                            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
                            events: '<?php echo site_url("api/events_pekerjaan.php"); ?>',
                            dateClick: function(info){
                                // open modal to create job on clicked date
                                if (typeof window.openPekerjaanModal === 'function'){
                                    window.openPekerjaanModal(info.dateStr);
                                }
                            },
                            locale: 'id',
                            navLinks: true,
                            eventClick: function(info){
                                var props = info.event.extendedProps || {};
                                var d = props.description || '';
                                var status = props.status || '';
                                var assigned = props.ditugaskan || '';
                                var footerHtml = '';
                                var buttons = {
                                    confirmButtonText: 'Tutup',
                                };
                                // if current user is assignee and not already done, allow marking done
                                if (currentUserNpp && assigned && assigned === currentUserNpp && status !== 'done') {
                                    if (window.Swal) {
                                        Swal.fire({
                                            title: info.event.title,
                                            html: '<p>' + d + '</p><p><small>Ditugaskan: ' + (assigned) + '</small></p>',
                                            showCancelButton: true,
                                            confirmButtonText: 'Tutup',
                                            cancelButtonText: 'Tandai Selesai',
                                        }).then(function(res){
                                            if (res.dismiss === Swal.DismissReason.cancel) {
                                                // mark done
                                                fetch('<?php echo site_url("api/mark_done.php"); ?>', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ id: info.event.id }) })
                                                .then(r => r.json()).then(function(json){
                                                    if (json && json.success){
                                                        if (window.Swal) Swal.fire({icon:'success', title:'Selesai'});
                                                        if (window.pekerjaanCalendar) window.pekerjaanCalendar.refetchEvents();
                                                    } else {
                                                        if (window.Swal) Swal.fire({icon:'error', title:'Gagal', text: json.message || 'Gagal memperbarui'});
                                                    }
                                                }).catch(function(err){ console.error(err); if (window.Swal) Swal.fire({icon:'error', title:'Error'}); });
                                            }
                                        });
                                    }
                                } else {
                                    if (window.Swal){
                                        Swal.fire({title: info.event.title, html: '<p>' + d + '</p>'});
                                    } else {
                                        alert(info.event.title + '\n' + d);
                                    }
                                }
                            }
                        });
                        calendar.render();
                        // expose calendar for refetch
                        window.pekerjaanCalendar = calendar;
                    } else if (attempts < 60) {
                        setTimeout(tryInit, 250);
                    } else {
                        console.warn('FullCalendar did not load after multiple attempts.');
                    }
                }
                tryInit();
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
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Buat Pekerjaan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="pekerjaanForm">
                                            <div class="mb-3">
                                                <label class="form-label">Judul</label>
                                                <input name="judul" id="pj_judul" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea name="deskripsi" id="pj_deskripsi" class="form-control" rows="4"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Tanggal Selesai</label>
                                                <input type="date" name="due_date" id="pj_due" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ditugaskan ke</label>
                                                <select name="ditugaskan" id="pj_ditugaskan" class="form-select" required>
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

                        <script>
                        // handle opening modal from calendar and submitting via fetch
                        (function(){
                                var modalEl = document.getElementById('pekerjaanModal');
                                var bsModal = null;
                                function ensureModal(){
                                        if (!bsModal && window.bootstrap && modalEl) bsModal = new bootstrap.Modal(modalEl);
                                        return bsModal;
                                }

                                // expose helper to calendar dateClick
                                window.openPekerjaanModal = function(dateStr){
                                        ensureModal();
                                        document.getElementById('pj_due').value = dateStr || '';
                                        document.getElementById('pj_judul').value = '';
                                        document.getElementById('pj_deskripsi').value = '';
                                        if (bsModal) bsModal.show();
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
                                    <th>Due</th>
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
                                            <td><?php echo e($r['due_date']); ?></td>
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