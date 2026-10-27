<?php
/**
 * P74 - Statutory GST Tax Invoice (Print Engine) - KARCEM Textile ERP
 * File: modules/print/views/tax-invoice.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;
use Core\Support\Barcode;

$invId = (int)($_GET['id'] ?? 1);
$copyLabel = $_GET['copy'] ?? 'Original for Recipient';

$invoice = Database::fetch("SELECT inv.*, c.name as customer_name, c.billing_address, c.gstin as customer_gstin, c.pan as customer_pan, c.state as customer_state, c.state_code as customer_state_code FROM sales_invoices inv JOIN customers c ON inv.customer_id = c.id WHERE inv.id = ?", [$invId]);
if (!$invoice) {
    die("Invoice not found");
}

$items = Database::fetchAll("SELECT ii.*, p.name as product_name, p.sku FROM sales_invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.sales_invoice_id = ?", [$invId]);
$company = Database::fetch("SELECT * FROM companies WHERE id = 1");

$amountWords = NumberToWords::inWords((float)$invoice['grand_total']);
$qrData = "IRN:{$invoice['irn_number']}|GSTIN:{$company['gstin']}|INV:{$invoice['invoice_no']}|AMT:{$invoice['grand_total']}";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>TAX INVOICE - <?= htmlspecialchars($invoice['invoice_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background: #f3f4f6;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      color: #111827;
      margin: 0;
      padding: 20px 0;
    }
    .print-sheet {
      width: 210mm;
      min-height: 297mm;
      margin: 0 auto;
      background: #ffffff;
      padding: 15mm 15mm;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
      position: relative;
    }
    .watermark {
      position: absolute;
      top: 40%;
      left: 15%;
      font-size: 5rem;
      font-weight: 800;
      color: rgba(220, 38, 38, 0.08);
      transform: rotate(-30deg);
      user-select: none;
      pointer-events: none;
      z-index: 10;
    }
    .invoice-table th, .invoice-table td {
      border: 1px solid #111827 !important;
      padding: 6px 8px;
    }
    .invoice-table thead th {
      background-color: #f8fafc !important;
      font-weight: 700;
    }
    @media print {
      body {
        background: #ffffff;
        padding: 0;
      }
      .print-sheet {
        box-shadow: none;
        margin: 0;
        width: 100%;
        min-height: auto;
        padding: 10mm 10mm;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

<!-- Control Bar -->
<div class="container mb-3 no-print" style="max-width: 210mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    <div class="d-flex align-items-center gap-2">
      <span class="fw-bold">Copy Type:</span>
      <select class="form-select form-select-sm w-auto" onchange="location.href='?id=<?= $invId ?>&copy='+encodeURIComponent(this.value)">
        <option value="Original for Recipient" <?= $copyLabel === 'Original for Recipient' ? 'selected' : '' ?>>Original for Recipient</option>
        <option value="Duplicate for Transporter" <?= $copyLabel === 'Duplicate for Transporter' ? 'selected' : '' ?>>Duplicate for Transporter</option>
        <option value="Triplicate for Supplier" <?= $copyLabel === 'Triplicate for Supplier' ? 'selected' : '' ?>>Triplicate for Supplier</option>
        <option value="Extra Copy" <?= $copyLabel === 'Extra Copy' ? 'selected' : '' ?>>Extra Copy</option>
      </select>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm px-3" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Invoice (A4)</button>
      <a href="<?= Url::to('/accounts/invoices') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return</a>
    </div>
  </div>
</div>

<div class="print-sheet">
  <?php if ($invoice['status'] === 'draft'): ?>
    <div class="watermark">DRAFT COPY</div>
  <?php elseif ($invoice['status'] === 'cancelled'): ?>
    <div class="watermark">CANCELLED</div>
  <?php endif; ?>

  <!-- Top Header & Copy Type -->
  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
    <div>
      <span class="badge bg-dark text-white text-uppercase px-2 py-1" style="letter-spacing: 0.05em; font-size: 0.75rem;">
        <?= htmlspecialchars($copyLabel) ?>
      </span>
    </div>
    <div class="text-center">
      <h2 class="h4 fw-bold text-dark text-uppercase mb-0 tracking-wider">TAX INVOICE</h2>
      <span class="small text-muted">(Issued under Section 31 of Central Goods and Services Tax Act, 2017)</span>
    </div>
    <div style="width: 80px; height: 80px;">
      <?= Barcode::qrSvg($qrData, 80) ?>
    </div>
  </div>

  <!-- Company (Supplier) and Buyer Details Grid -->
  <div class="row g-0 border mb-3" style="font-size: 0.8125rem;">
    <!-- Seller -->
    <div class="col-6 border-end p-2">
      <div class="fw-bold text-dark text-uppercase fs-6 mb-1"><?= htmlspecialchars($company['legal_name']) ?></div>
      <div><?= htmlspecialchars($company['address_line1']) ?>, <?= htmlspecialchars($company['address_line2']) ?></div>
      <div><?= htmlspecialchars($company['city']) ?>, <?= htmlspecialchars($company['state']) ?> - <?= htmlspecialchars($company['pincode']) ?></div>
      <div class="mt-1"><strong>GSTIN:</strong> <?= htmlspecialchars($company['gstin']) ?> | <strong>PAN:</strong> <?= htmlspecialchars($company['pan']) ?></div>
      <div><strong>State:</strong> Tamil Nadu (State Code: 33)</div>
      <div><strong>Phone:</strong> <?= htmlspecialchars($company['phone']) ?> | <strong>Email:</strong> <?= htmlspecialchars($company['email']) ?></div>
    </div>
    <!-- Invoice Reference -->
    <div class="col-6 p-2 bg-light">
      <div class="row g-1">
        <div class="col-5 fw-bold">Invoice Number:</div>
        <div class="col-7 font-monospace fw-bold text-primary"><?= htmlspecialchars($invoice['invoice_no']) ?></div>
        <div class="col-5 fw-bold">Invoice Date:</div>
        <div class="col-7"><?= date('d-m-Y', strtotime($invoice['invoice_date'])) ?></div>
        <div class="col-5 fw-bold">Due Date:</div>
        <div class="col-7"><?= date('d-m-Y', strtotime($invoice['due_date'])) ?></div>
        <div class="col-5 fw-bold">E-Way Bill No:</div>
        <div class="col-7 font-monospace"><?= htmlspecialchars($invoice['eway_bill_no'] ?? '541289004412') ?></div>
        <div class="col-5 fw-bold">IRN (E-Invoice):</div>
        <div class="col-7 font-monospace text-truncate" style="font-size: 0.7rem;"><?= htmlspecialchars($invoice['irn_number'] ?? '4a8f9c2d1e0b5a6c8d7e6f4a3b2c1d0e9f8a7b6c5d4e3f2a1b0c9d8e7f6a5b4') ?></div>
      </div>
    </div>
  </div>

  <!-- Buyer Information -->
  <div class="border p-2 mb-3 bg-light-subtle" style="font-size: 0.8125rem;">
    <div class="fw-bold text-uppercase border-bottom pb-1 mb-1">Details of Receiver / Billed To:</div>
    <div class="row">
      <div class="col-7">
        <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($invoice['customer_name']) ?></div>
        <div><?= nl2br(htmlspecialchars($invoice['billing_address'])) ?></div>
        <div class="mt-1"><strong>GSTIN / UIN:</strong> <?= htmlspecialchars($invoice['customer_gstin'] ?? 'Unregistered') ?></div>
      </div>
      <div class="col-5">
        <div><strong>State:</strong> <?= htmlspecialchars($invoice['customer_state']) ?></div>
        <div><strong>State Code:</strong> <?= htmlspecialchars($invoice['customer_state_code'] ?? '33') ?></div>
        <div><strong>Place of Supply:</strong> <?= htmlspecialchars($invoice['customer_state']) ?> (<?= htmlspecialchars($invoice['customer_state_code'] ?? '33') ?>)</div>
        <div><strong>Reverse Charge:</strong> No</div>
      </div>
    </div>
  </div>

  <!-- Items Table -->
  <table class="table invoice-table w-100 mb-3" style="font-size: 0.8125rem;">
    <thead>
      <tr class="text-center align-middle">
        <th style="width: 4%;">#</th>
        <th style="width: 38%;">Description of Goods / Fabric Specification</th>
        <th style="width: 10%;">HSN/SAC</th>
        <th style="width: 10%;">Qty (Kg)</th>
        <th style="width: 12%;">Rate (₹)</th>
        <th style="width: 12%;">Taxable Value (₹)</th>
        <th style="width: 14%;">Total Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($items as $it): ?>
        <tr>
          <td class="text-center"><?= $i++ ?></td>
          <td>
            <div class="fw-bold text-dark"><?= htmlspecialchars($it['product_name']) ?></div>
            <div class="text-muted" style="font-size: 0.72rem;">SKU: <?= htmlspecialchars($it['sku']) ?> · 100% Combed Cotton Single Jersey 180 GSM 72"</div>
          </td>
          <td class="text-center font-monospace"><?= htmlspecialchars($it['hsn_sac']) ?></td>
          <td class="text-end font-monospace fw-bold"><?= number_format($it['quantity'], 2) ?></td>
          <td class="text-end font-monospace"><?= NumberToWords::formatIndian($it['rate'], 2) ?></td>
          <td class="text-end font-monospace"><?= NumberToWords::formatIndian($it['quantity'] * $it['rate'], 2) ?></td>
          <td class="text-end font-monospace fw-bold"><?= NumberToWords::formatIndian($it['total_amount'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="text-end fw-bold">Total Taxable Value:</td>
        <td colspan="2" class="text-end font-monospace fw-bold">₹<?= NumberToWords::formatIndian($invoice['subtotal'], 2) ?></td>
      </tr>
      <tr>
        <td colspan="5" class="text-end">Central GST (CGST @ 2.5%):</td>
        <td colspan="2" class="text-end font-monospace">₹<?= NumberToWords::formatIndian($invoice['cgst_amount'], 2) ?></td>
      </tr>
      <tr>
        <td colspan="5" class="text-end">State GST (SGST @ 2.5%):</td>
        <td colspan="2" class="text-end font-monospace">₹<?= NumberToWords::formatIndian($invoice['sgst_amount'], 2) ?></td>
      </tr>
      <tr class="table-light">
        <td colspan="5" class="text-end fw-bold fs-6 text-uppercase">Total Invoice Value (in Figures):</td>
        <td colspan="2" class="text-end font-monospace fw-bold fs-6 text-dark">₹<?= NumberToWords::formatIndian($invoice['grand_total'], 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <!-- Amount in Words -->
  <div class="border p-2 mb-3 bg-light" style="font-size: 0.8125rem;">
    <strong>Total Invoice Amount in Words (Indian Rupees):</strong>
    <div class="fw-bold text-dark text-capitalize mt-1"><?= htmlspecialchars($amountWords) ?></div>
  </div>

  <!-- Bank Details & Terms -->
  <div class="row g-2 mb-4" style="font-size: 0.75rem;">
    <div class="col-7 border p-2">
      <div class="fw-bold text-uppercase border-bottom pb-1 mb-1">Company Bank Details for RTGS / NEFT:</div>
      <div><strong>Bank Name:</strong> HDFC Bank Limited</div>
      <div><strong>Branch:</strong> Tirupur Main Branch, Kumaran Road</div>
      <div><strong>Account Name:</strong> <?= htmlspecialchars($company['legal_name']) ?></div>
      <div><strong>Current A/c No:</strong> 50200012345678</div>
      <div><strong>IFSC Code:</strong> HDFC0000248</div>
    </div>
    <div class="col-5 border p-2">
      <div class="fw-bold text-uppercase border-bottom pb-1 mb-1">Terms & Conditions:</div>
      <div>1. Goods once sold will not be taken back.</div>
      <div>2. Interest @ 18% p.a. will be charged for delayed payments.</div>
      <div>3. Subject to Tirupur jurisdiction only.</div>
    </div>
  </div>

  <!-- Signatures -->
  <div class="row text-center mt-5" style="font-size: 0.8125rem;">
    <div class="col-6">
      <div class="border-top pt-2 mx-4 text-muted">Customer / Receiver's Signature & Seal</div>
    </div>
    <div class="col-6">
      <div class="fw-bold mb-4">For <?= htmlspecialchars($company['name']) ?></div>
      <div class="border-top pt-2 mx-4 fw-bold">Authorised Signatory</div>
    </div>
  </div>

</div>
</body>
</html>
