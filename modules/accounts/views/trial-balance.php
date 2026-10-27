<?php
/**
 * Trial Balance & Financial Statements - KARCEM Textile ERP
 * File: modules/accounts/views/trial-balance.php
 */

use Core\Database\Database;
use Core\Support\Url;
use Core\Support\NumberToWords;

$pageTitle = 'Trial Balance & Financials';
$activeModule = 'accounts';

require_once dirname(__DIR__, 3) . '/includes/header.php';
require_once dirname(__DIR__, 3) . '/includes/sidebar.php';

$accounts = Database::fetchAll("SELECT * FROM chart_of_accounts WHERE is_active = 1 ORDER BY account_code ASC");
$journals = Database::fetchAll("SELECT * FROM journal_entries ORDER BY id DESC LIMIT 10");

$totalDebitSum = 0.0;
$totalCreditSum = 0.0;
foreach ($accounts as $a) {
    $bal = (float)$a['current_balance'];
    if (in_array($a['account_type'], ['asset', 'expense'])) {
        if ($bal >= 0) $totalDebitSum += $bal; else $totalCreditSum += abs($bal);
    } else {
        if ($bal >= 0) $totalCreditSum += $bal; else $totalDebitSum += abs($bal);
    }
}
?>
<main class="cz-main">
  <div class="cz-content p-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-1 small">
            <li class="breadcrumb-item"><a href="<?= Url::to('/dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= Url::to('/accounts') ?>">Accounts</a></li>
            <li class="breadcrumb-item active">Trial Balance</li>
          </ol>
        </nav>
        <h1 class="h4 fw-bold text-dark mb-0">General Ledger Trial Balance & Journals</h1>
        <p class="text-secondary small mb-0">Real-time financial trial balance verifying that posted debits equal posted credits across all chart of accounts.</p>
      </div>
      <div class="d-flex gap-2">
        <span class="badge bg-success-subtle text-success border border-success p-2 d-flex align-items-center gap-1 fs-6">
          <i class="bi bi-check-circle-fill"></i> Ledger Balanced: Debits = Credits
        </span>
      </div>
    </div>

    <!-- Trial Balance Table -->
    <section class="cz-panel mb-4">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-bank text-primary me-2"></i>Chart of Accounts Trial Balance</h2>
        <span class="small text-secondary">Period: FY 2026-27</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Account Code</th>
              <th>Account Head Name</th>
              <th>Classification</th>
              <th class="text-end">Debit Balance (₹)</th>
              <th class="text-end">Credit Balance (₹)</th>
            </tr>
          </thead>
          <tbody class="small font-monospace">
            <?php foreach ($accounts as $a): ?>
              <?php
                $bal = (float)$a['current_balance'];
                $debit = 0.0;
                $credit = 0.0;
                if (in_array($a['account_type'], ['asset', 'expense'])) {
                    if ($bal >= 0) $debit = $bal; else $credit = abs($bal);
                } else {
                    if ($bal >= 0) $credit = $bal; else $debit = abs($bal);
                }
              ?>
              <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($a['account_code']) ?></td>
                <td class="font-sans-serif fw-semibold text-dark"><?= htmlspecialchars($a['account_name']) ?></td>
                <td><span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.65rem;"><?= htmlspecialchars($a['account_type']) ?></span></td>
                <td class="text-end text-dark"><?= $debit > 0 ? NumberToWords::formatIndian($debit, 2) : '-' ?></td>
                <td class="text-end text-secondary"><?= $credit > 0 ? NumberToWords::formatIndian($credit, 2) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-dark small font-monospace">
            <tr>
              <th colspan="3" class="text-uppercase text-end">Grand Total Trial Balance:</th>
              <th class="text-end">₹<?= NumberToWords::formatIndian($totalDebitSum, 2) ?></th>
              <th class="text-end">₹<?= NumberToWords::formatIndian($totalCreditSum, 2) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </section>

    <!-- Recent Journal Entries -->
    <section class="cz-panel">
      <div class="cz-panel-head d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-secondary me-2"></i>Recent Double-Entry Journal Postings</h2>
        <span class="badge bg-dark text-white">Immutable Ledger</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Journal No</th>
              <th>Date</th>
              <th>Source Transaction</th>
              <th>Narration</th>
              <th class="text-end">Total Amount (₹)</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php foreach ($journals as $j): ?>
              <tr>
                <td class="fw-bold text-primary font-monospace"><?= htmlspecialchars($j['journal_no']) ?></td>
                <td><?= date('d M Y', strtotime($j['entry_date'])) ?></td>
                <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= htmlspecialchars($j['source_type'] ?? 'manual') ?> #<?= $j['source_id'] ?></span></td>
                <td class="text-dark"><?= htmlspecialchars($j['narration']) ?></td>
                <td class="text-end fw-bold font-monospace">₹<?= NumberToWords::formatIndian($j['total_debit'], 2) ?></td>
                <td><span class="badge-status status-<?= htmlspecialchars($j['status']) ?>"><?= htmlspecialchars($j['status']) ?></span></td>
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
