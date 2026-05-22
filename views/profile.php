<!-- views/profile.php -->
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
    .profile-card { border-radius: 24px; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); background: #fff; overflow: hidden; }
    .profile-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); height: 120px; position: relative; }
    .avatar-container { position: absolute; bottom: -50px; left: 40px; }
    .profile-avatar-large { 
        width: 100px; height: 100px; border-radius: 30px; object-fit: cover; 
        border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: #fff;
    }
    .profile-body { padding: 70px 40px 40px; }
    .form-section-title { font-weight: 800; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .form-section-title::after { content: ""; height: 1px; background: #f1f5f9; flex-grow: 1; }
    .form-label { font-weight: 700; font-size: 13px; color: #475569; margin-bottom: 8px; }
    .form-control { border-radius: 12px; border: 1.5px solid #e2e8f0; padding: 12px 16px; transition: all 0.2s; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    .btn-update { border-radius: 12px; padding: 12px 24px; font-weight: 700; transition: all 0.3s; }
    .upload-btn-wrapper { position: relative; overflow: hidden; display: inline-block; cursor: pointer; }
    .upload-btn-wrapper input[type=file] { position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; }
</style>

<main class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="avatar-container">
                                <div class="position-relative" style="cursor: pointer;" onclick="document.getElementById('inputFoto').click();">
                                    <img src="<?php echo $user['foto'] ? 'uploads/profile/' . e($user['foto']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama_emp']) . '&background=10b981&color=fff&size=200'; ?>" 
                                         alt="Avatar" class="profile-avatar-large" id="profilePreview">
                                    <div class="btn btn-sm btn-primary rounded-circle shadow-sm position-absolute" style="bottom: 0; right: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-camera-fill text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-body">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <h4 class="fw-bold mb-0"><?php echo e($user['nama_emp']); ?></h4>
                                    <p class="text-muted small">NPP: <?php echo e($user['npp']); ?></p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('inputFoto').click();">
                                    <i class="bi bi-image me-1"></i> Ubah Foto Profil
                                </button>
                            </div>

                            <form id="formProfile">
                                <input type="file" id="inputFoto" style="display: none;" accept="image/*">
                                <div class="form-section-title">Informasi Pribadi</div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo e($user['nama_emp']); ?>" readonly disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor Telepon</label>
                                        <input type="text" name="telp" class="form-control" value="<?php echo e($user['telp']); ?>" placeholder="08xxx">
                                    </div>
                                </div>

                                <div class="form-section-title">Keamanan</div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Kata Sandi Baru</label>
                                        <div class="input-group">
                                            <input type="password" name="password" id="newPass" class="form-control" placeholder="Kosongkan jika tidak diubah">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('newPass')"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Konfirmasi Kata Sandi</label>
                                        <div class="input-group">
                                            <input type="password" id="confirmPass" class="form-control" placeholder="Ulangi kata sandi baru">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirmPass')"><i class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="submit" id="btnSaveProfile" class="btn btn-primary btn-update shadow-sm">
                                        <i class="bi bi-check-circle-fill me-2"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function togglePass(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

document.addEventListener('DOMContentLoaded', function() {
    const inputFoto = document.getElementById('inputFoto');
    const profilePreview = document.getElementById('profilePreview');
    const formProfile = document.getElementById('formProfile');
    const btnSave = document.getElementById('btnSaveProfile');

    // Preview Foto
    inputFoto.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    formProfile.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPass = document.getElementById('newPass').value;
        const confirmPass = document.getElementById('confirmPass').value;

        if (newPass && newPass !== confirmPass) {
            Swal.fire('Error', 'Konfirmasi kata sandi tidak cocok!', 'error');
            return;
        }

        const fd = new FormData(this);
        if (inputFoto.files[0]) {
            fd.append('foto', inputFoto.files[0]);
        }

        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';

        fetch('api/update_profile.php', {
            method: 'POST',
            body: fd
        })
        .then(async r => {
            const res = await r.json();
            if (!r.ok) throw new Error(res.message || 'Server error');
            return res;
        })
        .then(res => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        })
        .catch(err => {
            Swal.fire('Error', err.message, 'error');
        })
        .finally(() => {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Simpan Perubahan';
        });
    });
});
</script>
