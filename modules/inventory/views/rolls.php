<?php
/**
 * Fabric Roll Register & Barcode Traceability - KARCEM Textile ERP
 * File: modules/inventory/views/rolls.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\Barcode;

$pageTitle = 'Fabric Roll Register & Barcode';
$activeModule = 'inventory';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$rolls = Database::fetchAll("SELECT r.*, p.name as product_name, l.lot_number, w.name as warehouse_name FROM inventory_rolls r JOIN products p ON r.product_id = p.id JOIN inventory_lots l ON r.lot_id = l.id JOIN warehouses w ON r.warehouse_id = w.id ORDER BY r.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/inventory') ?>">Inventory</a></li>
            <li class="breadcrumb-item active">Roll Register</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Fabric Roll Traceability & Barcode Engine</h1>
        <p class="text-secondary small mb-0">Piece-by-piece roll genealogy from grey knitting receipt through soft flow dyeing to finished goods warehouse.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/print/roll-label?id=1') ?>" target="_blank" class="btn btn-outline-dark">
          <i class="bi bi-printer me-1"></i> Print Sample L01 Thermal Label
        </a>
      </div>
    </div>

    <!-- Rolls Table -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-upc-scan text-primary me-2"></i>Tracked Fabric Rolls</h2>
        <span class="small text-secondary"><?= count($rolls) ?> Rolls In Godown</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Roll Number</th>
              <th>Barcode (Code128)</th>
              <th>Product Specification</th>
              <th>Net Weight</th>
              <th>Length & Width</th>
              <th>GSM</th>
              <th>Shade Band</th>
              <th>Grade</th>
              <th>Location</th>
              <th class="text-end">Label</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($rolls as $r): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($r['roll_number']) ?></td>
                <td>
                  <div style="width: 140px;">
                    <?= Barcode::svg($r['barcode'], 22, 1) ?>
                  </div>
                </td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($r['product_name']) ?></div>
                  <span class="text-muted" style="font-size: 0.75rem;">Lot: <?= htmlspecialchars($r['lot_number']) ?></span>
                </td>
                <td class="fw-bold"><?= number_format($r['weight_kg'], 2) ?> Kg</td>
                <td><?= number_format($r['length_meters'], 1) ?>m &times; <?= $r['width_inches'] ?>"</td>
                <td><?= $r['gsm'] ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['shade_group'] ?? 'Band A') ?></span></td>
                <td>
                  <span class="badge bg-<?= $r['quality_grade'] === 'A' ? 'success' : 'warning' ?>">
                    Grade <?= htmlspecialchars($r['quality_grade']) ?>
                  </span>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($r['warehouse_name']) ?></span></td>
                <td class="text-end">
                  <a href="<?= Url::to('/print/roll-label?id=' . $r['id']) ?>" target="_blank" class="btn btn-sm btn-dark" title="Print L01 Thermal Barcode Label">
                    <i class="bi bi-upc-scan me-1"></i> Print Label
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
