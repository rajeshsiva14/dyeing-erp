<?php
/**
 * Dye Recipes & Formulations - KARCEM Textile ERP
 * File: modules/dyeing/views/recipes.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Security\CSRF;

$pageTitle = 'Dye Recipes & Formulations';
$activeModule = 'recipes';

// Handle Save New Recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_recipe') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');

    $recipeCode = trim($_POST['recipe_code'] ?? '');
    $shadeName = trim($_POST['shade_name'] ?? '');
    $comp = trim($_POST['fabric_composition'] ?? '100% Cotton Single Jersey');
    $lr = trim($_POST['liquor_ratio'] ?? '1:5');
    $dyeClass = $_POST['dye_class'] ?? 'reactive';
    $chemId = (int)($_POST['chemical_product_id'] ?? 5);
    $owf = (float)($_POST['dosage_owf'] ?? 2.80);

    if (!empty($recipeCode) && !empty($shadeName)) {
        Database::beginTransaction();
        try {
            $recipeId = Database::insert('dye_recipes', [
                'company_id'         => 1,
                'recipe_code'        => $recipeCode,
                'shade_name'         => $shadeName,
                'fabric_composition' => $comp,
                'liquor_ratio'       => $lr,
                'dye_class'          => $dyeClass,
                'revision_no'        => 1,
                'is_approved'        => 1,
                'approved_by'        => 1
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

            // Add standard auxiliaries (Glauber salt & Soda ash)
            Database::insert('dye_recipe_items', [
                'dye_recipe_id'          => $recipeId,
                'chemical_product_id'    => 6, // Glauber Salt
                'item_type'              => 'auxiliary',
                'dosage_percentage'      => null,
                'dosage_grams_per_litre' => 40.00,
                'process_stage'          => 'dyeing'
            ]);
            Database::insert('dye_recipe_items', [
                'dye_recipe_id'          => $recipeId,
                'chemical_product_id'    => 7, // Soda Ash
                'item_type'              => 'chemical',
                'dosage_percentage'      => null,
                'dosage_grams_per_litre' => 15.00,
                'process_stage'          => 'dyeing'
            ]);

            Database::commit();
            header('Location: ' . Url::to('/dyeing/recipes?success=1'));
            exit;
        } catch (\Throwable $e) {
            Database::rollBack();
            $errorMsg = $e->getMessage();
        }
    }
}

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$recipes = Database::fetchAll("SELECT r.*, c.name as customer_name FROM dye_recipes r LEFT JOIN customers c ON r.customer_id = c.id ORDER BY r.id DESC");
$chemicals = Database::fetchAll("SELECT id, name, sku FROM products WHERE category = 'dyes_chemical' ORDER BY name ASC");
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Recipes</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">Color Dye Recipes & Chemical Formulations</h1>
        <p class="text-secondary small mb-0">Approved color kitchen recipes, dyestuffs dosage (% OWF), auxiliary concentrations (g/l), and liquor ratios.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#newRecipeModal">
          <i class="bi bi-plus-lg me-1"></i> New Recipe Formulation
        </button>
      </div>
    </div>

    <!-- Recipes Grid -->
    <div class="row g-3">
      <?php foreach ($recipes as $r): ?>
        <?php
          $items = Database::fetchAll("SELECT ri.*, p.name as chem_name FROM dye_recipe_items ri JOIN products p ON ri.chemical_product_id = p.id WHERE ri.dye_recipe_id = ?", [$r['id']]);
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['recipe_code']) ?></span>
                <span class="badge bg-primary-subtle text-primary ms-1">Rev <?= $r['revision_no'] ?></span>
              </div>
              <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
            </div>
            <div class="card-body p-3">
              <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($r['shade_name']) ?></h5>
              <div class="small text-secondary mb-3">
                <span><?= htmlspecialchars($r['fabric_composition']) ?></span> ·
                <span class="fw-bold text-primary">Ratio <?= htmlspecialchars($r['liquor_ratio']) ?></span> ·
                <span class="text-uppercase"><?= htmlspecialchars($r['dye_class']) ?></span>
              </div>

              <div class="table-responsive">
                <table class="table table-sm table-borderless small mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Component / Chemical</th>
                      <th class="text-end">Dosage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $it): ?>
                      <tr>
                        <td>
                          <span class="badge bg-<?= $it['item_type'] === 'dye' ? 'primary' : 'secondary' ?> me-1" style="font-size: 0.65rem;">
                            <?= strtoupper($it['item_type']) ?>
                          </span>
                          <?= htmlspecialchars($it['chem_name']) ?>
                        </td>
                        <td class="text-end fw-bold font-monospace">
                          <?= $it['dosage_percentage'] !== null ? $it['dosage_percentage'] . '% OWF' : $it['dosage_grams_per_litre'] . ' g/l' ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer bg-light border-0 p-3 d-flex justify-content-between align-items-center">
              <span class="small text-muted"><?= htmlspecialchars($r['customer_name'] ?? 'General Palette') ?></span>
              <a href="<?= Url::to('/print/batch-card?id=1') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i> Formula Sheet
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</main>

<!-- New Recipe Modal -->
<div class="modal fade" id="newRecipeModal" tabindex="-1" aria-labelledby="newRecipeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0">
      <div class="modal-header bg-dark text-white p-3">
        <h5 class="modal-title fs-6 fw-bold" id="newRecipeModalLabel"><i class="bi bi-journal-plus me-2"></i>New Recipe Formulation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <?= CSRF::getField() ?>
        <input type="hidden" name="action" value="save_recipe">
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold">Recipe Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="recipe_code" placeholder="e.g. REC-NAVY-03" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Shade Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="shade_name" placeholder="e.g. Navy Blue Dark" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Fabric Composition</label>
              <input type="text" class="form-control" name="fabric_composition" value="100% Cotton Single Jersey">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Liquor Ratio</label>
              <input type="text" class="form-control" name="liquor_ratio" value="1:5">
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Primary Reactive Dye Component <span class="text-danger">*</span></label>
              <select class="form-select" name="chemical_product_id" required>
                <?php foreach ($chemicals as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Dosage (% OWF) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control" name="dosage_owf" value="2.80" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold">Dye Class</label>
              <select class="form-select" name="dye_class">
                <option value="reactive">Reactive (Cotton Knits)</option>
                <option value="disperse">Disperse (Polyester)</option>
                <option value="vat">Vat (Yarn Dyeing)</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light p-3">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark" id="saveRecBtn"><i class="bi bi-save me-1"></i> Save Recipe</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php
require_once dirname(__DIR__, 3) . '/includes/footer.php';
