<?php
/**
 * Print Form Engine Catalogue (P01 to P113, L01 to L08)
 * File: modules/print/views/index.php
 */

use Core\Support\Url;

$pageTitle = 'Print Form Engine Catalogue';
$activeModule = 'print';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$documents = [
    ['code' => 'P03', 'name' => 'Sales Order Confirmation', 'dept' => 'Sales', 'size' => 'A4 Portrait', 'url' => '/print/sales-order?id=1', 'badge' => 'Active Template'],
    ['code' => 'P40', 'name' => 'Dyeing Batch Card & Recipe Sheet', 'dept' => 'Dyeing', 'size' => 'A4 Portrait', 'url' => '/print/batch-card?id=1', 'badge' => 'Active Template'],
    ['code' => 'P46', 'name' => 'ASTM 4-Point System Fabric Quality Certificate', 'dept' => 'Quality', 'size' => 'A4 Portrait', 'url' => '/print/fabric-inspection?id=1', 'badge' => 'Active Template'],
    ['code' => 'P74', 'name' => 'Statutory GST Tax Invoice (E-Invoice IRN & QR)', 'dept' => 'Accounts', 'size' => 'A4 Portrait', 'url' => '/print/tax-invoice?id=1', 'badge' => 'Active Template'],
    ['code' => 'L01', 'name' => 'Thermal Fabric Roll Barcode Label (3" x 4")', 'dept' => 'Stores', 'size' => 'Thermal 4x3"', 'url' => '/print/roll-label?id=1', 'badge' => 'Active Template'],
    ['code' => 'P13', 'name' => 'Purchase Order (Supplier Release)', 'dept' => 'Purchase', 'size' => 'A4 Portrait', 'url' => '/print/sales-order?id=1', 'badge' => 'Standard'],
    ['code' => 'P15', 'name' => 'Goods Receipt Note (GRN Inward)', 'dept' => 'Stores', 'size' => 'A4 Portrait', 'url' => '/inventory/grn', 'badge' => 'Standard'],
    ['code' => 'P24', 'name' => 'Security Gate Pass (Returnable & Outward)', 'dept' => 'Gate', 'size' => 'A5 Landscape', 'url' => '/gate/passes', 'badge' => 'Standard'],
    ['code' => 'P49', 'name' => 'Garment Bundle Ticket with QR Code', 'dept' => 'Garments', 'size' => 'Thermal', 'url' => '/garments/cutting', 'badge' => 'Standard'],
    ['code' => 'P79', 'name' => 'Customer Receipt Acknowledgement Voucher', 'dept' => 'Accounts', 'size' => 'A5 Landscape', 'url' => '/accounts/invoices', 'badge' => 'Standard'],
    ['code' => 'P98', 'name' => 'Financial Trial Balance & P&L Statement', 'dept' => 'Finance', 'size' => 'A4 Landscape', 'url' => '/accounts/trial-balance', 'badge' => 'Standard'],
    ['code' => 'L02', 'name' => 'Yarn Cone / Carton Packaging Label', 'dept' => 'Spinning', 'size' => 'Thermal 4x3"', 'url' => '/print/roll-label?id=1', 'badge' => 'Standard'],
];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-dark text-white p-3 rounded-circle fs-3"><i class="bi bi-printer"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Print Form Engine & Barcode Template Manager</h1>
          <p class="text-secondary small mb-0">Controlled statutory print engine: A4 Portrait/Landscape, A5, and 3-inch/4-inch thermal labels with copy labels (Original/Duplicate/Triplicate), barcodes, QR codes, watermarks, and Tamil fonts.</p>
        </div>
      </div>
    </section>

    <!-- Document Catalogue Grid -->
    <div class="row g-3">
      <?php foreach ($documents as $d): ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="badge bg-dark font-monospace fs-6"><?= htmlspecialchars($d['code']) ?></span>
                  <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($d['badge']) ?></span>
                </div>
                <h5 class="fw-bold text-dark fs-6 mb-1"><?= htmlspecialchars($d['name']) ?></h5>
                <div class="small text-secondary mb-3">
                  <span>Department: <strong><?= htmlspecialchars($d['dept']) ?></strong></span> ·
                  <span>Size: <?= htmlspecialchars($d['size']) ?></span>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="text-muted small">Watermarked / Clean</span>
                <a href="<?= Url::to($d['url']) ?>" target="_blank" class="btn btn-sm btn-dark">
                  <i class="bi bi-printer me-1"></i> Preview Printout &rarr;
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
