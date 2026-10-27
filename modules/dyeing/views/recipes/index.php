<?php
/**
 * Dye Recipes & Formulations Register
 * Dedicated list & catalogue view for color kitchen dye recipes.
 * File: modules/dyeing/views/recipes.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Dye Recipes & Formulations';
$activeModule = 'recipes';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle POST to delete recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_recipe') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $recipeId = (int)($_POST['recipe_id'] ?? 0);

    $target = Database::fetch("SELECT * FROM dye_recipes WHERE id = ? AND company_id = ?", [$recipeId, $companyId]);
    if ($target) {
        $inBatch = Database::fetch("SELECT id FROM dye_batches WHERE dye_recipe_id = ? AND company_id = ? LIMIT 1", [$recipeId, $companyId]);
        if ($inBatch) {
            header('Location: ' . Url::to('/dyeing/recipes?error=in_use'));
            exit;
        }

        Database::execute("DELETE FROM dye_recipe_items WHERE dye_recipe_id = ?", [$recipeId]);
        Database::execute("DELETE FROM dye_recipes WHERE id = ? AND company_id = ?", [$recipeId, $companyId]);
        header('Location: ' . Url::to('/dyeing/recipes?deleted=1'));
        exit;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(6, min(100, (int)($_GET['limit'] ?? 18)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM dye_recipes WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$recipes = Database::fetchAll("
    SELECT r.*, c.name as customer_name 
    FROM dye_recipes r 
    LEFT JOIN customers c ON r.customer_id = c.id 
    WHERE r.company_id = ?
    ORDER BY r.id DESC
    LIMIT {$limit} OFFSET {$offset}
", [$companyId]);

$csrfToken = CSRF::getToken();

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>">Dyeing</a></li>
            <li class="breadcrumb-item active">Recipes</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-journal-bookmark text-dark me-2"></i>Color Dye Recipes & Chemical Formulations</h1>
        <p class="text-secondary small mb-0">Approved color kitchen recipes, dyestuffs dosage (% OWF), auxiliary concentrations (g/l), and liquor ratios.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/recipe-calculation') ?>" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-calculator me-1"></i> Recipe Calculator (% OWF)
        </a>
        <a href="<?= Url::to('/dyeing/recipes-create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> New Recipe Formulation
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> New Dye Recipe formulation saved and approved successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Dye Recipe formulation updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-trash-fill me-1"></i> Dye Recipe formulation deleted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'in_use'): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> Cannot delete this Recipe because active dyeing batches are allocated to it.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Recipes Grid (Click any card to update) -->
    <div class="row g-3">
      <?php if (empty($recipes)): ?>
        <div class="col-12">
          <div class="card p-5 text-center text-secondary">
            <i class="bi bi-inbox fs-2 mb-2"></i>
            <p class="mb-2">No dye recipe formulations found.</p>
            <div>
              <a href="<?= Url::to('/dyeing/recipes-create') ?>" class="btn btn-dark btn-sm">Create First Recipe</a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($recipes as $r): ?>
          <?php
            $items = Database::fetchAll("SELECT ri.*, p.name as chem_name FROM dye_recipe_items ri JOIN products p ON ri.chemical_product_id = p.id WHERE ri.dye_recipe_id = ?", [$r['id']]);
            $editUrl = Url::to('/dyeing/recipes-edit?id=' . $r['id']);
          ?>
          <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 cz-clickable-row" data-href="<?= $editUrl ?>" style="cursor: pointer;" title="Click anywhere to update this recipe">
              <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <div>
                  <span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['recipe_code']) ?></span>
                  <span class="badge bg-primary-subtle text-primary ms-1">Rev <?= $r['revision_no'] ?></span>
                </div>
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
              </div>
              <div class="card-body p-3">
                <h5 class="fw-bold text-dark mb-1">
                  <a href="<?= $editUrl ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($r['shade_name']) ?></a>
                </h5>
                <div class="small text-secondary mb-3">
                  <span><?= htmlspecialchars($r['fabric_composition']) ?></span> &bull;
                  <span class="fw-bold text-primary">Ratio <?= htmlspecialchars($r['liquor_ratio']) ?></span> &bull;
                  <span class="text-uppercase"><?= htmlspecialchars($r['dye_class']) ?></span>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-borderless small mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Component</th>
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
                <div class="btn-group btn-group-sm">
                  <a href="<?= Url::to('/print/batch-card?id=1') ?>" target="_blank" class="btn btn-outline-secondary" onclick="event.stopPropagation();" title="Print Formula Sheet">
                    <i class="bi bi-printer"></i>
                  </a>
                  <button type="button" class="btn btn-outline-danger js-del-recipe" data-id="<?= $r['id'] ?>" data-code="<?= htmlspecialchars($r['recipe_code']) ?>" data-shade="<?= htmlspecialchars($r['shade_name']) ?>" onclick="event.stopPropagation();" title="Delete Recipe">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Server-Side Pagination Bar -->
    <div class="cz-panel-foot d-flex flex-wrap justify-content-between align-items-center p-3 mt-4 border rounded bg-white">
      <div class="small text-secondary">
        Showing <span class="fw-bold text-dark"><?= min($totalRecords, $offset + 1) ?></span> to 
        <span class="fw-bold text-dark"><?= min($totalRecords, $offset + $limit) ?></span> of 
        <span class="fw-bold text-dark"><?= $totalRecords ?></span> recipes
      </div>
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>">Previous</a>
            </li>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?>&limit=<?= $limit ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteRecipeModal" tabindex="-1" aria-labelledby="deleteRecipeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
          <div class="modal-header bg-danger text-white p-3">
            <h5 class="modal-title fs-6 fw-bold" id="deleteRecipeModalLabel">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Dye Recipe Formulation
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= Url::to('/dyeing/recipes') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete_recipe">
            <input type="hidden" name="recipe_id" id="del_recipe_id" value="">

            <div class="modal-body p-4 text-center">
              <p class="mb-2">Are you sure you want to permanently delete recipe:</p>
              <div class="p-3 bg-light rounded border mb-3">
                <div class="fw-bold fs-5 text-dark font-monospace" id="del_recipe_code"></div>
                <div class="small text-secondary" id="del_recipe_shade"></div>
              </div>
              <p class="small text-danger mb-0">
                <i class="bi bi-info-circle me-1"></i> Formulation components will be permanently deleted.
              </p>
            </div>

            <div class="modal-footer bg-light p-3">
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger btn-sm px-4">
                <i class="bi bi-trash me-1"></i> Confirm Delete
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const delModalEl = document.getElementById('deleteRecipeModal');
      const delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;

      // Delete button click
      document.querySelectorAll('.js-del-recipe').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          document.getElementById('del_recipe_id').value = this.dataset.id;
          document.getElementById('del_recipe_code').textContent = this.dataset.code;
          document.getElementById('del_recipe_shade').textContent = this.dataset.shade;
          if (delModal) delModal.show();
        });
      });
    });
    </script>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
