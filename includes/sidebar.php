<?php
/* ---------------------------------------------------------------
 * Sidebar navigasi Worklog (AdminLTE dark)
 * Dipasang setelah header.php. Pembatas halaman `require_login()`
 * sudah dijalankan di header, sehingga session pasti tersedia di sini.
 * ------------------------------------------------------------- */
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_login();
ensure_session_started();

/* Halaman aktif menentukan item menu yang mendapat state "active" */
$currentPage = basename($_SERVER['PHP_SELF']);
$menuItems = [
    ['file' => 'index.php', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
    ['file' => 'daftar_pekerjaan.php', 'label' => 'Daftar Pekerjaan', 'icon' => 'bi-list-check'],
];

/* Inisial pengguna untuk avatar huruf (gravatar tidak tersedia, cukup initial) */
$userNama  = $_SESSION['nama_emp'] ?? $_SESSION['npp'] ?? '-';
$userInisial = strtoupper(mb_substr($userNama, 0, 1));
$userRole  = $_SESSION['role_name'] ?? null;
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?php echo site_url('index.php'); ?>" class="brand-link justify-content-center">
      <img src="<?php echo asset_url('img/logo.png'); ?>" alt="Logo Worklog" class="brand-image" />
      <span class="brand-text ms-2">Worklog</span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-3" aria-label="Navigasi utama">
      <ul class="nav sidebar-menu flex-column mb-auto" id="navigation">
        <?php foreach ($menuItems as $item): ?>
          <li class="nav-item">
            <a href="<?php echo site_url($item['file']); ?>"
               class="nav-link<?php echo $currentPage === $item['file'] ? ' active' : ''; ?>"
               <?php echo $currentPage === $item['file'] ? 'aria-current="page"' : ''; ?>>
              <i class="nav-icon bi <?php echo $item['icon']; ?>"></i>
              <p><?php echo e($item['label']); ?></p>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="sidebar-user mt-5">
        <hr class="sidebar-divider">
        <div class="d-flex align-items-center gap-2 px-3 py-1">
          <span class="avatar-initials" aria-hidden="true"><?php echo e($userInisial); ?></span>
          <div class="min-width-0">
            <div class="small fw-semibold text-truncate"><?php echo e($userNama); ?></div>
            <div class="smaller text-body-tertiary">
              <?php echo e($_SESSION['npp'] ?? ''); ?><?php echo $userRole ? ' &middot; ' . e($userRole) : ''; ?>
            </div>
          </div>
        </div>
        <a href="<?php echo site_url('logout.php'); ?>" class="btn btn-outline-danger btn-sm w-100 mx-3 mt-2 btn-logout">
          <i class="bi bi-box-arrow-right"></i> Keluar
        </a>
      </div>
    </nav>
  </div>
</aside>
