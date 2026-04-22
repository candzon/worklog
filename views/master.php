<!-- views/master.php -->
<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Master Pekerjaan </h3>
            <div class="mb-3">
                <button type="button" class="btn btn-primary" id="btnAddNewMaster">Buat Master Baru</button>
            </div>

            <div class="card">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr><th>#</th><th>Judul</th><th>Periode</th><th>Target Tgl</th><th>Bagian Penerima</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="masterTableBody">
                            <?php include __DIR__ . '/../api/get_master_list.php'; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5>Form Master Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="modalAlert" class="alert d-none"></div>
                <form id="formMaster">
                    <input type="hidden" name="id" id="fieldId">
                    <div class="mb-3"><label class="form-label">Judul</label><input name="judul" id="fieldJudul" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="fieldDeskripsi" class="form-control" rows="3"></textarea></div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Periode</label><select name="periode" id="fieldPeriode" class="form-select"><option value="bulanan">Bulanan</option></select></div>
                        <div class="col-md-4"><label class="form-label">Tanggal Target</label><input type="date" name="target_tgl" id="fieldTargetTgl" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Bagian Penerima</label>
                            <select name="bagian_id" id="fieldBagianId" class="form-select" required><option value="">- Pilih Bagian -</option>
                                <?php foreach ($bagians as $bag): ?><option value="<?php echo e($bag['id_bagian']); ?>"><?php echo e($bag['nama_bagian']); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" id="btnSaveMaster" class="btn btn-primary">Simpan</button></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('masterTableBody');
    const formEl = document.getElementById('formMaster');
    const modal = new bootstrap.Modal(document.getElementById('modalMaster'));
    const alertEl = document.getElementById('modalAlert');

    let currentMode = 'create';

    function refreshTable() {
        fetch('api/get_master_list.php' + window.location.search)
            .then(r => r.text())
            .then(html => { tableBody.innerHTML = html; });
    }

    document.getElementById('btnAddNewMaster').addEventListener('click', () => {
        currentMode = 'create';
        formEl.reset();
        document.getElementById('fieldId').value = '';
        alertEl.classList.add('d-none');
        modal.show();
    });

    tableBody.addEventListener('click', function(e) {
        const btnEdit = e.target.closest('.editMasterBtn');
        const btnDelete = e.target.closest('.deleteMasterBtn');
        
        if (btnEdit) {
            currentMode = 'edit';
            const data = JSON.parse(btnEdit.dataset.master || '{}');
            document.getElementById('fieldId').value = data.id;
            document.getElementById('fieldJudul').value = data.judul;
            document.getElementById('fieldDeskripsi').value = data.deskripsi;
            document.getElementById('fieldPeriode').value = data.periode;
            document.getElementById('fieldTargetTgl').value = data.target_tgl;
            document.getElementById('fieldBagianId').value = data.bagian_id;
            alertEl.classList.add('d-none');
            modal.show();
        }

        if (btnDelete) {
            if (window.Swal) {
                Swal.fire({ title: 'Hapus Master?', text: 'Data tidak bisa dikembalikan!', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus' }).then((result) => {
                    if (result.isConfirmed) executeDelete(btnDelete.dataset.id);
                });
            } else if (confirm('Yakin ingin menghapus?')) {
                executeDelete(btnDelete.dataset.id);
            }
        }
    });

    function executeDelete(id) {
        fetch('api/hapus_master_pekerjaan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        })
        .then(async r => {
            const res = await r.json();
            if (!r.ok) {
                if (res.type === 'restricted') {
                    Swal.fire({ title: 'Gagal Menghapus', text: res.message, icon: 'error', confirmButtonColor: '#3b82f6' });
                } else {
                    throw new Error(res.message || 'Server error');
                }
                return null;
            }
            return res;
        })
        .then(res => { 
            if(res && res.success) {
                refreshTable(); 
                Swal.fire({ title: 'Terhapus', text: 'Data master berhasil dihapus.', icon: 'success', timer: 1500, showConfirmButton: false });
            }
        })
        .catch(err => {
            if (err.message) Swal.fire('Error', err.message, 'error');
        });
    }

    document.getElementById('btnSaveMaster').addEventListener('click', function() {
        if (!formEl.checkValidity()) { formEl.reportValidity(); return; }
        
        if (window.Swal) {
            Swal.fire({ title: 'Simpan Data?', text: 'Pastikan data sudah benar.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Simpan' }).then((result) => {
                if (result.isConfirmed) executeSave();
            });
        } else {
            executeSave();
        }
    });

    function executeSave() {
        const url = (currentMode === 'edit') ? 'api/edit_master_pekerjaan.php' : 'api/create_master_pekerjaan.php';
        const btn = document.getElementById('btnSaveMaster');
        btn.disabled = true;

        fetch(url, { method: 'POST', body: new FormData(formEl) })
            .then(async r => {
                const res = await r.json();
                if (!r.ok) throw new Error(res.message || 'Server error');
                return res;
            })
            .then(res => {
                modal.hide();
                refreshTable();
                if (window.Swal) Swal.fire('Berhasil', res.message, 'success');
            })
            .catch(err => {
                alertEl.textContent = err.message;
                alertEl.classList.remove('d-none');
                alertEl.classList.add('alert-danger');
            })
            .finally(() => { btn.disabled = false; });
    }
});
</script>
