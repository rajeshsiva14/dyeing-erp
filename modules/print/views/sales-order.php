<?php
/**
 * P03 - Sales Order Confirmation (Print Engine) - KARCEM Textile ERP
 * File: modules/print/views/sales-order.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;
use Core\Support\Barcode;

$orderId = (int)($_GET['id'] ?? 1);
$order = Database::fetch("SELECT so.*, c.name as customer_name, c.billing_address, c.shipping_address, c.gstin as customer_gstin, c.phone as customer_phone, c.email as customer_email FROM sales_orders so JOIN customers c ON so.customer_id = c.id WHERE so.id = ?", [$orderId]);
if (!$order) {
    die("Sales Order not found");
}

$items = Database::fetchAll("SELECT soi.*, p.name as product_name, p.sku, u.code as uom_code FROM sales_order_items soi JOIN products p ON soi.product_id = p.id JOIN uoms u ON soi.uom_id = u.id WHERE soi.sales_order_id = ?", [$orderId]);
$company = Database::fetch("SELECT * FROM companies WHERE id = 1");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>SALES ORDER CONFIRMATION - <?= htmlspecialchars($order['order_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f3f4f6; font-family: 'Segoe UI', system-ui, sans-serif; padding: 20px 0; }
    .print-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; background: #ffffff; padding: 15mm; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    .order-table th, .order-table td { border: 1px solid #111827 !important; padding: 6px 8px; }
    @media print { body { background: #fff; padding: 0; } .print-sheet { box-shadow: none; width: 100%; padding: 10mm; } .no-print { display: none !important; } }
  </style>
</head>
<body>

<div class="container mb-3 no-print" style="max-width: 210mm;">
  <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
    <div class="fw-bold">P03 — Sales Order Confirmation</div>
    <div class="d-flex gap-2">
      <button class="btn btn-dark btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print Order</button>
      <a href="<?= Url::to('/sales/orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Return</a>
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
      <span class="badge bg-dark text-white px-3 py-1 fs-6">P03 SALES ORDER CONFIRMATION</span>
      <div class="font-monospace fw-bold fs-5 text-primary mt-1"><?= htmlspecialchars($order['order_no']) ?></div>
      <div class="small text-muted">Date: <?= date('d M Y', strtotime($order['order_date'])) ?></div>
    </div>
  </div>

  <div class="row g-2 border p-2 mb-3 bg-light small">
    <div class="col-6">
      <strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
      <strong>Billing Address:</strong> <?= nl2br(htmlspecialchars($order['billing_address'])) ?><br>
      <strong>GSTIN:</strong> <?= htmlspecialchars($order['customer_gstin'] ?? 'Unregistered') ?><br>
    </div>
    <div class="col-6">
      <strong>Customer PO No:</strong> <?= htmlspecialchars($order['customer_po_no'] ?? '-') ?><br>
      <strong>Promised Delivery Date:</strong> <span class="fw-bold text-dark"><?= date('d M Y', strtotime($order['promised_delivery_date'])) ?></span><br>
      <strong>Payment Terms:</strong> <?= htmlspecialchars($order['payment_terms'] ?? 'Net 30 Days') ?><br>
      <strong>Order Revision:</strong> Rev <?= $order['current_revision'] ?> (Approved)<br>
    </div>
  </div>

  <table class="table order-table w-100 small mb-3">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Product Specification</th>
        <th>Shade</th>
        <th class="text-end">Quantity</th>
        <th>UOM</th>
        <th class="text-end">Unit Price (₹)</th>
        <th class="text-end">Total Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($items as $it): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td>
            <div class="fw-bold"><?= htmlspecialchars($it['product_name']) ?></div>
            <div class="text-muted" style="font-size: 0.72rem;">SKU: <?= htmlspecialchars($it['sku']) ?></div>
          </td>
          <td><?= htmlspecialchars($it['shade_name'] ?? '-') ?></td>
          <td class="text-end fw-bold font-monospace"><?= number_format($it['ordered_qty'], 2) ?></td>
          <td><?= htmlspecialchars($it['uom_code']) ?></td>
          <td class="text-end font-monospace">₹<?= number_format($it['unit_price'], 2) ?></td>
          <td class="text-end fw-bold font-monospace">₹<?= number_format($it['total_amount'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="text-end fw-bold">Subtotal:</td>
        <td colspan="2" class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($order['subtotal'], 2) ?></td>
      </tr>
      <tr>
        <td colspan="5" class="text-end">GST Tax (5%):</td>
        <td colspan="2" class="text-end font-monospace">₹<?= NumberToWords::formatIndian($order['tax_total'], 2) ?></td>
      </tr>
      <tr class="table-light">
        <td colspan="5" class="text-end fw-bold fs-6">Grand Total:</td>
        <td colspan="2" class="text-end fw-bold font-monospace fs-6">₹<?= NumberToWords::formatIndian($order['grand_total'], 2) ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="border p-2 mb-4 bg-light small">
    <strong>Total Amount in Words:</strong> <?= NumberToWords::inWords((float)$order['grand_total']) ?>
  </div>

  <div class="row text-center mt-5 small">
    <div class="col-6">
      <div class="border-top pt-2 mx-4 text-muted">Customer Confirmation Signature</div>
    </div>
    <div class="col-6">
      <div class="fw-bold mb-4">For <?= htmlspecialchars($company['name']) ?></div>
      <div class="border-top pt-2 mx-4 fw-bold">Authorized Sales Manager</div>
    </div>
  </div>
</div>
</body>
</html>
