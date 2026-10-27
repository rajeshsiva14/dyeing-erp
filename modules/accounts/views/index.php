<?php
/**
 * Accounts, Billing & Finance Hub - KARCEM Textile ERP
 * File: modules/accounts/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Support\NumberToWords;

$pageTitle = 'Billing & Finance Hub';
$activeModule = 'accounts';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$totalInvoices = (int)Database::fetch("SELECT COUNT(*) FROM sales_invoices")['COUNT(*)'];
$totalReceipts = (int)Database::fetch("SELECT COUNT(*) FROM customer_receipts")['COUNT(*)'];
$totalJournals = (int)Database::fetch("SELECT COUNT(*) FROM journal_entries")['COUNT(*)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-bank"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Billing, General Ledger & Statutory GST</h1>
          <p class="text-secondary small mb-0">End-to-end accounting: GST Tax Invoices, E-Way Bill generation, receipt reconciliation, balanced double-entry journals, sub-ledgers, and real-time trial balance.</p>
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
                <span class="badge bg-primary text-white">GST Invoicing</span>
                <i class="bi bi-receipt fs-4 text-primary"></i>
              </div>
              <h5 class="fw-bold text-dark">Sales Tax Invoices</h5>
              <p class="text-secondary small">GST compliant sales invoices, IRN QR codes, E-Way bill generation, and customer payment tracking.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalInvoices ?> Invoices</span>
              <a href="<?= Url::to('/accounts/invoices') ?>" class="btn btn-sm btn-dark">Open Invoices &rarr;</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-success-subtle text-success">Double-Entry</span>
                <i class="bi bi-journal-text fs-4 text-success"></i>
              </div>
              <h5 class="fw-bold text-dark">General Ledger & Trial Balance</h5>
              <p class="text-secondary small">Real-time chart of accounts trial balance with automatic debit=credit balance validation.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="badge bg-success text-white">Balanced</span>
              <a href="<?= Url::to('/accounts/trial-balance') ?>" class="btn btn-sm btn-outline-dark">Trial Balance</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info">Collections</span>
                <i class="bi bi-cash-stack fs-4 text-info"></i>
              </div>
              <h5 class="fw-bold text-dark">Customer Receipts</h5>
              <p class="text-secondary small">Record customer NEFT/RTGS bank transfers, cheque clearances, and invoice allocations.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="fw-bold text-dark"><?= $totalReceipts ?> Receipts</span>
              <a href="<?= Url::to('/accounts/invoices') ?>" class="btn btn-sm btn-outline-dark">Receipt Register</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-dark text-white">P74 Document</span>
                <i class="bi bi-printer fs-4 text-dark"></i>
              </div>
              <h5 class="fw-bold text-dark">P74 Tax Invoice Printout</h5>
              <p class="text-secondary small">Official print format with GST breakup, HSN summary, E-Invoice IRN & QR, bank details, and amounts in words.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">P74 Printable</span>
              <a href="<?= Url::to('/print/tax-invoice?id=1') ?>" target="_blank" class="btn btn-sm btn-dark"><i class="bi bi-printer me-1"></i> Preview P74</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-warning-subtle text-warning">Payables</span>
                <i class="bi bi-bag-check fs-4 text-warning"></i>
              </div>
              <h5 class="fw-bold text-dark">Supplier Bills & 3-Way Match</h5>
              <p class="text-secondary small">Match PO rates, GRN accepted weights, and supplier bills with tolerance validation.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">3-Way Matching</span>
              <a href="<?= Url::to('/procurement/orders') ?>" class="btn btn-sm btn-outline-dark">Supplier Bills</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body p-4 d-flex flex-column justify-content-between">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-danger-subtle text-danger">Statutory</span>
                <i class="bi bi-file-earmark-check fs-4 text-danger"></i>
              </div>
              <h5 class="fw-bold text-dark">GST Returns Working Papers</h5>
              <p class="text-secondary small">GSTR-1 outward supplies, GSTR-3B monthly tax summary, and ITC reconciliation data.</p>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
              <span class="text-muted small">Tamil Nadu State 33</span>
              <a href="<?= Url::to('/accounts/trial-balance') ?>" class="btn btn-sm btn-outline-dark">GST Summary</a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
