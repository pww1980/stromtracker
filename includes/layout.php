<?php
// $pageTitle    - page title (string)
// $activePage   - current page identifier for nav highlighting
// $extraHead    - additional <head> content (string, optional)

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin();

$appTitle = getSetting('app_title', 'StromTracker');
$bp       = BASE_PATH;
?><!DOCTYPE html>
<html lang="de" data-bs-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars(($pageTitle ?? '') . ' – ' . $appTitle) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= $bp ?>/assets/css/style.css">
<script>window.BASE_PATH = <?= json_encode($bp) ?>;</script>
<?= $extraHead ?? '' ?>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <a href="<?= $bp ?>/dashboard.php" class="sidebar-brand">
      <i class="bi bi-lightning-charge-fill"></i>
      <span><?= htmlspecialchars($appTitle) ?></span>
    </a>
    <button class="sidebar-toggle d-lg-none" id="sidebarClose">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <ul class="sidebar-nav">
    <li>
      <a href="<?= $bp ?>/dashboard.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="<?= $bp ?>/entry.php" class="<?= ($activePage ?? '') === 'entry' ? 'active' : '' ?>">
        <i class="bi bi-plus-circle"></i><span>Eingabe</span>
      </a>
    </li>
    <li>
      <a href="<?= $bp ?>/history.php" class="<?= ($activePage ?? '') === 'history' ? 'active' : '' ?>">
        <i class="bi bi-table"></i><span>Verlauf</span>
      </a>
    </li>
    <li>
      <a href="<?= $bp ?>/settings.php" class="<?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
        <i class="bi bi-gear"></i><span>Einstellungen</span>
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="<?= $bp ?>/logout.php" class="sidebar-logout">
      <i class="bi bi-box-arrow-right"></i><span>Abmelden</span>
    </a>
  </div>
</nav>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Main content -->
<div class="main-content">
  <!-- Top bar -->
  <header class="topbar">
    <button class="topbar-toggle d-lg-none" id="sidebarOpen">
      <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></div>
    <div class="topbar-right">
      <span class="text-muted small d-none d-sm-inline">
        <?= date('d.m.Y') ?>
      </span>
    </div>
  </header>

  <main class="page-content">
