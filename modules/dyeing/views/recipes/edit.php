<?php
/**
 * Dedicated Standalone Form: Update Dye Recipe Formulation
 * File: modules/dyeing/views/recipes-edit.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Update Dye Recipe Formulation';
$activeModule = 'recipes';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$recipeId = (int)($_GET['id'] ?? ($_POST['recipe_id'] ?? 0));

if ($recipeId <= 0) {
    header('Location: ' . Url::to('/dyeing/recipes'));
    exit;
}

$errors = [];

// Handle POST to update recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_recipe') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $shadeName = trim($_POST['shade_name'] ?? '');
    $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $comp = trim($_POST['fabric_composition'] ?? '');
    $lr = trim($_POST['liquor_ratio'] ?? '1:6');
    $dyeClass = $_POST['dye_class'] ?? 'reactive';
    $isApproved = isset($_POST['is_approved']) ? 1 : 0;

    if ($shadeName === '') $errors[] = 'Shade name is required.';
    if ($comp === '') $errors[] = 'Fabric composition is required.';

    if (!$errors) {
        Database::execute("
            UPDATE dye_recipes 
            SET shade_name = ?, customer_id = ?, fabric_composition = ?,
                liquor_ratio = ?, dye_class = ?, is_approved = ?
            WHERE id = ? AND company_id = ?
        ", [
            $shadeName, $customerId, $comp,
            $lr, $dyeClass, $isApproved,
            $recipeId, $companyId
        ]);

        // Update items if supplied
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $itemId => $it) {
                $dosagePct = isset($it['dosage_percentage']) && $it['dosage_percentage'] !== '' ? (float)$it['dosage_percentage'] : null;
                $dosageGpl = isset($it['dosage_grams_per_litre']) && $it['dosage_grams_per_litre'] !== '' ? (float)$it['dosage_grams_per_litre'] : null;
                Database::execute("
                    UPDATE dye_recipe_items 
                    SET dosage_percentage = ?, dosage_grams_per_litre = ?
                    WHERE id = ? AND dye_recipe_id = ?
                ", [$dosagePct, $dosageGpl, (int)$itemId, $recipeId]);
            }
        }

        header('Location: ' . Url::to('/dyeing/recipes?updated=' . $recipeId));
        exit;
    }
}

$r = Database::fetch("SELECT * FROM dye_recipes WHERE id = ? AND company_id = ?", [$recipeId, $companyId]);
if (!$r) {
    header('Location: ' . Url::to('/dyeing/recipes'));
    exit;
}

$customers = Database::fetchAll("SELECT * FROM customers WHERE company_id = ? AND status = 'active' ORDER BY name ASC", [$companyId]);
$items = Database::fetchAll("
    SELECT ri.*, p.name as chem_name, p.sku 
    FROM dye_recipe_items ri 
    JOIN products p ON ri.chemical_product_id = p.id 
    WHERE ri.dye_recipe_id = ?
", [$recipeId]);

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Top Action Header -->
    <div class="cz-doc-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div class="d-flex align-items-center gap-3">
        <a href="<?= Url::to('/dyeing/recipes') ?>" class="btn btn-outline-dark btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Recipes
        </a>
        <div>
          <div class="d-flex align-items-center gap-2">
            <h1 class="h5 fw-bold text-dark mb-0">Update Dye Recipe Formulation</h1>
            <span class="badge bg-primary font-monospace"><?= htmlspecialchars($r['recipe_code']) ?></span>
            <span class="badge bg-light text-secondary border">Rev <?= $r['revision_no'] ?></span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Computerized color kitchen recipe standard, dyestuff exhaustion (% OWF), and chemical concentrations.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/print/batch-card?id=1') ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Formula Sheet
        </a>
        <a href="<?= Url::to('/dyeing/recipes') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="editRecipeForm" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Recipe
        </button>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger small mb-4" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="editRecipeForm" method="POST" action="<?= Url::to('/dyeing/recipes-edit') ?>">
      <input type="hidden" name="action" value="update_recipe">
      <input type="hidden" name="recipe_id" value="<?= $r['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Recipe & Shade Standards -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-palette"></i> Section A — Recipe Code & Master Shade</h2>
            <p class="cz-doc-section-desc">Unique recipe identifier, shade name, buyer linkage, and substrate composition.</p>
          </div>
          <span class="badge badge-bw font-monospace"><?= htmlspecialchars($r['recipe_code']) ?></span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Recipe Code</label>
            <input type="text" class="form-control form-control-sm font-monospace bg-light" value="<?= htmlspecialchars($r['recipe_code']) ?>" readonly>
          </div>
          <div class="col-md-5">
            <label class="form-label">Shade Name <span class="text-danger">*</span></label>
            <input type="text" name="shade_name" class="form-control form-control-sm" value="<?= htmlspecialchars($r['shade_name']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Buyer / Customer Allocation</label>
            <select name="customer_id" class="form-select form-select-sm">
              <option value="">-- General Factory Shade Palette --</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($r['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Target Substrate / Fabric Composition <span class="text-danger">*</span></label>
            <input type="text" name="fabric_composition" class="form-control form-control-sm" value="<?= htmlspecialchars($r['fabric_composition']) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Dye Class Classification <span class="text-danger">*</span></label>
            <select name="dye_class" class="form-select form-select-sm" required>
              <option value="reactive" <?= ($r['dye_class'] === 'reactive') ? 'selected' : '' ?>>Reactive Dyes (Cotton / Rayon / Modal)</option>
              <option value="disperse" <?= ($r['dye_class'] === 'disperse') ? 'selected' : '' ?>>Disperse Dyes (Polyester)</option>
              <option value="vat" <?= ($r['dye_class'] === 'vat') ? 'selected' : '' ?>>Vat Dyes (Industrial Workwear)</option>
              <option value="pigment" <?= ($r['dye_class'] === 'pigment') ? 'selected' : '' ?>>Pigment System</option>
              <option value="direct" <?= ($r['dye_class'] === 'direct') ? 'selected' : '' ?>>Direct Dyes</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Standard Liquor Ratio (L:R)</label>
            <input type="text" name="liquor_ratio" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars($r['liquor_ratio']) ?>">
          </div>
        </div>
      </div>

      <!-- Section B: Components Formulation Table -->
      <div class="cz-panel mb-4">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-eyedropper text-primary me-2"></i>Recipe Chemical Items Formulation</h2>
          <span class="small text-secondary"><?= count($items) ?> Chemicals</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Type</th>
                <th>Chemical / Dyestuff</th>
                <th>Stage</th>
                <th style="width: 200px;">Exhaustion (% OWF)</th>
                <th style="width: 200px;">Concentration (g/L)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td>
                    <span class="badge bg-<?= $it['item_type'] === 'dye' ? 'primary' : 'secondary' ?>">
                      <?= strtoupper($it['item_type']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($it['chem_name']) ?></div>
                    <span class="text-secondary small font-monospace">SKU: <?= htmlspecialchars($it['sku']) ?></span>
                  </td>
                  <td><span class="badge bg-light text-secondary border"><?= strtoupper($it['process_stage']) ?></span></td>
                  <td>
                    <?php if ($it['item_type'] === 'dye'): ?>
                      <div class="input-group input-group-sm">
                        <input type="number" step="0.001" name="items[<?= $it['id'] ?>][dosage_percentage]" class="form-control font-monospace fw-bold" value="<?= $it['dosage_percentage'] ?>">
                        <span class="input-group-text">% OWF</span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($it['item_type'] !== 'dye'): ?>
                      <div class="input-group input-group-sm">
                        <input type="number" step="0.1" name="items[<?= $it['id'] ?>][dosage_grams_per_litre]" class="form-control font-monospace" value="<?= $it['dosage_grams_per_litre'] ?>">
                        <span class="input-group-text">g/L</span>
                      </div>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/recipes') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save Changes & Update Recipe
        </button>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
