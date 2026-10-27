<?php
/**
 * Procurement & Purchasing Hub - KARCEM Textile ERP
 * File: modules/procurement/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Procurement & Purchasing Hub';
$activeModule = 'procurement';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$totalSuppliers = (int)Database::fetch("SELECT COUNT(*) FROM suppliers WHERE status = 'active'")['COUNT(*)'];
$totalPOs = (int)Database::fetch("SELECT COUNT(*) FROM purchase_orders")['COUNT(*)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-cart-check"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Procurement, Raw Materials & Supplier Management</h1>
          <p class="text-secondary small mb-0">Procure-to-pay workflow: Purchase requisition, RFQ supplier comparison, purchase orders, 3-way matching (PO vs GRN vs Bill), and vendor performance.</p>
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
                <span class="badge bg-primary text-white">Purchase Orders</span>
                <i class="bi bi-cart-check fs-4 text-primary"></i>
              </div>
              <h5 class="fw-bold text-dark">Purchase Orders (PO)</h5>
              <p class="text-secondary small">Release purchase orders to cotton ginners, yarn mills, and dye/chemical suppliers with tax terms.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalPOs ?> POs</span>
              <a href="<?= Url::to('/procurement/orders') ?>" class="btn btn-sm btn-dark">Open POs &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success">Vendors</span>
                <i class="bi bi-building fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">Supplier Master</h5>
              <p class="text-secondary small">Profiles, payment terms, MSME certifications, and trade payable ledgers.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalSuppliers ?> Suppliers</span>
              <a href="<?= Url::to('/masters/suppliers') ?>" class="btn btn-sm btn-outline-dark">Supplier Directory</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Inward Match</span>
                <i class="bi bi-box-arrow-in-down fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Goods Receipt (GRN)</h5>
              <p class="text-secondary small">Compare PO ordered vs actual accepted receipt quantity with tolerance limits.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">3-Way Match</span>
              <a href="<?= Url::to('/inventory/grn') ?>" class="btn btn-sm btn-outline-dark">Go to GRN</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
