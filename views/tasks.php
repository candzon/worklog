<!-- views/tasks.php -->
<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Pengisian Daftar Pekerjaan</h3>
            <div class="card mb-4"><div class="card-body"><div id="calendar" style="min-height:480px;"></div></div></div>
            
            <style>
                .fc-custom-event { font-family: system-ui; color: var(--bs-body-color); padding: 8px; border-radius: 8px; background: #fff; border: 1px solid rgba(0,0,0,0.04); display: flex; flex-direction: column; overflow: hidden; }
                .fc-custom-event .fc-ce-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
                .fc-custom-event .fc-ce-title { font-weight: 600; font-size: 14px; line-height: 1.15; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
                .fc-custom-event .fc-ce-assigned { font-size: 11px; color: #495057; margin-top: 8px; border-top: 1px dashed rgba(0,0,0,0.04); padding-top: 6px; }
                .fc-custom-event.is-continuation { border-left: none !important; border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; background-color: #ffffff !important; box-shadow: none !important; }
                .fc-custom-event.is-continuation > * { visibility: hidden; }
            </style>

            <!-- Modal Buat Pekerjaan -->
            <div class="modal fade" id="pekerjaanModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Buat Pekerjaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <form id="pekerjaanForm">
                                <div id="create-alert" class="alert d-none"></div>
                                <div class="mb-3"><label class="form-label">Judul</label><input name="judul" id="pj_judul" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="pj_deskripsi" class="form-control" rows="3" required></textarea></div>
                                <div class="mb-3"><label class="form-label">Ditugaskan ke</label>
                                    <select name="assigned_to_npp" id="pj_ditugaskan" class="form-select" required><option value="">-- Pilih Pegawai --</option>
                                        <?php foreach ($employees as $emp): ?><option value="<?php echo e($emp['npp']); ?>"><?php echo e($emp['nama_emp']); ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6"><label class="form-label">Mulai</label><input type="date" name="tgl_mulai" id="pj_mulai" class="form-control" required></div>
                                    <div class="col-6"><label class="form-label">Selesai</label><input type="date" name="tgl_selesai" id="pj_selesai" class="form-control" required></div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" id="pj_save" class="btn btn-primary">Simpan</button></div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail & Done -->
            <div class="modal fade" id="pekerjaanDetailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="w-100"><h5 class="modal-title mb-1" id="pj_detail_title"></h5><div class="d-flex justify-content-between small text-muted"><span id="pj_detail_tanggal"></span><span class="badge" id="pj_detail_status"></span></div></div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="pj_detail_alert" class="alert d-none"></div>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item px-0"><div class="small text-muted">DITUGASKAN</div><div class="fw-semibold" id="pj_detail_assigned"></div></div>
                                <div class="list-group-item px-0"><div class="small text-muted">KOORDINATOR</div><div class="fw-semibold" id="pj_detail_reporter"></div></div>
                                <div class="list-group-item px-0"><div class="small text-muted">DESKRIPSI</div><div id="pj_detail_desc" class="mt-1"></div></div>
                                <div class="list-group-item px-0 d-none" id="pj_detail_upload_row"><div class="small text-muted">LAMPIRAN BUKTI (MAKS 2MB)</div><input type="file" id="pj_detail_file" class="form-control mt-1"><div id="pj_detail_file_view" class="mt-2 d-none"><a id="pj_detail_file_link" href="#" target="_blank" class="btn btn-xs btn-outline-info">Lihat Lampiran</a></div></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-primary d-none" id="pj_detail_done">Tandai Selesai</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const modal = new bootstrap.Modal(document.getElementById('pekerjaanModal'));
    const detailModal = new bootstrap.Modal(document.getElementById('pekerjaanDetailModal'));

    // Tangkap parameter filter dari URL
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilter = urlParams.has('filter_npp');
    
    // Jika ada filter, gunakan tampilan List (Daftar) agar tugas langsung "terbuka" terlihat semua
    const defaultView = hasFilter ? 'listMonth' : (window.innerWidth < 576 ? 'listWeek' : 'dayGridMonth');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: defaultView,
        headerToolbar: { 
            left: 'prev,next today addPekerjaan', 
            center: 'title', 
            right: 'dayGridMonth,timeGridWeek,listMonth' 
        },
        customButtons: { addPekerjaan: { text: 'Tambah Pekerjaan', click: () => { document.getElementById('pekerjaanForm').reset(); modal.show(); } } },
        locale: 'id', events: 'api/events_pekerjaan.php',
        dateClick: (info) => { document.getElementById('pekerjaanForm').reset(); document.getElementById('pj_mulai').value = info.dateStr; document.getElementById('pj_selesai').value = info.dateStr; modal.show(); },
        eventClick: (info) => showDetail(info.event),
        eventContent: (arg) => {
            let props = arg.event.extendedProps;
            let statusVal = (props.status || '').toLowerCase();
            let isDone = ['done','selesai','completed'].includes(statusVal);
            let html = `<div class="fc-custom-event ${!arg.isStart ? 'is-continuation' : ''}">
                <div class="fc-ce-header">
                    <div class="fc-ce-title" title="${arg.event.title}">${arg.event.title}</div>
                    <span class="badge ${isDone ? 'bg-success' : 'bg-primary'}">${props.status || 'Open'}</span>
                </div>
            </div>`;
            return { html: html };
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
        
        document.getElementById('pj_detail_alert').classList.add('d-none');
        doneBtn.classList.toggle('d-none', isDone || p.ditugaskan !== '<?php echo $npp; ?>');
        uploadRow.classList.remove('d-none');
        document.getElementById('pj_detail_file').classList.toggle('d-none', isDone);
        document.getElementById('pj_detail_file').value = '';
        document.getElementById('pj_detail_file_view').classList.toggle('d-none', !p.lampiran);
        if(p.lampiran) document.getElementById('pj_detail_file_link').href = 'uploads/' + p.lampiran;

        doneBtn.onclick = () => {
            let file = document.getElementById('pj_detail_file').files[0];
            if(!file) { alert('Wajib mengunggah file bukti!'); return; }
            if(file.size > 2*1024*1024) { alert('Maksimal file adalah 2MB!'); return; }
            
            let fd = new FormData();
            if(p.is_master) { fd.append('master_tugas_id', p.master_tugas_id); fd.append('tgl_mulai', p.tgl_mulai); fd.append('token', p.occ_token); }
            else { fd.append('id', ev.id); }
            fd.append('lampiran', file);
            
            doneBtn.disabled = true;
            fetch('api/mark_done.php', { method:'POST', body: fd })
                .then(async r => {
                    const data = await r.json();
                    if (!r.ok) throw new Error(data.message || 'Server Error');
                    return data;
                })
                .then(res => { if(res.success){ detailModal.hide(); calendar.refetchEvents(); } })
                .catch(err => { alert(err.message); doneBtn.disabled = false; });
        };
        detailModal.show();
    }

    document.getElementById('pj_save').addEventListener('click', () => {
        let form = document.getElementById('pekerjaanForm');
        if(!form.checkValidity()) { form.reportValidity(); return; }
        
        let fd = new FormData(form);
        const btn = document.getElementById('pj_save');
        btn.disabled = true;
        fetch('api/create_pekerjaan.php', { method:'POST', body: fd })
            .then(async r => {
                const data = await r.json();
                if(!r.ok) throw new Error(data.message || 'Error');
                return data;
            })
            .then(res => { modal.hide(); calendar.refetchEvents(); })
            .catch(err => { alert(err.message); })
            .finally(() => { btn.disabled = false; });
    });
});
</script>
d('pj_save');
        btn.disabled = true;
        fetch('api/create_pekerjaan.php', { method:'POST', body: fd })
            .then(async r => {
                const data = await r.json();
                if(!r.ok) throw new Error(data.message || 'Error');
                return data;
            })
            .then(res => { modal.hide(); calendar.refetchEvents(); })
            .catch(err => { alert(err.message); })
            .finally(() => { btn.disabled = false; });
    });
});
</script>
