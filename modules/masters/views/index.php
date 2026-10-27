<?php
/**
 * Master Data & System Setup Hub - KARCEM Textile ERP
 * File: modules/masters/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Master Data Directory';
$activeModule = 'masters';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$totalCust = (int)Database::fetch("SELECT COUNT(*) FROM customers")['COUNT(*)'];
$totalSupp = (int)Database::fetch("SELECT COUNT(*) FROM suppliers")['COUNT(*)'];
$totalProd = (int)Database::fetch("SELECT COUNT(*) FROM products")['COUNT(*)'];
$totalMach = (int)Database::fetch("SELECT COUNT(*) FROM machines")['COUNT(*)'];
$totalWare = (int)Database::fetch("SELECT COUNT(*) FROM warehouses")['COUNT(*)'];
$totalTax = (int)Database::fetch("SELECT COUNT(*) FROM tax_codes")['COUNT(*)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-dark text-white p-3 rounded-circle fs-3"><i class="bi bi-database-gear"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Enterprise Master Data & System Configurations</h1>
          <p class="text-secondary small mb-0">Central configuration: Customer directory, suppliers, product SKUs, textile technical parameters (yarn counts, GSM, widths, constructions), machine capacities, and GST tax codes.</p>
        </div>
      </div>
    </section>

    <!-- Master Cards -->
    <div class="row g-3">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-primary text-white">Commercial</span>
                <i class="bi bi-people fs-4 text-primary"></i>
              </div>
              <h5 class="fw-bold text-dark">Customer Directory</h5>
              <p class="text-secondary small">Addresses, GSTINs, payment terms, credit limits, credit days, and customer-specific shade references.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalCust ?> Customers</span>
              <a href="<?= Url::to('/masters/customers') ?>" class="btn btn-sm btn-dark">Open Customers &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success">Procurement</span>
                <i class="bi bi-building fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">Supplier Master</h5>
              <p class="text-secondary small">Cotton ginners, spinning mills, chemical suppliers, dye manufacturers, and MSME registrations.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalSupp ?> Suppliers</span>
              <a href="<?= Url::to('/masters/suppliers') ?>" class="btn btn-sm btn-outline-dark">Open Suppliers</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Product Catalog</span>
                <i class="bi bi-box-seam fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Product & Technical Master</h5>
              <p class="text-secondary small">SKU, HSN/SAC codes, base UOMs, yarn counts, fabric constructions, GSM, width, and standard prices.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalProd ?> SKUs</span>
              <a href="<?= Url::to('/masters/products') ?>" class="btn btn-sm btn-outline-dark">Open Products</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-warning-subtle text-warning">Machinery</span>
                <i class="bi bi-cpu fs-4 text-warning"></i>
              </div>
              <h5 class="fw-bold text-dark">Machine & Work Centre Master</h5>
              <p class="text-secondary small">Dyeing vessels, stenters, compactors, knitting machines, capacities (Kg), and hourly operating costs.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalMach ?> Machines</span>
              <a href="<?= Url::to('/masters/machines') ?>" class="btn btn-sm btn-outline-dark">Open Machines</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary-subtle text-secondary">Godowns</span>
                <i class="bi bi-houses fs-4 text-secondary"></i>
              </div>
              <h5 class="fw-bold text-dark">Warehouse & Bin Master</h5>
              <p class="text-secondary small">Raw cotton, grey fabric, dyes/chemicals, finished goods, and designated quarantine holding areas.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalWare ?> Warehouses</span>
              <a href="<?= Url::to('/inventory') ?>" class="btn btn-sm btn-outline-dark">Warehouses</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-dark text-white">Statutory</span>
                <i class="bi bi-percent fs-4 text-dark"></i>
              </div>
              <h5 class="fw-bold text-dark">GST & Tax Master</h5>
              <p class="text-secondary small">GST 5% (Yarn/Fabric), GST 12% (Garments), GST 18% (Chemicals/Dyes) with CGST/SGST/IGST breakdown.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalTax ?> Tax Rates</span>
              <a href="<?= Url::to('/accounts') ?>" class="btn btn-sm btn-outline-dark">Tax Rates</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
