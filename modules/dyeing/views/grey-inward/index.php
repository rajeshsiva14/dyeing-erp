<?php
/**
 * Grey Fabric Inward Register & Lot Verification
 * Dedicated list & register view for incoming grey fabrics.
 * File: modules/dyeing/views/grey-inward/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Grey Fabric Inward Register';
$activeModule = 'grey-inward';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

// Handle POST to delete a grey fabric inward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_inward') {
    CSRF::validateToken($_POST['csrf_token'] ?? '');
    $inwardId = (int)($_POST['inward_id'] ?? 0);

    $target = Database::fetch("SELECT * FROM grey_fabric_inwards WHERE id = ? AND company_id = ?", [$inwardId, $companyId]);
    if ($target) {
        $inBatch = Database::fetch("SELECT id FROM dye_batches WHERE company_id = ? AND (batch_no = ? OR notes LIKE ?) LIMIT 1", [
            $companyId, $target['grey_lot_no'], '%' . $target['grey_lot_no'] . '%'
        ]);
        if ($inBatch) {
            header('Location: ' . Url::to('/dyeing/grey-inward?error=in_use'));
            exit;
        }

        Database::execute("DELETE FROM grey_fabric_inwards WHERE id = ? AND company_id = ?", [$inwardId, $companyId]);
        header('Location: ' . Url::to('/dyeing/grey-inward?deleted=1'));
        exit;
    }
}

// Pagination & Filtering
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(5, min(100, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$totalRecords = (int)(Database::fetch("SELECT COUNT(*) as c FROM grey_fabric_inwards WHERE company_id = ?", [$companyId])['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $limit));

$inwards = Database::fetchAll("SELECT * FROM grey_fabric_inwards WHERE company_id = ? ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}", [$companyId]);
$totalRolls = (int)(Database::fetch("SELECT SUM(received_rolls) as s FROM grey_fabric_inwards WHERE company_id = ?", [$companyId])['s'] ?? 0);
$totalNetKg = (float)(Database::fetch("SELECT SUM(net_weight_kg) as s FROM grey_fabric_inwards WHERE company_id = ?", [$companyId])['s'] ?? 0);
$pendingQc = (int)(Database::fetch("SELECT COUNT(*) as c FROM grey_fabric_inwards WHERE company_id = ? AND qc_status = 'inspection_pending'", [$companyId])['c'] ?? 0);

$csrfToken = CSRF::getToken();

require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/header.php';
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/sidebar.php';
?>
<style>
  /* Custom scoped styling for Grey Inward Register */
  .cz-stat-card-modern {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
  }
  .cz-stat-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
  }
  .cz-stat-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
  }
  
  /* Inward Register Table Layout */
  .cz-inward-table {
    min-width: 1220px;
    border-collapse: separate;
    border-spacing: 0;
  }
  .cz-inward-table thead th {
    background: #F8FAFC !important;
    color: #475569 !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    letter-spacing: 0.05em !important;
    text-transform: uppercase !important;
    border-top: none !important;
    border-bottom: 2px solid #E2E8F0 !important;
    padding: 13px 14px !important;
    white-space: nowrap !important;
  }
  .cz-inward-table tbody td {
    padding: 13px 14px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #F1F5F9 !important;
    background-color: #FFFFFF;
  }
  .cz-inward-table tbody tr.cz-clickable-row {
    cursor: pointer;
    transition: background-color 0.12s ease;
  }
  .cz-inward-table tbody tr.cz-clickable-row:hover td {
    background-color: #F8FAFC !important;
  }
  
  /* Clean action icon buttons */
  .cz-action-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.15s ease;
  }
  .cz-action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
  }
  
  /* Prevent word breaking on hyphenated codes */
  .nowrap-badge {
    white-space: nowrap !important;
    display: inline-block !important;
  }
</style>

