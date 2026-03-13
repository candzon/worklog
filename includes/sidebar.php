<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
// Ensure user is logged in for non-public pages
require_login();
ensure_session_started();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?php echo site_url('index.php'); ?>" class="brand-link ps-3">
      <span class="brand-flag" aria-hidden="true"></span>
      <img src="<?php echo asset_url('img/logo.png'); ?>" alt="Logo" class="brand-image opacity-75 shadow" />
      <!-- <span class="brand-text d-none d-lg-inline ms-2">Worklog</span> -->
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column"
          data-lte-toggle="treeview"
          role="navigation"
          aria-label="Main navigation"
          data-accordion="false"
          id="navigation">
        <li class="nav-item">
          <a href="<?php echo site_url('index.php'); ?>" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="<?php echo site_url('daftar_pekerjaan.php'); ?>" class="nav-link <?php echo $currentPage === 'daftar_pekerjaan.php' ? 'active' : ''; ?>">
            <i class="nav-icon bi bi-list-check"></i>
            <p>Daftar Pekerjaan</p>
          </a>
        </li>

        

      </ul>

      <div>
        <hr class="sidebar-divider">
        <a href="<?php echo site_url('logout.php'); ?>" class="btn btn-outline-danger w-100 mb-3">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>

    </nav>
  </div>
</aside>
