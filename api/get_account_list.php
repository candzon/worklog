<?php
/**
 * api/get_account_list.php
 * Mengembalikan fragment HTML baris tabel akun untuk AJAX refresh
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AccountModel.php';

ensure_session_started();

$model = new AccountModel($conn);
$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$accounts = $model->getAll($perPage, $offset);

if (empty($accounts)): ?>
    <tr><td colspan="5" class="text-center text-muted">Belum ada data pegawai.</td></tr>
<?php else: foreach ($accounts as $a): ?>
    <tr id="row-acc-<?php echo $a['npp']; ?>">
        <td><?php echo $a['npp']; ?></td>
        <td><?php echo e($a['nama_emp']); ?></td>
        <td><?php echo e($a['dept_name']); ?></td>
        <td><span class="badge bg-secondary"><?php echo e($a['role_name']); ?></span></td>
        <td>
            <button class="btn btn-sm btn-outline-primary editAccBtn" data-acc='<?php echo json_encode($a); ?>'>Edit</button>
            <button class="btn btn-sm btn-danger deleteAccBtn" data-npp="<?php echo $a['npp']; ?>">Hapus</button>
        </td>
    </tr>
<?php endforeach; endif; ?>
