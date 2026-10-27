<?php
/**
 * P15 - Goods Receipt Note (GRN Print Engine) - KARCEM Textile ERP
 * File: modules/print/views/grn.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$grnId = (int)($_GET['id'] ?? 1);
$grn = Database::fetch("
    SELECT grn.*, s.name as supplier_name, s.code as supplier_code, s.address as supplier_address, 
           s.gstin as supplier_gstin, s.phone as supplier_phone, w.name as warehouse_name
    FROM goods_receipts grn
    JOIN suppliers s ON grn.supplier_id = s.id
    JOIN warehouses w ON grn.warehouse_id = w.id
    WHERE grn.id = ?
", [$grnId]);

if (!$grn) {
    // Fallback to latest
    $grn = Database::fetch("
        SELECT grn.*, s.name as supplier_name, s.code as supplier_code, s.address as supplier_address, 
               s.gstin as supplier_gstin, s.phone as supplier_phone, w.name as warehouse_name
        FROM goods_receipts grn
        JOIN suppliers s ON grn.supplier_id = s.id
        JOIN warehouses w ON grn.warehouse_id = w.id
        ORDER BY grn.id DESC LIMIT 1
    ");
}

if (!$grn) {
    die("Goods Receipt Note not found");
}

$items = Database::fetchAll("
    SELECT gri.*, p.name as product_name, p.sku, u.code as uom_code
    FROM goods_receipt_items gri
    JOIN products p ON gri.product_id = p.id
    JOIN uoms u ON gri.uom_id = u.id
    WHERE gri.goods_receipt_id = ?
", [$grn['id']]);

$company = Database::fetch("SELECT * FROM companies WHERE id = 1");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>GOODS RECEIPT NOTE (P15) — <?= htmlspecialchars($grn['grn_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f3f4f6; font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px 0; }
    .print-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; background: #ffffff; padding: 15mm; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .grn-table th, .grn-table td { border: 1px solid #111827 !important; padding: 6px 8px; }
    @media print { body { background: #fff; padding: 0; } .print-sheet { box-shadow: none; width: 100%; padding: 10mm; } .no-print { display: none !important; } }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 210mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    <div class="fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>P15 — Goods Receipt Note (GRN) Print</div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print GRN</button>
      <a href="<?= Url::to('/inventory/grn') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return to GRN</a>
    </div>
  </div>
</div>

<div class="print-sheet">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($company['name']) ?></h3>
      <div class="small text-muted"><?= htmlspecialchars($company['address_line1']) ?>, <?= htmlspecialchars($company['city']) ?>, Tamil Nadu</div>
      <div class="small text-muted">GSTIN: <?= htmlspecialchars($company['gstin']) ?> | CIN: <?= htmlspecialchars($company['cin']) ?></div>
    </div>
    <div class="text-end">
      <span class="badge bg-dark text-white px-3 py-1 fs-6">P15 GOODS RECEIPT NOTE</span>
      <div class="font-monospace fw-bold fs-5 text-primary mt-1"><?= htmlspecialchars($grn['grn_no']) ?></div>
      <div class="small text-muted">Date: <?= date('d M Y', strtotime($grn['receipt_date'])) ?></div>
    </div>
  </div>

  <!-- Meta Info -->
  <div class="row g-3 mb-3 small">
    <div class="col-6">
      <div class="p-2 border rounded bg-light h-100">
        <div class="fw-bold text-dark text-uppercase">Supplier / Vendor:</div>
        <div class="fw-bold text-primary fs-6"><?= htmlspecialchars($grn['supplier_name']) ?></div>
        <div>Code: <?= htmlspecialchars($grn['supplier_code']) ?></div>
        <div><?= htmlspecialchars($grn['supplier_address'] ?? '') ?></div>
        <div>GSTIN: <?= htmlspecialchars($grn['supplier_gstin'] ?? 'Unregistered') ?></div>
      </div>
    </div>
    <div class="col-6">
      <div class="p-2 border rounded bg-light h-100">
        <div class="fw-bold text-dark text-uppercase">Receipt & Delivery Details:</div>
        <div><strong>Warehouse:</strong> <?= htmlspecialchars($grn['warehouse_name']) ?></div>
        <div><strong>Supplier DC No:</strong> <?= htmlspecialchars($grn['supplier_dc_no'] ?? 'N/A') ?></div>
        <div><strong>Vehicle No:</strong> <?= htmlspecialchars($grn['vehicle_no'] ?? 'N/A') ?></div>
        <div><strong>Inspection Status:</strong> <span class="badge bg-success-subtle text-success"><?= strtoupper($grn['status']) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Items Table -->
  <table class="table grn-table mb-4 small align-middle">
    <thead class="table-dark">
      <tr>
        <th style="width: 5%;">#</th>
        <th style="width: 35%;">Item Description / SKU</th>
        <th style="width: 15%;">Lot / Batch Ref</th>
        <th style="width: 15%;" class="text-end">Challan Qty</th>
        <th style="width: 15%;" class="text-end">Accepted Qty</th>
        <th style="width: 15%;" class="text-end">Rejected Qty</th>
      </tr>
    </thead>
    <tbody>
      <?php 
        $totChallan = 0; $totAccepted = 0; $totRejected = 0;
        foreach ($items as $idx => $it): 
          $totChallan += $it['challan_qty'];
          $totAccepted += $it['accepted_qty'];
          $totRejected += $it['rejected_qty'];
      ?>
        <tr>
          <td class="text-center"><?= $idx + 1 ?></td>
          <td>
            <div class="fw-bold"><?= htmlspecialchars($it['product_name']) ?></div>
            <div class="text-muted font-monospace" style="font-size: 0.75rem;">SKU: <?= htmlspecialchars($it['sku']) ?></div>
          </td>
          <td><?= htmlspecialchars($it['supplier_lot_no'] ?? $it['internal_lot_no'] ?? '-') ?></td>
          <td class="text-end"><?= number_format($it['challan_qty'], 2) ?> <?= $it['uom_code'] ?></td>
          <td class="text-end fw-bold text-success"><?= number_format($it['accepted_qty'], 2) ?> <?= $it['uom_code'] ?></td>
          <td class="text-end text-danger"><?= number_format($it['rejected_qty'], 2) ?> <?= $it['uom_code'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot class="table-light fw-bold">
      <tr>
        <td colspan="3" class="text-end">TOTALS:</td>
        <td class="text-end"><?= number_format($totChallan, 2) ?></td>
        <td class="text-end text-success"><?= number_format($totAccepted, 2) ?></td>
        <td class="text-end text-danger"><?= number_format($totRejected, 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <!-- Remarks & Signatures -->
  <div class="border p-2 rounded mb-4 small">
    <strong>Remarks / QC Notes:</strong> <?= htmlspecialchars($grn['remarks'] ?? 'Goods inspected, weighed, and verified against supplier delivery challan. Accepted material posted to store lot.') ?>
  </div>

  <div class="row pt-4 mt-5 text-center small">
    <div class="col-4">
      <div class="border-top pt-2">Stores Receiver / Weigher</div>
    </div>
    <div class="col-4">
      <div class="border-top pt-2">QC Inspector / Chemist</div>
    </div>
    <div class="col-4">
      <div class="border-top pt-2">Authorized Signatory / Stores Head</div>
    </div>
  </div>
</div>

</body>
</html>
