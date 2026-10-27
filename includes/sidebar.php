<?php
/**
 * KARCEM Textile ERP - Dedicated Dyeing & Wet Processing Navigation Sidebar
 * File: includes/sidebar.php
 * Ultra-Clean Minimalist Black & White Theme with Organized Collapsible Groups
 * Compact width with numbers removed and dual module/URL matching to ensure dropdowns remain open
 */

use Core\Support\Url;

$baseUrl = Url::base();
$activeModule = $activeModule ?? 'dashboard';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

// Clean Grouped Structure with Lucide and Bootstrap Fallback Icons
$menuGroups = [
    [
        'id' => 'grpInward',
        'title' => 'Inward & Verification',
        'lucide' => 'package-plus',
        'icon' => 'bi-box-arrow-in-down',
        'modules' => ['grey-inward', 'grey-inspection'],
        'items' => [
            ['key' => 'grey-inward', 'path' => '/dyeing/grey-inward', 'label' => 'Grey Fabric Inward'],
            ['key' => 'grey-inspection', 'path' => '/dyeing/grey-inspection', 'label' => 'Grey Inspection & QC'],
        ]
    ],
    [
        'id' => 'grpPlanning',
        'title' => 'Planning & Machine',
        'lucide' => 'layers',
        'icon' => 'bi-calendar3',
        'modules' => ['job-cards', 'batch-planning', 'machine-allocation'],
        'items' => [
            ['key' => 'job-cards', 'path' => '/dyeing/job-cards', 'label' => 'Dyeing Job Cards'],
            ['key' => 'batch-planning', 'path' => '/dyeing/batch-planning', 'label' => 'Batch Planning'],
            ['key' => 'machine-allocation', 'path' => '/dyeing/machine-allocation', 'label' => 'Machine Allocation'],
        ]
    ],
    [
        'id' => 'grpLab',
        'title' => 'Lab Dip & Chemicals',
        'lucide' => 'flask-conical',
        'icon' => 'bi-eyedropper',
        'modules' => ['lab-dips', 'recipes', 'recipe-calculation', 'chemical-requests'],
        'items' => [
            ['key' => 'lab-dips', 'path' => '/dyeing/lab-dips', 'label' => 'Lab Dip & Approvals'],
            ['key' => 'recipes', 'path' => '/dyeing/recipes', 'label' => 'Dye Recipe Master'],
            ['key' => 'recipe-calculation', 'path' => '/dyeing/recipe-calculation', 'label' => 'Recipe Calculation'],
            ['key' => 'chemical-requests', 'path' => '/dyeing/chemical-requests', 'label' => 'Chemical Requests'],
        ]
    ],
    [
        'id' => 'grpFloor',
        'title' => 'Vessel Floor Execution',
        'lucide' => 'gauge',
        'icon' => 'bi-cpu',
        'modules' => ['process-sheets', 'loading-sheets', 'production-entry', 'batches', 'shade-checks'],
        'items' => [
            ['key' => 'process-sheets', 'path' => '/dyeing/process-sheets', 'label' => 'Process Sheets'],
            ['key' => 'loading-sheets', 'path' => '/dyeing/loading-sheets', 'label' => 'Loading Sheets'],
            ['key' => 'production-entry', 'path' => '/dyeing/production-entry', 'label' => 'Production Entry'],
            ['key' => 'shade-checks', 'path' => '/dyeing/shade-checks', 'label' => 'In-process Shade'],
            ['key' => 'batches', 'path' => '/dyeing/batches', 'label' => 'Active Batches'],
        ]
    ],
    [
        'id' => 'grpQC',
        'title' => 'QC & Finishing',
        'lucide' => 'clipboard-check',
        'icon' => 'bi-check2-circle',
        'modules' => ['batch-qc', 'reprocess-requests', 'finishing', 'four-point-inspection'],
        'items' => [
            ['key' => 'batch-qc', 'path' => '/dyeing/batch-qc', 'label' => 'Dyeing Batch QC'],
            ['key' => 'reprocess-requests', 'path' => '/dyeing/reprocess-requests', 'label' => 'Re-dye / Reprocess'],
            ['key' => 'finishing', 'path' => '/dyeing/finishing', 'label' => 'Finishing & Stenter'],
            ['key' => 'four-point-inspection', 'path' => '/dyeing/inspection', 'label' => 'Fabric Inspection'],
        ]
    ],
    [
        'id' => 'grpPacking',
        'title' => 'Packing & Handover',
        'lucide' => 'package-check',
        'icon' => 'bi-box-seam',
        'modules' => ['roll-packing', 'wastage-loss', 'batch-costing', 'job-work', 'stock-transfer', 'reports'],
        'items' => [
            ['key' => 'roll-packing', 'path' => '/dyeing/roll-packing', 'label' => 'Roll Packing & Labels'],
            ['key' => 'wastage-loss', 'path' => '/dyeing/wastage-loss', 'label' => 'Wastage & Loss'],
            ['key' => 'batch-costing', 'path' => '/dyeing/batch-costing', 'label' => 'Batch Costing'],
            ['key' => 'job-work', 'path' => '/dyeing/job-work', 'label' => 'Job-Work Challans'],
            ['key' => 'stock-transfer', 'path' => '/dyeing/stock-transfer', 'label' => 'Stock Transfer'],
            ['key' => 'reports', 'path' => '/dyeing/reports', 'label' => 'Dyeing Reports'],
        ]
    ],
    [
        'id' => 'grpMasters',
        'title' => 'Masters & Settings',
        'lucide' => 'settings-2',
        'icon' => 'bi-sliders',
        'modules' => ['organization', 'masters', 'print'],
        'items' => [
            ['key' => 'organization', 'path' => '/dyeing/organization', 'label' => 'Company & Multi-Units'],
            ['key' => 'masters', 'path' => '/dyeing/masters', 'label' => 'Dyeing Masters'],
            ['key' => 'print', 'path' => '/dyeing/print', 'label' => 'Print Form Engine'],
        ]
    ]
];

