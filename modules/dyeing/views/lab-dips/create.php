<?php
/**
 * Dedicated Full-Page Form: Create New Lab Dip Shade Request
 * File: modules/dyeing/views/lab-dips/create.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'New Lab Dip Shade Request';
$activeModule = 'lab-dips';
$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);
$errors = [];

// Handle new Lab Dip Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_lab_dip') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    
    $reqNo = 'LAB-' . date('Y') . '-' . str_pad((string)rand(10, 9999), 4, '0', STR_PAD_LEFT);
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $shadeName = trim($_POST['shade_name'] ?? '');
    $shadeCode = trim($_POST['shade_code'] ?? 'SH-NAV-01');
    $fabricType = trim($_POST['fabric_type'] ?? '100% Combed Cotton Single Jersey 30s');
    $lightSource = trim($_POST['light_source'] ?? 'D65 / TL84');
    $dueDate = $_POST['due_date'] ?? date('Y-m-d', strtotime('+3 days'));

    if (!$customerId) {
        $errors[] = 'Please select a customer.';
    }
    if (empty($shadeName)) {
        $errors[] = 'Please specify a shade name.';
    }

    if (!$errors) {
        try {
            Database::insert('lab_dip_requests', [
                'company_id'   => $companyId,
                'request_no'   => $reqNo,
                'customer_id'  => $customerId,
                'shade_name'   => $shadeName,
                'shade_code'   => $shadeCode,
                'fabric_type'  => $fabricType,
                'light_source' => $lightSource,
                'due_date'     => $dueDate,
                'status'       => 'trial_in_progress'
            ]);

            header('Location: ' . Url::to('/dyeing/lab-dips?created=1'));
            exit;
        } catch (\Throwable $e) {
            $errors[] = 'Failed to create request: ' . $e->getMessage();
        }
    }
}

$customers = Database::fetchAll("SELECT * FROM customers WHERE company_id = ? ORDER BY name ASC", [$companyId]);
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
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/lab-dips') ?>">Lab Dips</a></li>
            <li class="breadcrumb-item active">New Shade Request</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-eyedropper text-dark me-2"></i>New Lab Dip Shade Matching Request</h1>
        <p class="text-secondary small mb-0">Initiate lab dip trial request for buyer color standard, Pantone TCX code, or fabric swatch matching.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> Back to Register
        </a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="action" value="save_lab_dip">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

      <div class="row g-4">
        <!-- Customer & Due Date -->
        <div class="col-lg-4">
          <div class="cz-panel h-100">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-person me-2"></i>Customer & Buyer Specs</h2>
            </div>
            <div class="p-3">
              <div class="mb-3">
                <label class="form-label small fw-bold">Select Customer / Brand *</label>
                <select name="customer_id" class="form-select form-select-sm" required>
                  <option value="">-- Choose Customer --</option>
                  <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['code'] ?? '') ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Target Due Date *</label>
                <input type="date" name="due_date" class="form-control form-control-sm fw-bold" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Illuminant / Light Sources *</label>
                <select name="light_source" class="form-select form-select-sm" required>
                  <option value="D65 / TL84" selected>D65 (Daylight) + TL84 (Store / Fluorescent)</option>
                  <option value="D65 / CWF">D65 + CWF (Cool White Fluorescent)</option>
                  <option value="D65 / Incandescent A">D65 + Incandescent A (Home / Tungsten)</option>
                  <option value="D65 / TL84 / UV">D65 + TL84 + UV (Optical Brightener Check)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Shade & Fabric Details -->
        <div class="col-lg-8">
          <div class="cz-panel mb-4">
            <div class="cz-panel-head">
              <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-palette me-2"></i>Shade Definition & Target Substrate</h2>
            </div>
            <div class="p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Shade / Color Name *</label>
                  <input type="text" name="shade_name" class="form-control form-control-sm fw-bold" placeholder="e.g., Turkish Sea Navy" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Pantone TCX / Buyer Shade Code *</label>
                  <input type="text" name="shade_code" class="form-control form-control-sm font-monospace fw-bold" placeholder="e.g., 19-4053 TCX" required>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Fabric Substrate / Yarn Specification *</label>
                  <input type="text" name="fabric_type" class="form-control form-control-sm" value="100% Combed Cotton Single Jersey 30s (160 GSM)" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Target Spectrophotometer Tolerance</label>
                  <input type="text" class="form-control form-control-sm bg-light font-monospace" value="ΔE <= 0.80 CMC (2:1)" readonly>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Lab Routing Status</label>
                  <input type="text" class="form-control form-control-sm bg-light font-monospace" value="Trial In Progress (A/B/C Swatches)" readonly>
                </div>
              </div>
            </div>
            <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
              <a href="<?= Url::to('/dyeing/lab-dips') ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
              <button type="submit" class="btn btn-dark btn-sm px-4">
                <i class="bi bi-flask me-1"></i> Submit to Color Kitchen Lab
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
