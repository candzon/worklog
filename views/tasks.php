<!-- views/tasks.php -->
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
    .card { border-radius: 20px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    
    /* Calendar Styling */
    #calendar { background: #fff; border-radius: 12px; padding: 10px; }
    .fc .fc-toolbar-title { font-weight: 800; color: #1e3a8a; font-size: 1.25rem !important; }
    .fc .fc-button-primary { background: #fff; border: 1.5px solid #e2e8f0; color: #475569; font-weight: 600; border-radius: 8px; text-transform: capitalize; padding: 6px 12px; }
    .fc .fc-button-primary:hover { background: #f1f5f9; color: #1e3a8a; border-color: #cbd5e1; }
    .fc .fc-button-active { background: #1e3a8a !important; color: #fff !important; border-color: #1e3a8a !important; }
    
    /* Custom Event Styling */
    .fc-custom-event { 
        padding: 5px 8px; border-radius: 8px; border: none !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-size: 11px;
    }
    .fc-ce-header { display: flex; align-items: center; justify-content: space-between; gap: 4px; }
    .fc-ce-title { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: inherit; }
    .fc-ce-badge { font-size: 8px; text-transform: uppercase; padding: 2px 4px; border-radius: 4px; background: rgba(255,255,255,0.2); }

    /* Modal Styling */
    .modal-content { border-radius: 24px; border: none; }
    .modal-header { border-bottom: 1px solid #f1f5f9; padding: 20px 24px; }
    .modal-body { padding: 24px; }
    .form-label { font-weight: 700; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .form-control, .form-select { border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 10px 14px; transition: all 0.2s; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    
    /* Detail Info list */
    .detail-item { padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
    .detail-item:last-child { border-bottom: none; }
    .detail-label { font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 2px; }
    .detail-value { font-weight: 600; color: #1e293b; font-size: 14px; }
</style>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold mb-0">Kalender Pekerjaan</h3>
                    <p class="text-muted small mb-0">Pantau dan kelola jadwal tugas harian maupun rutin tim Anda.</p>
                </div>
            </div>

            <div class="card p-2">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buat Pekerjaan -->
<div class="modal fade" id="pekerjaanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form id="pekerjaanForm">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Buat Tugas Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">JUDUL TUGAS</label>
                        <input name="judul" id="pj_judul" class="form-control" placeholder="Apa yang harus dikerjakan?" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DESKRIPSI LENGKAP</label>
                        <textarea name="deskripsi" id="pj_deskripsi" class="form-control" rows="3" placeholder="Tambahkan instruksi pengerjaan..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PELAKSANA (DITUGASKAN KE)</label>
                        <select name="assigned_to_npp" id="pj_ditugaskan" class="form-select" required>
                            <option value="">-- PILIH PEGAWAI --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo e($emp['npp']); ?>"><?php echo e($emp['nama_emp']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">TANGGAL MULAI</label>
                            <input type="date" name="tgl_mulai" id="pj_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TENGGAT WAKTU (DEADLINE)</label>
                            <input type="date" name="tgl_selesai" id="pj_selesai" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="pj_save" class="btn btn-primary px-4">Simpan & Jadwalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail & Done -->
<div class="modal fade" id="pekerjaanDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <div class="w-100">
                    <h5 class="fw-bold mb-1" id="pj_detail_title"></h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge" id="pj_detail_status"></span>
                        <small class="text-muted" id="pj_detail_tanggal"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="pj_detail_alert" class="alert d-none rounded-3"></div>
                
                <div class="detail-item">
                    <div class="detail-label">PELAKSANA TUGAS</div>
                    <div class="detail-value text-primary" id="pj_detail_assigned"></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">KOORDINATOR / PENGAWAS</div>
                    <div class="detail-value" id="pj_detail_reporter"></div>
                </div>
                <div class="detail-item border-0">
                    <div class="detail-label">INSTRUKSI & DESKRIPSI</div>
                    <div class="detail-value mt-2 bg-light p-3 rounded-3" id="pj_detail_desc" style="font-weight: 400; line-height: 1.6;"></div>
                </div>

                <div id="pj_detail_upload_row" class="mt-3 d-none">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <label class="form-label text-dark">UNGGAH BUKTI PENYELESAIAN (MAKS 2MB)</label>
                            <input type="file" id="pj_detail_file" class="form-control bg-white">
                            <div id="pj_detail_file_view" class="mt-2 d-none">
                                <a id="pj_detail_file_link" href="#" target="_blank" class="btn btn-sm btn-outline-info w-100 mt-2">
                                    <i class="bi bi-file-earmark-text me-1"></i> Lihat Lampiran Bukti
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" id="pj_detail_done" class="btn btn-success w-100 py-2 fw-bold d-none">
                    <i class="bi bi-check-circle-fill me-2"></i> TANDAI TUGAS SELESAI
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const modal = new bootstrap.Modal(document.getElementById('pekerjaanModal'));
    const detailModal = new bootstrap.Modal(document.getElementById('pekerjaanDetailModal'));

    const urlParams = new URLSearchParams(window.location.search);
    const defaultView = urlParams.has('filter_npp') ? 'listMonth' : (window.innerWidth < 576 ? 'listWeek' : 'dayGridMonth');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: defaultView,
        headerToolbar: { left: 'prev,next today addPekerjaan', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
        customButtons: { 
            addPekerjaan: { 
                text: 'Tambah Tugas', 
                click: () => { document.getElementById('pekerjaanForm').reset(); modal.show(); } 
            } 
        },
        locale: 'id',
        events: 'api/events_pekerjaan.php' + window.location.search,
        eventClick: (info) => showDetail(info.event),
        eventContent: (arg) => {
            let props = arg.event.extendedProps;
            let statusVal = (props.status || '').toLowerCase();
            let isDone = ['done','selesai','completed'].includes(statusVal);
            
            // Set Color based on status
            let bgColor = isDone ? '#10b981' : (props.is_master ? '#0ea5e9' : '#3b82f6');
            
            return {
                html: `<div class="fc-custom-event ${!arg.isStart ? 'is-continuation' : ''}" style="background: ${bgColor}; color: #fff;">
                    <div class="fc-ce-header">
                        <div class="fc-ce-title">${arg.event.title}</div>
                        <span class="fc-ce-badge">${isDone ? 'DONE' : 'OPEN'}</span>
                    </div>
                </div>`
            };
        }
    });
    calendar.render();

    function showDetail(ev) {
        let p = ev.extendedProps;
        document.getElementById('pj_detail_title').textContent = ev.title;
        document.getElementById('pj_detail_assigned').textContent = p.assigned_name || '-';
        document.getElementById('pj_detail_reporter').textContent = p.reporter_name || 'Manager';
        document.getElementById('pj_detail_desc').textContent = p.description || '-';
        document.getElementById('pj_detail_tanggal').textContent = (p.tgl_mulai || '') + (p.tgl_selesai ? ' s/d ' + p.tgl_selesai : '');
        
        let doneBtn = document.getElementById('pj_detail_done');
        let uploadRow = document.getElementById('pj_detail_upload_row');
        let isDone = ['done','selesai','completed'].includes((p.status||'').toLowerCase());
        
        let statusEl = document.getElementById('pj_detail_status');
        statusEl.textContent = isDone ? 'SELESAI' : 'AKTIF';
        statusEl.className = 'badge ' + (isDone ? 'bg-success' : 'bg-primary');

        doneBtn.classList.toggle('d-none', isDone || p.ditugaskan !== '<?php echo $npp; ?>');
        uploadRow.classList.remove('d-none');
        document.getElementById('pj_detail_file').classList.toggle('d-none', isDone);
        document.getElementById('pj_detail_file_view').classList.toggle('d-none', !p.lampiran);
        if(p.lampiran) document.getElementById('pj_detail_file_link').href = 'uploads/' + p.lampiran;

        doneBtn.onclick = () => {
            let file = document.getElementById('pj_detail_file').files[0];
            if(!file) { Swal.fire('Perhatian', 'Wajib mengunggah bukti penyelesaian!', 'warning'); return; }
            if(file.size > 2*1024*1024) { Swal.fire('Error', 'Ukuran file maksimal 2MB!', 'error'); return; }
            
            let fd = new FormData();
            if(p.is_master) { fd.append('master_tugas_id', p.master_tugas_id); fd.append('tgl_mulai', p.tgl_mulai); fd.append('token', p.occ_token); }
            else { fd.append('id', ev.id); }
            fd.append('lampiran', file);
            
            doneBtn.disabled = true;
            fetch('api/mark_done.php', { method:'POST', body: fd }).then(r => r.json()).then(res => {
                if(res.success){ detailModal.hide(); calendar.refetchEvents(); Swal.fire('Berhasil', 'Tugas telah diselesaikan.', 'success'); }
            }).finally(() => doneBtn.disabled = false);
        };
        detailModal.show();
    }

    document.getElementById('pj_save').addEventListener('click', () => {
        let form = document.getElementById('pekerjaanForm');
        if(!form.checkValidity()) { form.reportValidity(); return; }
        const btn = document.getElementById('pj_save');
        btn.disabled = true;
        fetch('api/create_pekerjaan.php', { method:'POST', body: new FormData(form) })
            .then(r => r.json()).then(res => {
                if(res.success){ modal.hide(); calendar.refetchEvents(); Swal.fire('Tersimpan', 'Tugas berhasil dijadwalkan.', 'success'); }
            }).finally(() => btn.disabled = false);
    });
});
</script>