// Dual Matching: Match by activeModule OR URL request path so dropdowns never close on active page
foreach ($menuGroups as &$grp) {
    $grp['has_active'] = in_array($activeModule, $grp['modules']);
    foreach ($grp['items'] as &$item) {
        $item['is_active'] = ($activeModule === $item['key'])
            || ($requestPath === $item['path'])
            || (str_ends_with($requestPath, $item['path']))
            || ($item['path'] !== '/dyeing' && rtrim($requestPath, '/') === rtrim($item['path'], '/'));
        if ($item['is_active']) {
            $grp['has_active'] = true;
        }
    }
    unset($item);
}
unset($grp);
?>
<!-- Left Minimalist Sidebar Navigation -->
<aside class="cz-sidebar" id="czMainSidebar">
  <nav class="cz-nav-simple">

    <!-- Direct Dashboard Link -->
    <a class="cz-menu-link <?= ($activeModule === 'dashboard' || $requestPath === '/dyeing/dashboard' || $requestPath === '/dashboard') ? 'active' : '' ?>" 
       href="<?= Url::to('/dyeing/dashboard') ?>" 
       title="Dyeing Dashboard">
      <i data-lucide="layout-dashboard" class="cz-menu-icon"><i class="bi bi-grid"></i></i>
      <span>Dashboard</span>
    </a>

    <div class="my-2 border-top border-light opacity-50"></div>

    <!-- Clean Grouped Accordion Sections (No Numbers, No Badges) -->
    <?php foreach ($menuGroups as $grp): ?>
      <div class="cz-group">
        <button class="cz-group-btn <?= $grp['has_active'] ? 'has-active' : '' ?>" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#<?= $grp['id'] ?>" 
                aria-expanded="<?= $grp['has_active'] ? 'true' : 'false' ?>" 
                aria-controls="<?= $grp['id'] ?>"
                title="<?= htmlspecialchars($grp['title']) ?>">
          <div class="d-flex align-items-center gap-2 text-truncate">
            <i data-lucide="<?= htmlspecialchars($grp['lucide']) ?>" class="cz-menu-icon"><i class="bi <?= htmlspecialchars($grp['icon']) ?>"></i></i>
            <span class="text-truncate"><?= htmlspecialchars($grp['title']) ?></span>
          </div>
          <i class="bi bi-chevron-down cz-group-chevron flex-shrink-0 ms-1"></i>
        </button>

        <div class="collapse <?= $grp['has_active'] ? 'show' : '' ?>" id="<?= $grp['id'] ?>">
          <div class="cz-submenu">
            <?php foreach ($grp['items'] as $item): ?>
              <a class="cz-sub-link <?= $item['is_active'] ? 'active' : '' ?>" href="<?= Url::to($item['path']) ?>" title="<?= htmlspecialchars($item['label']) ?>">
                <span class="text-truncate"><?= htmlspecialchars($item['label']) ?></span>
                <?php if ($item['is_active']): ?>
                  <i class="bi bi-check2 text-white small fw-bold flex-shrink-0 ms-1"></i>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </nav>

  <!-- Sidebar Footer Status -->
  <div class="cz-sidebar-footer">
    <div class="cz-footer-text d-flex align-items-center gap-2 text-truncate">
      <span class="status-live-dot flex-shrink-0"></span>
      <span class="small text-truncate" style="font-size: 0.74rem; color: #475569 !important; font-weight: 500;">Live SF-01/02</span>
    </div>
    <button class="cz-icon-btn p-0 flex-shrink-0" id="czSidebarCollapseBtn" type="button" aria-label="Toggle Collapse" title="Collapse Sidebar">
      <i class="bi bi-chevron-bar-left fs-6"></i>
    </button>
  </div>
