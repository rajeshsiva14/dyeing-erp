<?php
/**
 * Organization & Multi-Unit Setup — KARCEM Textile ERP
 * File: modules/dyeing/views/organization.php
 * Manage corporate legal entities and multiple factory branches/mills.
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\SessionManager;
use Core\Security\CSRF;

SessionManager::start();
$currentUser = SessionManager::getUser();
$pageTitle = 'Organization & Multi-Unit Setup';
$activeModule = 'organization';

$msg = '';
$msgType = 'success';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $action = $_POST['form_action'] ?? '';

    // 1. Update Company Profile
    if ($action === 'update_company') {
        $companyId = (int)($_POST['company_id'] ?? 1);
        $name = trim($_POST['name'] ?? '');
        $legalName = trim($_POST['legal_name'] ?? '');
        $code = trim($_POST['code'] ?? 'KARCEM');
        $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
        $pan = strtoupper(trim($_POST['pan'] ?? ''));
        $cin = strtoupper(trim($_POST['cin'] ?? ''));
        $address1 = trim($_POST['address_line1'] ?? '');
        $address2 = trim($_POST['address_line2'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? 'Tamil Nadu');
        $stateCode = trim($_POST['state_code'] ?? '33');
        $pincode = trim($_POST['pincode'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');

        Database::query("
            UPDATE companies 
            SET name = ?, legal_name = ?, code = ?, gstin = ?, pan = ?, cin = ?,
                address_line1 = ?, address_line2 = ?, city = ?, state = ?, state_code = ?,
                pincode = ?, phone = ?, email = ?, website = ?
            WHERE id = ?
        ", [
            $name, $legalName, $code, $gstin, $pan, $cin,
            $address1, $address2, $city, $state, $stateCode,
            $pincode, $phone, $email, $website, $companyId
        ]);

        $msg = 'Company profile and statutory details saved successfully.';
    }

    // 2. Add New Branch / Mill Unit
    if ($action === 'add_branch') {
        $companyId = (int)($_POST['company_id'] ?? 1);
        $code = strtoupper(trim($_POST['unit_code'] ?? ''));
        $name = trim($_POST['unit_name'] ?? '');
        $branchType = trim($_POST['branch_type'] ?? 'dyeing_unit');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? 'Tamil Nadu');
        $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
        $status = trim($_POST['status'] ?? 'active');

        if (!empty($name) && !empty($code)) {
            Database::query("
                INSERT INTO branches (company_id, code, name, branch_type, address, city, state, gstin, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [$companyId, $code, $name, $branchType, $address, $city, $state, $gstin, $status]);
            $msg = "New factory branch [{$code} - {$name}] created successfully.";
        } else {
            $msg = 'Please specify both Unit Code and Unit Name.';
            $msgType = 'danger';
        }
    }

    // 3. Update Existing Branch
    if ($action === 'update_branch') {
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $code = strtoupper(trim($_POST['unit_code'] ?? ''));
        $name = trim($_POST['unit_name'] ?? '');
        $branchType = trim($_POST['branch_type'] ?? 'dyeing_unit');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? 'Tamil Nadu');
        $gstin = strtoupper(trim($_POST['gstin'] ?? ''));
        $status = trim($_POST['status'] ?? 'active');

        if ($branchId > 0 && !empty($name)) {
            Database::query("
                UPDATE branches
                SET code = ?, name = ?, branch_type = ?, address = ?, city = ?, state = ?, gstin = ?, status = ?
                WHERE id = ?
            ", [$code, $name, $branchType, $address, $city, $state, $gstin, $status, $branchId]);
            $msg = "Factory unit [{$name}] updated successfully.";
        }
    }

    // 4. Switch Active Unit
    if ($action === 'set_active_branch') {
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $branch = Database::fetch("SELECT * FROM branches WHERE id = ?", [$branchId]);
        if ($branch) {
            $_SESSION['active_branch_id'] = $branch['id'];
            $_SESSION['active_branch_name'] = $branch['name'] . ' (' . ($branch['city'] ?? 'Unit') . ')';
            $msg = "Switched active operating plant to: " . htmlspecialchars($_SESSION['active_branch_name']);
        }
    }

    // 5. Toggle Status
    if ($action === 'toggle_branch_status') {
        $branchId = (int)($_POST['branch_id'] ?? 0);
        $currentStatus = trim($_POST['current_status'] ?? 'active');
        $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
        Database::query("UPDATE branches SET status = ? WHERE id = ?", [$newStatus, $branchId]);
        $msg = "Unit status toggled to {$newStatus}.";
    }
}

// Fetch Company & Branches
$company = Database::fetch("SELECT * FROM companies ORDER BY id ASC LIMIT 1");
if (!$company) {
    $company = [
        'id' => 1,
        'code' => 'KARCEM',
        'name' => 'KARCEM Textiles & Processors Pvt Ltd',
        'legal_name' => 'KARCEM Textiles and Processors Private Limited',
        'gstin' => '33AAACK1234F1Z5',
        'pan' => 'AAACK1234F',
        'cin' => 'U17111TZ2018PTC029871',
        'address_line1' => 'SF No. 248/1A, SIPCOT Industrial Growth Estate',
        'address_line2' => 'Perundurai Road',
        'city' => 'Tirupur',
        'state' => 'Tamil Nadu',
        'state_code' => '33',
        'pincode' => '641604',
        'phone' => '+91 421 2234890',
        'email' => 'operations@karcemtextiles.com',
        'website' => 'https://karcemtextiles.com',
        'base_currency' => 'INR',
        'currency_symbol' => '₹',
        'status' => 'active'
    ];
}

$branches = Database::fetchAll("SELECT * FROM branches ORDER BY id ASC");
$totalBranches = count($branches);
$activeUnits = count(array_filter($branches, fn($b) => ($b['status'] ?? '') === 'active'));
$dyeingUnits = count(array_filter($branches, fn($b) => ($b['branch_type'] ?? '') === 'dyeing_unit'));

$activeSessionBranchId = $_SESSION['active_branch_id'] ?? 3; // Default to DYE-SIPCOT / Tirupur

require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="cz-main">
  <div class="cz-content p-3 p-md-4">

    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dyeing/dashboard">Dyeing ERP</a></li>
            <li class="breadcrumb-item"><a href="<?= $baseUrl ?>/dyeing/masters">Masters & Settings</a></li>
            <li class="breadcrumb-item active" aria-current="page">Organization & Mills Setup</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">
          <i data-lucide="building" class="text-primary me-2"><i class="bi bi-buildings text-primary"></i></i>
          Organization & Multi-Unit Setup
        </h1>
        <p class="text-secondary small mb-0 mt-1">
          Configure corporate legal entity, GSTIN, PAN, and manage multiple spinning, weaving, wet processing dyeing mills, and commercial branches.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#newUnitModal">
          <i data-lucide="plus" style="width: 16px; height: 16px;"><i class="bi bi-plus-lg"></i></i>
          Add New Mill / Unit
        </button>
      </div>
    </div>

    <!-- Alert Message -->
    <?php if (!empty($msg)): ?>
      <div class="alert alert-<?= $msgType ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm py-2 px-3 mb-4" role="alert">
        <i data-lucide="<?= $msgType === 'success' ? 'check-circle' : 'alert-circle' ?>"><i class="bi bi-info-circle"></i></i>
        <div><?= htmlspecialchars($msg) ?></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- KPI Metric Summary Cards -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="cz-card p-3 h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-secondary small fw-medium">Company Entity</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2">Registered</span>
          </div>
          <div class="fs-6 fw-bold text-dark text-truncate" title="<?= htmlspecialchars($company['name']) ?>"><?= htmlspecialchars($company['name']) ?></div>
          <div class="text-secondary small mt-1">GSTIN: <span class="fw-semibold text-dark"><?= htmlspecialchars($company['gstin'] ?? '33AAACK1234F1Z5') ?></span></div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="cz-card p-3 h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-secondary small fw-medium">Total Factory Units</span>
            <i data-lucide="network" class="text-primary" style="width: 18px; height: 18px;"><i class="bi bi-diagram-3"></i></i>
          </div>
          <div class="fs-4 fw-bold text-dark"><?= $totalBranches ?> <span class="fs-6 fw-normal text-secondary">Units</span></div>
          <div class="text-success small mt-1"><i class="bi bi-check-circle me-1"></i><?= $activeUnits ?> Active operating locations</div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="cz-card p-3 h-100">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-secondary small fw-medium">Dyeing & Processing Plants</span>
            <i data-lucide="droplet" class="text-info" style="width: 18px; height: 18px;"><i class="bi bi-droplet"></i></i>
          </div>
          <div class="fs-4 fw-bold text-dark"><?= $dyeingUnits ?> <span class="fs-6 fw-normal text-secondary">Mills</span></div>
          <div class="text-secondary small mt-1">Wet Processing & Soft-flow Vessels</div>
        </div>
      </div>

      <div class="col-12 col-sm-6 col-xl-3">
        <div class="cz-card p-3 h-100 border-primary-subtle" style="background: #F8FAFD;">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-secondary small fw-medium">Current Active Workspace</span>
            <span class="pulse-dot"></span>
          </div>
          <div class="fs-6 fw-bold text-primary text-truncate"><?= htmlspecialchars($_SESSION['active_branch_name'] ?? 'Tirupur Mill (TN 33)') ?></div>
          <div class="text-secondary small mt-1">Logged In Workspace Scope</div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs cz-tabs mb-4" id="orgTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="units-tab" data-bs-toggle="tab" data-bs-target="#units-pane" type="button" role="tab" aria-selected="true">
          <i data-lucide="factory" class="me-1" style="width: 16px; height: 16px;"><i class="bi bi-building"></i></i>
          Factory & Mill Units (<?= $totalBranches ?>)
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="company-tab" data-bs-toggle="tab" data-bs-target="#company-pane" type="button" role="tab" aria-selected="false">
          <i data-lucide="shield-check" class="me-1" style="width: 16px; height: 16px;"><i class="bi bi-card-heading"></i></i>
          Corporate Legal Profile & GSTIN
        </button>
      </li>
    </ul>

    <div class="tab-content" id="orgTabsContent">
      <!-- ============================================================== -->
      <!-- TAB 1: Factory & Mill Units                                     -->
      <!-- ============================================================== -->
      <div class="tab-pane fade show active" id="units-pane" role="tabpanel">
        <div class="cz-card">
          <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
              <h2 class="h6 fw-bold text-dark mb-0">Registered Processing Units & Locations</h2>
              <p class="text-secondary small mb-0">Switch your operating workspace or configure branch address, facility type, and GSTIN.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newUnitModal">
              <i class="bi bi-plus me-1"></i>Add Unit
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 100px;">Unit Code</th>
                  <th>Facility / Unit Name</th>
                  <th>Facility Type</th>
                  <th>Location & Address</th>
                  <th>Unit GSTIN</th>
                  <th>Status</th>
                  <th class="text-end" style="width: 190px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($branches)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No units registered yet. Click "Add New Mill / Unit" to get started.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($branches as $b): 
                    $isCurrentActive = ($b['id'] == $activeSessionBranchId);
                    $typeLabels = [
                      'head_office' => ['label' => 'Head Office', 'class' => 'bg-secondary-subtle text-secondary'],
                      'mill' => ['label' => 'Spinning & Weaving Mill', 'class' => 'bg-info-subtle text-info-emphasis'],
                      'dyeing_unit' => ['label' => 'Dyeing & Wet Processing', 'class' => 'bg-primary-subtle text-primary'],
                      'garment_unit' => ['label' => 'Garmenting & Apparel', 'class' => 'bg-purple-subtle text-purple'],
                      'warehouse' => ['label' => 'Warehouse & Yard', 'class' => 'bg-warning-subtle text-warning-emphasis'],
                      'sales_office' => ['label' => 'Commercial Sales Office', 'class' => 'bg-light text-dark'],
                    ];
                    $tInfo = $typeLabels[$b['branch_type']] ?? ['label' => ucfirst($b['branch_type']), 'class' => 'bg-light text-dark'];
                  ?>
                    <tr class="<?= $isCurrentActive ? 'table-primary-subtle' : '' ?>">
                      <td>
                        <span class="badge bg-dark-subtle text-dark border px-2 py-1 font-monospace"><?= htmlspecialchars($b['code']) ?></span>
                      </td>
                      <td>
                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                          <?= htmlspecialchars($b['name']) ?>
                          <?php if ($isCurrentActive): ?>
                            <span class="badge bg-primary text-white" style="font-size: 0.65rem;">ACTIVE WORKSPACE</span>
                          <?php endif; ?>
                        </div>
                        <div class="text-secondary small"><?= htmlspecialchars($b['city'] ?? '') ?>, <?= htmlspecialchars($b['state'] ?? '') ?></div>
                      </td>
                      <td>
                        <span class="badge <?= $tInfo['class'] ?> border px-2 py-1" style="font-size: 0.72rem;">
                          <?= htmlspecialchars($tInfo['label']) ?>
                        </span>
                      </td>
                      <td>
                        <div class="small text-dark text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($b['address']) ?>">
                          <?= htmlspecialchars($b['address']) ?>
                        </div>
                      </td>
                      <td>
                        <span class="small font-monospace text-secondary"><?= htmlspecialchars($b['gstin'] ?: $company['gstin'] ?: '—') ?></span>
                      </td>
                      <td>
                        <?php if ($b['status'] === 'active'): ?>
                          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                        <?php else: ?>
                          <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Inactive</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <div class="d-inline-flex align-items-center gap-1">
                          <?php if (!$isCurrentActive): ?>
                            <form method="POST" action="" class="d-inline">
                              <?= CSRF::getField() ?>
                              <input type="hidden" name="form_action" value="set_active_branch">
                              <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                              <button type="submit" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;" title="Switch to this Unit">
                                Switch
                              </button>
                            </form>
                          <?php endif; ?>

                          <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2 edit-branch-btn" 
                                  style="font-size: 0.75rem;"
                                  data-bs-toggle="modal" 
                                  data-bs-target="#editUnitModal"
                                  data-id="<?= $b['id'] ?>"
                                  data-code="<?= htmlspecialchars($b['code']) ?>"
                                  data-name="<?= htmlspecialchars($b['name']) ?>"
                                  data-type="<?= htmlspecialchars($b['branch_type']) ?>"
                                  data-address="<?= htmlspecialchars($b['address']) ?>"
                                  data-city="<?= htmlspecialchars($b['city']) ?>"
                                  data-state="<?= htmlspecialchars($b['state']) ?>"
                                  data-gstin="<?= htmlspecialchars($b['gstin']) ?>"
                                  data-status="<?= htmlspecialchars($b['status']) ?>"
                                  title="Edit Unit">
                            <i class="bi bi-pencil"></i>
                          </button>

                          <form method="POST" action="" class="d-inline" onsubmit="return confirm('Toggle status of this factory unit?');">
                            <?= CSRF::getField() ?>
                            <input type="hidden" name="form_action" value="toggle_branch_status">
                            <input type="hidden" name="branch_id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="current_status" value="<?= $b['status'] ?>">
                            <button type="submit" class="btn btn-xs btn-light py-1 px-2 border" style="font-size: 0.75rem;" title="Toggle Active/Inactive">
                              <i class="bi bi-arrow-repeat text-secondary"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TAB 2: Corporate Legal Profile & GSTIN                         -->
      <!-- ============================================================== -->
      <div class="tab-pane fade" id="company-pane" role="tabpanel">
        <div class="cz-card p-4">
          <form method="POST" action="">
            <?= CSRF::getField() ?>
            <input type="hidden" name="form_action" value="update_company">
            <input type="hidden" name="company_id" value="<?= $company['id'] ?>">

            <div class="row g-3">
              <div class="col-12 border-bottom pb-2 mb-2">
                <h2 class="h6 fw-bold text-dark mb-0">Legal Identification & Registration</h2>
                <div class="text-secondary small">These details populate invoices, job work challans, delivery slips, and statutory compliance forms.</div>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Trade / Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($company['name']) ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Full Legal Entity Name <span class="text-danger">*</span></label>
                <input type="text" name="legal_name" class="form-control" value="<?= htmlspecialchars($company['legal_name']) ?>" required>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Company Short Code</label>
                <input type="text" name="code" class="form-control font-monospace" value="<?= htmlspecialchars($company['code']) ?>" required>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">GSTIN (Head Office) <span class="text-danger">*</span></label>
                <input type="text" name="gstin" class="form-control font-monospace" maxlength="15" value="<?= htmlspecialchars($company['gstin'] ?? '') ?>" placeholder="33AAACK1234F1Z5" required>
              </div>

              <div class="col-md-4">
                <label class="form-label small fw-bold text-dark">Permanent Account Number (PAN)</label>
                <input type="text" name="pan" class="form-control font-monospace" maxlength="10" value="<?= htmlspecialchars($company['pan'] ?? '') ?>" placeholder="AAACK1234F">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Corporate Identification Number (CIN)</label>
                <input type="text" name="cin" class="form-control font-monospace" value="<?= htmlspecialchars($company['cin'] ?? '') ?>" placeholder="U17111TZ2018PTC029871">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Official ERP Contact Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email'] ?? '') ?>">
              </div>

              <div class="col-12 border-bottom pb-2 mb-2 mt-4">
                <h2 class="h6 fw-bold text-dark mb-0">Registered Office Address</h2>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Address Line 1 <span class="text-danger">*</span></label>
                <input type="text" name="address_line1" class="form-control" value="<?= htmlspecialchars($company['address_line1'] ?? '') ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Address Line 2 / Landmark</label>
                <input type="text" name="address_line2" class="form-control" value="<?= htmlspecialchars($company['address_line2'] ?? '') ?>">
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-dark">City <span class="text-danger">*</span></label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($company['city'] ?? '') ?>" required>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-dark">State <span class="text-danger">*</span></label>
                <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($company['state'] ?? 'Tamil Nadu') ?>" required>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-dark">State Code (GST)</label>
                <input type="text" name="state_code" class="form-control font-monospace" value="<?= htmlspecialchars($company['state_code'] ?? '33') ?>" required>
              </div>

              <div class="col-md-3">
                <label class="form-label small fw-bold text-dark">Pincode</label>
                <input type="text" name="pincode" class="form-control font-monospace" value="<?= htmlspecialchars($company['pincode'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Telephone / Mobile</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label small fw-bold text-dark">Company Website</label>
                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($company['website'] ?? '') ?>">
              </div>

              <div class="col-12 mt-4 pt-2 border-top d-flex justify-content-end gap-2">
                <button type="reset" class="btn btn-light border">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Company Details</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: ADD NEW FACTORY UNIT / MILL                             -->
<!-- ============================================================== -->
<div class="modal fade" id="newUnitModal" tabindex="-1" aria-labelledby="newUnitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <form method="POST" action="">
        <?= CSRF::getField() ?>
        <input type="hidden" name="form_action" value="add_branch">
        <input type="hidden" name="company_id" value="<?= $company['id'] ?>">

        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fs-6 fw-bold text-dark" id="newUnitModalLabel">
            <i class="bi bi-building-add text-primary me-2"></i>Add Factory Branch / Processing Mill
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Unit Code <span class="text-danger">*</span></label>
              <input type="text" name="unit_code" class="form-control font-monospace" placeholder="e.g. DYE-02 / TN-34" required>
              <div class="form-text" style="font-size: 0.72rem;">Unique mill/branch identifier</div>
            </div>

            <div class="col-md-8">
              <label class="form-label small fw-bold text-dark">Unit / Mill Name <span class="text-danger">*</span></label>
              <input type="text" name="unit_name" class="form-control" placeholder="e.g. Perundurai SIPCOT Dyeing Mill (Unit 2)" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Facility Operational Type <span class="text-danger">*</span></label>
              <select name="branch_type" class="form-select" required>
                <option value="dyeing_unit" selected>Dyeing & Wet Processing Plant</option>
                <option value="mill">Spinning & Weaving Mill</option>
                <option value="garment_unit">Garmenting & Apparel Finishing</option>
                <option value="warehouse">Central Raw Material & Fabric Warehouse</option>
                <option value="head_office">Head Office & Commercial Operations</option>
                <option value="sales_office">Regional Sales Office</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Unit GSTIN (if separate from HO)</label>
              <input type="text" name="gstin" class="form-control font-monospace" placeholder="e.g. 33AAACK1234F1Z5" value="<?= htmlspecialchars($company['gstin'] ?? '') ?>">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-dark">Factory Physical Address <span class="text-danger">*</span></label>
              <input type="text" name="address" class="form-control" placeholder="SF No, Industrial Estate, Road, Landmark" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">City / District <span class="text-danger">*</span></label>
              <input type="text" name="city" class="form-control" placeholder="e.g. Tirupur / Erode / Coimbatore" required>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold text-dark">State</label>
              <input type="text" name="state" class="form-control" value="Tamil Nadu" required>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold text-dark">Status</label>
              <select name="status" class="form-select">
                <option value="active" selected>Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top py-2 px-4 bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm">Save Mill Unit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: EDIT FACTORY UNIT                                       -->
<!-- ============================================================== -->
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <form method="POST" action="">
        <?= CSRF::getField() ?>
        <input type="hidden" name="form_action" value="update_branch">
        <input type="hidden" name="branch_id" id="edit_branch_id">

        <div class="modal-header border-bottom py-3">
          <h5 class="modal-title fs-6 fw-bold text-dark" id="editUnitModalLabel">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Factory Mill / Unit Details
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-dark">Unit Code <span class="text-danger">*</span></label>
              <input type="text" name="unit_code" id="edit_unit_code" class="form-control font-monospace" required>
            </div>

            <div class="col-md-8">
              <label class="form-label small fw-bold text-dark">Unit / Mill Name <span class="text-danger">*</span></label>
              <input type="text" name="unit_name" id="edit_unit_name" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Facility Operational Type <span class="text-danger">*</span></label>
              <select name="branch_type" id="edit_branch_type" class="form-select" required>
                <option value="dyeing_unit">Dyeing & Wet Processing Plant</option>
                <option value="mill">Spinning & Weaving Mill</option>
                <option value="garment_unit">Garmenting & Apparel Finishing</option>
                <option value="warehouse">Central Raw Material & Fabric Warehouse</option>
                <option value="head_office">Head Office & Commercial Operations</option>
                <option value="sales_office">Regional Sales Office</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">Unit GSTIN</label>
              <input type="text" name="gstin" id="edit_gstin" class="form-control font-monospace">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-dark">Factory Physical Address <span class="text-danger">*</span></label>
              <input type="text" name="address" id="edit_address" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-dark">City / District <span class="text-danger">*</span></label>
              <input type="text" name="city" id="edit_city" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold text-dark">State</label>
              <input type="text" name="state" id="edit_state" class="form-control" required>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold text-dark">Status</label>
              <select name="status" id="edit_status" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top py-2 px-4 bg-light">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4 shadow-sm">Update Unit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.edit-branch-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.getElementById('edit_branch_id').value = this.dataset.id;
      document.getElementById('edit_unit_code').value = this.dataset.code;
      document.getElementById('edit_unit_name').value = this.dataset.name;
      document.getElementById('edit_branch_type').value = this.dataset.type;
      document.getElementById('edit_address').value = this.dataset.address;
      document.getElementById('edit_city').value = this.dataset.city;
      document.getElementById('edit_state').value = this.dataset.state;
      document.getElementById('edit_gstin').value = this.dataset.gstin;
      document.getElementById('edit_status').value = this.dataset.status;
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
