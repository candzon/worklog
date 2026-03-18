<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

require_app('config/database.php');
require_app('functions/helpers.php');

ensure_session_started();

$roleName = function_exists('get_current_role_name') ? get_current_role_name($conn ?? null) : null;
$isManager = function_exists('role_is') ? role_is($roleName, 'manager') : (strtolower((string) $roleName) === 'manager');
if (!$isManager) {
    http_response_code(403);
    require_once __DIR__ . '/includes/403.php';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = !empty($_POST['id']) ? intval($_POST['id']) : null;
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $npp = trim($_POST['npp'] ?? '');
        $bagian_id = isset($_POST['bagian_id']) && $_POST['bagian_id'] !== '' ? intval($_POST['bagian_id']) : null;
        $periode = $_POST['periode'] ?? 'bulanan';

        if ($judul === '') {
            $message = 'Judul wajib diisi.';
        } else {
            if (isset($conn)) {
                if ($id) {
                    $stmt = $conn->prepare("UPDATE master_tugas SET judul = ?, deskripsi = ?, npp = ?, bagian_id = ?, periode = ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param('sssisi', $judul, $deskripsi, $npp, $bagian_id, $periode, $id);
                        $stmt->execute();
                        $stmt->close();
                        flash_swal('success', 'Tersimpan', 'Master tugas diperbarui.');
                    }
                } else {
                    $stmt = $conn->prepare("INSERT INTO master_tugas (judul, deskripsi, npp, bagian_id, periode, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    if ($stmt) {
                        $stmt->bind_param('sssis', $judul, $deskripsi, $npp, $bagian_id, $periode);
                        $stmt->execute();
                        $stmt->close();
                        flash_swal('success', 'Tersimpan', 'Master tugas ditambahkan.');
                    }
                }
            }
            header('Location: ' . site_url('master_pekerjaan.php'));
            exit;
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0 && isset($conn)) {
            $stmt = $conn->prepare('DELETE FROM master_tugas WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
                flash_swal('success', 'Dihapus', 'Master tugas dihapus.');
            }
        }
        header('Location: ' . site_url('master_pekerjaan.php'));
        exit;
    }
}

$masters = [];
$bagians = [];
$employees = [];
if (isset($conn)) {
    $perPage = 10;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($page < 1) $page = 1;

    $totalRows = 0;
    $rc = $conn->query('SELECT COUNT(*) AS cnt FROM master_tugas');
    if ($rc) {
        $rowCnt = $rc->fetch_assoc();
        $totalRows = (int) ($rowCnt['cnt'] ?? 0);
        $rc->free();
    }

    $totalPages = (int) ceil(max(1, $totalRows) / $perPage);
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $stmt = $conn->prepare('SELECT m.*, b.nama_bagian AS bagian_nama, e.nama_emp AS pemilik_nama FROM master_tugas m LEFT JOIN bagian b ON m.bagian_id = b.id_bagian LEFT JOIN employee e ON m.npp = e.npp ORDER BY m.id DESC LIMIT ? OFFSET ?');
    if ($stmt) {
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $masters[] = $r;
            $res->free();
        }
        $stmt->close();
    }

    $rb = $conn->query('SELECT id_bagian, nama_bagian FROM bagian ORDER BY nama_bagian');
    if ($rb) {
        while ($r = $rb->fetch_assoc()) $bagians[] = $r;
        $rb->free();
    }

    $re = $conn->query('SELECT npp, nama_emp FROM employee ORDER BY nama_emp');
    if ($re) {
        while ($r = $re->fetch_assoc()) $employees[] = $r;
        $re->free();
    }
}
?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Master Pekerjaan</h3>

            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#masterModal" id="createMasterBtn">Buat Master Baru</button>
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
                                    <tr><td colspan="8" class="text-center text-muted">Belum ada master tugas.</td></tr>
                                <?php else: foreach ($masters as $m): ?>
                                    <tr>
                                        <td><?php echo e($m['id']); ?></td>
                                        <td><?php echo e($m['judul']); ?></td>
                                        <td><?php echo e(mb_strimwidth($m['deskripsi'], 0, 80, '...')); ?></td>
                                        <td><?php echo e($m['periode'] ?? ''); ?></td>
                                        <td><?php echo e($m['bagian_nama'] ?? ''); ?></td>
                                        <td><?php echo e($m['pemilik_nama'] ?? $m['npp'] ?? ''); ?></td>
                                        <td><?php echo e(!empty($m['created_at']) ? format_datetime_id($m['created_at']) : ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary editMasterBtn" data-master='<?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?>'>Edit</button>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Hapus master ini?');">
                                                <input type="hidden" name="action" value="delete">
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
                        if ($p < 1) $p = 1;
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
                                    <a class="page-link" href="<?php echo e($buildPageUrl($page - 1)); ?>" tabindex="-1">Prev</a>
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
                        <form method="post" id="masterForm">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" id="m_id">
                            <div class="modal-header">
                                <h5 class="modal-title">Master Pekerjaan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                        <label class="form-label">Judul</label>
                                        <input name="judul" id="m_judul" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea name="deskripsi" id="m_deskripsi" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Pemilik (NPP)</label>
                                            <select name="npp" id="m_npp" class="form-select">
                                                <option value="">- Pilih Pemilik -</option>
                                                <?php foreach ($employees as $emp): ?>
                                                    <option value="<?php echo e($emp['npp']); ?>"><?php echo e($emp['nama_emp']); ?> (<?php echo e($emp['npp']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bagian</label>
                                            <select name="bagian_id" id="m_bagian" class="form-select">
                                                <option value="">- Pilih Bagian -</option>
                                                <?php foreach ($bagians as $b): ?>
                                                    <option value="<?php echo e($b['id_bagian']); ?>"><?php echo e($b['nama_bagian']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Periode</label>
                                            <select name="periode" id="m_periode" class="form-select">
                                                <option value="bulanan">Bulanan</option>
                                                <option value="mingguan">Mingguan</option>
                                                <option value="harian">Harian</option>
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
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.editMasterBtn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var data = JSON.parse(this.getAttribute('data-master'));
            document.getElementById('m_id').value = data.id || '';
            document.getElementById('m_judul').value = data.judul || '';
            document.getElementById('m_deskripsi').value = data.deskripsi || '';
            // set pemilik, bagian, periode
            if (document.getElementById('m_npp')) document.getElementById('m_npp').value = data.npp || '';
            if (document.getElementById('m_bagian')) document.getElementById('m_bagian').value = data.bagian_id || '';
            if (document.getElementById('m_periode')) document.getElementById('m_periode').value = data.periode || 'bulanan';
            var modal = new bootstrap.Modal(document.getElementById('masterModal'));
            modal.show();
        });
    });
    document.getElementById('createMasterBtn').addEventListener('click', function(){
        document.getElementById('m_id').value = '';
        document.getElementById('m_judul').value = '';
        document.getElementById('m_deskripsi').value = '';
        if (document.getElementById('m_npp')) document.getElementById('m_npp').value = '';
        if (document.getElementById('m_bagian')) document.getElementById('m_bagian').value = '';
        if (document.getElementById('m_periode')) document.getElementById('m_periode').value = 'bulanan';
    });
});
</script>
