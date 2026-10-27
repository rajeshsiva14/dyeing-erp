<?php
/**
 * KARCEM Textile ERP - Shared Footer & Global Search Modal
 * File: includes/footer.php
 */

use Core\Support\Url;
$baseUrl = Url::base();
?>
      <!-- KARCEM Standard Footer -->
      <footer class="cz-footer d-flex justify-content-between align-items-center py-2 px-3 border-top bg-white">
        <span class="small text-secondary">&copy; <?= date('Y') ?> KARCEM Textiles & Processors Pvt Ltd · Tirupur, Tamil Nadu</span>
        <span class="small text-secondary"><i class="bi bi-shield-check text-dark"></i> ISO 9001:2015 · TNPCB ZLD Compliant · GST 33AAACK1234F1Z5</span>
      </footer>

    </div><!-- /.cz-content -->
  </main><!-- /.cz-main -->
</div><!-- /.cz-app -->

<!-- Global Smart Search Modal (Cmd+K / Ctrl+K) -->
<div class="modal fade" id="globalSearchModal" tabindex="-1" aria-labelledby="globalSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border" style="border-radius: 12px; overflow: hidden; border-color: var(--cz-border);">
      <div class="modal-header border-bottom p-3" style="background-color: var(--cz-bg-card); border-color: var(--cz-divider);">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-0 fs-5"><i data-lucide="search" class="text-primary"><i class="bi bi-search"></i></i></span>
          <input type="text" class="form-control border-0 fs-5 shadow-none bg-transparent" id="czGlobalSearchInput" placeholder="Quick find dyeing forms, batches, recipes, QC... (e.g. 'recipe', 'QC', 'stenter', '40')" autofocus>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3" style="max-height: 480px; overflow-y: auto; background-color: var(--cz-bg-main);">
        <div class="row g-2" id="czSearchResults">
          <!-- 30 Dyeing ERP Forms Quick Access -->
          <div class="col-12"><span class="text-uppercase text-secondary small fw-bold" style="font-size:0.7rem; letter-spacing:0.05em;">Dyeing Operations Forms</span></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/dashboard" class="card card-body p-2 text-decoration-none text-dark hover-bg-light"><i class="bi bi-speedometer2 text-primary me-2"></i>1. Dyeing Dashboard</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/grey-inward" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-box-arrow-in-down text-secondary me-2"></i>2. Grey Fabric Inward</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/grey-inspection" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-search text-secondary me-2"></i>3. Grey Inspection & QC</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/job-cards" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-card-checklist text-secondary me-2"></i>4. Dyeing Job Cards</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/batch-planning" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-calendar-range text-secondary me-2"></i>5. Batch Planning & Split</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/machine-allocation" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-cpu text-secondary me-2"></i>6. Machine Allocation</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/lab-dips" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-eyedropper text-secondary me-2"></i>7-9. Lab Dip & Approvals</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/recipes" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-journal-bookmark text-secondary me-2"></i>10. Dye Recipe Master</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/recipe-calculation" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-calculator text-secondary me-2"></i>11. Recipe Calc (% OWF)</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/chemical-requests" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-cart-plus text-secondary me-2"></i>12-13. Chemical Issue & Usage</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/process-sheets" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-file-earmark-text text-secondary me-2"></i>14,17. Process Sheets & Stages</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/loading-sheets" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-box-arrow-in-right text-secondary me-2"></i>15. Machine Loading Sheet</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/production-entry" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-broadcast text-secondary me-2"></i>16. Live Production Entry</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/shade-checks" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-palette text-secondary me-2"></i>18. Shade Check (ΔE)</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/batch-qc" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-shield-check text-secondary me-2"></i>19. Dyeing Batch QC</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/reprocess-requests" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-arrow-repeat text-secondary me-2"></i>20. Re-dye / Reprocess</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/finishing" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-water text-secondary me-2"></i>21-23. Hydro, Dryer, Stenter</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/inspection" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-clipboard-check text-secondary me-2"></i>24. 4-Point Inspection (ASTM)</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/roll-packing" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-upc-scan text-secondary me-2"></i>25. Roll Formation & Labels</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/wastage-loss" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-pie-chart text-secondary me-2"></i>26. Dyeing Wastage & Loss</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/batch-costing" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-currency-rupee text-secondary me-2"></i>27. Batch Costing & Margins</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/job-work" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-arrow-left-right text-secondary me-2"></i>28. Job-Work Challans</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/stock-transfer" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-truck text-secondary me-2"></i>29. Dyeing Stock Transfer</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/reports" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-bar-chart-line text-secondary me-2"></i>30. Dyeing Reports & Logs</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/masters" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-gear-wide-connected text-secondary me-2"></i>Dyeing Masters Setup</a></div>
          <div class="col-md-4"><a href="<?= $baseUrl ?>/dyeing/print" class="card card-body p-2 text-decoration-none text-dark"><i class="bi bi-printer text-secondary me-2"></i>Print Form Engine (P40, L01)</a></div>
        </div>
      </div>
      <div class="modal-footer border-top p-2 d-flex justify-content-between small text-secondary" style="background-color: var(--cz-bg-card); border-color: var(--cz-divider);">
        <div>Navigate with <kbd class="cz-search-trigger__kbd">↑</kbd> <kbd class="cz-search-trigger__kbd">↓</kbd> · Select with <kbd class="cz-search-trigger__kbd">Enter</kbd></div>
        <div>Press <kbd class="cz-search-trigger__kbd">Esc</kbd> to close</div>
      </div>
    </div>
  </div>
