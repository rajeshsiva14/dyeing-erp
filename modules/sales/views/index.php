<?php
/**
 * Commercial & Sales Management Hub - KARCEM Textile ERP
 * File: modules/sales/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Commercial & Sales Hub';
$activeModule = 'sales';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$totalOrders = (int)Database::fetch("SELECT COUNT(*) FROM sales_orders")['COUNT(*)'];
$confirmedOrders = (int)Database::fetch("SELECT COUNT(*) FROM sales_orders WHERE status = 'confirmed'")['COUNT(*)'];
$totalCustomers = (int)Database::fetch("SELECT COUNT(*) FROM customers WHERE status = 'active'")['COUNT(*)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-briefcase"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Commercial, Merchandising & Customer Orders</h1>
          <p class="text-secondary small mb-0">Order-to-cash workflow from customer enquiry, price quotation, customer PO confirmation, credit checks, fabric specifications to delivery.</p>
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
                <span class="badge bg-primary text-white">Core Workflow</span>
                <i class="bi bi-file-earmark-text fs-4 text-primary"></i>
              </div>
              <h5 class="fw-bold text-dark">Sales Orders Register</h5>
              <p class="text-secondary small">Confirmed customer orders, delivery schedules, shade codes, and size breakdown matrix.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalOrders ?> Orders</span>
              <a href="<?= Url::to('/sales/orders') ?>" class="btn btn-sm btn-dark">Open Orders &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success">Estimations</span>
                <i class="bi bi-calculator fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">Quotations & Price Terms</h5>
              <p class="text-secondary small">Fabric and yarn rates, volume discounts, payment terms, and client quotation validity.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Active Price Lists</span>
              <a href="<?= Url::to('/sales/orders') ?>" class="btn btn-sm btn-outline-dark">View Quotations</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Global Trade</span>
                <i class="bi bi-globe fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Export Orders & LC</h5>
              <p class="text-secondary small">Letter of Credit (LC) tracking, Proforma Invoices, Incoterms, and overseas buyer compliance.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Incoterms: FOB / CIF</span>
              <a href="<?= Url::to('/sales/orders') ?>" class="btn btn-sm btn-outline-dark">Export Desk</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-warning-subtle text-warning">Directory</span>
                <i class="bi bi-people fs-4 text-warning"></i>
              </div>
              <h5 class="fw-bold text-dark">Customer Master Directory</h5>
              <p class="text-secondary small">GSTIN, billing/shipping addresses, credit limits, credit days, and customer ledger balance.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalCustomers ?> Customers</span>
              <a href="<?= Url::to('/masters/customers') ?>" class="btn btn-sm btn-outline-dark">Customer Master</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-dark text-white">Printable P03</span>
                <i class="bi bi-printer fs-4 text-dark"></i>
              </div>
              <h5 class="fw-bold text-dark">Order Confirmation Printout</h5>
              <p class="text-secondary small">Standard P03 sales order confirmation sheet with size/color matrix, terms, and signature blocks.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">P03 Document Format</span>
              <a href="<?= Url::to('/print/sales-order?id=1') ?>" target="_blank" class="btn btn-sm btn-dark"><i class="bi bi-printer me-1"></i> Preview P03</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-danger-subtle text-danger">Analytics</span>
                <i class="bi bi-graph-up fs-4 text-danger"></i>
              </div>
              <h5 class="fw-bold text-dark">Order Lifecycle Tracker</h5>
              <p class="text-secondary small">Real-time tracking of order progression: Order &rarr; Yarn &rarr; Knitting &rarr; Dyeing &rarr; QC &rarr; Dispatch.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Traceability Matrix</span>
              <a href="<?= Url::to('/reports/order-lifecycle') ?>" class="btn btn-sm btn-outline-dark">View Lifecycle</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
