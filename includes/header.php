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
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Buka sidebar"><i class="bi bi-list"></i></a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item d-none d-sm-block">
            <span class="nav-link pe-0 py-1 d-flex align-items-center gap-2">
              <span class="avatar-initials" aria-hidden="true"><?php echo e(strtoupper(mb_substr($_SESSION['nama_emp'] ?? 'U', 0, 1))); ?></span>
              <span class="small fw-semibold"><?php echo e($_SESSION['nama_emp'] ?? $_SESSION['npp'] ?? ''); ?></span>
            </span>
          </li>
        </ul>
      </div>
    </nav>

    <!-- app-wrapper remains open; footer will close it -->