<?php
/**
 * Stores & Inventory Hub - KARCEM Textile ERP
 * File: modules/inventory/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Support\NumberToWords;

$pageTitle = 'Stores & Inventory Hub';
$activeModule = 'inventory';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$totalLots = (int)Database::fetch("SELECT COUNT(*) FROM inventory_lots")['COUNT(*)'];
$totalRolls = (int)Database::fetch("SELECT COUNT(*) FROM inventory_rolls")['COUNT(*)'];
$totalGrns = (int)Database::fetch("SELECT COUNT(*) FROM goods_receipts")['COUNT(*)'];
$stockMovementsCount = (int)Database::fetch("SELECT COUNT(*) FROM inventory_movements")['COUNT(*)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-box-seam"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Stores & Immutable Inventory Ledger</h1>
          <p class="text-secondary small mb-0">Multi-warehouse stock control, incoming GRN, quality inspection quarantine, lot & roll tracking, material reservations, and immutable movement ledger.</p>
        </div>
      </div>
    </section>

    <!-- Quick Cards Grid -->
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary text-white">Inward Gate</span>
                <i class="bi bi-box-arrow-in-down fs-4 text-primary"></i>
              </div>
              <h5 class="fw-bold text-dark">Goods Receipt Notes (GRN)</h5>
              <p class="text-secondary small">Received yarn, grey fabric, dyes, chemicals with supplier challan reference and weighbridge entry.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalGrns ?> GRNs</span>
              <a href="<?= Url::to('/inventory/grn') ?>" class="btn btn-sm btn-dark">Open GRN &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success">Barcodes</span>
                <i class="bi bi-upc-scan fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">Fabric Roll Traceability</h5>
              <p class="text-secondary small">Individual fabric rolls with barcode tags, net weight, length, GSM, width, and quality grade.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalRolls ?> Rolls</span>
              <a href="<?= Url::to('/inventory/rolls') ?>" class="btn btn-sm btn-outline-dark">Roll Register</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Immutable Ledger</span>
                <i class="bi bi-journal-arrow-down fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Stock Movement Ledger</h5>
              <p class="text-secondary small">Audit-compliant immutable ledger for all material ins, outs, transfers, issues, and adjustments.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $stockMovementsCount ?> Postings</span>
              <a href="<?= Url::to('/inventory/movements') ?>" class="btn btn-sm btn-outline-dark">Stock Ledger</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-warning-subtle text-warning">Genealogy</span>
                <i class="bi bi-diagram-3 fs-4 text-warning"></i>
              </div>
              <h5 class="fw-bold text-dark">Lot Traceability & Ownership</h5>
              <p class="text-secondary small">Distinct separation of Company Owned vs Customer Owned stock (Jobwork material isolation).</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalLots ?> Active Lots</span>
              <a href="<?= Url::to('/inventory/lots') ?>" class="btn btn-sm btn-outline-dark">Lot Genealogy</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-dark text-white">Logistics</span>
                <i class="bi bi-truck fs-4 text-dark"></i>
              </div>
              <h5 class="fw-bold text-dark">Gate Pass & Weighbridge</h5>
              <p class="text-secondary small">Returnable and non-returnable materials, vehicle tare/gross weighment, security gate logs.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Security & Transporters</span>
              <a href="<?= Url::to('/gate/passes') ?>" class="btn btn-sm btn-outline-dark">Gate System</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-danger-subtle text-danger">Stock Health</span>
                <i class="bi bi-hourglass-split fs-4 text-danger"></i>
              </div>
              <h5 class="fw-bold text-dark">Stock Aging & Dead Stock</h5>
              <p class="text-secondary small">Aging analysis of grey and dyed fabrics, slow-moving chemicals, and physical count reconciliation.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Aging Analysis</span>
              <a href="<?= Url::to('/reports/stock-aging') ?>" class="btn btn-sm btn-outline-dark">Stock Aging</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
