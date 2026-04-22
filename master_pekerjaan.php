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

$masters = [];
$bagians = [];
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

    // Ambil data master dengan join ke tabel bagian
    $stmt = $conn->prepare('SELECT m.*, b.nama_bagian 
                            FROM master_tugas m 
                            LEFT JOIN bagian b ON m.bagian_id = b.id_bagian 
                            ORDER BY m.id DESC LIMIT ? OFFSET ?');
    if ($stmt) {
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $masters[] = $r;
        $stmt->close();
    }

    // Ambil list bagian untuk dropdown
    $rb = $conn->query("SELECT id_bagian, nama_bagian FROM bagian WHERE nama_bagian IN ('Apoteker', 'TTK') ORDER BY nama_bagian");
    if ($rb) {
        while ($r = $rb->fetch_assoc()) $bagians[] = $r;
        $rb->free();
    }
}
?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Master Pekerjaan (Relasi Bagian)</h3>

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
                                    <th>Periode</th>
                                    <th>Target Tgl</th>
                                    <th>Bagian Penerima</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($masters)): ?>
                                    <tr><td colspan="6" class="text-center text-muted">Belum ada master tugas.</td></tr>
                                <?php else: foreach ($masters as $m): ?>
                                    <tr>
                                        <td><?php echo e($m['id']); ?></td>
                                        <td><?php echo e($m['judul']); ?></td>
                                        <td><?php echo e($m['periode']); ?></td>
                                        <td><?php echo e($m['target_tgl'] ? format_date_id($m['target_tgl']) : '-'); ?></td>
                                        <td><span class="badge text-bg-info"><?php echo e(strtoupper($m['nama_bagian'] ?: '-')); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary editMasterBtn" data-master='<?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?>'>Edit</button>
                                            <form method="post" class="d-inline deleteMasterForm" action="api/hapus_master_pekerjaan.php" data-swal-confirm="Ya, hapus">
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
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer clearfix">
                        <?php echo render_pagination($totalRows, $perPage, $page, site_url('master_pekerjaan.php'), $_GET); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="masterModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form method="post" id="masterForm" action="api/create_master_pekerjaan.php">
                            <input type="hidden" name="id" id="m_id">
                            <div class="modal-header">
                                <h5 class="modal-title">Form Master Pekerjaan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Judul Pekerjaan</label>
                                    <input name="judul" id="m_judul" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="deskripsi" id="m_deskripsi" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Periode</label>
                                        <select name="periode" id="m_periode" class="form-select">
                                            <option value="bulanan">Bulanan</option>
                                            <option value="harian">Harian</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal Target</label>
                                        <input type="date" name="target_tgl" id="m_target_tgl" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Bagian Penerima</label>
                                        <select name="bagian_id" id="m_bagian_id" class="form-select" required>
                                            <option value="">- Pilih Bagian -</option>
                                            <?php foreach ($bagians as $bag): ?>
                                                <option value="<?php echo e($bag['id_bagian']); ?>"><?php echo e($bag['nama_bagian']); ?></option>
                                            <?php endforeach; ?>
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
    const modal = new bootstrap.Modal(document.getElementById('masterModal'));
    
    const fields = {
        id: document.getElementById('m_id'),
        judul: document.getElementById('m_judul'),
        deskripsi: document.getElementById('m_deskripsi'),
        periode: document.getElementById('m_periode'),
        target_tgl: document.getElementById('m_target_tgl'),
        bagian_id: document.getElementById('m_bagian_id')
    };

    document.getElementById('createMasterBtn').addEventListener('click', function () {
        form.action = 'api/create_master_pekerjaan.php';
        Object.values(fields).forEach(f => { if(f) f.value = ''; });
        fields.periode.value = 'bulanan';
    });

    document.querySelectorAll('.editMasterBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            let data = {};
            try { data = JSON.parse(this.getAttribute('data-master') || '{}'); } catch (e) { data = {}; }
            form.action = 'api/edit_master_pekerjaan.php';
            
            fields.id.value = data.id || '';
            fields.judul.value = data.judul || '';
            fields.deskripsi.value = data.deskripsi || '';
            fields.periode.value = data.periode || 'bulanan';
            fields.target_tgl.value = data.target_tgl || '';
            fields.bagian_id.value = data.bagian_id || '';
            
            modal.show();
        });
    });
});
</script>
