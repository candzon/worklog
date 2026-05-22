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

<!-- Document Viewer Modal -->
<div class="modal fade" id="docViewerModal" tabindex="-1" aria-hidden="true" style="z-index: 2000;">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0" style="border-radius: 0;">
            <div class="modal-header bg-dark text-white border-0 py-2">
                <h6 class="modal-title small fw-bold" id="docViewerTitle">Pratinjau Dokumen</h6>
                <div class="d-flex gap-2">
                    <a href="#" id="btnDownloadActual" class="btn btn-sm btn-outline-light rounded-pill px-3" target="_blank">
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-secondary d-flex justify-content-center align-items-center">
                <div id="docLoader" class="text-white text-center position-absolute">
                    <div class="spinner-border" role="status"></div>
                    <div class="mt-2 small">Memuat Dokumen...</div>
                </div>
                <iframe id="docViewerIframe" src="" width="100%" height="100%" style="border:none; background: #fff;" onload="document.getElementById('docLoader').classList.add('d-none')"></iframe>
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
                            <label class="form-label text-dark">UNGGAH BUKTI PENYELESAIAN (Semua Tipe File, Maks 5MB)</label>
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

    // Helper untuk escape JS string
    function escapeJs(str) {
        return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    // Fungsi Preview Dokumen (Universal)
    window.viewDocument = function(fileName, title) {
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
        iframe.src = ''; // Reset

        // Logika Router Viewer
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
            iframe.src = fileUrl; // Gambar langsung
        } else if (ext === 'pdf') {
            iframe.src = fileUrl; // Browser modern dukung PDF
        } else if (['xlsx', 'xls', 'doc', 'docx', 'ppt', 'pptx'].includes(ext)) {
            // Gunakan Google Docs Viewer untuk Office Files
            iframe.src = `https://docs.google.com/viewer?url=${encodedUrl}&embedded=true`;
        } else {
            iframe.src = fileUrl;
        }

        viewerModal.show();
    };

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
            let rawTitle = arg.event.title || '';
            
            // Hapus kategori dari title (misal: "[HARIAN] Tugas" menjadi "Tugas")
            let cleanTitle = rawTitle.replace(/^\[.*?\]\s*/, '');
            
            let statusVal = (props.status || '').toLowerCase();
            let isDone = ['done','selesai','completed'].includes(statusVal);
            
            // Set Color based on status
            let bgColor = isDone ? '#10b981' : (props.is_master ? '#0ea5e9' : '#3b82f6');
            
            return {
                html: `<div class="fc-custom-event ${!arg.isStart ? 'is-continuation' : ''}" style="background: ${bgColor}; color: #fff;">
                    <div class="fc-ce-title">${cleanTitle}</div>
                </div>`
            };
        }
    });
    calendar.render();

    function showDetail(ev) {
        let p = ev.extendedProps;
        let rawTitle = ev.title || '';
        let cleanTitle = rawTitle.replace(/^\[.*?\]\s*/, '');
        
        document.getElementById('pj_detail_title').textContent = cleanTitle;
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
        
        if(p.lampiran) {
            document.getElementById('pj_detail_file_link').onclick = (e) => {
                e.preventDefault();
                viewDocument(p.lampiran, cleanTitle);
            };
        }

        doneBtn.onclick = () => {
            let file = document.getElementById('pj_detail_file').files[0];
            if(!file) { Swal.fire('Perhatian', 'Wajib mengunggah bukti penyelesaian!', 'warning'); return; }
            if(file.size > 5*1024*1024) { Swal.fire('Error', 'Ukuran file maksimal 5MB!', 'error'); return; }
            
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
