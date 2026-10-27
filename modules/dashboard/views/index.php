<?php
/**
 * Executive Management Dashboard - KARCEM Textile ERP
 * File: modules/dashboard/views/index.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$pageTitle = 'Executive Dashboard';
$activeModule = 'dashboard';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

// Fetch live ERP metrics from database
$totalOrders = (int)Database::fetch("SELECT COUNT(*) FROM sales_orders")['COUNT(*)'];
$openOrderValue = (float)Database::fetch("SELECT COALESCE(SUM(grand_total), 0) FROM sales_orders WHERE status IN ('draft', 'confirmed', 'in_production')")['COALESCE(SUM(grand_total), 0)'];
$activeBatchesCount = (int)Database::fetch("SELECT COUNT(*) FROM dye_batches WHERE status NOT IN ('completed')")['COUNT(*)'];
$todayDyeingOutput = (float)Database::fetch("SELECT COALESCE(SUM(output_weight_kg), 0) FROM dye_batches WHERE DATE(created_at) = CURDATE() OR status = 'completed'")['COALESCE(SUM(output_weight_kg), 0)'];
$etpRecoveryPct = 98.2; // ZLD Water Recovery %
$arOutstanding = (float)Database::fetch("SELECT COALESCE(SUM(balance_amount), 0) FROM sales_invoices WHERE status != 'paid'")['COALESCE(SUM(balance_amount), 0)'];

// Recent Sales Orders
$recentOrders = Database::fetchAll("SELECT so.*, c.name as customer_name FROM sales_orders so JOIN customers c ON so.customer_id = c.id ORDER BY so.id DESC LIMIT 5");

// Active Dyeing Batches
$activeBatches = Database::fetchAll("SELECT b.*, r.recipe_code, r.shade_name, m.code as machine_code FROM dye_batches b JOIN dye_recipes r ON b.dye_recipe_id = r.id JOIN machines m ON b.machine_id = m.id ORDER BY b.id DESC LIMIT 4");

// Stock Breakdown by Category
$greyStock = (float)Database::fetch("SELECT COALESCE(SUM(current_stock), 0) FROM products WHERE category = 'grey_fabric'")['COALESCE(SUM(current_stock), 0)'];
$dyedStock = (float)Database::fetch("SELECT COALESCE(SUM(current_stock), 0) FROM products WHERE category = 'dyed_fabric'")['COALESCE(SUM(current_stock), 0)'];
$yarnStock = (float)Database::fetch("SELECT COALESCE(SUM(current_stock), 0) FROM products WHERE category = 'yarn'")['COALESCE(SUM(current_stock), 0)'];
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-dark mb-1">Textile Manufacturing Executive Overview</h1>
        <p class="text-secondary small mb-0">Live operations across Spinning, Knitting, Soft-Flow Dyeing, Finishing, Garmenting & Finance.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/sales/orders') ?>" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i> New Sales Order</a>
        <a href="<?= Url::to('/dyeing/batches') ?>" class="btn btn-outline-primary"><i class="bi bi-droplet-half me-1"></i> Load Vessel</a>
        <a href="<?= Url::to('/print/tax-invoice?id=1') ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i> Sample GST Invoice</a>
      </div>
    </div>

    <!-- Live Operational KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-xl-3">
        <div class="cz-stat-card border-start border-4 border-primary">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="cz-stat-lbl">Open Order Book</div>
              <div class="cz-stat-val">₹<?= NumberToWords::formatIndian($openOrderValue, 0) ?></div>
            </div>
            <div class="bg-primary-subtle text-primary p-2 rounded"><i class="bi bi-briefcase fs-4"></i></div>
          </div>
          <div class="mt-2 small text-secondary">
            <span class="text-success fw-bold"><i class="bi bi-arrow-up"></i> <?= $totalOrders ?> Active Orders</span> · In production
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="cz-stat-card border-start border-4 border-info">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="cz-stat-lbl">Dyeing Output (Batches)</div>
              <div class="cz-stat-val"><?= number_format($todayDyeingOutput, 1) ?> <span class="fs-6 fw-normal text-muted">Kg</span></div>
            </div>
            <div class="bg-info-subtle text-info p-2 rounded"><i class="bi bi-droplet-half fs-4"></i></div>
          </div>
          <div class="mt-2 small text-secondary">
            <span class="text-info fw-bold">RFT: 96.5%</span> Right-First-Time shade match
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="cz-stat-card border-start border-4 border-success">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="cz-stat-lbl">TNPCB ZLD Water Recovery</div>
              <div class="cz-stat-val"><?= $etpRecoveryPct ?>%</div>
            </div>
            <div class="bg-success-subtle text-success p-2 rounded"><i class="bi bi-tree fs-4"></i></div>
          </div>
          <div class="mt-2 small text-secondary">
            <span class="text-success fw-bold">458 KLD</span> Recycled to Soft Flow Vessels
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3">
        <div class="cz-stat-card border-start border-4 border-warning">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="cz-stat-lbl">Trade Receivables (AR)</div>
              <div class="cz-stat-val">₹<?= NumberToWords::formatIndian($arOutstanding, 0) ?></div>
            </div>
            <div class="bg-warning-subtle text-warning p-2 rounded"><i class="bi bi-cash-stack fs-4"></i></div>
          </div>
          <div class="mt-2 small text-secondary">
            <span class="text-dark fw-bold">Net 30-45 Days</span> average credit period
          </div>
        </div>
      </div>
    </div>

    <!-- Production Lifecycle Pipeline Banner -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-diagram-3-fill text-primary me-2"></i>End-to-End Production Lifecycle Pipeline</h2>
        <span class="badge bg-light text-dark border">KARCEM Unified Route</span>
      </div>
      <div class="p-3">
        <div class="row text-center g-2">
          <div class="col">
            <div class="p-3 rounded bg-light border">
              <i class="bi bi-bullseye fs-3 text-secondary mb-1"></i>
              <div class="fw-bold small text-dark">01. Spinning</div>
              <div class="text-muted" style="font-size: 0.75rem;">Fibre → Combed Yarn</div>
              <span class="badge bg-secondary-subtle text-secondary mt-1">14.5 T Yarn</span>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center text-muted"><i class="bi bi-arrow-right"></i></div>
          <div class="col">
            <div class="p-3 rounded bg-light border">
              <i class="bi bi-grid-3x3 fs-3 text-secondary mb-1"></i>
              <div class="fw-bold small text-dark">02. Knitting</div>
              <div class="text-muted" style="font-size: 0.75rem;">Single Jersey & Rib</div>
              <span class="badge bg-secondary-subtle text-secondary mt-1">8.4 T Grey</span>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center text-muted"><i class="bi bi-arrow-right"></i></div>
          <div class="col">
            <div class="p-3 rounded bg-primary-subtle border border-primary">
              <i class="bi bi-droplet-half fs-3 text-primary mb-1"></i>
              <div class="fw-bold small text-primary">03. Dyeing (Live)</div>
              <div class="text-muted" style="font-size: 0.75rem;">Soft Flow & Reactive</div>
              <span class="badge bg-primary text-white mt-1">SF-01 Running</span>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center text-muted"><i class="bi bi-arrow-right"></i></div>
          <div class="col">
            <div class="p-3 rounded bg-light border">
              <i class="bi bi-layers fs-3 text-secondary mb-1"></i>
              <div class="fw-bold small text-dark">04. Finishing</div>
              <div class="text-muted" style="font-size: 0.75rem;">Stenter & Compactor</div>
              <span class="badge bg-secondary-subtle text-secondary mt-1">5.2 T Dyed</span>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center text-muted"><i class="bi bi-arrow-right"></i></div>
          <div class="col">
            <div class="p-3 rounded bg-light border">
              <i class="bi bi-shield-check fs-3 text-success mb-1"></i>
              <div class="fw-bold small text-dark">05. Quality QC</div>
              <div class="text-muted" style="font-size: 0.75rem;">ASTM 4-Point System</div>
              <span class="badge bg-success text-white mt-1">Grade A: 94%</span>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center text-muted"><i class="bi bi-arrow-right"></i></div>
          <div class="col">
            <div class="p-3 rounded bg-light border">
              <i class="bi bi-scissors fs-3 text-secondary mb-1"></i>
              <div class="fw-bold small text-dark">06. Garmenting</div>
              <div class="text-muted" style="font-size: 0.75rem;">Cut, Sew, Pack</div>
              <span class="badge bg-secondary-subtle text-secondary mt-1">2,400 Pcs/Day</span>
            </div>
          </div>
          <div class="col-auto d-flex align-items-center text-muted"><i class="bi bi-arrow-right"></i></div>
          <div class="col">
            <div class="p-3 rounded bg-light border">
              <i class="bi bi-truck fs-3 text-dark mb-1"></i>
              <div class="fw-bold small text-dark">07. Dispatch</div>
              <div class="text-muted" style="font-size: 0.75rem;">E-Way Bill & Tax Inv</div>
              <span class="badge bg-dark text-white mt-1">₹9.24 L Posted</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content Grid -->
    <div class="row g-4 mb-4">
      <!-- Active Dyeing Vessels & Batches -->
      <div class="col-lg-7">
        <section class="cz-panel h-100">
          <div class="cz-panel-head d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-droplet-half text-primary me-2"></i>Soft Flow Dyeing Vessels — Live Floor Status</h2>
            <a href="<?= Url::to('/dyeing/batches') ?>" class="small text-decoration-none">View All Batches &rarr;</a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Batch No</th>
                  <th>Machine / Vessel</th>
                  <th>Shade / Recipe</th>
                  <th>Weight (Kg)</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php foreach ($activeBatches as $b): ?>
                  <tr>
                    <td class="fw-bold text-primary"><?= htmlspecialchars($b['batch_no']) ?></td>
                    <td><span class="badge bg-dark text-white"><?= htmlspecialchars($b['machine_code']) ?></span></td>
                    <td>
                      <div class="fw-bold text-dark"><?= htmlspecialchars($b['shade_name']) ?></div>
                      <span class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($b['recipe_code']) ?> (<?= $b['liquor_ratio'] ?>)</span>
                    </td>
                    <td><?= number_format($b['actual_loaded_kg'], 1) ?> Kg</td>
                    <td><span class="badge-status status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                    <td>
                      <a href="<?= Url::to('/print/batch-card?id=' . $b['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Batch Card">
                        <i class="bi bi-printer"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- Live Inventory Breakdown -->
      <div class="col-lg-5">
        <section class="cz-panel h-100">
          <div class="cz-panel-head d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-pie-chart text-success me-2"></i>Live Inventory Valuation</h2>
            <a href="<?= Url::to('/inventory/movements') ?>" class="small text-decoration-none">Movement Ledger &rarr;</a>
          </div>
          <div class="p-3">
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div>
                <div class="fw-bold text-dark">Grey Fabric Stock</div>
                <div class="small text-muted">Single Jersey & Interlock (Wh: WH-GREY)</div>
              </div>
              <div class="text-end">
                <div class="fw-bold text-dark"><?= number_format($greyStock, 1) ?> Kg</div>
                <span class="badge bg-secondary-subtle text-secondary">₹<?= NumberToWords::formatIndian($greyStock * 320, 0) ?></span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div>
                <div class="fw-bold text-dark">Finished Dyed Fabric</div>
                <div class="small text-muted">Inspected Grade A rolls (Wh: WH-FINISH)</div>
              </div>
              <div class="text-end">
                <div class="fw-bold text-dark"><?= number_format($dyedStock, 1) ?> Kg</div>
                <span class="badge bg-success-subtle text-success">₹<?= NumberToWords::formatIndian($dyedStock * 420, 0) ?></span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div>
                <div class="fw-bold text-dark">Combed Cotton Yarn Stock</div>
                <div class="small text-muted">30s / 40s Cones (Wh: WH-YARN)</div>
              </div>
              <div class="text-end">
                <div class="fw-bold text-dark"><?= number_format($yarnStock, 1) ?> Kg</div>
                <span class="badge bg-info-subtle text-info">₹<?= NumberToWords::formatIndian($yarnStock * 285, 0) ?></span>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3">
              <span class="fw-bold text-dark">Total Estimated Warehouse Value:</span>
              <span class="fs-5 fw-bold text-primary">₹<?= NumberToWords::formatIndian(($greyStock * 320) + ($dyedStock * 420) + ($yarnStock * 285), 0) ?></span>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Recent Sales Orders Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-briefcase text-primary me-2"></i>Recent Sales Orders & Commercial Traceability</h2>
        <a href="<?= Url::to('/sales/orders') ?>" class="btn btn-sm btn-dark">Open Sales Register &rarr;</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Order Number</th>
              <th>Customer</th>
              <th>Order Date</th>
              <th>Promised Date</th>
              <th>Grand Total</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($recentOrders as $so): ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($so['order_no']) ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($so['customer_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;">PO Ref: <?= htmlspecialchars($so['customer_po_no'] ?? 'N/A') ?></span>
                </td>
                <td><?= date('d M Y', strtotime($so['order_date'])) ?></td>
                <td><span class="text-dark fw-bold"><?= date('d M Y', strtotime($so['promised_delivery_date'])) ?></span></td>
                <td class="fw-bold">₹<?= NumberToWords::formatIndian($so['grand_total'], 2) ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($so['status']) ?>"><?= htmlspecialchars($so['status']) ?></span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="<?= Url::to('/print/sales-order?id=' . $so['id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print P03 Confirmation">
                      <i class="bi bi-printer"></i>
                    </a>
                    <a href="<?= Url::to('/planning/orders') ?>" class="btn btn-outline-primary" title="View Production Orders">
                      <i class="bi bi-gear"></i>
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
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
