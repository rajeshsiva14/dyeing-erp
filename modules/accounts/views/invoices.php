<?php
/**
 * GST Tax Invoices & E-Way Bills - KARCEM Textile ERP
 * File: modules/accounts/views/invoices.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$pageTitle = 'GST Tax Invoices & Billing';
$activeModule = 'accounts';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$invoices = Database::fetchAll("SELECT inv.*, c.name as customer_name, c.gstin as customer_gstin, c.city as customer_city FROM sales_invoices inv JOIN customers c ON inv.customer_id = c.id ORDER BY inv.id DESC");
$customers = Database::fetchAll("SELECT id, code, name FROM customers WHERE status = 'active' ORDER BY name ASC");
$products = Database::fetchAll("SELECT id, sku, name, standard_price, hsn_sac FROM products WHERE is_active = 1 ORDER BY name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/accounts') ?>">Accounts</a></li>
            <li class="breadcrumb-item active">Tax Invoices</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">GST Tax Invoices & Statutory E-Invoicing</h1>
        <p class="text-secondary small mb-0">Compliant Indian GST invoices, CGST/SGST/IGST split, HSN/SAC codes, E-Way Bill numbers and IRN QR codes.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newInvoiceModal">
          <i class="bi bi-plus-lg me-1"></i> New Tax Invoice
        </button>
      </div>
    </div>

    <!-- Invoices Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-receipt text-primary me-2"></i>Sales Tax Invoices Register</h2>
        <span class="small text-secondary"><?= count($invoices) ?> Invoices</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Invoice Number</th>
              <th>Customer</th>
              <th>Date & Due</th>
              <th>Taxable Subtotal</th>
              <th>GST Tax (CGST+SGST)</th>
              <th>Grand Total</th>
              <th>Paid / Balance</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($invoices as $inv): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace">
                  <?= htmlspecialchars($inv['invoice_no']) ?>
                  <?php if (!empty($inv['irn_number'])): ?>
                    <span class="badge bg-success-subtle text-success d-block" style="font-size: 0.65rem;">IRN GENERATED</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($inv['customer_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;">GSTIN: <?= htmlspecialchars($inv['customer_gstin'] ?? 'URP') ?> · <?= htmlspecialchars($inv['customer_city']) ?></span>
                </td>
                <td>
                  <span class="text-dark fw-semibold"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></span>
                  <span class="text-muted d-block" style="font-size: 0.75rem;">Due: <?= date('d M Y', strtotime($inv['due_date'])) ?></span>
                </td>
                <td>₹<?= NumberToWords::formatIndian($inv['subtotal'], 2) ?></td>
                <td class="text-secondary">₹<?= NumberToWords::formatIndian($inv['tax_total'], 2) ?></td>
                <td class="fw-bold text-dark">₹<?= NumberToWords::formatIndian($inv['grand_total'], 2) ?></td>
                <td>
                  <div class="fw-bold text-success">₹<?= NumberToWords::formatIndian($inv['amount_paid'], 2) ?></div>
                  <span class="text-danger small">Bal: ₹<?= NumberToWords::formatIndian($inv['balance_amount'], 2) ?></span>
                </td>
                <td><span class="badge-status status-<?= htmlspecialchars($inv['status']) ?>"><?= htmlspecialchars($inv['status']) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="<?= Url::to('/print/tax-invoice?id=' . $inv['id']) ?>" target="_blank" class="btn btn-outline-dark" title="Print P74 GST Tax Invoice">
                      <i class="bi bi-printer me-1"></i> P74 Print
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
</main>

<!-- New Invoice Modal -->
<div class="modal fade" id="newInvoiceModal" tabindex="-1" aria-labelledby="newInvoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newInvoiceModalLabel"><i class="bi bi-receipt me-2"></i>Generate GST Tax Invoice</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="newInvoiceForm" onsubmit="submitNewInvoice(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Customer <span class="text-danger">*</span></label>
              <select class="form-select" id="inv_customer_id" required>
                <option value="">Select Customer...</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Invoice Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="inv_date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <!-- Item selection -->
            <div class="col-12 border-top pt-3">
              <h6 class="fw-bold small text-dark mb-2">Invoice Product Item</h6>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label small">Product SKU <span class="text-danger">*</span></label>
                  <select class="form-select" id="inv_item_product_id" required onchange="setInvPrice(this)">
                    <option value="">Select Product...</option>
                    <?php foreach ($products as $p): ?>
                      <option value="<?= $p['id'] ?>" data-price="<?= $p['standard_price'] ?>" data-hsn="<?= $p['hsn_sac'] ?>"><?= htmlspecialchars($p['name']) ?> (HSN: <?= $p['hsn_sac'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Quantity (Kg/Pcs) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" class="form-control" id="inv_item_qty" value="500" required oninput="calcInvTotal()">
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Rate (₹) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" class="form-control" id="inv_item_rate" value="480" required oninput="calcInvTotal()">
                </div>
              </div>
            </div>

            <!-- Tax preview -->
            <div class="col-12 bg-light p-3 rounded">
              <div class="row text-center g-2 small">
                <div class="col-4">
                  <span class="text-secondary d-block">Taxable Amount</span>
                  <span class="fw-bold fs-6 text-dark" id="inv_subtotal_lbl">₹240,000.00</span>
                </div>
                <div class="col-4">
                  <span class="text-secondary d-block">CGST 2.5% + SGST 2.5%</span>
                  <span class="fw-bold fs-6 text-secondary" id="inv_tax_lbl">₹12,000.00</span>
                </div>
                <div class="col-4">
                  <span class="text-secondary d-block">Grand Total</span>
                  <span class="fw-bold fs-6 text-primary" id="inv_grand_lbl">₹252,000.00</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveInvBtn"><i class="bi bi-save me-1"></i> Post Invoice & Post to Ledger</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function setInvPrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt.getAttribute('data-price');
    if (price) {
      document.getElementById('inv_item_rate').value = price;
      calcInvTotal();
    }
  }

  function calcInvTotal() {
    const qty = parseFloat(document.getElementById('inv_item_qty').value) || 0;
    const rate = parseFloat(document.getElementById('inv_item_rate').value) || 0;
    const sub = qty * rate;
    const tax = sub * 0.05;
    const grand = sub + tax;

    document.getElementById('inv_subtotal_lbl').textContent = '₹' + sub.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('inv_tax_lbl').textContent = '₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('inv_grand_lbl').textContent = '₹' + grand.toLocaleString('en-IN', {minimumFractionDigits: 2});
  }

  async function submitNewInvoice(e) {
    e.preventDefault();
    const btn = document.getElementById('saveInvBtn');
    btn.disabled = true;

    const payload = {
      customer_id: parseInt(document.getElementById('inv_customer_id').value),
      invoice_date: document.getElementById('inv_date').value,
      items: [{
        product_id: parseInt(document.getElementById('inv_item_product_id').value),
        quantity: parseFloat(document.getElementById('inv_item_qty').value),
        rate: parseFloat(document.getElementById('inv_item_rate').value),
        tax_pct: 5.0
      }]
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/sales-invoices') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Invoice Posted', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to post invoice');
        btn.disabled = false;
      }
    } catch (err) {
      alert('Error: ' + err.message);
      btn.disabled = false;
    }
  }
</script>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
