<?php
/**
 * Chemical Issue Request, Return & Actual Consumption Register
 * Dedicated list & register view for chemical stores.
 * File: modules/dyeing/views/chemical-requests/index.php
 */

use Core\Support\Url;
use Core\Database\Database;
use Core\Security\CSRF;
use Core\Security\SessionManager;

$pageTitle = 'Chemical Issue & Consumption';
$activeModule = 'chemical-requests';

$companyId = (int)(SessionManager::getUser()['company_id'] ?? 1);

$requests = Database::fetchAll("SELECT * FROM chemical_issue_requests WHERE company_id = ? ORDER BY id DESC", [$companyId]);
$selectedReqId = (int)($_GET['req_id'] ?? ($requests[0]['id'] ?? 0));
$items = ($selectedReqId > 0) ? Database::fetchAll("SELECT * FROM chemical_issue_items WHERE request_id = ? ORDER BY id ASC", [$selectedReqId]) : [];

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
            <li class="breadcrumb-item active">Chemical Issue & Usage</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0"><i class="bi bi-cart-check text-dark me-2"></i>Chemical Store Issue, Return & Consumption</h1>
        <p class="text-secondary small mb-0">Color kitchen dispensing, barcode/lot scanning, tare weight verification, unconsumed returns, and recipe variance.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= Url::to('/dyeing/chemical-requests-create') ?>" class="btn btn-dark btn-sm">
          <i class="bi bi-plus-circle me-1"></i> New Store Issue Request
        </a>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show small" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> Chemical issue request generated, weighed, and dispensed successfully from Store Bay 2.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Active Chemical Issue Requests Table (Click any row to select lines) -->
    <div class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-inbox-fill text-dark me-2"></i>Chemical Issue Requests (Color Kitchen Indents)</h2>
        <span class="small text-secondary"><?= count($requests) ?> Indents (Click row to view & reconcile consumption)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th>Request No</th>
              <th>Batch No</th>
              <th>Date & Time</th>
              <th>Requester</th>
              <th>Storehouse</th>
              <th class="text-center">Items</th>
              <th>Status</th>
              <th>Approved By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($requests)): ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-secondary">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i> No chemical issue requests found. Click "New Store Issue Request" to dispense chemicals.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($requests as $req): ?>
                <?php $editUrl = Url::to('/dyeing/chemical-requests/edit?id=' . $req['id']); ?>
                <?php $selectUrl = Url::to('/dyeing/chemical-requests?req_id=' . $req['id']); ?>
                <tr class="cz-clickable-row <?= ($req['id'] == $selectedReqId) ? 'table-active' : '' ?>" data-href="<?= $editUrl ?>" title="Click anywhere to update this chemical indent">
                  <td class="fw-bold font-monospace text-primary">
                    <a href="<?= $editUrl ?>" class="text-decoration-none"><?= htmlspecialchars($req['request_no']) ?></a>
                  </td>
                  <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($req['batch_no']) ?></span></td>
                  <td><?= date('d M Y', strtotime($req['request_date'])) ?> <?= $req['required_time'] ?></td>
                  <td><?= htmlspecialchars($req['requester_name']) ?></td>
                  <td>Dyes & Chemicals Store (Bay 2)</td>
                  <td class="text-center fw-bold font-monospace"><?= $req['total_chemicals_count'] ?></td>
                  <td>
                    <span class="badge bg-success-subtle text-success text-uppercase"><?= htmlspecialchars($req['status']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($req['approved_by'] ?? 'Auto-approved') ?></td>
                  <td class="text-end">
                    <a href="<?= $selectUrl ?>" class="btn btn-sm btn-outline-dark" onclick="event.stopPropagation();">
                      <i class="bi bi-eye me-1"></i> View Lines
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detailed Material Issue, Return & Consumption Lines -->
    <?php if (!empty($items)): ?>
      <div class="cz-panel">
        <div class="cz-panel-head d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-bold mb-0 text-dark">
            <i class="bi bi-check2-all text-dark me-2"></i>Issued Materials, Unused Returns & Actual Consumption Reconciliation
          </h2>
          <span class="small text-secondary">Formula: Net Used = Issued − Returned | Variance = Consumed − Planned</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th>Material / Chemical</th>
                <th>Type</th>
                <th>Lot Number</th>
                <th class="text-end">Required (Kg)</th>
                <th class="text-end">Issued (Kg)</th>
                <th class="text-end">Returned (Kg)</th>
                <th class="text-end">Actual Consumed (Kg)</th>
                <th class="text-center">Variance</th>
                <th class="text-end">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
                $totReq = 0; $totIss = 0; $totRet = 0; $totCons = 0;
                foreach ($items as $it): 
                  $variance = $it['actual_consumed_qty'] - $it['required_qty'];
                  $totReq += $it['required_qty'];
                  $totIss += $it['issued_qty'];
                  $totRet += $it['returned_qty'];
                  $totCons += $it['actual_consumed_qty'];
              ?>
                <tr>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($it['chemical_name']) ?></div>
                  </td>
                  <td><span class="badge bg-<?= $it['item_type'] === 'dye' ? 'primary' : 'secondary' ?>"><?= strtoupper($it['item_type']) ?></span></td>
                  <td><code class="code-pill"><?= htmlspecialchars($it['lot_number'] ?: 'LOT-DISP-01') ?></code></td>
                  <td class="text-end font-monospace"><?= number_format($it['required_qty'], 3) ?></td>
                  <td class="text-end font-monospace fw-bold text-primary"><?= number_format($it['issued_qty'], 3) ?></td>
                  <td class="text-end font-monospace text-muted"><?= number_format($it['returned_qty'], 3) ?></td>
                  <td class="text-end font-monospace fw-bold text-dark"><?= number_format($it['actual_consumed_qty'], 3) ?></td>
                  <td class="text-center font-monospace">
                    <?php if (abs($variance) < 0.001): ?>
                      <span class="badge bg-success">0.000 (Exact)</span>
                    <?php elseif ($variance > 0): ?>
                      <span class="badge bg-danger">+<?= number_format($variance, 3) ?></span>
                    <?php else: ?>
                      <span class="badge bg-info"><?= number_format($variance, 3) ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Reconciled</span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th colspan="3" class="text-end fw-bold">Totals (Kg):</th>
                <th class="text-end fw-bold font-monospace"><?= number_format($totReq, 3) ?></th>
                <th class="text-end fw-bold font-monospace text-primary"><?= number_format($totIss, 3) ?></th>
                <th class="text-end fw-bold font-monospace text-warning"><?= number_format($totRet, 3) ?></th>
                <th class="text-end fw-bold font-monospace text-dark fs-6"><?= number_format($totCons, 3) ?></th>
                <th colspan="2"></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>
<?php require_once (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/includes/footer.php'; ?>
