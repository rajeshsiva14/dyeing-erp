<?php
/**
 * Sales Orders Management - KARCEM Textile ERP
 * File: modules/sales/views/orders.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$pageTitle = 'Sales Orders Register';
$activeModule = 'sales';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$orders = Database::fetchAll("SELECT so.*, c.name as customer_name, c.code as customer_code FROM sales_orders so JOIN customers c ON so.customer_id = c.id ORDER BY so.id DESC");
$customers = Database::fetchAll("SELECT id, code, name FROM customers WHERE status = 'active' ORDER BY name ASC");
$products = Database::fetchAll("SELECT id, sku, name, standard_price FROM products WHERE is_active = 1 AND category IN ('dyed_fabric', 'finished_fabric', 'garment', 'yarn') ORDER BY name ASC");

$totalCount = count($orders);
$confirmedCount = count(array_filter($orders, fn($o) => in_array($o['status'], ['confirmed', 'in_production'])));
$draftCount = count(array_filter($orders, fn($o) => $o['status'] === 'draft'));
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Head -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/sales') ?>">Sales</a></li>
            <li class="breadcrumb-item active">Sales Orders</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Customer Sales Orders</h1>
        <p class="text-secondary small mb-0">Commercial customer orders, fabric specifications, price terms, and production release status.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newOrderModal">
          <i class="bi bi-plus-lg me-1"></i> New Sales Order
        </button>
      </div>
    </div>

    <!-- Status Tabs -->
    <ul class="nav cz-pill-tabs mb-3">
      <li class="nav-item"><button class="nav-link active" onclick="filterTable('all')">All Orders <span class="badge bg-secondary ms-1"><?= $totalCount ?></span></button></li>
      <li class="nav-item"><button class="nav-link" onclick="filterTable('confirmed')">Confirmed & In-Production <span class="badge bg-success ms-1"><?= $confirmedCount ?></span></button></li>
      <li class="nav-item"><button class="nav-link" onclick="filterTable('draft')">Draft / Pending <span class="badge bg-warning text-dark ms-1"><?= $draftCount ?></span></button></li>
    </ul>

    <!-- Orders Panel -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <div class="input-group cz-search" style="max-width: 380px;">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control" id="orderSearch" placeholder="Search order no, customer, PO ref..." onkeyup="searchOrders()">
        </div>
        <div class="small text-secondary">
          Displaying <?= $totalCount ?> recorded sales orders
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="ordersTable">
          <thead class="table-light small">
            <tr>
              <th>Order Number</th>
              <th>Customer</th>
              <th>Order Date</th>
              <th>Promised Date</th>
              <th>Subtotal</th>
              <th>GST Tax</th>
              <th>Grand Total</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($orders as $so): ?>
              <tr data-status="<?= htmlspecialchars($so['status']) ?>">
                <td class="fw-bold text-primary"><?= htmlspecialchars($so['order_no']) ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($so['customer_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($so['customer_code']) ?> · PO: <?= htmlspecialchars($so['customer_po_no'] ?? '-') ?></span>
                </td>
                <td><?= date('d M Y', strtotime($so['order_date'])) ?></td>
                <td><span class="fw-semibold text-dark"><?= date('d M Y', strtotime($so['promised_delivery_date'])) ?></span></td>
                <td>₹<?= NumberToWords::formatIndian($so['subtotal'], 2) ?></td>
                <td class="text-secondary">₹<?= NumberToWords::formatIndian($so['tax_total'], 2) ?></td>
                <td class="fw-bold text-dark">₹<?= NumberToWords::formatIndian($so['grand_total'], 2) ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($so['status']) ?>"><?= htmlspecialchars($so['status']) ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <?php if ($so['status'] === 'draft'): ?>
                      <button class="btn btn-outline-success" onclick="confirmOrder(<?= $so['id'] ?>)" title="Confirm & Release Order">
                        <i class="bi bi-check-lg me-1"></i> Confirm
                      </button>
                    <?php endif; ?>
                    <a href="<?= Url::to('/print/sales-order?id=' . $so['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print P03 Confirmation">
                      <i class="bi bi-printer"></i>
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

<!-- New Sales Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newOrderModalLabel"><i class="bi bi-briefcase me-2"></i>Create New Customer Sales Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="newOrderForm" onsubmit="submitNewOrder(event)">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Customer <span class="text-danger">*</span></label>
              <select class="form-select" id="new_customer_id" required>
                <option value="">Select Customer...</option>
                <?php foreach ($customers as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['code'] ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Customer PO Reference</label>
              <input type="text" class="form-control" id="new_customer_po_no" placeholder="e.g. PO-AK-8890">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Order Date</label>
              <input type="date" class="form-control" id="new_order_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Promised Delivery Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="new_delivery_date" value="<?= date('Y-m-d', strtotime('+3 weeks')) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Order Category</label>
              <select class="form-select" id="new_order_type">
                <option value="domestic">Domestic Sale (Tamil Nadu)</option>
                <option value="export">Direct Export</option>
                <option value="jobwork">Job-Work Processing</option>
              </select>
            </div>

            <!-- Order Item Detail -->
            <div class="col-12 border-top pt-3">
              <h6 class="fw-bold small text-dark mb-2"><i class="bi bi-box me-1"></i>Order Line Item Specification</h6>
              <div class="row g-2">
                <div class="col-md-5">
                  <label class="form-label small">Product / Fabric Specification <span class="text-danger">*</span></label>
                  <select class="form-select" id="line_product_id" required onchange="setStandardPrice(this)">
                    <option value="">Select SKU...</option>
                    <?php foreach ($products as $p): ?>
                      <option value="<?= $p['id'] ?>" data-price="<?= $p['standard_price'] ?>"><?= htmlspecialchars($p['name']) ?> (₹<?= $p['standard_price'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Shade Reference</label>
                  <input type="text" class="form-control" id="line_shade_name" placeholder="e.g. Royal Blue / Black">
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Quantity (Kg/Pcs) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" class="form-control" id="line_qty" value="1000" required oninput="calcTotal()">
                </div>
                <div class="col-md-2">
                  <label class="form-label small">Unit Rate (₹) <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" class="form-control" id="line_rate" value="480" required oninput="calcTotal()">
                </div>
              </div>
            </div>

            <div class="col-12 bg-light p-3 rounded d-flex justify-content-between align-items-center">
              <div class="small text-secondary">
                <div>Standard Tamil Nadu GST (5% for Fabric): <span class="fw-bold text-dark" id="calc_tax">₹24,000.00</span></div>
                <div class="text-muted" style="font-size: 0.75rem;">HSN 6006 / 6109 applicable.</div>
              </div>
              <div class="text-end">
                <span class="small text-secondary d-block">Estimated Order Value</span>
                <span class="fs-5 fw-bold text-primary" id="calc_grand_total">₹504,000.00</span>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label small">Delivery / Destination Address</label>
              <textarea class="form-control" id="new_shipping_address" rows="2" placeholder="Factory / Warehouse delivery location..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveOrderBtn"><i class="bi bi-save me-1"></i> Create Sales Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function setStandardPrice(sel) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt.getAttribute('data-price');
    if (price) {
      document.getElementById('line_rate').value = price;
      calcTotal();
    }
  }

  function calcTotal() {
    const qty = parseFloat(document.getElementById('line_qty').value) || 0;
    const rate = parseFloat(document.getElementById('line_rate').value) || 0;
    const subtotal = qty * rate;
    const tax = subtotal * 0.05;
    const grand = subtotal + tax;

    document.getElementById('calc_tax').textContent = '₹' + tax.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('calc_grand_total').textContent = '₹' + grand.toLocaleString('en-IN', {minimumFractionDigits: 2});
  }

  function searchOrders() {
    const q = document.getElementById('orderSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    rows.forEach(r => {
      r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }

  function filterTable(status) {
    const rows = document.querySelectorAll('#ordersTable tbody tr');
    rows.forEach(r => {
      if (status === 'all') {
        r.style.display = '';
      } else if (status === 'confirmed') {
        r.style.display = (r.getAttribute('data-status') === 'confirmed' || r.getAttribute('data-status') === 'in_production') ? '' : 'none';
      } else {
        r.style.display = r.getAttribute('data-status') === status ? '' : 'none';
      }
    });

    document.querySelectorAll('.cz-pill-tabs .nav-link').forEach(btn => btn.classList.remove('active'));
    event.target.closest('.nav-link').classList.add('active');
  }

  async function submitNewOrder(e) {
    e.preventDefault();
    const btn = document.getElementById('saveOrderBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';

    const payload = {
      customer_id: parseInt(document.getElementById('new_customer_id').value),
      customer_po_no: document.getElementById('new_customer_po_no').value,
      order_date: document.getElementById('new_order_date').value,
      promised_delivery_date: document.getElementById('new_delivery_date').value,
      order_type: document.getElementById('new_order_type').value,
      shipping_address: document.getElementById('new_shipping_address').value,
      items: [{
        product_id: parseInt(document.getElementById('line_product_id').value),
        shade_name: document.getElementById('line_shade_name').value,
        quantity: parseFloat(document.getElementById('line_qty').value),
        unit_price: parseFloat(document.getElementById('line_rate').value),
        tax_pct: 5.0
      }]
    };

    try {
      const res = await fetch('<?= Url::to('/api/v1/sales-orders') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Order Created', data.data.message || 'Sales order created successfully!', true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to create sales order');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Create Sales Order';
      }
    } catch (err) {
      alert('Error creating order: ' + err.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-save me-1"></i> Create Sales Order';
    }
  }

  async function confirmOrder(id) {
    if (!confirm('Are you sure you want to confirm and release this Sales Order for manufacturing planning?')) {
      return;
    }
    try {
      const res = await fetch('<?= Url::to('/api/v1/sales-orders/') ?>' + id + '/confirm', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
      });
      const data = await res.json();
      if (data.success) {
        window.showToast('Order Confirmed', data.data.message, true);
        setTimeout(() => location.reload(), 800);
      } else {
        alert(data.error?.message || 'Failed to confirm order');
      }
    } catch (err) {
      alert('Error confirming order: ' + err.message);
    }
  }
</script>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
