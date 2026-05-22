<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
// Ensure user is logged in for non-public pages
require_login();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Worklog</title>

  <link rel="stylesheet" href="<?php echo asset_url('css/adminlte.css'); ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    crossorigin="anonymous">
  <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <style>
    /* User Profile Dropdown Styling */
    .user-profile-dropdown {
      position: relative;
    }

    .user-profile-btn {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 6px 12px;
      border-radius: 50px;
      border: none;
      background: transparent;
      transition: all 0.2s;
    }

    .user-profile-btn:hover {
      background: rgba(0, 0, 0, 0.05);
    }

    .user-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #10b981;
      padding: 2px;
    }

    .user-info-text {
      text-align: left;
      line-height: 1.2;
    }

    .user-name {
      display: block;
      font-weight: 700;
      font-size: 14px;
      color: #1e293b;
    }

    .user-role {
      display: block;
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .profile-dropdown-menu {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      width: 240px;
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(0, 0, 0, 0.05);
      padding: 8px;
      display: none;
      z-index: 1050;
    }

    .profile-dropdown-menu.show {
      display: block;
      animation: fadeIn 0.2s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .dropdown-item-custom {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      color: #334155;
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      border-radius: 12px;
      transition: all 0.2s;
    }

    .dropdown-item-custom:hover {
      background: #f1f5f9;
      color: #1e3a8a;
    }

    .dropdown-item-custom i {
      font-size: 18px;
      color: #64748b;
    }

    .dropdown-item-custom:hover i {
      color: #3b82f6;
    }

    .dropdown-item-custom.active {
      background: #f0f9ff;
      color: #0369a1;
    }

    .dropdown-item-custom.active i {
      color: #0ea5e9;
    }

    .dropdown-divider-custom {
      height: 1px;
      background: #f1f5f9;
      margin: 8px 0;
    }
  </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto align-items-center">
          <?php
          // Fetch latest user data for avatar and notifications
          $header_npp = $_SESSION['npp'] ?? '';
          $header_user = null;
          $notif_count = 0;
          $notif_tasks = [];

          if ($header_npp) {
            // Get user info
            $h_stmt = $conn->prepare("SELECT foto, nama_emp FROM employee WHERE npp = ? LIMIT 1");
            $h_stmt->bind_param('s', $header_npp);
            $h_stmt->execute();
            $header_user = $h_stmt->get_result()->fetch_assoc();

            // Get recent open tasks
            $n_stmt = $conn->prepare("SELECT id, judul, tgl_mulai, tgl_selesai FROM pekerjaan WHERE assigned_to_npp = ? AND status = 'open' ORDER BY tgl_selesai ASC LIMIT 5");
            $n_stmt->bind_param('s', $header_npp);
            $n_stmt->execute();
            $res = $n_stmt->get_result();
            while ($row = $res->fetch_assoc()) {
              $notif_tasks[] = $row;
            }

            // Get total count of open tasks
            $c_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM pekerjaan WHERE assigned_to_npp = ? AND status = 'open'");
            $c_stmt->bind_param('s', $header_npp);
            $c_stmt->execute();
            $notif_count = $c_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
          }
          $avatar_url = ($header_user && $header_user['foto'])
            ? asset_url('../uploads/profile/' . $header_user['foto'])
            : 'https://ui-avatars.com/api/?name=' . urlencode($header_user['nama_emp'] ?? 'User') . '&background=10b981&color=fff';
          ?>

          <!-- Notifications Dropdown -->
          <!-- <li class="nav-item user-profile-dropdown me-3">
            <button class="btn btn-link text-secondary position-relative p-0" id="notifDropdownBtn" style="border: none; background: transparent;">
              <i class="bi bi-bell" style="font-size: 1.4rem; color: #64748b;"></i>
              <?php if ($notif_count > 0): ?>
                <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="top: 2px; left: 100%; font-size: 0.6rem; padding: 0.3em 0.5em; border: 2px solid #fff;">
                  <?php echo $notif_count > 99 ? '99+' : $notif_count; ?>
                </span>
              <?php endif; ?>
            </button>
            
            <div class="profile-dropdown-menu" id="notifMenu" style="width: 320px; right: -20px; padding: 0; overflow: hidden;">
              <div class="px-3 py-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color: #1e293b; font-size: 14px;">Notifikasi Tugas</span>
                <?php if ($notif_count > 0): ?>
                    <span class="badge bg-primary rounded-pill"><?php echo $notif_count; ?> Baru</span>
                <?php endif; ?>
              </div>
              <div style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($notif_tasks)): ?>
                  <div class="p-4 text-center text-muted small">
                    <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                    Bagus! Semua tugas Anda sudah selesai.
                  </div>
                <?php else: ?>
                  <?php foreach ($notif_tasks as $nt): ?>
                    <a href="<?php echo site_url('index.php'); ?>" class="dropdown-item px-3 py-3 border-bottom text-wrap" style="transition: background 0.2s; white-space: normal;">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                            <i class="bi bi-list-task"></i>
                        </div>
                        <div>
                            <div class="fw-bold mb-1 text-dark" style="font-size: 13px; line-height: 1.4;"><?php echo e($nt['judul']); ?></div>
                            <div class="small text-muted" style="font-size: 11px;">
                                <i class="bi bi-clock me-1"></i> Tenggat: <?php echo e($nt['tgl_selesai'] ? date('d M Y', strtotime($nt['tgl_selesai'])) : 'Tidak ada'); ?>
                            </div>
                        </div>
                      </div>
                    </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div class="p-2 text-center bg-light border-top">
                <a href="<?php echo site_url('index.php'); ?>" class="text-decoration-none small fw-bold text-primary">Buka Dashboard Pekerjaan</a>
              </div>
            </div>
          </li> -->

          <!-- User Profile Dropdown -->
          <li class="nav-item user-profile-dropdown">
            <button class="user-profile-btn" id="userProfileBtn">
              <img src="<?php echo $avatar_url; ?>" alt="User Avatar" class="user-avatar">
              <i class="bi bi-chevron-down small text-muted ms-1"></i>
            </button>

            <div class="profile-dropdown-menu" id="userProfileMenu">
              <div class="px-3 py-3 text-center border-bottom mb-2">
                <span class="user-name d-block"><?php echo e($_SESSION['nama_emp'] ?? 'Pegawai'); ?></span>
                <!-- <span class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px;"><?php echo e($_SESSION['role_name'] ?? 'User'); ?></span> -->
              </div>
              <a href="<?php echo site_url('profile.php'); ?>"
                class="dropdown-item-custom <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                <i class="bi bi-person"></i>
                <span>Profile</span>
              </a>
              <div class="dropdown-divider-custom"></div>
              <a href="<?php echo site_url('logout.php'); ?>" class="dropdown-item-custom text-danger">
                <i class="bi bi-box-arrow-right text-danger"></i>
                <span>Logout</span>
              </a>
            </div>
          </li>
        </ul>
      </div>
    </nav>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const profileBtn = document.getElementById('userProfileBtn');
        const profileMenu = document.getElementById('userProfileMenu');
        const notifBtn = document.getElementById('notifDropdownBtn');
        const notifMenu = document.getElementById('notifMenu');

        // Toggle Profile
        profileBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          if (notifMenu) notifMenu.classList.remove('show');
          profileMenu.classList.toggle('show');
        });

        // Toggle Notifications
        if (notifBtn) {
          notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            profileMenu.classList.remove('show');
            notifMenu.classList.toggle('show');
          });
        }

        // Close on click outside
        document.addEventListener('click', function () {
          profileMenu.classList.remove('show');
          if (notifMenu) notifMenu.classList.remove('show');
        });

        // Prevent closing when clicking inside menus
        profileMenu.addEventListener('click', function (e) {
          if (!e.target.closest('a')) e.stopPropagation();
        });
        if (notifMenu) {
          notifMenu.addEventListener('click', function (e) {
            if (!e.target.closest('a')) e.stopPropagation();
          });
        }
      });
    </script>

    <!-- app-wrapper remains open; footer will close it -->