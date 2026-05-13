<?php
/**
 * api/get_master_list.php
 * Fragment HTML baris tabel
 */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MasterModel.php';

ensure_session_started();

$model = new MasterModel($conn);
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$masters = $model->getAll($perPage, $offset);

if (empty($masters)): ?>
    <tr><td colspan="6" class="text-center text-muted">Belum ada master tugas.</td></tr>
<?php else: foreach ($masters as $m): ?>
    <tr id="row-master-<?php echo $m['id']; ?>">
        <td><?php echo e($m['id']); ?></td>
        <td><?php echo e($m['judul']); ?></td>
        <td><span class="badge text-bg-light border text-uppercase"><?php echo e($m['periode']); ?></span></td>
        <td><?php echo e($m['target_tgl'] ? format_date_id($m['target_tgl']) : '-'); ?></td>
        <td><span class="badge text-bg-info"><?php echo e(strtoupper($m['nama_bagian'] ?: '-')); ?></span></td>
        <td>
            <!-- Pastikan tombol memiliki class yang benar dan data-attribute lengkap -->
            <button type="button" class="btn btn-sm btn-outline-primary editMasterBtn" 
                    data-master='<?php echo json_encode($m, JSON_UNESCAPED_UNICODE); ?>'>Edit</button>
            <button type="button" class="btn btn-sm btn-danger deleteMasterBtn" 
                    data-id="<?php echo $m['id']; ?>">Hapus</button>
        </td>
    </tr>
<?php endforeach; endif; ?>