</aside>

<!-- Mobile Offcanvas Sidebar (Clean Professional Light) -->
<div class="offcanvas offcanvas-start bg-white text-dark border-end" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel" style="width: 270px;">
  <div class="offcanvas-header border-bottom p-3">
    <div class="d-flex align-items-center gap-2">
      <div class="cz-brand-icon-box" style="width: 32px; height: 32px;">
        <i data-lucide="droplet"><i class="bi bi-droplet-half"></i></i>
      </div>
      <div>
        <h5 class="offcanvas-title fs-6 fw-bold text-dark mb-0" id="mobileSidebarLabel">KARCEM DYEING</h5>
        <span class="text-secondary small" style="font-size: 0.70rem; font-weight: 500;">Wet Processing ERP</span>
      </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-2" style="overflow-y: auto;">
    <a class="cz-menu-link mb-2 <?= ($activeModule === 'dashboard' || $requestPath === '/dyeing/dashboard') ? 'active' : '' ?>" href="<?= Url::to('/dyeing/dashboard') ?>">
      <i data-lucide="layout-dashboard"><i class="bi bi-grid"></i></i>
      <span>Dashboard</span>
    </a>
    <?php foreach ($menuGroups as $grp): ?>
      <div class="cz-group mb-1">
        <button class="cz-group-btn <?= $grp['has_active'] ? 'has-active' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#mob_<?= $grp['id'] ?>">
          <div class="d-flex align-items-center gap-2 text-truncate">
            <i data-lucide="<?= htmlspecialchars($grp['lucide']) ?>"><i class="bi <?= htmlspecialchars($grp['icon']) ?>"></i></i>
            <span class="text-truncate"><?= htmlspecialchars($grp['title']) ?></span>
          </div>
          <i class="bi bi-chevron-down cz-group-chevron"></i>
        </button>
        <div class="collapse <?= $grp['has_active'] ? 'show' : '' ?> ps-2" id="mob_<?= $grp['id'] ?>">
          <div class="cz-submenu">
            <?php foreach ($grp['items'] as $item): ?>
              <a class="cz-sub-link <?= $item['is_active'] ? 'active' : '' ?>" href="<?= Url::to($item['path']) ?>">
                <span class="text-truncate"><?= htmlspecialchars($item['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
