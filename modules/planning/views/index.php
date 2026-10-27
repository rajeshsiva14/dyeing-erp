<?php
/**
 * Production Planning & BOM Hub - KARCEM Textile ERP
 * File: modules/planning/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Production Planning Hub';
$activeModule = 'planning';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$totalBOM = (int)Database::fetch("SELECT COUNT(*) FROM bom_headers")['COUNT(*)'];
$totalProdOrders = (int)Database::fetch("SELECT COUNT(*) FROM production_orders")['COUNT(*)'];
$totalMachines = (int)Database::fetch("SELECT COUNT(*) FROM machines WHERE is_active = 1")['COUNT(*)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-calendar2-week"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Production Planning, Scheduling & Material Demand</h1>
          <p class="text-secondary small mb-0">Translate customer sales orders into Bill of Materials (BOM), manufacturing routes, machine allocation, material reservations, and shop-floor production orders.</p>
        </div>
      </div>
    </section>

    <!-- Cards Grid -->
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary text-white">Execution</span>
                <i class="bi bi-calendar2-check fs-4 text-primary"></i>
              </div>
              <h5 class="fw-bold text-dark">Production Orders Register</h5>
              <p class="text-secondary small">Release production orders to floor, track stage-wise output, scrap factors, and target delivery dates.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalProdOrders ?> Planned</span>
              <a href="<?= Url::to('/planning/orders') ?>" class="btn btn-sm btn-dark">Open Orders &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success">Engineering</span>
                <i class="bi bi-diagram-3 fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">Bill of Materials (BOM)</h5>
              <p class="text-secondary small">Multi-level textile BOMs with yarn counts, fabric constructions, dyeing liquor ratios, and trim consumption.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Standard Yield %</span>
              <a href="<?= Url::to('/planning/orders') ?>" class="btn btn-sm btn-outline-dark">BOM Master</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Floor Capacity</span>
                <i class="bi bi-cpu fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Machine & Work Centre Master</h5>
              <p class="text-secondary small">Soft flow dyeing vessels, stenters, circular knitting machines, spindles, and hourly operating costs.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalMachines ?> Machines</span>
              <a href="<?= Url::to('/masters/machines') ?>" class="btn btn-sm btn-outline-dark">Machine Master</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
