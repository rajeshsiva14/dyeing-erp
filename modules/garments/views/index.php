<?php
/**
 * Garments & Apparel Manufacturing Hub - KARCEM Textile ERP
 * File: modules/garments/views/index.php
 */

use Core\Support\Url;
use Core\Database\Database;

$pageTitle = 'Garments Manufacturing Hub';
$activeModule = 'garments';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$styles = Database::fetchAll("SELECT gs.*, c.name as customer_name FROM garment_styles gs JOIN customers c ON gs.customer_id = c.id ORDER BY gs.id DESC");
$cuttings = Database::fetchAll("SELECT co.*, gs.style_number FROM cutting_orders co JOIN garment_styles gs ON co.style_id = gs.id ORDER BY co.id DESC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Hero -->
    <section class="cz-panel mb-4">
      <div class="cz-module-hero p-4 d-flex align-items-center gap-3">
        <span class="cz-module-symbol bg-primary-subtle text-primary p-3 rounded-circle fs-3"><i class="bi bi-scissors"></i></span>
        <div class="cz-module-hero__text">
          <h1 class="h4 fw-bold text-dark mb-1">Garments & Apparel Manufacturing Division</h1>
          <p class="text-secondary small mb-0">Avinashi Unit: Style Tech Packs, SAM minutes, CAD marker planning, automated fabric spreading, bundle ticketing, Juki sewing assembly lines, and carton packing.</p>
        </div>
      </div>
    </section>

    <!-- Styles & SAM Minutes Grid -->
    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <section class="cz-panel h-100">
          <div class="cz-panel-head d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-person-badge text-primary me-2"></i>Active Garment Styles & SAM Labour Costing</h2>
            <span class="small text-secondary"><?= count($styles) ?> Styles Configured</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Style No</th>
                  <th>Description</th>
                  <th>Customer</th>
                  <th>Season</th>
                  <th class="text-end">SAM (Mins)</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php foreach ($styles as $st): ?>
                  <tr>
                    <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($st['style_number']) ?></td>
                    <td class="fw-semibold text-dark"><?= htmlspecialchars($st['description']) ?></td>
                    <td><?= htmlspecialchars($st['customer_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($st['season']) ?></span></td>
                    <td class="text-end fw-bold font-monospace"><?= number_format($st['sam_minutes'], 2) ?> mins</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- Cutting Dockets -->
      <div class="col-lg-6">
        <section class="cz-panel h-100">
          <div class="cz-panel-head d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-scissors text-danger me-2"></i>Cutting Dockets & Spreading Efficiency</h2>
            <span class="badge bg-dark text-white">Fabric Spreading Floor</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Docket No</th>
                  <th>Style</th>
                  <th>Layers</th>
                  <th>Planned Pcs</th>
                  <th>Cut Output</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php foreach ($cuttings as $co): ?>
                  <tr>
                    <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($co['cutting_no']) ?></td>
                    <td><?= htmlspecialchars($co['style_number']) ?></td>
                    <td><?= $co['layers_count'] ?> Lays</td>
                    <td class="fw-bold"><?= number_format($co['planned_pieces'], 0) ?> pcs</td>
                    <td class="fw-bold text-success"><?= number_format($co['actual_cut_pieces'], 0) ?> pcs</td>
                    <td><span class="badge-status status-<?= htmlspecialchars($co['status']) ?>"><?= htmlspecialchars($co['status']) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>

  </div>
</main>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
