<?php
/**
 * daftar_akun.php
 */
require_once __DIR__ . '/functions/helpers.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/AccountModel.php';

ensure_session_started();

$roleName = $_SESSION['role_name'] ?? null;
if (strtolower((string)$roleName) !== 'admin') {
    http_response_code(403);
    require_once __DIR__ . '/includes/403.php';
    exit;
}

$model = new AccountModel($conn);

$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$totalRows = $model->getCount();
$totalPages = ceil($totalRows / $perPage);
$roles = $model->getRoles();
$depts = $model->getDepts();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Manajemen Akun Pegawai (AJAX CRUD)</h3>
            <div class="mb-3"><button class="btn btn-primary" id="btn-add-account">Tambah Pegawai</button></div>
            
            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>NPP</th><th>Nama</th><th>Bagian</th><th>Role</th><th>Aksi</th></tr></thead>
                        <tbody id="account-table-body">
                            <?php include __DIR__ . '/api/get_account_list.php'; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix" id="account-pagination">
                    <?php if ($totalPages > 1): ?>
                        <?php echo render_pagination($totalRows, $perPage, $page, site_url('daftar_akun.php'), $_GET); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="accountForm">
                <div class="modal-header"><h5>Form Pegawai</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div id="acc-alert" class="alert d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">NPP</label><input name="npp" id="acc_npp" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Nama Lengkap</label><input name="nama_emp" id="acc_nama" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input name="telp" id="acc_telp" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Jenis Kelamin</label><select name="jenis_kelamin" id="acc_jk" class="form-select"><option value="0">Laki-laki</option><option value="1">Perempuan</option></select></div>
                        <div class="col-md-6"><label class="form-label">Bagian</label>
                            <select name="bagian_id" id="acc_dept" class="form-select" required>
                                <option value="">- Pilih Bagian -</option>
                                <?php foreach($depts as $d): ?><option value="<?php echo $d['id_bagian']; ?>"><?php echo $d['nama_bagian']; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Role</label>
                            <select name="role_id" id="acc_role" class="form-select" required>
                                <option value="">- Pilih Role -</option>
                                <?php foreach($roles as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" id="btn-save-acc" class="btn btn-primary">Simpan</button></div>
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
        if(e.target.classList.contains('editAccBtn')) {
            const data = JSON.parse(e.target.dataset.acc);
            document.getElementById('acc_npp').value = data.npp;
            document.getElementById('acc_npp').readOnly = true;
            document.getElementById('acc_nama').value = data.nama_emp;
            document.getElementById('acc_telp').value = data.telp;
            document.getElementById('acc_jk').value = data.jenis_kelamin;
            document.getElementById('acc_dept').value = data.bagian_id;
            document.getElementById('acc_role').value = data.role_id;
            alertEl.classList.add('d-none');
            modal.show();
        }
        if(e.target.classList.contains('deleteAccBtn')) {
            if(confirm('Yakin ingin menghapus akun ini?')) {
                const npp = e.target.dataset.npp;
                fetch('api/manage_account.php?action=delete', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'npp='+npp
                }).then(r => r.json()).then(res => { if(res.success) refreshTable(); });
            }
        }
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        const btnSave = document.getElementById('btn-save-acc');
        btnSave.disabled = true;
        
        fetch('api/manage_account.php?action=upsert', { method:'POST', body: new FormData(form) })
        .then(async r => {
            const res = await r.json();
            if(!r.ok) throw new Error(res.message || 'Error');
            return res;
        })
        .then(res => {
            modal.hide();
            refreshTable();
            if(window.Swal) Swal.fire('Berhasil', res.message, 'success');
        })
        .catch(err => {
            alertEl.textContent = err.message;
            alertEl.classList.remove('d-none');
            alertEl.classList.add('alert-danger');
        })
        .finally(() => { btnSave.disabled = false; });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
