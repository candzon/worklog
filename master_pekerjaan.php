<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

require_app('config/database.php');
require_app('functions/helpers.php');

ensure_session_started();

// Detect optional column support for npp_manager
$hasMasterNppManagerCol = false;
if (isset($conn)) {
    $colRes = $conn->query("SHOW COLUMNS FROM master_tugas LIKE 'npp_manager'");
    if ($colRes) {
        $hasMasterNppManagerCol = ($colRes->num_rows > 0);
        $colRes->free();
    }
}

$roleName = function_exists('get_current_role_name') ? get_current_role_name($conn ?? null) : null;
$isManager = function_exists('role_is') ? role_is($roleName, 'manager') : (strtolower((string) $roleName) === 'manager');
if (!$isManager) {
    http_response_code(403);
    require_once __DIR__ . '/includes/403.php';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$message = '';
$masters = [];
$bagians = [];
$employees = [];
if (isset($conn)) {
    $perPage = 10;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($page < 1)
        $page = 1;

    $totalRows = 0;
    $rc = $conn->query('SELECT COUNT(*) AS cnt FROM master_tugas');
    if ($rc) {
        $rowCnt = $rc->fetch_assoc();
        $totalRows = (int) ($rowCnt['cnt'] ?? 0);
        $rc->free();
    }

    $totalPages = (int) ceil(max(1, $totalRows) / $perPage);
    if ($page > $totalPages)
        $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare('SELECT m.*, b.nama_bagian AS bagian_nama, e.nama_emp AS pemilik_nama FROM master_tugas m LEFT JOIN bagian b ON m.bagian_id = b.id_bagian LEFT JOIN employee e ON m.npp = e.npp ORDER BY m.id DESC LIMIT ? OFFSET ?');
    if ($stmt) {
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc())
                $masters[] = $r;
            $res->free();
        }
        $stmt->close();
    }

    $rb = $conn->query('SELECT id_bagian, nama_bagian FROM bagian ORDER BY nama_bagian');
    if ($rb) {
        while ($r = $rb->fetch_assoc())
            $bagians[] = $r;
        $rb->free();
    }

    $re = $conn->query('SELECT npp, nama_emp FROM employee ORDER BY nama_emp');
    if ($re) {
        while ($r = $re->fetch_assoc())
            $employees[] = $r;
        $re->free();
    }
}
?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Master Pekerjaan</h3>

            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#masterModal"
                    id="createMasterBtn">Buat Master Baru</button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Judul</th>
                                    <th>Deskripsi</th>
                                    <th>Periode</th>
                                    <th>Bagian</th>
                                    <th>Pemilik</th>
                                    <th>Dibuat Pada</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($masters)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Belum ada master tugas.</td>
                                    </tr>
                                <?php else:
                                    foreach ($masters as $m): ?>
                                        <tr>
                                            <td><?php echo e($m['id']); ?></td>
                                            <td><?php echo e($m['judul']); ?></td>
                                            <td><?php echo e(mb_strimwidth($m['deskripsi'], 0, 80, '...')); ?></td>
                                            <td><?php echo e($m['periode'] ?? ''); ?></td>
                                            <td><?php echo e($m['bagian_nama'] ?? ''); ?></td>
                                            <td><?php echo e($m['pemilik_nama'] ?? $m['npp'] ?? ''); ?></td>
                                            <td><?php echo e(!empty($m['created_at']) ? format_datetime_id($m['created_at']) : ''); ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary editMasterBtn"
                                                    data-master='<?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?>'>Edit</button>
                                                <form method="post" class="d-inline deleteMasterForm" action="api/hapus_master_pekerjaan.php"
                                                    data-swal-title="Hapus master ini?"
                                                    data-swal-text="Data master akan dihapus permanen."
                                                    data-swal-confirm="Ya, hapus"
                                                    data-swal-cancel="Batal"
                                                >
                                                    <input type="hidden" name="id" value="<?php echo e($m['id']); ?>">
                                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (isset($totalPages) && $totalPages > 1):
                    $buildPageUrl = function ($p) {
                        $p = (int) $p;
                        if ($p < 1)
                            $p = 1;
                        $params = $_GET;
                        $params['page'] = $p;
                        return site_url('master_pekerjaan.php') . '?' . http_build_query($params);
                    };
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    ?>
                    <div class="card-footer d-flex justify-content-end">
                        <nav aria-label="Pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo e($buildPageUrl($page - 1)); ?>"
                                        tabindex="-1">Prev</a>
                                </li>
                                <?php for ($p = $start; $p <= $end; $p++): ?>
                                    <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo e($buildPageUrl($p)); ?>"><?php echo e($p); ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo e($buildPageUrl($page + 1)); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="masterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" id="masterForm" action="api/create_master_pekerjaan.php"
                            data-swal-cancel="Batal"
                        >
                            <input type="hidden" name="id" id="m_id">
                            <div class="modal-header">
                                <h5 class="modal-title">Master Pekerjaan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Judul</label>
                                    <input name="judul" id="m_judul" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="deskripsi" id="m_deskripsi" class="form-control"
                                        rows="3"></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Pemilik (NPP)</label>
                                        <select name="npp" id="m_npp" class="form-select">
                                            <option value="">- Pilih Pemilik -</option>
                                            <?php foreach ($employees as $emp): ?>
                                                <option value="<?php echo e($emp['npp']); ?>">
                                                    <?php echo e($emp['nama_emp']); ?> (<?php echo e($emp['npp']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Bagian</label>
                                        <select name="bagian_id" id="m_bagian" class="form-select">
                                            <option value="">- Pilih Bagian -</option>
                                            <option value="12">Apoteker</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Periode</label>
                                        <select name="periode" id="m_periode" class="form-select">
                                            <option value="bulanan">Bulanan</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('masterForm');
        const modalEl = document.getElementById('masterModal');
        const modal = new bootstrap.Modal(modalEl);
        const idField = document.getElementById('m_id');
        const judulField = document.getElementById('m_judul');
        const deskripsiField = document.getElementById('m_deskripsi');
        const nppField = document.getElementById('m_npp');
        const bagianField = document.getElementById('m_bagian');
        const periodeField = document.getElementById('m_periode');

        // Create: set action to create endpoint and clear fields
        const createBtn = document.getElementById('createMasterBtn');
        if (createBtn) {
            createBtn.addEventListener('click', function () {
                form.action = 'api/create_master_pekerjaan.php';

                // SweetAlert confirm text for CREATE
                form.dataset.swalIcon = 'question';
                form.dataset.swalTitle = 'Simpan master baru?';
                form.dataset.swalText = 'Master tugas akan ditambahkan.';
                form.dataset.swalConfirm = 'Ya, simpan';
                form.dataset.swalCancel = 'Batal';

                idField.value = '';
                judulField.value = '';
                deskripsiField.value = '';
                if (nppField) nppField.value = '';
                if (bagianField) bagianField.value = '';
                if (periodeField) periodeField.value = 'bulanan';
            });
        }

        // Edit: set action to edit endpoint and populate fields
        document.querySelectorAll('.editMasterBtn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                let data = {};
                try { data = JSON.parse(this.getAttribute('data-master') || '{}'); } catch (e) { data = {}; }
                form.action = 'api/edit_master_pekerjaan.php';

                // SweetAlert confirm text for EDIT
                form.dataset.swalIcon = 'question';
                form.dataset.swalTitle = 'Simpan perubahan?';
                form.dataset.swalText = 'Perubahan master tugas akan disimpan.';
                form.dataset.swalConfirm = 'Ya, simpan';
                form.dataset.swalCancel = 'Batal';

                idField.value = data.id || '';
                judulField.value = data.judul || '';
                deskripsiField.value = data.deskripsi || '';
                if (nppField) nppField.value = data.npp || '';
                if (bagianField) bagianField.value = data.bagian_id || '';
                if (periodeField) periodeField.value = data.periode || 'bulanan';
                modal.show();
            });
        });

    });
</script>

<?php
// SweetAlert confirm for delete forms
if (function_exists('render_swal_confirm_forms')) {
    render_swal_confirm_forms('form.deleteMasterForm');
    render_swal_confirm_forms('#masterForm');
}
?>