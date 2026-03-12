<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Worklog</title>

  <link rel="stylesheet" href="<?php echo asset_url('css/adminlte.css'); ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?php echo asset_url('css/style.css'); ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
          </li>
          <!-- <li class="nav-item d-none d-md-block"><a href="<?php echo site_url('index.php'); ?>" class="nav-link">Home</a></li>
          <li class="nav-item d-none d-md-block"><a href="<?php echo site_url('contact.php'); ?>" class="nav-link">Contact</a></li> -->
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" data-widget="navbar-search" href="#" role="button"><i class="bi bi-search"></i></a></li>
        </ul>
      </div>
    </nav>

    <!-- app-wrapper remains open; footer will close it -->
    