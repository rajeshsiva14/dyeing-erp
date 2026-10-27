<?php
/**
 * Dedicated Standalone Form: New Dye Recipe Formulation
 * File: modules/dyeing/views/recipes-create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'New Dye Recipe Formulation';
$activeModule = 'recipes';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$errors = [];

// Handle POST to save new Recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_recipe') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $recipeCode = trim($_POST['recipe_code'] ?? '');
    $shadeName = trim($_POST['shade_name'] ?? '');
    $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $comp = trim($_POST['fabric_composition'] ?? '100% Cotton Single Jersey');
    $lr = trim($_POST['liquor_ratio'] ?? '1:6');
    $dyeClass = $_POST['dye_class'] ?? 'reactive';
    $chemId = (int)($_POST['chemical_product_id'] ?? 0);
    $owf = (float)($_POST['dosage_owf'] ?? 0);
    $glauberGpl = (float)($_POST['glauber_gpl'] ?? 40.00);
    $sodaAshGpl = (float)($_POST['soda_ash_gpl'] ?? 15.00);

    if ($recipeCode === '') $errors[] = 'Recipe code is required.';
    if ($shadeName === '') $errors[] = 'Shade name is required.';
    if ($chemId <= 0) $errors[] = 'Primary Dyestuff selection is required.';
    if ($owf <= 0) $errors[] = 'Primary Dyestuff dosage (% OWF) must be greater than 0.';

    if (!$errors) {
        Database::beginTransaction();
        try {
            $recipeId = Database::insert('dye_recipes', [
                'company_id'         => $companyId,
                'recipe_code'        => $recipeCode,
                'shade_name'         => $shadeName,
                'customer_id'        => $customerId,
                'fabric_composition' => $comp,
                'liquor_ratio'       => $lr,
                'dye_class'          => $dyeClass,
                'revision_no'        => 1,
                'is_approved'        => 1,
                'approved_by'        => (int)(SessionManager::getUser()['id'] ?? 1)
            ]);

            // Insert Primary Dye Component
            Database::insert('dye_recipe_items', [
                'dye_recipe_id'          => $recipeId,
                'chemical_product_id'    => $chemId,
                'item_type'              => 'dye',
                'dosage_percentage'      => $owf,
                'dosage_grams_per_litre' => null,
                'process_stage'          => 'dyeing'
            ]);

            // Standard auxiliaries: Glauber salt & Soda ash
            Database::insert('dye_recipe_items', [
                'dye_recipe_id'          => $recipeId,
                'chemical_product_id'    => 6, // Glauber Salt
                'item_type'              => 'auxiliary',
                'dosage_percentage'      => null,
                'dosage_grams_per_litre' => $glauberGpl,
                'process_stage'          => 'dyeing'
            ]);
            Database::insert('dye_recipe_items', [
                'dye_recipe_id'          => $recipeId,
                'chemical_product_id'    => 7, // Soda Ash
                'item_type'              => 'chemical',
                'dosage_percentage'      => null,
                'dosage_grams_per_litre' => $sodaAshGpl,
                'process_stage'          => 'dyeing'
            ]);

            Database::commit();
            header('Location: ' . Url::to('/dyeing/recipes?success=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$customers = Database::fetchAll("SELECT * FROM customers WHERE company_id = ? AND status = 'active' ORDER BY name ASC", [$companyId]);
$chemicals = Database::fetchAll("SELECT id, name, sku FROM products WHERE company_id = ? AND category = 'dyes_chemical' ORDER BY name ASC", [$companyId]);

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
            <h1 class="h5 fw-bold text-dark mb-0">New Dye Recipe Formulation</h1>
            <span class="badge badge-bw">NEW RECIPE</span>
          </div>
          <p class="text-secondary small mb-0 mt-1">Computerized color kitchen recipe standard, dyestuff exhaustion (% OWF), and chemical concentrations.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= Url::to('/dyeing/recipes') ?>" class="btn btn-outline-secondary btn-sm">Discard</a>
        <button type="submit" form="newRecipeForm" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save & Approve Recipe
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

    <form id="newRecipeForm" method="POST" action="<?= Url::to('/dyeing/recipes-create') ?>">
      <input type="hidden" name="action" value="save_recipe">
      <input type="hidden" name="csrf_token" value="<?= CSRF::getToken() ?>">

      <!-- Section A: Recipe & Shade Standards -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-palette"></i> Section A — Recipe Code & Master Shade</h2>
            <p class="cz-doc-section-desc">Unique recipe identifier, shade name, buyer linkage, and substrate composition.</p>
          </div>
          <span class="badge badge-bw font-monospace">MASTER SHADE</span>
        </div>

        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Recipe Code <span class="text-danger">*</span></label>
            <input type="text" name="recipe_code" class="form-control form-control-sm font-monospace" value="REC-2026-<?= str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT) ?>" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Shade Name <span class="text-danger">*</span></label>
            <input type="text" name="shade_name" class="form-control form-control-sm" placeholder="e.g. Royal Navy Blue" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Buyer / Customer Allocation</label>
            <select name="customer_id" class="form-select form-select-sm">
              <option value="">-- General Factory Shade Palette --</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Target Substrate / Fabric Composition <span class="text-danger">*</span></label>
            <input type="text" name="fabric_composition" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Dye Class Classification <span class="text-danger">*</span></label>
            <select name="dye_class" class="form-select form-select-sm" required>
              <option value="reactive" selected>Reactive Dyes (Cotton / Rayon / Modal)</option>
              <option value="disperse">Disperse Dyes (Polyester)</option>
              <option value="vat">Vat Dyes (Industrial Workwear)</option>
              <option value="pigment">Pigment System</option>
              <option value="direct">Direct Dyes</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Standard Liquor Ratio (L:R)</label>
            <input type="text" name="liquor_ratio" class="form-control form-control-sm font-monospace" value="1:6">
          </div>
        </div>
      </div>

      <!-- Section B: Dyestuffs & Chemical Auxiliaries -->
      <div class="cz-doc-section mb-4">
        <div class="cz-doc-section-head">
          <div>
            <h2 class="cz-doc-section-title"><i class="bi bi-eyedropper"></i> Section B — Dyestuffs (% OWF) & Chemical Auxiliaries (g/l)</h2>
            <p class="cz-doc-section-desc">Specify primary exhaustion colorant percentage and fixing electrolyte concentrations.</p>
          </div>
          <span class="badge badge-bw font-monospace">FORMULATION</span>
        </div>

        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Primary Colorant / Dyestuff <span class="text-danger">*</span></label>
            <select name="chemical_product_id" class="form-select form-select-sm" required>
              <option value="">-- Select Dyestuff Product --</option>
              <?php foreach ($chemicals as $ch): ?>
                <option value="<?= $ch['id'] ?>"><?= htmlspecialchars($ch['name']) ?> (SKU: <?= htmlspecialchars($ch['sku']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Exhaustion Dosage (% OWF - On Weight of Fabric) <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <input type="number" step="0.001" name="dosage_owf" class="form-control font-monospace fw-bold" value="2.850" required>
              <span class="input-group-text">% OWF</span>
            </div>
          </div>

          <div class="col-12"><hr class="my-2"></div>

          <div class="col-md-6">
            <label class="form-label">Glauber Salt / Sodium Sulphate Electrolyte</label>
            <div class="input-group input-group-sm">
              <input type="number" step="0.1" name="glauber_gpl" class="form-control font-monospace" value="40.0">
              <span class="input-group-text">g/L (Grams per Litre)</span>
            </div>
            <div class="text-secondary small mt-1">Accelerates reactive dye exhaustion onto cellulose fiber.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Soda Ash / Sodium Carbonate Alkali Fixative</label>
            <div class="input-group input-group-sm">
              <input type="number" step="0.1" name="soda_ash_gpl" class="form-control font-monospace" value="15.0">
              <span class="input-group-text">g/L (Grams per Litre)</span>
            </div>
            <div class="text-secondary small mt-1">Maintains bath pH 10.8 for covalent fiber-dye fixation.</div>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
        <a href="<?= Url::to('/dyeing/recipes') ?>" class="btn btn-outline-dark btn-sm">Discard</a>
        <button type="submit" class="btn btn-dark btn-sm px-4">
          <i class="bi bi-check2-circle me-1"></i> Save & Approve Recipe
        </button>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
