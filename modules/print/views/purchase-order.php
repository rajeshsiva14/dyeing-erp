<?php
/**
 * P13 - Purchase Order (Print Engine) - KARCEM Textile ERP
 * File: modules/print/views/purchase-order.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;
use Core\Support\Barcode;

$poId = (int)($_GET['id'] ?? 1);
$po = Database::fetch("SELECT po.*, s.name as supplier_name, s.code as supplier_code, s.address as supplier_address, s.gstin as supplier_gstin, s.phone as supplier_phone, s.email as supplier_email FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id WHERE po.id = ?", [$poId]);
if (!$po) {
    die("Purchase Order not found");
}

$items = Database::fetchAll("SELECT poi.*, p.name as product_name, p.sku, u.code as uom_code FROM purchase_order_items poi JOIN products p ON poi.product_id = p.id JOIN uoms u ON poi.uom_id = u.id WHERE poi.purchase_order_id = ?", [$poId]);
$company = Database::fetch("SELECT * FROM companies WHERE id = 1");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>PURCHASE ORDER — <?= htmlspecialchars($po['po_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f3f4f6; font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px 0; }
    .print-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; background: #ffffff; padding: 15mm; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .po-table th, .po-table td { border: 1px solid #111827 !important; padding: 6px 8px; }
    @media print { body { background: #fff; padding: 0; } .print-sheet { box-shadow: none; width: 100%; padding: 10mm; } .no-print { display: none !important; } }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 210mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    <div class="fw-bold">P13 — Purchase Order Print</div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print PO</button>
      <a href="<?= Url::to('/procurement/orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return</a>
    </div>
  </div>
</div>

<div class="print-sheet">
  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($company['name']) ?></h3>
      <div class="small text-muted"><?= htmlspecialchars($company['address_line1']) ?>, <?= htmlspecialchars($company['city']) ?>, Tamil Nadu</div>
      <div class="small text-muted">GSTIN: <?= htmlspecialchars($company['gstin']) ?> | CIN: <?= htmlspecialchars($company['cin']) ?></div>
    </div>
    <div class="text-end">
      <span class="badge bg-dark text-white px-3 py-1 fs-6">P13 PURCHASE ORDER</span>
      <div class="font-monospace fw-bold fs-5 text-primary mt-1"><?= htmlspecialchars($po['po_no']) ?></div>
      <div class="small text-muted">Date: <?= date('d M Y', strtotime($po['po_date'])) ?></div>
    </div>
  </div>

  <div class="row g-3 mb-3 small">
    <div class="col-6">
      <div class="p-2 border rounded bg-light">
        <div class="text-uppercase fw-bold text-secondary mb-1" style="font-size:0.75rem;">SUPPLIER DETAILS (VENDOR)</div>
        <div class="fw-bold fs-6"><?= htmlspecialchars($po['supplier_name']) ?></div>
        <div><?= nl2br(htmlspecialchars($po['supplier_address'] ?? '')) ?></div>
        <div><strong>GSTIN:</strong> <?= htmlspecialchars($po['supplier_gstin'] ?? 'N/A') ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars($po['supplier_phone'] ?? 'N/A') ?></div>
      </div>
    </div>
    <div class="col-6">
      <div class="p-2 border rounded bg-light">
        <div class="text-uppercase fw-bold text-secondary mb-1" style="font-size:0.75rem;">DELIVERY & PAYMENT TERMS</div>
        <div><strong>Expected Delivery:</strong> <?= date('d M Y', strtotime($po['delivery_date'])) ?></div>
        <div><strong>Payment Terms:</strong> <?= htmlspecialchars($po['payment_terms'] ?? '30 Days Net') ?></div>
        <div><strong>Delivery Site:</strong> <?= htmlspecialchars($po['delivery_address'] ?? 'Main Processing Factory, Tirupur') ?></div>
        <div><strong>Status:</strong> <span class="badge bg-success"><?= strtoupper($po['status']) ?></span></div>
      </div>
    </div>
  </div>

  <table class="table po-table small mb-3">
    <thead class="table-dark">
      <tr>
        <th style="width: 40px;" class="text-center">#</th>
        <th>Item Description & Specification</th>
        <th>SKU</th>
        <th class="text-end" style="width: 90px;">Qty</th>
        <th class="text-center" style="width: 60px;">UOM</th>
        <th class="text-end" style="width: 100px;">Rate (₹)</th>
        <th class="text-end" style="width: 90px;">Tax (₹)</th>
        <th class="text-end" style="width: 110px;">Total (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($items as $item): ?>
      <tr>
        <td class="text-center"><?= $i++ ?></td>
        <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
        <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
        <td class="text-end fw-bold"><?= number_format($item['ordered_qty'], 2) ?></td>
        <td class="text-center"><?= htmlspecialchars($item['uom_code']) ?></td>
        <td class="text-end">₹<?= number_format($item['unit_price'], 2) ?></td>
        <td class="text-end">₹<?= number_format($item['tax_amount'], 2) ?></td>
        <td class="text-end fw-bold">₹<?= number_format($item['total_amount'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="6" class="text-end fw-bold">Subtotal:</td>
        <td colspan="2" class="text-end fw-bold">₹<?= number_format($po['subtotal'], 2) ?></td>
      </tr>
      <tr>
        <td colspan="6" class="text-end fw-bold">Taxes (GST):</td>
        <td colspan="2" class="text-end fw-bold">₹<?= number_format($po['tax_total'], 2) ?></td>
      </tr>
      <tr class="table-light fs-6">
        <td colspan="6" class="text-end fw-bold">Grand Total (INR):</td>
        <td colspan="2" class="text-end fw-bold text-primary">₹<?= number_format($po['grand_total'], 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="border p-2 rounded mb-3 small bg-light">
    <strong>Amount in Words:</strong> <?= NumberToWords::convert((float)$po['grand_total']) ?>
  </div>

  <div class="row pt-4 text-center small">
    <div class="col-4">
      <div class="border-top pt-2">Prepared By<br><strong class="text-dark">Stores & Procurement</strong></div>
    </div>
    <div class="col-4">
      <div class="border-top pt-2">Verified By<br><strong class="text-dark">Materials Manager</strong></div>
    </div>
    <div class="col-4">
      <div class="border-top pt-2">Authorized Signatory<br><strong class="text-dark">Managing Director</strong></div>
    </div>
  </div>

  <div class="text-center text-muted small border-top pt-2 mt-4" style="font-size:0.75rem;">
    This is an ERP-generated Purchase Order subject to standard textile procurement terms. Vendor must quote PO No on delivery challan and invoice.
  </div>
</div>

</body>
</html>