</div>

<!-- Standard Global Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
  <div id="czLiveToast" class="toast border shadow-sm" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 8px; border-color: var(--cz-border);">
    <div class="toast-header bg-white border-bottom text-dark" style="border-color: var(--cz-divider);">
      <strong class="me-auto" id="toastTitle"><i class="bi bi-bell-fill text-primary me-1"></i> Factory Alert</strong>
      <small class="text-secondary">Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body bg-white text-dark" id="toastBody">Action completed successfully.</div>
  </div>
</div>

<!-- Vendor Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?= $baseUrl ?>/assets/js/cloudzoo.js"></script>

<script>
  // Initialize Lucide Outline Icons
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }

  // Modern Sidebar Controller (Toggle & Persistence)
  (function() {
    const toggleBtn = document.getElementById('czSidebarToggleBtn');
    const collapseBtn = document.getElementById('czSidebarCollapseBtn');
    const sidebar = document.getElementById('czMainSidebar');

    function toggleSidebar() {
      if (window.innerWidth < 992) {
        if (sidebar) sidebar.classList.toggle('mobile-open');
      } else {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('czDyeingSidebarCollapsed', isCollapsed ? '1' : '0');
        if (collapseBtn) {
          collapseBtn.innerHTML = isCollapsed 
            ? '<i class="bi bi-chevron-bar-right fs-6"></i>' 
            : '<i class="bi bi-chevron-bar-left fs-6"></i>';
        }
      }
      if (window.lucide) window.lucide.createIcons();
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (collapseBtn) collapseBtn.addEventListener('click', toggleSidebar);

    // Keyboard shortcut: Ctrl+B / Cmd+B toggles sidebar
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        toggleSidebar();
      }
    });

    // Restore state from localStorage (default is EXPANDED!)
    if (window.innerWidth >= 992 && localStorage.getItem('czDyeingSidebarCollapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
      if (collapseBtn) {
        collapseBtn.innerHTML = '<i class="bi bi-chevron-bar-right fs-6"></i>';
      }
    }

    // Initialize Bootstrap Tooltips for modern items
    if (window.bootstrap) {
      const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltips.forEach(function(el) {
        new bootstrap.Tooltip(el, {
          boundary: document.body,
          fallbackPlacements: ['left', 'bottom']
        });
      });
    }
  })();

  // Global Shortcut: Cmd+K / Ctrl+K opens Smart Search
  document.addEventListener('keydown', function(e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      const modalEl = document.getElementById('globalSearchModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
      setTimeout(function() {
        if (window.lucide) window.lucide.createIcons();
      }, 50);
    }
  });

  // Client-side search filter
  document.getElementById('czGlobalSearchInput')?.addEventListener('input', function(e) {
    const q = e.target.value.toLowerCase().trim();
    const cards = document.querySelectorAll('#czSearchResults .col-md-4');
    cards.forEach(card => {
      const text = card.textContent.toLowerCase();
      card.style.display = (q === '' || text.includes(q)) ? '' : 'none';
    });
  });

  // Helper function to trigger notifications
  window.showToast = function(title, message, isSuccess = true) {
    const toastEl = document.getElementById('czLiveToast');
    if (!toastEl) return;
    document.getElementById('toastTitle').innerHTML = (isSuccess ? '<i class="bi bi-check-circle-fill text-success me-1"></i> ' : '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> ') + title;
    document.getElementById('toastBody').textContent = message;
    const t = bootstrap.Toast.getOrCreateInstance(toastEl);
    t.show();
  };

  // Global Excel/CSV Export Utility for Registers
  window.exportTableToCSV = function(tableId, filename = 'register_export.csv') {
    const table = document.getElementById(tableId);
    if (!table) {
      alert('Table not found for export');
      return;
    }

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
      let row = [];
      const cols = rows[i].querySelectorAll('th, td');
      for (let j = 0; j < cols.length; j++) {
        // Skip Actions column
        const headerText = cols[j].innerText.trim().toLowerCase();
        if (headerText === 'actions' || headerText === 'action' || headerText === 'print actions') {
          continue;
        }
        if (j === cols.length - 1 && cols[j].querySelector('.btn, a.btn, button')) {
          continue;
        }
        let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
        data = data.replace(/"/g, '""');
        row.push('"' + data + '"');
      }
      if (row.length > 0) {
        csv.push(row.join(','));
      }
    }

    const blob = new Blob(['\uFEFF' + csv.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Global Table Row Click-to-Update Navigation
  document.addEventListener('click', function(e) {
    const row = e.target.closest('.cz-clickable-row');
    if (!row || !row.dataset.href) return;
    // Ignore clicks on buttons, links, inputs, selects, or elements marked with event.stopPropagation
    if (e.target.closest('a, button, input, select, textarea, label, .btn, .btn-group, .dropdown-menu, .modal')) {
      return;
    }
    window.location.href = row.dataset.href;
  });
</script>
</body>
</html>
