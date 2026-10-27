<?php
/**
 * Knitting & Weaving Production Hub - KARCEM Textile ERP
 * File: modules/knitting/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Knitting & Weaving Hub';
$activeModule = 'knitting';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$knitOrders = Database::fetchAll("SELECT ko.*, m.code as machine_code, p.name as yarn_name FROM knitting_orders ko JOIN machines m ON ko.machine_id = m.id JOIN products p ON ko.yarn_product_id = p.id ORDER BY ko.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-grid-3x3"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Knitting & Weaving Manufacturing Floor</h1>
          <p class="text-secondary small mb-0">High-speed circular knitting machines (Mayer & Cie 30" 24GG / 28GG), yarn lot tracking, roll piece weight, grey inspection, defect tracking, and loom production.</p>
        </div>
      </div>
    </section>

    <!-- Knitting Orders Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Circular Knitting Machine Orders</h2>
        <span class="badge bg-light text-dark border">Knitting Floor Palladam</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Order No</th>
              <th>Fabric Type</th>
              <th>Machine (Dia &times; GG)</th>
              <th>Yarn Specification</th>
              <th>Target GSM & Width</th>
              <th>Planned (Kg)</th>
              <th>Produced (Kg)</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($knitOrders as $ko): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($ko['order_no']) ?></td>
                <td class="fw-bold text-dark"><?= htmlspecialchars($ko['fabric_type']) ?></td>
                <td><span class="badge bg-dark"><?= htmlspecialchars($ko['machine_code']) ?></span> <?= htmlspecialchars($ko['diameter_gauge']) ?></td>
                <td>
                  <div><?= htmlspecialchars($ko['yarn_count']) ?></div>
                  <span class="text-muted" style="font-size: 0.72rem;">Lot: <?= htmlspecialchars($ko['yarn_lot_no']) ?></span>
                </td>
                <td><?= $ko['target_gsm'] ?> GSM &times; <?= $ko['target_width_inches'] ?>"</td>
                <td class="fw-bold font-monospace"><?= number_format($ko['planned_weight_kg'], 1) ?></td>
                <td class="fw-bold text-success font-monospace"><?= number_format($ko['produced_weight_kg'], 1) ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($ko['status']) ?>"><?= htmlspecialchars($ko['status']) ?></span></td>
                <td class="text-end">
                  <a href="<?= Url::to('/inventory/rolls') ?>" class="btn btn-sm btn-outline-dark" title="View Produced Grey Rolls">
                    <i class="bi bi-box-seam me-1"></i> Grey Rolls
                  </a>
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
