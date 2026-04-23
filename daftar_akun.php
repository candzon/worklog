<?php
/**
 * daftar_akun.php (Controller)
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AccountModel.php';

ensure_session_started();

$roleId = $_SESSION['role_id'] ?? null;
$roleName = $_SESSION['role_name'] ?? null;
$isAuthorized = ($roleId == 1 || $roleId == 2 || strtolower((string)$roleName) === 'admin' || strtolower((string)$roleName) === 'manager');

if (!$isAuthorized) {
    http_response_code(403);
    require_once __DIR__ . '/includes/403.php';
    exit;
}

$model = new AccountModel($conn);

$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$accounts = $model->getAll($perPage, $offset);
$totalRows = $model->getCount();
$totalPages = ceil($totalRows / $perPage);
$roles = $model->getRoles();
$depts = $model->getDepts();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
    .card { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .table thead th { background: #f1f5f9; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 16px; }
    .table tbody td { padding: 16px; vertical-align: middle; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
    .btn-primary { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border: none; border-radius: 10px; padding: 8px 20px; font-weight: 600; }
    .btn-outline-primary { border-radius: 10px; border-color: #3b82f6; color: #3b82f6; }
    .btn-danger { border-radius: 10px; }
    .badge-role { background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; }
    .modal-content { border-radius: 20px; border: none; }
    .form-control, .form-select { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 10px 14px; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
</style>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Manajemen Akun</h3>
                    <p class="text-muted small mb-0">Kelola data akses pegawai dan penempatan bagian.</p>
                </div>
                <button class="btn btn-primary" id="btn-add-account">
                    <i class="bi bi-person-plus-fill me-2"></i> Tambah Pegawai
                </button>
            </div>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>NPP / ID Pegawai</th>
                                    <th>Nama Lengkap</th>
                                    <th>Bagian</th>
                                    <th>Hak Akses</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="account-table-body">
                                <?php include __DIR__ . '/api/get_account_list.php'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white border-0 py-3" id="account-pagination">
                        <?php echo render_pagination($totalRows, $perPage, $page, site_url('daftar_akun.php'), $_GET); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="accountForm">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Informasi Pegawai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="acc-alert" class="alert d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NPP (Nomor Pegawai)</label>
                            <input name="npp" id="acc_npp" class="form-control" placeholder="Contoh: 20910012" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input name="nama_emp" id="acc_nama" class="form-control" placeholder="Nama tanpa gelar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon</label>
                            <input name="telp" id="acc_telp" class="form-control" placeholder="08xxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="acc_jk" class="form-select">
                                <option value="0">Laki-laki</option>
                                <option value="1">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penempatan Bagian</label>
                            <select name="bagian_id" id="acc_dept" class="form-select" required>
                                <option value="">- Pilih Bagian -</option>
                                <?php foreach($depts as $d): ?>
                                    <option value="<?php echo $d['id_bagian']; ?>"><?php echo $d['nama_bagian']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role Akses</label>
                            <select name="role_id" id="acc_role" class="form-select" required>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo strtoupper($r['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btn-save-acc" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('accountForm');
    const tableBody = document.getElementById('account-table-body');
    const modalEl = document.getElementById('accountModal');
    const modal = new bootstrap.Modal(modalEl);
    const alertEl = document.getElementById('acc-alert');

    function refreshTable() {
        fetch('api/get_account_list.php' + window.location.search)
            .then(r => r.text())
            .then(html => { tableBody.innerHTML = html; });
    }

    document.getElementById('btn-add-account').addEventListener('click', () => {
        form.reset();
        document.getElementById('acc_npp').readOnly = false;
        alertEl.classList.add('d-none');
        modal.show();
    });

    tableBody.addEventListener('click', e => {
        const btnEdit = e.target.closest('.editAccBtn');
        const btnDelete = e.target.closest('.deleteAccBtn');

        if(btnEdit) {
            const data = JSON.parse(btnEdit.dataset.acc);
            document.getElementById('acc_npp').value = data.npp;
            document.getElementById('acc_npp').readOnly = true;
            document.getElementById('acc_nama').value = data.nama_emp;
            document.getElementById('acc_telp').value = data.telp;
            document.getElementById('acc_jk').value = data.jenis_kelamin;
            document.getElementById('acc_dept').value = data.nama_bagian; // Kolom di DB adalah nama_bagian tapi isinya ID
            document.getElementById('acc_role').value = data.role_id;
            alertEl.classList.add('d-none');
            modal.show();
        }

        if(btnDelete) {
            Swal.fire({
                title: 'Hapus Akun?',
                text: "Akses pegawai ini akan dicabut secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus Akun'
            }).then((result) => {
                if (result.isConfirmed) {
                    const npp = btnDelete.dataset.npp;
                    fetch('api/manage_account.php?action=delete', {
                        method:'POST',
                        headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:'npp='+npp
                    }).then(r => r.json()).then(res => { 
                        if(res.success) {
                            refreshTable();
                            Swal.fire('Terhapus!', 'Akun berhasil dihapus.', 'success');
                        } 
                    });
                }
            });
        }
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        const btnSave = document.getElementById('btn-save-acc');
        const originalText = btnSave.innerHTML;
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';
        
        fetch('api/manage_account.php?action=upsert', { method:'POST', body: new FormData(form) })
        .then(async r => {
            const res = await r.json();
            if(!r.ok) throw new Error(res.message || 'Error');
            return res;
        })
        .then(res => {
            modal.hide();
            refreshTable();
            Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
        })
        .catch(err => {
            alertEl.textContent = err.message;
            alertEl.classList.remove('d-none');
            alertEl.classList.add('alert-danger');
        })
        .finally(() => { 
            btnSave.disabled = false; 
            btnSave.innerHTML = originalText;
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
