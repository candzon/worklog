<!-- views/master.php -->
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
    .card { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .table thead th { background: #f1f5f9; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 16px; }
    .table tbody td { padding: 16px; vertical-align: middle; color: #1e293b; border-bottom: 1px solid #f1f5f9; }
    .btn-primary { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border: none; border-radius: 10px; padding: 8px 20px; font-weight: 600; }
    .btn-outline-primary { border-radius: 10px; border-color: #3b82f6; color: #3b82f6; font-weight: 600; }
    .btn-danger { border-radius: 10px; }
    .badge-info { background: #e0f2fe; color: #0369a1; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-periode { background: #f1f5f9; color: #475569; padding: 6px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; border: 1px solid #e2e8f0; }
    .modal-content { border-radius: 24px; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
    .form-control, .form-select { border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 12px 16px; font-size: 14px; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
</style>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Master Pekerjaan</h3>
                    <p class="text-muted small mb-0">Atur template tugas rutin yang akan otomatis didistribusikan ke bagian terkait.</p>
                </div>
                <button type="button" class="btn btn-primary shadow-sm" id="btnAddNewMaster">
                    <i class="bi bi-plus-circle-fill me-2"></i> Buat Master Baru
                </button>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Judul Pekerjaan Rutin</th>
                                    <th>Periode</th>
                                    <th>Tgl Target</th>
                                    <th>Penerima (Bagian)</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="masterTableBody">
                                <?php include __DIR__ . '/../api/get_master_list.php'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-3" id="master-pagination">
                    <?php if (isset($totalPages) && $totalPages > 1): ?>
                        <?php echo render_pagination($totalRows, $perPage, $page, site_url('master_pekerjaan.php'), $_GET); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalMaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i> Konfigurasi Master Tugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalAlert" class="alert d-none"></div>
                <form id="formMaster">
                    <input type="hidden" name="id" id="fieldId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">JUDUL PEKERJAAN</label>
                        <input name="judul" id="fieldJudul" class="form-control" placeholder="Contoh: Stok Opname Barang Masuk" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">DESKRIPSI TUGAS</label>
                        <textarea name="deskripsi" id="fieldDeskripsi" class="form-control" rows="3" placeholder="Jelaskan detail instruksi tugas di sini..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">PERIODE</label>
                            <select name="periode" id="fieldPeriode" class="form-select">
                                <option value="bulanan">BULANAN</option>
                                <option value="triwulan">TRIWULAN</option>
                                <option value="mingguan">MINGGUAN</option>
                                <option value="harian">HARIAN</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">TANGGAL TARGET</label>
                            <input type="date" name="target_tgl" id="fieldTargetTgl" class="form-control" required>
                            <div id="targetHelperText" class="mt-2 small text-primary fw-semibold" style="min-height: 20px;"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">BAGIAN PENERIMA</label>
                            <select name="bagian_id" id="fieldBagianId" class="form-select" required>
                                <option value="">- PILIH BAGIAN -</option>
                                <?php foreach ($bagians as $bag): ?>
                                    <option value="<?php echo e($bag['id_bagian']); ?>"><?php echo strtoupper($bag['nama_bagian']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveMaster" class="btn btn-primary">Simpan Master</button>
            </div>
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

    // Helper text logic for target date
    const fieldPeriode = document.getElementById('fieldPeriode');
    const fieldTargetTgl = document.getElementById('fieldTargetTgl');
    const targetHelperText = document.getElementById('targetHelperText');

    function updateTargetHelper() {
        const periode = fieldPeriode.value;
        const dateVal = fieldTargetTgl.value;
        if (!dateVal) {
            targetHelperText.textContent = '';
            return;
        }

        const date = new Date(dateVal);
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const dayName = dayNames[date.getDay()];
        const dayOfMonth = date.getDate();

        let message = '';
        switch (periode) {
            case 'harian':
                message = '<i class="bi bi-info-circle me-1"></i> Tugas akan muncul SETIAP HARI.';
                break;
            case 'mingguan':
                message = `<i class="bi bi-info-circle me-1"></i> Tugas akan muncul SETIAP HARI ${dayName.toUpperCase()}.`;
                break;
            case 'bulanan':
                message = `<i class="bi bi-info-circle me-1"></i> Tugas akan muncul SETIAP TANGGAL ${dayOfMonth} tiap bulan.`;
                break;
            case 'triwulan':
                message = `<i class="bi bi-info-circle me-1"></i> Tugas akan muncul SETIAP TANGGAL ${dayOfMonth} setiap 3 bulan sekali.`;
                break;
        }
        targetHelperText.innerHTML = message;
    }

    fieldPeriode.addEventListener('change', updateTargetHelper);
    fieldTargetTgl.addEventListener('change', updateTargetHelper);

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
            updateTargetHelper(); // Update helper text after filling data
            alertEl.classList.add('d-none');
            modal.show();
        }

        if (btnDelete) {
            Swal.fire({
                title: 'Hapus Master?',
                text: "Penugasan otomatis ke pegawai akan dihentikan.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    const id = btnDelete.dataset.id;
                    fetch('api/hapus_master_pekerjaan.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + id
                    })
                    .then(async r => {
                        const res = await r.json();
                        if (!r.ok) {
                            if (res.type === 'restricted') {
                                Swal.fire({ title: 'Gagal', text: res.message, icon: 'error' });
                            } else {
                                throw new Error(res.message);
                            }
                            return null;
                        }
                        return res;
                    })
                    .then(res => { 
                        if(res && res.success) {
                            refreshTable(); 
                            Swal.fire({ title: 'Terhapus', text: 'Data master berhasil dihapus.', icon: 'success', timer: 1000, showConfirmButton: false });
                        }
                    })
                    .catch(err => Swal.fire('Error', err.message, 'error'));
                }
            });
        }
    });

    document.getElementById('btnSaveMaster').addEventListener('click', function() {
        if (!formEl.checkValidity()) { formEl.reportValidity(); return; }
        
        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

        const url = (currentMode === 'edit') ? 'api/edit_master_pekerjaan.php' : 'api/create_master_pekerjaan.php';

        fetch(url, { method: 'POST', body: new FormData(formEl) })
            .then(async r => {
                const res = await r.json();
                if (!r.ok) throw new Error(res.message || 'Server error');
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
                btn.disabled = false; 
                btn.innerHTML = originalText;
            });
    });
});
</script>
