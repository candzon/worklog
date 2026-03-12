<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// handle form submission
require_app('config/database.php');
require_app('functions/helpers.php');

ensure_session_started();
$npp = $_SESSION['npp'] ?? null;
$nama_emp = $_SESSION['nama_emp'] ?? null;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $due = trim($_POST['due_date'] ?? '');

    if ($judul === '')
        $errors[] = 'Judul pekerjaan wajib diisi';


    if (isset($conn)) {
        $conn->query($createSql);

        $stmt = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, npp, nama_emp, due_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $dueParam = $due !== '' ? $due : null;
            $stmt->bind_param('sssss', $judul, $deskripsi, $npp, $nama_emp, $dueParam);
            $stmt->execute();
            $stmt->close();
            if (function_exists('flash_swal'))
                flash_swal('success', 'Tersimpan', 'Pekerjaan berhasil ditambahkan');
            header('Location: ' . site_url('daftar_pekerjaan.php'));
            exit;
        } else {
            $errors[] = 'Gagal menyiapkan penyimpanan.';
        }
    } else {
        $errors[] = 'Koneksi database tidak tersedia.';
    }
}

// fetch recent entries
$rows = [];
if (isset($conn)) {
    $res = $conn->query("SELECT * FROM pekerjaan ORDER BY created_at DESC LIMIT 50");
    if ($res) {
        while ($r = $res->fetch_assoc())
            $rows[] = $r;
        $res->free();
    }
}

?>

<div class="app-main">
    <div class="app-content p-4">
        <div class="container-fluid">
            <h3 class="mb-3">Daftar Pekerjaan</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $err)
                        echo '<li>' . e($err) . '</li>'; ?></ul>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="post" action="<?php echo site_url('daftar_pekerjaan.php'); ?>">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input name="judul" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Tanggal Jatuh Tempo</label>
                                <input type="date" name="due_date" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Dilaporkan Oleh</label>
                                <input class="form-control" value="<?php echo e($nama_emp ?? $npp ?? ''); ?>" disabled>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-primary">Simpan Pekerjaan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Pekerjaan Terbaru</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Judul</th>
                                    <th>Deskripsi</th>
                                    <th>Due</th>
                                    <th>Pelapor</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center small text-muted">Belum ada pekerjaan.</td>
                                    </tr>
                                <?php else:
                                    foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?php echo e($r['id']); ?></td>
                                            <td><?php echo e($r['judul']); ?></td>
                                            <td><?php echo e(mb_strimwidth($r['deskripsi'], 0, 120, '...')); ?></td>
                                            <td><?php echo e($r['due_date']); ?></td>
                                            <td><?php echo e($r['nama_emp'] ?: $r['npp']); ?></td>
                                            <td><?php echo e($r['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>