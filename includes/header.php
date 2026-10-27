<?php
/**
 * KARCEM Textile ERP - Shared Header & Top Navigation
 * File: includes/header.php
 * Professional Enterprise SaaS Light Theme
 */

use Core\Support\Url;
use Core\Security\SessionManager;
use Core\Security\CSRF;

SessionManager::start();
$currentUser = SessionManager::getUser();
$pageTitle = $pageTitle ?? 'KARCEM Textile ERP';
$baseUrl = Url::base();
$csrfToken = CSRF::getToken();
$brandName = 'KARCEM Textiles';
$activeModule = $activeModule ?? 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — KARCEM Textile ERP</title>
  <!-- Favicon: Dyeing ERP Droplet Logo -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $baseUrl ?>/assets/img/favicon-32x32.png?v=<?= filemtime(__DIR__ . '/../assets/img/favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $baseUrl ?>/assets/img/favicon-16x16.png?v=<?= filemtime(__DIR__ . '/../assets/img/favicon-16x16.png') ?>">
  <link rel="icon" type="image/png" sizes="64x64" href="<?= $baseUrl ?>/assets/img/favicon.png?v=<?= filemtime(__DIR__ . '/../assets/img/favicon.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= $baseUrl ?>/assets/img/apple-touch-icon.png?v=<?= filemtime(__DIR__ . '/../assets/img/apple-touch-icon.png') ?>">
  <link rel="shortcut icon" href="<?= $baseUrl ?>/assets/img/favicon.ico?v=<?= filemtime(__DIR__ . '/../assets/img/favicon.ico') ?>">
  
  <!-- Typography: Plus Jakarta Sans, Inter & JetBrains Mono Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Core Styles -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/cloudzoo.css?v=<?= filemtime(__DIR__ . '/../assets/css/cloudzoo.css') ?>">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/my-page-ui.css?v=<?= filemtime(__DIR__ . '/../assets/css/my-page-ui.css') ?>">
  <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/dyeing-modern-theme.css?v=<?= filemtime(__DIR__ . '/../assets/css/dyeing-modern-theme.css') ?>">

  <!-- Lucide Outline Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    /* Status Badges conforming to exact palette */
    .badge-status {
      font-size: 0.72rem;
      font-weight: 500;
      padding: 0.28em 0.6em;
      border-radius: 4px;
      letter-spacing: 0.02em;
    }
    .status-confirmed, .status-posted, .status-accepted, .status-completed, .status-active, .status-passed {
      background: #EAF7F0;
      color: #16845B;
      border: 1px solid #B7E8CE;
    }
    .status-draft, .status-normal {
      background: #F1F5F9;
      color: #475569;
      border: 1px solid #E2E8F0;
    }
    .status-planned, .status-pending, .status-on_hold, .status-hold {
      background: #FFF5E5;
      color: #A86A12;
      border: 1px solid #FCE0B5;
    }
    .status-loaded, .status-in_progress, .status-in_production, .status-running, .status-dispatched {
      background: #EEF4FF;
      color: #3267D6;
      border: 1px solid #C7D9FB;
    }
    .status-rejected, .status-cancelled, .status-breakdown, .status-failed, .status-urgent {
      background: #FDEEEF;
      color: #C23B42;
      border: 1px solid #F8C8CA;
    }
  </style>
  <script>
    window.CZ_BASE_URL = '<?= $baseUrl ?>';
    window.CZ_CSRF_TOKEN = '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>';
  </script>
</head>
<body>
<div class="cz-app">

  <!-- Redesigned Modern Light Enterprise Topbar -->
  <header class="cz-topbar">
    <!-- Brand / Mill Identification -->
    <div class="cz-topbar__brand">
      <button class="cz-icon-btn me-1" id="czSidebarToggleBtn" type="button" aria-label="Toggle Navigation" title="Toggle Navigation (Ctrl+B)">
        <i data-lucide="panel-left"><i class="bi bi-layout-sidebar-inset"></i></i>
      </button>
      <div class="d-flex align-items-center gap-2 cz-brand-info">
        <div class="cz-brand-icon-box">
          <img src="<?= $baseUrl ?>/assets/img/dyeing-mark.png?v=<?= filemtime(__DIR__ . '/../assets/img/dyeing-mark.png') ?>" alt="Dyeing ERP" width="26" height="26" style="object-fit: contain;">
        </div>
        <div class="overflow-hidden">
          <div class="cz-brand-name">KARCEM DYEING</div>
          <div class="cz-brand-sub">WET PROCESSING ERP</div>
        </div>
      </div>
    </div>

    <!-- Center Search Bar (Prominent & Modern) -->
    <div class="cz-topbar__search-wrap d-none d-md-block">
      <div class="cz-topbar-search-box" data-bs-toggle="modal" data-bs-target="#globalSearchModal" role="button" tabindex="0">
        <i data-lucide="search" class="search-icon"><i class="bi bi-search"></i></i>
        <span class="search-text">Search batches, job cards, recipes, QC, challans...</span>
        <kbd class="search-kbd">⌘K</kbd>
      </div>
    </div>

    <!-- Right Utility Actions -->
    <div class="cz-topbar__actions">
      <!-- Mobile Search Button -->
      <button class="cz-icon-btn d-md-none" type="button" data-bs-toggle="modal" data-bs-target="#globalSearchModal" title="Search">
        <i data-lucide="search"><i class="bi bi-search"></i></i>
      </button>

      <!-- Mill Facility Status Badge (Click to Switch/Configure Multi-Units) -->
      <a href="<?= $baseUrl ?>/dyeing/organization" class="cz-unit-badge d-none d-lg-inline-flex text-decoration-none" title="Current Mill Unit: Click to Switch or Configure Units">
        <span class="pulse-dot"></span>
        <i data-lucide="factory" style="width: 15px; height: 15px;"><i class="bi bi-building"></i></i>
        <span><?= htmlspecialchars($_SESSION['active_branch_name'] ?? 'Tirupur Mill (TN 33)') ?></span>
        <i class="bi bi-chevron-down opacity-50 ms-1" style="font-size: 0.65rem;"></i>
      </a>

      <!-- Quick Action / Print Forms Engine -->
      <a href="<?= $baseUrl ?>/dyeing/print" class="cz-icon-btn d-none d-sm-inline-flex" title="P40 & Print Form Engine">
        <i data-lucide="printer"><i class="bi bi-printer"></i></i>
      </a>

      <!-- Notifications -->
      <button class="cz-icon-btn position-relative" type="button" aria-label="Notifications" title="Factory Alerts & Notifications">
        <i data-lucide="bell"><i class="bi bi-bell"></i></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.60rem; padding: 0.25em 0.45em;">2</span>
      </button>

      <div class="vr my-2 mx-1 d-none d-sm-block text-secondary opacity-25" style="height: 24px;"></div>

      <!-- User Profile Menu -->
      <div class="dropdown">
        <button class="cz-profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
          <span class="cz-avatar fw-bold"><?= htmlspecialchars($currentUser['initials'] ?? 'SK') ?></span>
          <div class="text-start d-none d-lg-block">
            <div class="cz-profile-trigger__name"><?= htmlspecialchars($currentUser['full_name'] ?? 'M. Senthilkumar') ?></div>
            <div class="cz-profile-trigger__role">Plant Admin</div>
          </div>
          <i class="bi bi-chevron-down cz-profile-trigger__chev ms-1"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end cz-profile-menu p-3 shadow-lg border" style="min-width: 260px; border-radius: 12px;">
          <div class="d-flex align-items-center gap-3 pb-3 border-bottom mb-2">
            <span class="cz-avatar fs-5 fw-bold" style="width: 40px; height: 40px;"><?= htmlspecialchars($currentUser['initials'] ?? 'SK') ?></span>
            <div>
              <div class="fw-bold text-dark" style="font-size: 0.90rem;"><?= htmlspecialchars($currentUser['full_name'] ?? 'M. Senthilkumar') ?></div>
              <div class="text-secondary small" style="font-size: 0.75rem;"><?= htmlspecialchars($currentUser['email'] ?? 'admin@karcemtextiles.com') ?></div>
              <span class="badge bg-light text-primary border border-primary-subtle mt-1" style="font-size: 0.65rem;"><?= strtoupper(htmlspecialchars($currentUser['designation'] ?? 'Managing Director')) ?></span>
            </div>
          </div>
          <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/dyeing/organization"><i data-lucide="building" class="text-secondary"><i class="bi bi-buildings"></i></i> Organization & Multi-Units</a>
          <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/dyeing/masters"><i data-lucide="settings-2" class="text-secondary"><i class="bi bi-sliders"></i></i> System Masters</a>
          <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/dyeing/reports"><i data-lucide="chart-no-axes-combined" class="text-secondary"><i class="bi bi-file-text"></i></i> Dyeing Registers</a>
          <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= $baseUrl ?>/dyeing/print"><i data-lucide="printer" class="text-secondary"><i class="bi bi-printer"></i></i> Print Form Engine</a>
          <div class="dropdown-divider my-2"></div>
          <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="<?= $baseUrl ?>/logout">
            <i data-lucide="log-out" class="text-danger"><i class="bi bi-box-arrow-right"></i></i> Sign Out
          </a>
        </div>
      </div>
    </div>
  </header>