<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Page Header & Action Controls -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dyeing/dashboard') ?>" class="text-decoration-none">Dyeing</a></li>
            <li class="breadcrumb-item text-secondary">Inward Logistics</li>
            <li class="breadcrumb-item active fw-semibold text-dark">Grey Fabric Inward</li>
          </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
          <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-box-arrow-in-down text-dark me-2"></i>Grey Fabric Inward & Verification</h1>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 fs-7"><?= $totalRecords ?> Lots</span>
        </div>
        <p class="text-secondary small mb-0 mt-1">Record incoming rolls from Knitting / Weaving / Subcontractors with tare weighment and lot verification.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= Url::to('/print/grey-inward?format=pdf') ?>" target="_blank" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-file-earmark-pdf me-1"></i> TCPDF Report
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-printer me-1"></i> Print Register
        </button>
        <button type="button" onclick="exportTableToCSV('greyInwardTable', 'grey_fabric_inward_register.csv')" class="btn btn-outline-success btn-sm">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel/CSV
        </button>
        <a href="<?= Url::to('/dyeing/grey-inward-create') ?>" class="btn btn-dark btn-sm shadow-sm px-3">
          <i class="bi bi-plus-circle me-1"></i> New Grey Inward
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
        <div><?= max(1, (int)$_GET['success']) ?> grey fabric lot(s) recorded successfully. Assigned to QC for inspection.</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success alert-dismissible fade show small d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-check-circle-fill fs-5 text-success"></i>
        <div>Grey Fabric Inward updated successfully with recalculated tare weighment.</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert alert-success alert-dismissible fade show small d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-trash-fill fs-5 text-success"></i>
        <div>Grey Fabric Inward deleted successfully. Inventory updated.</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'in_use'): ?>
      <div class="alert alert-danger alert-dismissible fade show small d-flex align-items-center gap-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
        <div>Cannot delete this inward lot because it is already allocated to an active dyeing batch.</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Stat KPI Cards (Redesigned with modern visual hierarchy) -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-lg-3">
        <div class="cz-stat-card-modern d-flex align-items-center justify-content-between">
          <div>
            <div class="text-secondary small fw-semibold text-uppercase" style="font-size:0.72rem; letter-spacing:0.04em;">Total Inward Lots</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totalRecords) ?></div>
            <div class="text-secondary small" style="font-size:0.75rem;">Recorded shipments</div>
          </div>
          <div class="cz-stat-icon-wrap bg-primary-subtle text-primary">
            <i class="bi bi-journal-check"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="cz-stat-card-modern d-flex align-items-center justify-content-between">
          <div>
            <div class="text-secondary small fw-semibold text-uppercase" style="font-size:0.72rem; letter-spacing:0.04em;">Total Rolls Received</div>
            <div class="fs-4 fw-bold text-dark mt-1 font-monospace"><?= number_format($totalRolls) ?></div>
            <div class="text-secondary small" style="font-size:0.75rem;">Incoming fabric rolls</div>
          </div>
          <div class="cz-stat-icon-wrap bg-info-subtle text-info">
            <i class="bi bi-layers"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="cz-stat-card-modern d-flex align-items-center justify-content-between">
          <div>
            <div class="text-secondary small fw-semibold text-uppercase" style="font-size:0.72rem; letter-spacing:0.04em;">Total Net Fabric</div>
            <div class="fs-4 fw-bold text-success mt-1 font-monospace"><?= number_format($totalNetKg, 1) ?> <span class="fs-6 fw-normal text-secondary">Kg</span></div>
            <div class="text-secondary small" style="font-size:0.75rem;">Net dyeable inventory</div>
          </div>
          <div class="cz-stat-icon-wrap bg-success-subtle text-success">
            <i class="bi bi-speedometer2"></i>
          </div>
        </div>
      </div>
      <div class="col-sm-6 col-lg-3">
        <div class="cz-stat-card-modern d-flex align-items-center justify-content-between">
          <div>
            <div class="text-secondary small fw-semibold text-uppercase" style="font-size:0.72rem; letter-spacing:0.04em;">Pending QC Check</div>
            <div class="fs-4 fw-bold <?= $pendingQc > 0 ? 'text-warning-emphasis' : 'text-dark' ?> mt-1 font-monospace"><?= $pendingQc ?></div>
            <div class="text-secondary small" style="font-size:0.75rem;">Ready for 4-point check</div>
          </div>
          <div class="cz-stat-icon-wrap bg-warning-subtle text-warning-emphasis">
            <i class="bi bi-hourglass-split"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Register Panel with Live Search & Filter Bar -->
    <div class="cz-panel shadow-sm border rounded-3 bg-white overflow-hidden">
      
      <!-- Panel Header & Search Bar -->
      <div class="p-3 border-bottom bg-light bg-opacity-50">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <h2 class="h6 fw-bold mb-1 text-dark d-flex align-items-center gap-2">
              <i class="bi bi-journal-text text-primary"></i> Grey Fabric Inward Register
            </h2>
            <div class="small text-secondary">
              Showing <span id="visibleCount" class="fw-bold text-dark"><?= count($inwards) ?></span> of <span class="fw-bold text-dark"><?= $totalRecords ?></span> inward receipts &bull; Click any row to edit
            </div>
          </div>
          
          <!-- Live Interactive Search & Filter Controls -->
          <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="min-width: 240px;">
              <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-search"></i></span>
              <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="Search Inward #, Lot #, Supplier...">
            </div>
            
            <select id="sourceFilter" class="form-select form-select-sm" style="width: auto;">
              <option value="">All Sources</option>
              <option value="knitting">Knitting</option>
              <option value="customer_jobwork">Customer Jobwork</option>
              <option value="weaving">Weaving</option>
              <option value="external">External Supplier</option>
            </select>

            <select id="qcFilter" class="form-select form-select-sm" style="width: auto;">
              <option value="">All QC Status</option>
              <option value="passed">Passed</option>
              <option value="inspection_pending">Pending QC</option>
              <option value="rejected">Rejected</option>
            </select>

            <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
              <i class="bi bi-arrow-counterclockwise"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Inwards Table with Generous Spacing and Zero Congestion -->
      <div class="table-responsive">
        <table id="greyInwardTable" class="table cz-inward-table align-middle mb-0">
          <thead>
            <tr>
              <th style="width: 140px;">Inward No</th>
              <th style="width: 200px;">Supplier / Source</th>
              <th style="width: 150px;">Challan / Vehicle</th>
              <th style="width: 220px;">Fabric Specification</th>
              <th style="width: 150px;">Lot No</th>
              <th style="width: 80px;" class="text-center">Rolls</th>
              <th style="width: 100px;" class="text-end">Gross (Kg)</th>
              <th style="width: 95px;" class="text-end">Tare (Kg)</th>
              <th style="width: 120px;" class="text-end">Net (Kg)</th>
              <th style="width: 120px;" class="text-center">QC Status</th>
              <th style="width: 150px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody id="inwardTableBody">
            <?php if (empty($inwards)): ?>
              <tr id="emptyTableRow">
                <td colspan="11" class="text-center py-5 text-secondary">
                  <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                  <div class="fw-semibold text-dark">No grey fabric inward records found</div>
                  <div class="small text-muted mt-1">Click "New Grey Inward" above to record incoming fabric shipments.</div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($inwards as $in): ?>
                <?php 
                  $qcStatus = $in['qc_status'] ?? 'inspection_pending';
                  $src = strtolower($in['source_type'] ?? 'knitting');
                  $editUrl = Url::to('/dyeing/grey-inward-edit?id=' . $in['id']);
                ?>
                <tr class="cz-clickable-row js-inward-row" 
                    data-href="<?= $editUrl ?>" 
                    data-inward="<?= strtolower($in['inward_no']) ?>"
                    data-lot="<?= strtolower($in['grey_lot_no']) ?>"
                    data-supplier="<?= strtolower($in['supplier_name']) ?>"
                    data-fabric="<?= strtolower($in['fabric_description'] . ' ' . $in['fabric_code']) ?>"
                    data-challan="<?= strtolower($in['delivery_challan_no']) ?>"
                    data-vehicle="<?= strtolower($in['vehicle_no']) ?>"
                    data-source="<?= $src ?>"
                    data-qc="<?= $qcStatus ?>"
                    title="Click anywhere to update inward details">
                  
                  <!-- 1. Inward No & Date -->
                  <td class="text-nowrap">
                    <a href="<?= $editUrl ?>" class="fw-bold font-monospace text-primary text-decoration-none nowrap-badge d-flex align-items-center gap-1" title="Open Inward Update Form">
                      <i class="bi bi-box-seam opacity-75"></i> <?= htmlspecialchars($in['inward_no']) ?>
                    </a>
                    <div class="text-muted small mt-1" style="font-size:0.75rem;">
                      <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($in['inward_date'] ?? 'now')) ?>
                    </div>
                  </td>

                  <!-- 2. Supplier / Source -->
                  <td>
                    <div class="fw-bold text-dark text-truncate" style="max-width: 190px;" title="<?= htmlspecialchars($in['supplier_name']) ?>">
                      <?= htmlspecialchars($in['supplier_name']) ?>
                    </div>
                    <?php if ($src === 'knitting'): ?>
                      <span class="badge rounded-pill mt-1" style="background:#EEF2FF; color:#4F46E5; border:1px solid #C7D2FE; font-size:0.68rem;">
                        <i class="bi bi-diagram-3 me-1"></i>Knitting Mill
                      </span>
                    <?php elseif ($src === 'customer_jobwork'): ?>
                      <span class="badge rounded-pill mt-1" style="background:#F0FDF4; color:#16A34A; border:1px solid #BBF7D0; font-size:0.68rem;">
                        <i class="bi bi-briefcase me-1"></i>Customer Jobwork
                      </span>
                    <?php elseif ($src === 'weaving'): ?>
                      <span class="badge rounded-pill mt-1" style="background:#F8FAFC; color:#475569; border:1px solid #CBD5E1; font-size:0.68rem;">
                        <i class="bi bi-grid-3x3 me-1"></i>Weaving Mill
                      </span>
                    <?php else: ?>
                      <span class="badge rounded-pill mt-1" style="background:#FFFBEB; color:#B45309; border:1px solid #FDE68A; font-size:0.68rem;">
                        <i class="bi bi-truck me-1"></i>External Supplier
                      </span>
                    <?php endif; ?>
                  </td>

                  <!-- 3. Challan & Vehicle -->
                  <td class="text-nowrap">
                    <div class="fw-semibold text-dark font-monospace">
                      <i class="bi bi-receipt me-1 text-secondary opacity-75"></i><?= htmlspecialchars($in['delivery_challan_no']) ?>
                    </div>
                    <div class="text-secondary small mt-1" style="font-size:0.75rem;">
                      <i class="bi bi-truck me-1"></i><?= htmlspecialchars($in['vehicle_no'] ?: 'Direct Handover') ?>
                    </div>
                  </td>

                  <!-- 4. Fabric Specification -->
                  <td>
                    <div class="fw-semibold text-dark text-truncate" style="max-width: 210px;" title="<?= htmlspecialchars($in['fabric_description']) ?>">
                      <?= htmlspecialchars($in['fabric_description']) ?>
                    </div>
                    <div class="d-flex align-items-center gap-1 mt-1 text-nowrap">
                      <span class="badge bg-light text-dark border fw-normal" style="font-size:0.7rem;"><?= (int)$in['gsm'] ?> GSM</span>
                      <span class="badge bg-light text-dark border fw-normal" style="font-size:0.7rem;"><?= htmlspecialchars($in['width_inches']) ?>"</span>
                      <span class="badge bg-light text-secondary border fw-normal" style="font-size:0.7rem;"><?= ucfirst($in['construction'] ?? 'Knitted') ?></span>
                    </div>
                  </td>

                  <!-- 5. Lot No (Guaranteed No-Wrap) -->
                  <td class="text-nowrap">
                    <span class="badge font-monospace nowrap-badge px-2 py-1" style="background:#F1F5F9; color:#0F172A; border:1px solid #CBD5E1; font-size:0.8rem; font-weight:700;">
                      <i class="bi bi-tag-fill me-1 text-secondary"></i><?= htmlspecialchars($in['grey_lot_no']) ?>
                    </span>
                  </td>

                  <!-- 6. Rolls Received -->
                  <td class="text-center text-nowrap">
                    <span class="fw-bold font-monospace fs-6 text-dark"><?= $in['received_rolls'] ?></span>
                    <span class="text-secondary small d-block" style="font-size:0.7rem;">rolls</span>
                  </td>

                  <!-- 7. Gross (Kg) -->
                  <td class="text-end text-nowrap font-monospace text-secondary fw-medium">
                    <?= number_format($in['gross_weight_kg'], 2) ?>
                  </td>

                  <!-- 8. Tare (Kg) -->
                  <td class="text-end text-nowrap font-monospace text-muted">
                    <?= number_format($in['tare_weight_kg'], 2) ?>
                  </td>

                  <!-- 9. Net (Kg) - Prominent Green Pill Badge -->
                  <td class="text-end text-nowrap">
                    <span class="badge font-monospace nowrap-badge px-2 py-1" style="background:#ECFDF5; color:#065F46; border:1px solid #A7F3D0; font-size:0.84rem; font-weight:700;">
                      <i class="bi bi-check2 me-1"></i><?= number_format($in['net_weight_kg'], 2) ?> <span class="fw-normal" style="font-size:0.75rem;">Kg</span>
                    </span>
                  </td>

                  <!-- 10. QC Status Pill -->
                  <td class="text-center text-nowrap">
                    <?php if ($qcStatus === 'passed'): ?>
                      <span class="badge rounded-pill nowrap-badge px-2 py-1 fw-semibold" style="background:#DCFCE7; color:#15803D; border:1px solid #BBF7D0; font-size:0.74rem;">
                        <i class="bi bi-check-circle-fill me-1"></i>PASSED
                      </span>
                    <?php elseif ($qcStatus === 'rejected'): ?>
                      <span class="badge rounded-pill nowrap-badge px-2 py-1 fw-semibold" style="background:#FEE2E2; color:#B91C1C; border:1px solid #FECACA; font-size:0.74rem;">
                        <i class="bi bi-x-circle-fill me-1"></i>REJECTED
                      </span>
                    <?php else: ?>
                      <span class="badge rounded-pill nowrap-badge px-2 py-1 fw-semibold" style="background:#FEF3C7; color:#B45309; border:1px solid #FDE68A; font-size:0.74rem;">
                        <i class="bi bi-hourglass-split me-1"></i>PENDING QC
                      </span>
                    <?php endif; ?>
                  </td>

                  <!-- 11. Actions -->
                  <td class="text-end text-nowrap">
                    <div class="d-inline-flex align-items-center gap-1">
                      <!-- Edit Inward -->
                      <a href="<?= $editUrl ?>" class="btn btn-sm btn-light border cz-action-btn text-primary" title="Update Inward Details">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                      
                      <!-- Official TCPDF Slip -->
                      <a href="<?= Url::to('/print/grey-inward?id=' . $in['id'] . '&format=pdf') ?>" target="_blank" class="btn btn-sm btn-light border cz-action-btn text-info" title="Download TCPDF Slip" onclick="event.stopPropagation();">
                        <i class="bi bi-file-earmark-pdf"></i>
                      </a>
                      
                      <!-- 4-Point Inspection -->
                      <a href="<?= Url::to('/dyeing/grey-inspection?inward_id=' . $in['id']) ?>" class="btn btn-sm btn-light border cz-action-btn text-warning-emphasis" title="Inspect Fabric (4-Point QC)" onclick="event.stopPropagation();">
                        <i class="bi bi-shield-check"></i>
                      </a>
                      
                      <!-- Roll Barcode Labels -->
                      <a href="<?= Url::to('/print/roll-label?lot=' . urlencode($in['grey_lot_no'])) ?>" target="_blank" class="btn btn-sm btn-light border cz-action-btn text-secondary" title="Print Roll Labels" onclick="event.stopPropagation();">
                        <i class="bi bi-upc-scan"></i>
                      </a>
                      
                      <!-- Delete Inward -->
                      <button type="button" class="btn btn-sm btn-light border cz-action-btn text-danger js-del-inward" data-id="<?= $in['id'] ?>" data-no="<?= htmlspecialchars($in['inward_no']) ?>" data-lot="<?= htmlspecialchars($in['grey_lot_no']) ?>" title="Delete Inward" onclick="event.stopPropagation();">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Server-Side Pagination Bar -->
      <div class="cz-panel-foot d-flex flex-wrap justify-content-between align-items-center p-3 border-top gap-2 bg-light bg-opacity-50">
        <div class="small text-secondary">
          Showing <span class="fw-bold text-dark"><?= min($totalRecords, $offset + 1) ?></span> to 
          <span class="fw-bold text-dark"><?= min($totalRecords, $offset + $limit) ?></span> of 
          <span class="fw-bold text-dark"><?= $totalRecords ?></span> entries
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
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteInwardModal" tabindex="-1" aria-labelledby="deleteInwardModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0 rounded-3">
          <div class="modal-header bg-danger text-white p-3">
            <h5 class="modal-title fs-6 fw-bold" id="deleteInwardModalLabel">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Grey Fabric Inward Record
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="<?= Url::to('/dyeing/grey-inward') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="delete_inward">
            <input type="hidden" name="inward_id" id="del_inward_id" value="">

            <div class="modal-body p-4 text-center">
              <p class="mb-2 text-dark">Are you sure you want to permanently delete inward record:</p>
              <div class="p-3 bg-light rounded-3 border mb-3">
                <div class="fw-bold fs-5 text-dark font-monospace" id="del_inward_no"></div>
                <div class="small text-secondary" id="del_lot_no"></div>
              </div>
              <p class="small text-danger mb-0">
                <i class="bi bi-info-circle me-1"></i> This action cannot be undone. Inward inventory will be removed.
              </p>
            </div>

            <div class="modal-footer bg-light p-3">
              <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger btn-sm px-4 shadow-sm">
                <i class="bi bi-trash me-1"></i> Confirm Delete
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Client-Side Live Search & Filtering + Modal Wiring Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // 1. Delete Modal Wiring
      const delModalEl = document.getElementById('deleteInwardModal');
      const delModal = delModalEl ? new bootstrap.Modal(delModalEl) : null;

      document.querySelectorAll('.js-del-inward').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          document.getElementById('del_inward_id').value = this.dataset.id;
          document.getElementById('del_inward_no').textContent = this.dataset.no;
          document.getElementById('del_lot_no').textContent = 'Lot No: ' + this.dataset.lot;
          if (delModal) delModal.show();
        });
      });

      // 2. Real-Time Instant Filtering
      const searchInput = document.getElementById('liveSearchInput');
      const sourceFilter = document.getElementById('sourceFilter');
      const qcFilter = document.getElementById('qcFilter');
      const resetBtn = document.getElementById('resetFiltersBtn');
      const rows = document.querySelectorAll('.js-inward-row');
      const visibleCountEl = document.getElementById('visibleCount');

      function filterTable() {
        const query = (searchInput.value || '').trim().toLowerCase();
        const src = (sourceFilter.value || '').trim().toLowerCase();
        const qc = (qcFilter.value || '').trim().toLowerCase();
        let visibleCount = 0;

        rows.forEach(function(row) {
          const rowInward = row.dataset.inward || '';
          const rowLot = row.dataset.lot || '';
          const rowSupplier = row.dataset.supplier || '';
          const rowFabric = row.dataset.fabric || '';
          const rowChallan = row.dataset.challan || '';
          const rowVehicle = row.dataset.vehicle || '';
          const rowSource = row.dataset.source || '';
          const rowQc = row.dataset.qc || '';

          const matchesQuery = !query || 
            rowInward.includes(query) || 
            rowLot.includes(query) || 
            rowSupplier.includes(query) || 
            rowFabric.includes(query) || 
            rowChallan.includes(query) || 
            rowVehicle.includes(query);

          const matchesSource = !src || rowSource === src;
          const matchesQc = !qc || rowQc === qc;

          if (matchesQuery && matchesSource && matchesQc) {
            row.style.display = '';
            visibleCount++;
          } else {
            row.style.display = 'none';
          }
        });

        if (visibleCountEl) {
          visibleCountEl.textContent = visibleCount;
        }
      }

      if (searchInput) searchInput.addEventListener('input', filterTable);
      if (sourceFilter) sourceFilter.addEventListener('change', filterTable);
      if (qcFilter) qcFilter.addEventListener('change', filterTable);

      if (resetBtn) {
        resetBtn.addEventListener('click', function() {
          if (searchInput) searchInput.value = '';
          if (sourceFilter) sourceFilter.value = '';
          if (qcFilter) qcFilter.value = '';
          filterTable();
        });
      }
    });
    </script>

  </div>
</main>
<?php
require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php';
