
// CloudZoo standard: a list's "select all" checkbox is ticked only when every row is selected, never half-selected (no dash).
// List scripts set `.indeterminate` for a partial selection; ignore it for every checkbox.
(function () {
  const desc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'indeterminate');
  if (!desc || !desc.set) return;
  Object.defineProperty(HTMLInputElement.prototype, 'indeterminate', {
    configurable: true,
    get: desc.get,
    set() { desc.set.call(this, false); }
  });
})();

// CloudZoo ERP - Secure Same-Origin API Helper (P1-02)
window.czApiFetch = async function (path, options = {}) {
  const url = new URL(path, window.location.origin);
  if (url.origin !== window.location.origin) {
    throw new Error('External URLs are not allowed in czApiFetch');
  }
  options = Object.assign({}, options);
  options.headers = Object.assign({}, options.headers || {});

  // Attach session CSRF token on state-changing requests
  if (window.CZ_CSRF_TOKEN && !options.headers['X-CSRF-Token']) {
    options.headers['X-CSRF-Token'] = window.CZ_CSRF_TOKEN;
  }

  return fetch(url.toString(), options);
};

// Attach the CSRF token to state-changing fetch() calls. Same-origin only: the token is never
// sent to another site, and no bearer token is ever attached by the browser.
(() => {
  const nativeFetch = window.fetch.bind(window);
  const SAFE = ['GET', 'HEAD', 'OPTIONS'];

  window.fetch = (input, init = {}) => {
    try {
      const isRequest = typeof input !== 'string' && !(input instanceof URL);
      const url = new URL(isRequest ? input.url : String(input), window.location.href);
      const method = String(init.method || (isRequest ? input.method : 'GET')).toUpperCase();

      if (window.CZ_CSRF_TOKEN && url.origin === window.location.origin && !SAFE.includes(method)) {
        const headers = new Headers(init.headers || (isRequest ? input.headers : undefined));
        if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', window.CZ_CSRF_TOKEN);
        init = Object.assign({}, init, { headers });
      }
    } catch (e) {
      // fall through to the untouched request
    }
    return nativeFetch(input, init);
  };
})();

// Same for jQuery $.ajax(): same-origin, state-changing requests carry the CSRF token header.
if (window.jQuery) {
  window.jQuery.ajaxPrefilter((options, originalOptions, jqXHR) => {
    const method = String(options.type || options.method || 'GET').toUpperCase();
    if (!window.CZ_CSRF_TOKEN || ['GET', 'HEAD', 'OPTIONS'].includes(method) || options.crossDomain) return;
    jqXHR.setRequestHeader('X-CSRF-Token', window.CZ_CSRF_TOKEN);
  });
}

(() => {
  const sidebar = document.querySelector('.cz-sidebar');
  const main = document.querySelector('.cz-main');
  const toggle = document.querySelector('[data-cz-sidebar-toggle]');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      if (window.innerWidth < 992) {
        sidebar.classList.toggle('mobile-open');
      } else {
        sidebar.classList.toggle('expanded');
        if (main) main.classList.toggle('expanded');
        localStorage.setItem('czSidebarExpanded', sidebar.classList.contains('expanded') ? '1' : '0');
      }
    });
  }

  if (window.innerWidth >= 992 && localStorage.getItem('czSidebarExpanded') === '1') {
    sidebar?.classList.add('expanded');
    main?.classList.add('expanded');
  }

  document.addEventListener('click', (e) => {
    if (window.innerWidth >= 992 || !sidebar?.classList.contains('mobile-open')) return;
    if (!sidebar.contains(e.target) && !toggle?.contains(e.target)) {
      sidebar.classList.remove('mobile-open');
    }
  });

  // Sidebar tooltips: only while the sidebar is collapsed (labels hidden)
  if (sidebar && window.bootstrap) {
    sidebar.querySelectorAll('.cz-nav-item[title]').forEach(item => {
      const tip = new bootstrap.Tooltip(item, {
        placement: 'right',
        container: 'body',
        trigger: 'hover focus',
        customClass: 'cz-tooltip',
        delay: { show: 150, hide: 0 }
      });
      item.addEventListener('show.bs.tooltip', (e) => {
        if (sidebar.classList.contains('expanded')) e.preventDefault();
      });
      item.addEventListener('click', () => tip.hide());
    });
  }

  const selectAll = document.querySelector('[data-cz-select-all]');
  const rowChecks = [...document.querySelectorAll('[data-cz-row-check]')];
  if (selectAll && rowChecks.length) {
    selectAll.addEventListener('change', () => {
      rowChecks.forEach(c => c.checked = selectAll.checked);
      updateSelected();
    });
    rowChecks.forEach(c => c.addEventListener('change', updateSelected));
  }

  function updateSelected() {
    const selected = rowChecks.filter(c => c.checked).length;
    document.querySelectorAll('[data-cz-selected-count]').forEach(el => el.textContent = selected);
    const bulk = document.querySelector('[data-cz-bulk-actions]');
    if (bulk) {
      bulk.classList.toggle('d-none', selected === 0);
      bulk.classList.toggle('d-flex', selected > 0);
    }
    if (selectAll) {
      selectAll.indeterminate = selected > 0 && selected < rowChecks.length;
      selectAll.checked = selected === rowChecks.length && rowChecks.length > 0;
    }
  }

  // Server-rendered lists (data-cz-local-list): column sort and the delete confirmation for the bulk form
  const localList = document.querySelector('[data-cz-local-list]');
  if (localList) {
    const tbody = localList.querySelector('tbody');
    let sortCol = null, sortDir = 1;
    localList.querySelectorAll('[data-cz-sort-col]').forEach(btn => btn.addEventListener('click', () => {
      const col = parseInt(btn.dataset.czSortCol, 10);
      sortDir = sortCol === col ? -sortDir : 1;
      sortCol = col;
      const numeric = btn.dataset.czSortType === 'number';
      const value = (tr) => tr.children[col - 1]?.dataset.sortValue ?? '';
      [...tbody.querySelectorAll('tr[data-cz-search-row]')]
        .sort((a, b) => sortDir * (numeric ? (parseFloat(value(a)) || 0) - (parseFloat(value(b)) || 0) : value(a).localeCompare(value(b))))
        .forEach(tr => tbody.appendChild(tr));
    }));

    const bulkForm = localList.querySelector('form[data-cz-bulk-actions]');
    const modalEl = document.getElementById('czConfirmModal');
    const confirmDelete = (text, onOk) => {
      if (!modalEl || !window.bootstrap) {
        if (confirm(text)) onOk();
        return;
      }
      modalEl.querySelector('[data-cz-confirm-text]').textContent = text;
      const ok = modalEl.querySelector('[data-cz-confirm-ok]');
      ok.onclick = () => { ok.disabled = true; onOk(); };
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };
    localList.querySelector('[data-cz-delete-selected]')?.addEventListener('click', () => {
      const count = rowChecks.filter(c => c.checked).length;
      if (count) confirmDelete(`Delete ${count} selected record(s)? This cannot be undone.`, () => bulkForm.submit());
    });
    localList.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-cz-delete-one]');
      if (!btn || !bulkForm) return;
      confirmDelete(`Delete "${btn.dataset.name}"? This cannot be undone.`, () => {
        // Submit only this record, whatever else is ticked
        rowChecks.forEach(c => c.checked = c.value === btn.dataset.czDeleteOne);
        bulkForm.submit();
      });
    });
  }

  const tableSearch = document.querySelector('[data-cz-table-search]');
  if (tableSearch) {
    const rows = [...document.querySelectorAll('[data-cz-search-row]')];
    tableSearch.addEventListener('input', () => {
      const q = tableSearch.value.trim().toLowerCase();
      rows.forEach(row => {
        row.style.display = !q || row.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
      const visible = rows.filter(row => row.style.display !== 'none').length;
      const noResult = document.querySelector('[data-cz-no-results]');
      if (noResult) noResult.classList.toggle('d-none', visible !== 0);
    });
  }

  const sameBilling = document.querySelector('[data-cz-same-billing]');
  if (sameBilling) {
    const billingFields = ['street', 'country', 'state', 'city', 'zip'];
    sameBilling.addEventListener('change', () => {
      billingFields.forEach(name => {
        const from = document.querySelector(`[data-cz-billing="${name}"]`);
        const to = document.querySelector(`[data-cz-shipping="${name}"]`);
        if (!to) return;
        if (sameBilling.checked && from) to.value = from.value;
        to.disabled = sameBilling.checked;
      });
    });

    billingFields.forEach(name => {
      document.querySelector(`[data-cz-billing="${name}"]`)?.addEventListener('input', (e) => {
        if (!sameBilling.checked) return;
        const to = document.querySelector(`[data-cz-shipping="${name}"]`);
        if (to) to.value = e.target.value;
      });
      document.querySelector(`[data-cz-billing="${name}"]`)?.addEventListener('change', (e) => {
        if (!sameBilling.checked) return;
        const to = document.querySelector(`[data-cz-shipping="${name}"]`);
        if (to) to.value = e.target.value;
      });
    });

    sameBilling.dispatchEvent(new Event('change'));
  }

  document.querySelectorAll('[data-cz-demo-toast]').forEach(btn => {
    btn.addEventListener('click', () => {
      const toastEl = document.getElementById('czToast');
      if (!toastEl || !window.bootstrap) return;
      bootstrap.Toast.getOrCreateInstance(toastEl).show();
    });
  });

  const form = document.querySelector('[data-cz-form]');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      form.classList.add('was-validated');
      if (!form.checkValidity()) return;
      const toastEl = document.getElementById('czToast');
      if (toastEl && window.bootstrap) bootstrap.Toast.getOrCreateInstance(toastEl).show();
    });
  }

  document.querySelectorAll('[data-cz-clear-form]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.querySelector(btn.dataset.czClearForm || '[data-cz-form]');
      if (!target) return;
      target.reset();
      target.classList.remove('was-validated');
      sameBilling?.dispatchEvent(new Event('change'));
    });
  });

  // Global Search Drawer (Offcanvas) Controller & Live Filter
  const drawerEl = document.getElementById("globalSearchDrawer");
  if (drawerEl) {
    const searchInput = document.getElementById("czDrawerSearchInput");
    const clearBtn = document.getElementById("czDrawerClearBtn");
    const drawerItems = document.querySelectorAll(".cz-drawer-item");
    const drawerCategories = document.querySelectorAll(".cz-drawer-section-title");
    const emptyState = document.getElementById("czDrawerEmptyState");

    document.addEventListener("keydown", (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
        e.preventDefault();
        if (window.bootstrap) {
          const offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
          offcanvasInstance.toggle();
        }
      }
    });

    drawerEl.addEventListener("shown.bs.offcanvas", () => {
      if (searchInput) {
        searchInput.value = "";
        searchInput.focus();
        if (clearBtn) clearBtn.style.display = "none";
        filterDrawerItems("");
      }
    });

    if (clearBtn && searchInput) {
      clearBtn.addEventListener("click", () => {
        searchInput.value = "";
        clearBtn.style.display = "none";
        searchInput.focus();
        filterDrawerItems("");
      });
    }

    if (searchInput) {
      searchInput.addEventListener("input", function () {
        const val = this.value.trim().toLowerCase();
        if (clearBtn) clearBtn.style.display = val.length > 0 ? "block" : "none";
        filterDrawerItems(val);
      });
    }

    function filterDrawerItems(term) {
      let visibleCount = 0;

      drawerItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        const keywords = (item.dataset.keywords || "").toLowerCase();
        const matches = term === "" || text.includes(term) || keywords.includes(term);

        item.style.display = matches ? "flex" : "none";
        if (matches) visibleCount++;
      });

      drawerCategories.forEach(cat => {
        if (term === "") {
          cat.style.display = "flex";
        } else {
          let hasVisible = false;
          let next = cat.nextElementSibling;
          while (next && !next.classList.contains("cz-drawer-section-title") && next.id !== "czDrawerEmptyState") {
            if (next.style.display !== "none") {
              hasVisible = true;
              break;
            }
            next = next.nextElementSibling;
          }
          cat.style.display = hasVisible ? "flex" : "none";
        }
      });

      if (emptyState) {
        emptyState.classList.toggle("d-none", visibleCount > 0);
      }
    }
  }
})();

// ============================================================================
// CloudZoo Global Utility Helpers
// ============================================================================
window.CloudZoo = window.CloudZoo || {};
// Compact date-range filter with the API's separate from/to fields.
window.CloudZoo.dateRangeFilter = function (root) {
  if (!root) return null;
  const from = root.querySelector('[name="from"]');
  const to = root.querySelector('[name="to"]');
  const label = root.querySelector('[data-cz-range-label]');
  const trigger = root.querySelector('[data-bs-toggle="dropdown"]');
  const display = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value) ? value.split('-').reverse().join('-') : '';
  function sync() {
    label.textContent = from.value && to.value ? `${display(from.value)} to ${display(to.value)}` : 'Select date range';
    to.min = from.value;
  }
  [from, to].forEach((input) => input.addEventListener('change', () => {
    const preset = root.querySelector('[data-cz-report-year]');
    if (preset) { preset.value = 'custom'; window.CloudZoo.masters?.refresh?.(preset); }
    input.classList.remove('is-invalid');
    sync();
  }));
  root.querySelector('[data-cz-range-apply]').addEventListener('click', () => {
    const validFrom = !!from.value && from.checkValidity();
    const validTo = !!to.value && to.checkValidity() && to.value >= from.value;
    from.classList.toggle('is-invalid', !validFrom);
    to.classList.toggle('is-invalid', !validTo);
    if (!validFrom || !validTo) { (!validFrom ? from : to).focus(); return; }
    sync();
    bootstrap.Dropdown.getOrCreateInstance(trigger).hide();
    root.dispatchEvent(new CustomEvent('cz:date-range-applied', {bubbles: true}));
  });
  sync();
  return {sync};
};

// Bar and segment widths come from data (no inline style in the markup): <span data-cz-width="42.5"> becomes 42.5% wide.
// Call it again on markup added later: CloudZoo.applyWidths(container).
window.CloudZoo.applyWidths = function (root) {
  (root || document).querySelectorAll('[data-cz-width]').forEach((el) => {
    const w = parseFloat(el.getAttribute('data-cz-width'));
    if (Number.isFinite(w)) el.style.width = Math.max(0, Math.min(100, w)) + '%';
  });
};
document.addEventListener('DOMContentLoaded', () => window.CloudZoo.applyWidths());

/**
 * Counts a number up (or down) to a value: CloudZoo.animateNumber(el, 1250) shows 0, ... 1,250 over about 0.6 s. The shown value is kept on the
 * element, so the next call continues from it. opts: format (value => text), duration (ms), bump (flash the element when it grows).
 */
window.CloudZoo.animateNumber = function (el, to, opts = {}) {
  if (!el) return;
  const format = opts.format || ((v) => Math.round(v).toLocaleString('en-IN'));
  const from = Number(el._czNum || 0);
  el._czNum = to;
  cancelAnimationFrame(el._czRaf || 0);
  if (from === to || window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) { el.textContent = format(to); return; }
  if (opts.bump && to > from) { el.classList.remove('is-bumped'); void el.offsetWidth; el.classList.add('is-bumped'); }
  const duration = opts.duration || 600;
  const start = performance.now();
  const step = (now) => {
    const t = Math.min(1, (now - start) / duration);
    el.textContent = format(from + (to - from) * (1 - Math.pow(1 - t, 3)));
    if (t < 1) el._czRaf = requestAnimationFrame(step);
  };
  el._czRaf = requestAnimationFrame(step);
};

/**
 * The window of a long run (the Sync button of Attendance and of Unsynced Punches): a progress ring, three steps, live speed / time left and
 * the result of the run. It builds its own modal once and is safe to close at any moment (Bootstrap ignores a hide() sent while the window
 * is still opening, so close() waits for it to be shown).
 *
 *   const win = CloudZoo.syncWindow({ title: 'Sync punches', subtitle: 'Biometric punches to attendance', closeLabel: 'Done', onClose: () => {} });
 *   win.open('Checking the punches waiting to sync...');   // step 1: looking at the queue (a turning arc)
 *   win.start(total, 'Pairing IN / OUT punches...');       // step 2: the ring fills as win.update() is called
 *   win.update({ processed, inserted, updated, skipped, errors }, 'Batch 2');
 *   win.done('All punches are paired and saved.');         // or win.empty('Everything is already synced.') / win.fail('why it stopped')
 */
window.CloudZoo.syncWindow = function (options = {}) {
  const C = 326.73;   // the ring's circumference (r = 52)
  const ID = 'czSyncWin';
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
  let el = document.getElementById(ID);
  if (el) el.remove();   // a page builds its window once; a stale one from an earlier call is replaced
  el = document.createElement('div');
  el.id = ID;
  el.className = 'modal fade cz-syncwin';
  el.tabIndex = -1;
  el.setAttribute('aria-hidden', 'true');
  el.setAttribute('aria-labelledby', ID + 'Title');
  el.dataset.bsBackdrop = 'static';
  el.dataset.bsKeyboard = 'false';
  el.innerHTML = `
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="cz-syncwin__head">
          <div><h5 class="cz-syncwin__title" id="${ID}Title">${esc(options.title || 'Sync')}</h5><p class="cz-syncwin__subtitle">${esc(options.subtitle || '')}</p></div>
          <span class="cz-badge neutral" data-w="pill">Checking</span>
        </div>
        <div class="cz-syncwin__body">
          <div class="cz-syncwin__ring">
            <span class="cz-syncwin__pulse"></span>
            <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="cz-syncwin__track" cx="60" cy="60" r="52"/><circle class="cz-syncwin__arc" cx="60" cy="60" r="52" data-w="arc"/></svg>
            <div class="cz-syncwin__center"><span class="cz-syncwin__pct" data-w="pct" role="status" aria-live="polite">0%</span><span class="cz-syncwin__count" data-w="count">&nbsp;</span></div>
            <svg class="cz-syncwin__tick" viewBox="0 0 40 40" aria-hidden="true"><path d="M9 21l8 8 14-17"/></svg>
          </div>
          <p class="cz-syncwin__message" data-w="message"></p>
          <ol class="cz-syncwin__steps">
            <li class="cz-syncwin__step" data-w="s1"><span class="cz-syncwin__dot">1</span>Check punches</li>
            <li class="cz-syncwin__step" data-w="s2"><span class="cz-syncwin__dot">2</span>Pair &amp; save</li>
            <li class="cz-syncwin__step" data-w="s3"><span class="cz-syncwin__dot">3</span>Finish</li>
          </ol>
          <div class="cz-syncwin__live">
            <div><span class="cz-syncwin__label">Elapsed</span><span class="cz-syncwin__value" data-w="elapsed">0s</span></div>
            <div><span class="cz-syncwin__label">Speed</span><span class="cz-syncwin__value" data-w="speed">–</span></div>
            <div><span class="cz-syncwin__label">Time left</span><span class="cz-syncwin__value" data-w="left">–</span></div>
          </div>
          <div class="cz-syncwin__results">
            <div class="cz-syncwin__tile cz-syncwin__tile--ok"><b data-w="inserted">0</b><span>Inserted</span></div>
            <div class="cz-syncwin__tile"><b data-w="updated">0</b><span>Updated</span></div>
            <div class="cz-syncwin__tile cz-syncwin__tile--warn"><b data-w="skipped">0</b><span>Skipped</span></div>
            <div class="cz-syncwin__tile cz-syncwin__tile--bad"><b data-w="errors">0</b><span>Errors</span></div>
          </div>
        </div>
        <div class="cz-syncwin__foot"><button type="button" class="btn btn-dark" data-w="close">${esc(options.closeLabel || 'Done')}</button></div>
      </div>
    </div>`;
  document.body.appendChild(el);
  const w = (n) => el.querySelector(`[data-w="${n}"]`);
  const modal = () => window.bootstrap.Modal.getOrCreateInstance(el);
  const num = (n) => Number(n || 0).toLocaleString('en-IN');
  const time = (ms) => {
    if (!(ms > 0) || !isFinite(ms)) return '–';
    const t = Math.round(ms / 1000);
    if (t < 1) return '< 1s';
    return [t >= 3600 ? `${Math.floor(t / 3600)}h` : '', t % 3600 >= 60 ? `${Math.floor((t % 3600) / 60)}m` : '', t % 60 || t < 60 ? `${t % 60}s` : ''].filter(Boolean).join(' ');
  };
  const state = { total: 0, startedAt: 0, finishedAt: 0, last: { processed: 0 }, shown: null, ticker: 0 };
  const stage = (cls) => { el.classList.remove('is-check', 'is-running', 'is-done', 'is-empty', 'is-error'); el.classList.add(cls); };
  const steps = (...st) => ['s1', 's2', 's3'].forEach((k, i) => { const li = w(k); li.className = 'cz-syncwin__step' + (st[i] ? ' is-' + st[i] : ''); li.querySelector('.cz-syncwin__dot').innerHTML = st[i] === 'ok' ? '<i class="bi bi-check-lg"></i>' : st[i] === 'bad' ? '<i class="bi bi-x-lg"></i>' : String(i + 1); });
  const pill = (text, tone) => { const p = w('pill'); p.textContent = text; p.className = `cz-badge ${tone}`; };
  const say = (m) => { if (m !== undefined) w('message').textContent = m; };
  const paint = () => {
    const done = state.finishedAt || 0;
    const ms = (done || Date.now()) - (state.startedAt || Date.now());
    if (state.startedAt) w('elapsed').textContent = time(ms);
    const p = state.last.processed || 0;
    if (p > 0 && ms > 0) {
      const speed = p / (ms / 1000);
      w('speed').textContent = `${speed.toFixed(speed >= 100 ? 0 : 1)} rec/s`;
      w('left').textContent = done ? '–' : time(((Math.max(state.total, p) - p) / speed) * 1000);
    }
  };
  const stop = () => { clearInterval(state.ticker); state.ticker = 0; };

  const api = {
    /** shows the window (looking at the queue); resolves once it is fully shown */
    open(message) {
      stop();
      Object.assign(state, { total: 0, startedAt: 0, finishedAt: 0, last: { processed: 0 } });
      ['pct', 'inserted', 'updated', 'skipped', 'errors'].forEach((n) => { w(n)._czNum = 0; });
      w('pct').textContent = '0%';
      ['inserted', 'updated', 'skipped', 'errors'].forEach((n) => { w(n).textContent = '0'; });
      w('count').innerHTML = '&nbsp;';
      w('arc').style.strokeDashoffset = '';
      ['elapsed'].forEach((n) => { w(n).textContent = '0s'; });
      w('speed').textContent = '–';
      w('left').textContent = '–';
      stage('is-check');
      steps('on', '', '');
      pill('Checking', 'neutral');
      say(message || 'Checking the punches waiting to sync...');
      state.shown = new Promise((resolve) => { el.addEventListener('shown.bs.modal', () => resolve(), { once: true }); });
      modal().show();
      return state.shown;
    },
    /** the queue is known: the ring becomes a progress ring */
    start(total, message) {
      state.total = Math.max(0, Number(total) || 0);
      state.startedAt = Date.now();
      stage('is-running');
      steps('ok', 'on', '');
      pill('Running', 'info');
      say(message || 'Pairing IN / OUT punches and saving attendance...');
      stop();
      state.ticker = setInterval(paint, 1000);
      api.update({ processed: 0 });
    },
    /** @param {{processed?: number, inserted?: number, updated?: number, skipped?: number, errors?: number}} t totals so far */
    update(t, note) {
      state.last = { ...state.last, ...t };
      const p = state.last.processed || 0;
      const total = Math.max(state.total, p);
      const pct = total > 0 ? Math.min(100, Math.round((p / total) * 100)) : 0;
      w('arc').style.strokeDashoffset = String(C * (1 - pct / 100));
      window.CloudZoo.animateNumber(w('pct'), pct, { format: (v) => `${Math.round(v)}%` });
      w('count').textContent = `${num(p)} / ${num(total)}`;
      ['inserted', 'updated', 'skipped', 'errors'].forEach((n) => { if (t[n] !== undefined) window.CloudZoo.animateNumber(w(n), t[n], { bump: true }); });
      if (note) say(note);
      paint();
    },
    /** the run finished */
    done(message) {
      state.finishedAt = Date.now();
      stop();
      const total = Math.max(state.total, state.last.processed || 0);
      api.update({ processed: total });
      stage('is-done');
      steps('ok', 'ok', 'ok');
      pill('Complete', 'success');
      say(message || 'All punch records were paired and saved.');
      paint();
    },
    /** there was nothing to do */
    empty(message) {
      stop();
      stage('is-empty');
      steps('ok', '', '');
      pill('Up to date', 'success');
      say(message || 'Everything is already synced.');
      w('count').innerHTML = '&nbsp;';
    },
    /** the run stopped on an error */
    fail(message) {
      state.finishedAt = Date.now();
      stop();
      stage('is-error');
      steps('ok', 'bad', '');
      pill('Stopped', 'danger');
      say(message || 'The sync stopped.');
      paint();
    },
    /** hides the window, even while it is still opening */
    async close() {
      stop();
      try { await state.shown; } catch (e) { /* never opened */ }
      modal().hide();
    },
  };
  w('close').addEventListener('click', async () => { await api.close(); options.onClose?.(); });
  return api;
};

window.CloudZoo.toast = function (message, type = 'success', title = 'CloudZoo ERP') {
  const toastEl = document.getElementById('czToast');
  if (!toastEl || !window.bootstrap) return;

  const titleEl = document.getElementById('czToastTitle');
  const bodyEl = document.getElementById('czToastBody');
  const timeEl = document.getElementById('czToastTime');

  if (titleEl) titleEl.textContent = title;
  if (bodyEl) bodyEl.textContent = message;
  if (timeEl) timeEl.textContent = 'Just now';

  toastEl.classList.remove('cz-toast--success', 'cz-toast--danger', 'cz-toast--warning');
  if (type === 'success') {
    toastEl.classList.add('cz-toast--success');
  } else if (type === 'danger' || type === 'error') {
    toastEl.classList.add('cz-toast--danger');
  } else if (type === 'warning') {
    toastEl.classList.add('cz-toast--warning');
  }

  bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show();
};

// A message in a popup in the centre of the page (falls back to a toast when Bootstrap's modal is not there).
window.CloudZoo.notice = function (message, title = 'Cannot delete') {
  if (!window.bootstrap?.Modal) return window.CloudZoo.toast(message, 'warning', title);
  const wrap = document.createElement('div');
  wrap.className = 'modal fade';
  wrap.tabIndex = -1;
  wrap.setAttribute('aria-hidden', 'true');
  wrap.innerHTML = `<div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-sm">
    <div class="modal-header border-bottom"><h5 class="modal-title fs-6 fw-semibold"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body text-center py-4"><i class="bi bi-exclamation-triangle fs-1 text-warning d-block mb-2"></i><p class="mb-0" data-cz-notice-text></p></div>
    <div class="modal-footer border-top bg-light justify-content-center"><button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">OK</button></div>
  </div></div>`;
  wrap.querySelector('.modal-title').textContent = title;
  wrap.querySelector('[data-cz-notice-text]').textContent = message;
  document.body.appendChild(wrap);
  wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
  bootstrap.Modal.getOrCreateInstance(wrap).show();
};

// ============================================================================
// CloudZoo Reusable Searchable Select Component (No External Buttons)
// ============================================================================
window.CloudZoo.searchableSelect = function (selectEl, config = {}) {
  if (!selectEl) return null;
  if (selectEl.dataset.czSelectInit === "1") {
    if (selectEl._czUpdate) selectEl._czUpdate();
    return selectEl._czSelectInstance;
  }

  selectEl.dataset.czSelectInit = "1";
  selectEl.classList.add("cz-select-hidden");

  const wrapper = document.createElement("div");
  wrapper.className = "cz-select";

  const trigger = document.createElement("button");
  trigger.type = "button";
  trigger.className = "cz-select__trigger";
  trigger.setAttribute("aria-haspopup", "listbox");
  trigger.setAttribute("aria-expanded", "false");

  const labelSpan = document.createElement("span");
  labelSpan.className = "cz-select__label";

  const arrowIcon = document.createElement("i");
  arrowIcon.className = "bi bi-chevron-down cz-select__arrow";

  trigger.appendChild(labelSpan);
  trigger.appendChild(arrowIcon);
  wrapper.appendChild(trigger);

  const dropdown = document.createElement("div");
  dropdown.className = "cz-select__dropdown d-none";

  const searchBox = document.createElement("div");
  searchBox.className = "cz-select__search";
  searchBox.innerHTML = `
    <i class="bi bi-search cz-select__search-icon"></i>
    <input type="text" class="cz-select__search-input" placeholder="Search..." autocomplete="off">
    <button type="button" class="cz-select__search-clear d-none"><i class="bi bi-x"></i></button>
  `;
  dropdown.appendChild(searchBox);

  const searchInput = searchBox.querySelector(".cz-select__search-input");
  const clearBtn = searchBox.querySelector(".cz-select__search-clear");

  const list = document.createElement("div");
  list.className = "cz-select__list";
  list.setAttribute("role", "listbox");
  dropdown.appendChild(list);

  const addLabel = config.addLabel || selectEl.dataset.addLabel;
  if (addLabel || config.onAddNew) {
    const footer = document.createElement("div");
    footer.className = "cz-select__footer";
    const addBtn = document.createElement("button");
    addBtn.type = "button";
    addBtn.className = "cz-select__add-btn";
    addBtn.innerHTML = `<i class="bi bi-plus-lg me-1"></i> ${addLabel || "Add New"}`;
    addBtn.addEventListener("click", (e) => {
      e.preventDefault();
      closeDropdown();
      if (typeof config.onAddNew === "function") {
        config.onAddNew(selectEl);
      }
      selectEl.dispatchEvent(new CustomEvent("cz:add-new", {
        bubbles: true,
        detail: {
          select: selectEl,
          masterType: selectEl.dataset.masterType,
          label: addLabel
        }
      }));
    });
    footer.appendChild(addBtn);
    dropdown.appendChild(footer);
  }

  // Insert wrapper right after native select
  selectEl.parentNode.insertBefore(wrapper, selectEl.nextSibling);

  function renderList(query = "") {
    list.innerHTML = "";
    const options = Array.from(selectEl.options);
    const q = query.toLowerCase().trim();
    let matches = 0;

    options.forEach((opt, idx) => {
      if (opt.value === "" && idx === 0) return;
      if (opt.value === "__ADD_NEW__") return;
      const text = opt.textContent;
      if (q && !text.toLowerCase().includes(q)) return;

      matches++;
      const item = document.createElement("div");
      item.className = "cz-select__option";
      if (opt.selected) item.classList.add("is-selected");
      item.textContent = text;
      item.dataset.value = opt.value;
      item.dataset.index = idx;

      item.addEventListener("click", () => {
        selectOption(opt.value);
        closeDropdown();
      });

      list.appendChild(item);
    });

    if (matches === 0) {
      const empty = document.createElement("div");
      empty.className = "cz-select__empty";
      empty.textContent = "No matches found";
      list.appendChild(empty);
    }
  }

  function updateTriggerLabel() {
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    if (selectedOpt && selectedOpt.value !== "") {
      labelSpan.textContent = selectedOpt.textContent;
      labelSpan.classList.remove("is-placeholder");
    } else {
      const placeholder = selectEl.getAttribute("placeholder") || selectEl.options[0]?.textContent || "Select...";
      labelSpan.textContent = placeholder;
      labelSpan.classList.add("is-placeholder");
    }
  }

  function selectOption(val) {
    if (selectEl.value !== val) {
      selectEl.value = val;
      selectEl.dispatchEvent(new Event("change", { bubbles: true }));
    }
    updateTriggerLabel();
  }

  function openDropdown() {
    document.querySelectorAll(".cz-select.is-open").forEach(other => {
      if (other !== wrapper) {
        other.classList.remove("is-open");
        other.querySelector(".cz-select__dropdown")?.classList.add("d-none");
        other.querySelector(".cz-select__trigger")?.setAttribute("aria-expanded", "false");
      }
    });

    wrapper.classList.add("is-open");
    dropdown.classList.remove("d-none");
    trigger.setAttribute("aria-expanded", "true");
    searchInput.value = "";
    clearBtn.classList.add("d-none");
    renderList();

    setTimeout(() => {
      searchInput.focus();
      const selItem = list.querySelector(".is-selected");
      if (selItem) {
        selItem.scrollIntoView({ block: "nearest" });
      }
    }, 40);
  }

  function closeDropdown() {
    wrapper.classList.remove("is-open");
    dropdown.classList.add("d-none");
    trigger.setAttribute("aria-expanded", "false");
  }

  trigger.addEventListener("click", (e) => {
    e.preventDefault();
    if (wrapper.classList.contains("is-open")) {
      closeDropdown();
    } else {
      openDropdown();
    }
  });

  searchInput.addEventListener("input", (e) => {
    const val = e.target.value;
    clearBtn.classList.toggle("d-none", !val);
    renderList(val);
  });

  clearBtn.addEventListener("click", () => {
    searchInput.value = "";
    clearBtn.classList.add("d-none");
    renderList("");
    searchInput.focus();
  });

  wrapper.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeDropdown();
      trigger.focus();
    } else if (e.key === "ArrowDown" && !wrapper.classList.contains("is-open")) {
      e.preventDefault();
      openDropdown();
    }
  });

  selectEl.addEventListener("change", () => {
    updateTriggerLabel();
  });

  updateTriggerLabel();

  selectEl._czUpdate = function () {
    updateTriggerLabel();
    if (wrapper.classList.contains("is-open")) {
      renderList(searchInput.value);
    }
  };

  const instance = {
    wrapper,
    trigger,
    dropdown,
    update: selectEl._czUpdate,
    open: openDropdown,
    close: closeDropdown,
    destroy() {
      wrapper.remove();
      selectEl.classList.remove("cz-select-hidden");
      delete selectEl.dataset.czSelectInit;
      delete selectEl._czUpdate;
      delete selectEl._czSelectInstance;
    }
  };

  selectEl._czSelectInstance = instance;
  return instance;
};

// Global click to close open cz-selects
document.addEventListener("click", (e) => {
  if (!e.target.closest(".cz-select")) {
    document.querySelectorAll(".cz-select.is-open").forEach(w => {
      w.classList.remove("is-open");
      w.querySelector(".cz-select__dropdown")?.classList.add("d-none");
      w.querySelector(".cz-select__trigger")?.setAttribute("aria-expanded", "false");
    });
  }
});

// Common function to initialize all searchable dropdowns
window.CloudZoo.initSearchableSelects = function (container = document) {
  const selects = container.querySelectorAll("select.cz-searchable-select");
  selects.forEach(sel => {
    window.CloudZoo.searchableSelect(sel);
  });
};


// ============================================================================
// CloudZoo Masters helper: Country > State > City cascades, open-master lists and "+ Add New" modals
// Used by every form that has these dropdowns. Data comes from /api/masters (core/Services/MasterService).
// ============================================================================
window.CloudZoo.masters = (() => {
  const base = () => (window.CZ_BASE_URL || "") + "/api/masters";
  const esc = (v) => String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[c]));

  // Countries, states and cities are read once per page and kept (a form asks for the same lists again for billing and shipping, and again when a
  // saved record is shown). Any write (a "+ Add New" country / state / city / master) forgets them, so a new entry shows up at once.
  const cache = new Map();
  const cached = (key, load) => {
    if (!cache.has(key)) cache.set(key, load().catch((err) => { cache.delete(key); throw err; }));
    return cache.get(key);
  };

  async function call(action, params = {}, body = null) {
    if (body) cache.clear();
    const url = `${base()}?${new URLSearchParams({ action, ...params })}`;
    const res = await fetch(url, body ? { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) } : {});
    let json;
    try { json = await res.json(); } catch (e) { json = { success: false, message: "Unexpected server response." }; }
    if (!res.ok || json.success === false) throw new Error(json.message || "Request failed.");
    return json.data;
  }

  const ADD_NEW = "__ADD_NEW__";

  /** Redraw a Select2 after its value or options were changed in code (does not fire change handlers). */
  function refresh(select) {
    if (window.jQuery && window.jQuery(select).data("select2")) window.jQuery(select).trigger("change.select2");
  }

  /** Replace the options of a select (keeping a value that is not in the list); adds "+ Add New" when data-add-label is set. */
  function fill(select, names, selected = "", placeholder = "Select...") {
    const unique = new Map();   // names that differ only in letter case are one entry
    const labels = new Map();   // value => label of a { value, label } item (a plain name is its own label)
    (names || []).filter(Boolean).forEach((item) => {
      const isObject = typeof item === "object";
      const value = String(isObject ? item.value ?? "" : item).trim();
      const key = value.toLowerCase();
      if (value && !unique.has(key)) {
        unique.set(key, value);
        if (isObject) labels.set(value, String(item.label ?? value));
      }
    });
    const canonical = (v) => unique.get(String(v || "").trim().toLowerCase()) || String(v || "").trim();
    // a multiple select has no empty "placeholder" option (it would be a chosen, empty item); `selected` may be a list of names
    const chosen = select.multiple ? [].concat(selected || []).filter(Boolean).map(canonical) : [];
    if (!select.multiple) selected = canonical(selected);
    const list = [...unique.values()];
    if (select.multiple) chosen.forEach((n) => { if (!list.includes(n)) list.unshift(n); });
    else if (selected && !list.includes(selected)) list.unshift(selected);
    select.dataset.czPlaceholder = placeholder;
    select.innerHTML = (select.multiple ? "" : `<option value="">${esc(placeholder)}</option>`) +
      list.map((n) => `<option value="${esc(n)}"${(select.multiple ? chosen.includes(n) : n === selected) ? " selected" : ""}>${esc(labels.get(n) || n)}</option>`).join("") +
      (select.dataset.addLabel ? `<option value="${ADD_NEW}" class="cz-select2-add-option">${esc(select.dataset.addLabel)}...</option>` : "");
    if (!select.multiple) {
      select.value = selected || "";
      select.dataset.prev = select.value;
    }
    refresh(select);
  }

  /**
   * Turn a select into a searchable Select2 styled by cloudzoo.css. With an add label, the last entry is
   * "+ Add New...": choosing it restores the previous value and fires a bubbling "cz:add-new" event on the select.
   * Select2 only fires jQuery change events, so a native "change" is dispatched too for plain listeners.
   */
  function select2(select, addLabel = null) {
    if (addLabel) select.dataset.addLabel = addLabel;
    const $ = window.jQuery;
    if (!$ || typeof $.fn.select2 !== "function") return;
    const $el = $(select);
    if ($el.data("select2")) $el.select2("destroy");
    $el.off("select2:select.czAdd");   // destroy keeps our handler: without this every refill of a list would add one more, and "+ Add New" would open the dialog several times
    select.dataset.prev = select.value;
    const inGrid = !!select.closest(".cz-grid");   // in a table cell the list may be wider than the cell, so names are not squeezed onto several lines
    $el.select2({
      width: "100%",
      dropdownAutoWidth: inGrid,
      dropdownCssClass: inGrid ? "cz-select2-auto" : "",
      dropdownParent: $(select.closest(".modal, .offcanvas") || document.body),
      placeholder: select.multiple ? (select.dataset.czPlaceholder || "Select...") : (select.options[0]?.textContent || "Select..."),
      allowClear: false,
      templateResult(data) {
        if (data.id === ADD_NEW) return $(`<span class="cz-select2-add-new-bar"><i class="bi bi-plus-lg me-1"></i>${esc(String(data.text).replace(/\.\.\.$/, ""))}</span>`);
        return data.text;
      },
    });
    $el.off("select2:open.czFit");
    if (inGrid) {   // a wide list opened from a cell near the right edge is pulled back so it stays on the screen
      $el.on("select2:open.czFit", () => requestAnimationFrame(() => {
        const dd = document.querySelector(".select2-dropdown.cz-select2-auto");
        if (!dd) return;
        dd.style.left = "0px";
        const over = dd.getBoundingClientRect().right - (document.documentElement.clientWidth - 12);
        if (over > 0) dd.style.left = `-${Math.ceil(over)}px`;
      }));
    }
    $el.on("select2:select.czAdd", (e) => {
      if (e.params.data.id === ADD_NEW) {
        if (select.multiple) {   // keep what is chosen; only the "+ Add New" entry itself is taken out again
          [...select.options].forEach((o) => { if (o.value === ADD_NEW) o.selected = false; });
          refresh(select);
          select.dispatchEvent(new CustomEvent("cz:add-new", { bubbles: true, detail: { select, label: select.dataset.addLabel } }));
          return;
        }
        select.value = select.dataset.prev || "";
        refresh(select);
        select.dispatchEvent(new CustomEvent("cz:add-new", { bubbles: true, detail: { select, label: select.dataset.addLabel } }));
        return;
      }
      if (!select.multiple) select.dataset.prev = select.value;
      // Select2 already fired a jQuery "change", which never reaches addEventListener code: this native one is for that code
      // only, so it is flagged and jQuery handlers skip it (they would otherwise run twice)
      const change = new Event("change", { bubbles: true });
      change.czSelect2 = true;
      select.dispatchEvent(change);
    });
  }
  if (window.jQuery && !window.jQuery.event.czSelect2Patched) {
    const dispatch = window.jQuery.event.dispatch;
    window.jQuery.event.dispatch = function (e) { return e && e.czSelect2 ? undefined : dispatch.apply(this, arguments); };
    window.jQuery.event.czSelect2Patched = true;
  }

  const countries = () => cached("countries", async () => (await call("countries")).map((c) => c.country_name));
  const states = async (country) => (country ? cached(`states|${country}`, async () => (await call("states", { country })).map((s) => s.state_name)) : []);
  const cities = async (state, country) => (state ? cached(`cities|${country}|${state}`, async () => (await call("cities", { state, country })).map((c) => c.city_name)) : []);
  const openMaster = async (type) => (await call("open_master", { type })).map((m) => m.name);
  const agents = async (status = "Active") => (await call("agents", { status })).data || [];

  /**
   * Show a small modal and resolve with the typed values (or null when cancelled).
   * When the first field has `rows: true` the dialog takes several rows of (name, code) instead: "+ Add row" (in the title bar, beside the close button) adds one, each row has a remove button,
   * and the values are { rows: [{ name, code }] } (rows with no name are left out).
   */
  function ask(title, fields, listUrl = "") {
    return new Promise((resolve) => {
      const id = "czQuickAddModal";
      const rowsMode = !!fields[0]?.rows;
      document.getElementById(id)?.remove();
      const wrap = document.createElement("div");
      wrap.className = "modal fade cz-quickadd";
      wrap.id = id;
      wrap.tabIndex = -1;
      wrap.setAttribute("aria-labelledby", id + "Title");
      const fieldsHtml = `<div class="row g-3">${fields.map((f, i) => `
                <div class="col-12">
                  <label class="form-label" for="${id}F${i}">${esc(f.label)} ${f.required ? '<span class="cz-required">*</span>' : ""}</label>
                  ${f.options
          ? `<select class="form-select" id="${id}F${i}" name="${f.name}" ${f.required ? "required" : ""}>${f.options.map((o) => `<option value="${esc(o)}"${o === f.value ? " selected" : ""}>${esc(o)}</option>`).join("")}</select>`
          : `<input class="form-control" id="${id}F${i}" name="${f.name}" maxlength="${f.max || 100}" value="${esc(f.value || "")}" ${f.required ? "required" : ""} autocomplete="off">`}
                  <div class="invalid-feedback">${esc(f.label)} is required.</div>
                </div>`).join("")}
              </div>`;
      const rowsHtml = `
              <div class="cz-quickrows">
                <div class="cz-quickrow cz-quickrow--head"><span>${esc(fields[0].label)} <span class="cz-required">*</span></span><span>${esc(fields[1]?.label || "Code")}</span><span></span></div>
                <div data-cz-quick-rowlist></div>
              </div>`;
      wrap.innerHTML = `
        <div class="modal-dialog modal-dialog-centered${rowsMode ? " modal-lg" : ""}">
          <form class="modal-content border-0 shadow-sm" novalidate>
            <div class="modal-header border-bottom">
              <h5 class="modal-title fs-6 fw-semibold" id="${id}Title">${esc(title)}</h5>
              ${rowsMode ? '<button class="btn btn-outline-secondary btn-sm ms-auto me-2" type="button" data-cz-quick-addrow><i class="bi bi-plus-lg me-1"></i> Add row</button>' : ""}
              <button type="button" class="btn-close${rowsMode ? " m-0" : ""}" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              ${rowsMode ? rowsHtml : fieldsHtml}
              <div class="cz-field-error d-none" data-cz-quick-error role="alert"></div>
            </div>
            <div class="modal-footer border-top bg-light">
              ${listUrl ? `<a class="btn btn-outline-secondary btn-sm me-auto" href="${esc(listUrl)}" target="_blank" rel="noopener" title="Open the list page to edit or delete the items already there"><i class="bi bi-list-ul me-1"></i> Manage list</a>` : ""}
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-dark btn-sm">Save</button>
            </div>
          </form>
        </div>`;
      document.body.appendChild(wrap);
      const modal = bootstrap.Modal.getOrCreateInstance(wrap);
      let done = false;
      const form = wrap.querySelector("form");
      const errorBox = wrap.querySelector("[data-cz-quick-error]");
      const showError = (message) => { errorBox.textContent = message; errorBox.classList.toggle("d-none", !message); };

      // ---- rows mode
      const list = wrap.querySelector("[data-cz-quick-rowlist]");
      const addRow = (focus = true) => {
        const row = document.createElement("div");
        row.className = "cz-quickrow";
        row.dataset.czQuickRow = "";
        row.innerHTML = `<input class="form-control" data-cz-qr="name" maxlength="${fields[0].max || 150}" placeholder="${esc(fields[0].label)}" aria-label="${esc(fields[0].label)}" autocomplete="off">
          <input class="form-control" data-cz-qr="code" maxlength="${fields[1]?.max || 50}" placeholder="Optional" aria-label="${esc(fields[1]?.label || "Code")}" autocomplete="off">
          <button class="cz-icon-btn light" type="button" data-cz-qr-del title="Remove this row" aria-label="Remove this row"><i class="bi bi-trash3"></i></button>`;
        list.appendChild(row);
        if (focus) row.querySelector("input").focus();
      };
      if (rowsMode) {
        addRow(false);
        wrap.querySelector("[data-cz-quick-addrow]").addEventListener("click", () => addRow());
        list.addEventListener("click", (e) => {
          const del = e.target.closest("[data-cz-qr-del]");
          if (!del) return;
          const row = del.closest("[data-cz-quick-row]");
          if (list.children.length > 1) row.remove();
          else row.querySelectorAll("input").forEach((i) => { i.value = ""; });   // the last row is cleared, not removed
        });
        list.addEventListener("keydown", (e) => {   // Enter moves on: to the next row, or a new one after the last row
          if (e.key !== "Enter" || !e.target.matches("input")) return;
          e.preventDefault();
          const row = e.target.closest("[data-cz-quick-row]");
          if (row.nextElementSibling) row.nextElementSibling.querySelector("input").focus();
          else if (row.querySelector("[data-cz-qr=name]").value.trim()) addRow();
        });
        list.addEventListener("input", (e) => {
          if (!e.target.matches("input")) return;
          e.target.classList.remove("is-invalid");
          showError("");   // a new row is added only with "Add row" (or Enter on a filled last row), never while typing
        });
      }

      form.addEventListener("submit", async (e) => {
        e.preventDefault();
        let values;
        if (rowsMode) {
          const rows = [...list.children].map((r) => ({ name: r.querySelector("[data-cz-qr=name]").value.trim(), code: r.querySelector("[data-cz-qr=code]").value.trim() })).filter((r) => r.name);
          if (!rows.length) {
            const first = list.querySelector("[data-cz-qr=name]");
            first.classList.add("is-invalid");
            first.focus();
            showError(`Enter at least one ${fields[0].label.toLowerCase()}.`);
            return;
          }
          values = { rows };
        } else {
          form.classList.add("was-validated");
          if (!form.checkValidity()) return;
          values = Object.fromEntries(new FormData(form).entries());
          Object.keys(values).forEach((k) => (values[k] = String(values[k]).trim()));
        }
        try {
          wrap._submit && (await wrap._submit(values));
        } catch (err) {
          showError(err.message);
          return;
        }
        done = true;
        modal.hide();
        resolve(values);
      });
      wrap.addEventListener("hidden.bs.modal", () => { if (!done) resolve(null); wrap.remove(); });
      wrap.addEventListener("shown.bs.modal", () => wrap.querySelector("input,select")?.focus());
      wrap._setSubmit = (fn) => { wrap._submit = fn; };
      modal.show();
      ask.last = wrap;
    });
  }

  /** Open the right "+ Add New" dialog for a master type. Resolves with the new name, or null. */
  async function quickAdd(type, ctx = {}) {
    const label = ctx.label || "Item";
    const run = (saveFn) => {
      const pending = ask(`Add New ${label}`, ctx.fields, ctx.listUrl || "");   // listUrl: the page that lists the items (edit, delete), when the caller has one
      ask.last._setSubmit(saveFn);
      return pending;
    };

    if (type === "country") {
      ctx.fields = [{ name: "country_name", label: "Country name", required: true }, { name: "country_code", label: "ISO code (e.g. IN)", required: true, max: 3 }];
      const v = await run((v) => call("add_country", {}, { country_name: v.country_name, country_code: v.country_code }));
      return v ? v.country_name : null;
    }
    if (type === "state") {
      const list = await countries();
      ctx.fields = [{ name: "country_name", label: "Country", required: true, options: list, value: ctx.country || list[0] }, { name: "state_name", label: "State name", required: true }];
      const v = await run((v) => call("add_state", {}, { country_name: v.country_name, state_name: v.state_name }));
      return v ? { name: v.state_name, country: v.country_name } : null;
    }
    if (type === "city") {
      const list = await countries();
      const country = ctx.country || list[0];
      const stateList = await states(country);
      ctx.fields = [
        { name: "country_name", label: "Country", required: true, options: list, value: country },
        { name: "state_name", label: "State", required: true, options: stateList.length ? stateList : [""], value: ctx.state || stateList[0] },
        { name: "city_name", label: "City name", required: true },
      ];
      const v = await run((v) => call("add_city", {}, { country_name: v.country_name, state_name: v.state_name, city_name: v.city_name }));
      return v ? { name: v.city_name, state: v.state_name, country: v.country_name } : null;
    }
    if (type === "agent") {
      ctx.fields = [
        { name: "agent_name", label: "Agent Name", required: true, max: 100 },
        { name: "agent_commission", label: "Agent Commission %", required: true, max: 6, value: ctx.commission || "0" },
        { name: "phone", label: "Phone", required: false, max: 25 },
        { name: "email", label: "Email", required: false, max: 100 },
      ];
      const v = await run((v) => call("add_agent", {}, {
        agent_name: v.agent_name,
        agent_commission: parseFloat(v.agent_commission || 0),
        phone: v.phone || null,
        email: v.email || null,
      }));
      return v ? { name: v.agent_name, commission: parseFloat(v.agent_commission || 0) } : null;
    }
    ctx.fields = [{ name: "name", label: `${label} name`, required: true, max: 150, rows: true }, { name: "code", label: "Code", required: false, max: 50 }];
    const created = [];
    const v = await run(async (v) => {
      // several rows: a name typed twice is added once; a name that already exists is skipped and the others are still added
      const seen = new Set();
      const rows = v.rows.filter((r) => !seen.has(r.name.toLowerCase()) && seen.add(r.name.toLowerCase()));
      const skipped = [];
      let firstError = null;
      for (const r of rows) {
        try { created.push((await call("add_open_master", {}, { master_type: type, name: r.name, code: r.code })).name || r.name); } catch (err) { skipped.push(r.name); firstError = firstError || err; }
      }
      if (!created.length) throw firstError;   // nothing was added: the dialog stays open and shows why
      if (skipped.length) window.CloudZoo?.toast?.(`${created.length} added. Not added (already there or invalid): ${skipped.join(", ")}`, "warning");
    });
    if (!v) return null;
    return created.length > 1 ? created : (created[0] || null);
  }

  /**
   * Wire a Country > State > City group of selects. Each select becomes searchable and gets a "+ Add New" button.
   * Returns { set(values), onChange(fn) } so a form can load saved values or mirror billing into shipping.
   */
  function bindGeo({ country, state, city }) {
    const listeners = [];
    const notify = () => listeners.forEach((fn) => fn());
    select2(country, "Add New Country"); select2(state, "Add New State"); select2(city, "Add New City");

    async function loadStates(selected = "") { fill(state, await states(country.value), selected, "Select State"); }
    async function loadCities(selected = "") { fill(city, await cities(state.value, country.value), selected, "Select City"); }

    country.addEventListener("change", async () => { await loadStates(); await loadCities(); notify(); });
    state.addEventListener("change", async () => { await loadCities(); notify(); });
    city.addEventListener("change", notify);

    country.addEventListener("cz:add-new", async () => {
      const name = await quickAdd("country", { label: "Country" });
      if (name) { fill(country, await countries(), name, "Select Country"); country.dispatchEvent(new Event("change")); }
    });
    state.addEventListener("cz:add-new", async () => {
      const added = await quickAdd("state", { label: "State", country: country.value });
      if (added) {
        fill(country, await countries(), added.country, "Select Country");
        fill(state, await states(added.country), added.name, "Select State");
        await loadCities(); notify();
      }
    });
    city.addEventListener("cz:add-new", async () => {
      const added = await quickAdd("city", { label: "City", country: country.value, state: state.value });
      if (added) {
        fill(country, await countries(), added.country, "Select Country");
        fill(state, await states(added.country), added.state, "Select State");
        fill(city, await cities(added.state, added.country), added.name, "Select City"); notify();
      }
    });

    return {
      async init() { fill(country, await countries(), country.value || "", "Select Country"); await loadStates(); await loadCities(); },
      async set(v = {}) {
        // the three lists do not depend on each other's answer here (the saved values are known), so they load together
        const [names, stateNames, cityNames] = await Promise.all([countries(), states(v.country || ""), cities(v.state || "", v.country || "")]);
        fill(country, names, v.country || "", "Select Country");
        fill(state, stateNames, v.state || "", "Select State");
        fill(city, cityNames, v.city || "", "Select City");
      },
      onChange(fn) { listeners.push(fn); },
    };
  }

  /** Make a select searchable with an "+ Add New" button that saves an open-master item and selects it. */
  document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-cz-open-master-add]");
    if (!button) return;
    const select = document.getElementById(button.dataset.czOpenMasterAdd);
    if (select) select.dispatchEvent(new CustomEvent("cz:add-new", { bubbles: true }));
  });

  function bindOpenMaster(select, type, label) {
    select2(select, `Add New ${label}`);
    select.addEventListener("cz:add-new", async () => {
      const result = await quickAdd(type, { label, listUrl: select.dataset.czListUrl || "" });
      if (result) {
        const added = [].concat(result);   // one name, or several when "Add multiple names" was used
        const names = Array.from(select.options).map((o) => o.value).filter((v) => v !== ADD_NEW);
        if (select.multiple) {   // the new items join what is already chosen
          fill(select, [...names, ...added], [...Array.from(select.selectedOptions).map((o) => o.value), ...added], select.dataset.czPlaceholder || "Select...");
        } else {
          fill(select, [...names, ...added], added[0], select.options[0]?.textContent || "Select...");   // a single dropdown shows the first one added
        }
        select.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });
  }

  /**
   * A dropdown of contacts (suppliers, staff ...): searchable, with "+ Add New ..." at the end of its list as the master
   * dropdowns have. A contact needs its whole form, so the entry opens that form in a new tab; when the user comes back
   * to this tab `reload` is called once to read the list again.
   */
  function bindContactAdd(select, label, url, reload) {
    select2(select, `Add New ${label}`);
    if (![...select.options].some((o) => o.value === ADD_NEW)) {
      select.insertAdjacentHTML("beforeend", `<option value="${ADD_NEW}" class="cz-select2-add-option">${esc(select.dataset.addLabel)}...</option>`);
    }
    select.addEventListener("cz:add-new", () => {
      window.open(url, "_blank", "noopener");
      if (typeof reload !== "function") return;
      const back = () => {
        if (document.visibilityState !== "visible") return;
        document.removeEventListener("visibilitychange", back);
        window.removeEventListener("focus", back);
        reload();
      };
      document.addEventListener("visibilitychange", back);
      window.addEventListener("focus", back);
    });
  }

  /** Make an agent select searchable with an "+ Add New" button and auto-fills a commission input. */
  function bindAgentMaster(select, commissionInput) {
    select2(select, "Add New Agent");

    // Auto-fill commission when agent changes
    select.addEventListener("change", () => {
      const opt = select.options[select.selectedIndex];
      if (opt && opt.dataset && opt.dataset.commission !== undefined && commissionInput) {
        commissionInput.value = opt.dataset.commission;
        commissionInput.dispatchEvent(new Event("input", { bubbles: true }));
        commissionInput.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });

    select.addEventListener("cz:add-new", async () => {
      const added = await quickAdd("agent", { label: "Agent" });
      if (added) {
        const comm = added.commission !== undefined ? added.commission : 0;
        const opt = new Option(`${added.name} (${comm}%)`, added.name, true, true);
        opt.dataset.commission = comm;
        select.add(opt);
        refresh(select);
        select.value = added.name;
        select.dispatchEvent(new Event("change", { bubbles: true }));
        if (commissionInput) {
          commissionInput.value = comm;
          commissionInput.dispatchEvent(new Event("input", { bubbles: true }));
          commissionInput.dispatchEvent(new Event("change", { bubbles: true }));
        }
      }
    });
  }

  /**
   * Line item "Sku / Item" (Invoice, Proforma Invoice, Quotation): a searchable list of the Product Master items, searched
   * by name and SKU code. A name that is not in the master can still be typed (export-erp's SKU box allowed it), and a saved
   * name that is no longer in the master stays selected. Picking an item fires a bubbling "input" event on the select, so
   * the form fills the line's HSN, rate and tax % as it did for the typed name. The select should carry data-cz-native so
   * autoSelect2 leaves it to this function.
   * @param {HTMLSelectElement} select
   * @param {Array<{product_name: string, sku?: string}>} products
   * @param {string} value  the line's saved item
   * @param {{locked?: boolean}} opts  locked: the item cannot be changed (a line taken from a source document)
   */
  function skuSelect(select, products, value = "", opts = {}) {
    const current = String(value ?? "").trim();
    const list = [...new Map((products || []).filter((p) => p && p.product_name).map((p) => [String(p.product_name), p])).values()];
    select.innerHTML = `<option value=""></option>` +
      (current && !list.some((p) => String(p.product_name) === current) ? `<option value="${esc(current)}">${esc(current)}</option>` : "") +
      list.map((p) => `<option value="${esc(p.product_name)}" data-sku="${esc(p.sku || "")}">${esc(p.product_name)}</option>`).join("");
    select.value = current;
    select.disabled = !!opts.locked;
    const $ = window.jQuery;
    if (!$ || typeof $.fn.select2 !== "function") return select;
    const $el = $(select);
    if ($el.data("select2")) $el.select2("destroy");
    $el.select2({
      width: "100%",
      tags: true,                                    // an item that is not in the Product Master can be typed
      placeholder: "Select or type an item",
      allowClear: false,
      dropdownAutoWidth: true,
      dropdownCssClass: "cz-select2-auto cz-sku-dropdown",
      dropdownParent: $(select.closest(".modal") || document.body),
      matcher(params, data) {
        const term = String(params.term || "").trim().toLowerCase();
        if (!term) return data;
        return `${data.text} ${data.element?.dataset.sku || ""}`.toLowerCase().includes(term) ? data : null;
      },
      templateResult(data) {
        const sku = data.element?.dataset.sku;
        if (!data.id) return data.text;
        // item name on the first line, its SKU code under it (a typed item that is not in the master has none)
        return $(`<span class="cz-sku-option"><span class="cz-sku-option__name">${esc(data.text)}</span>` +
          `<span class="cz-sku-option__code">${sku ? esc(sku) : "Not in Product Master"}</span></span>`);
      },
      createTag(params) {
        const term = String(params.term || "").trim();
        return term ? { id: term, text: term } : null;
      },
    });
    $el.off("select2:select.czSku").on("select2:select.czSku", () => select.dispatchEvent(new Event("input", { bubbles: true })));
    return select;
  }

  /** Put the cursor in a select: a Select2 opens its search box, a plain select gets the focus. */
  function openSelect(select) {
    const $ = window.jQuery;
    if ($ && $(select).data("select2")) $(select).select2("open");
    else select?.focus();
  }

  /**
   * Make every dropdown under `root` a searchable Select2, including the ones scripts add later (new rows, dialogs).
   * For pages whose own scripts work with plain selects: when such a script sets a select's value or replaces its
   * options, the Select2 box is redrawn so it shows the same thing, and it is hidden or shown with its select. A select
   * marked data-cz-native is left as it is, and so is one a page hides with an inline display:none (a data holder).
   * `match` limits which selects are taken (every page: the Bootstrap ".form-select" dropdowns).
   */
  function autoSelect2(root = document.body, match = "select") {
    const $ = window.jQuery;
    if (!$ || typeof $.fn.select2 !== "function") return;
    const native = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, "value");
    const enhance = (sel) => {
      if (!sel.matches(match) || sel.dataset.czNative !== undefined || sel.dataset.czAuto || sel.closest("template") || sel.style.display === "none") return;
      sel.dataset.czAuto = "1";
      if (!$(sel).data("select2")) select2(sel);
      Object.defineProperty(sel, "value", { configurable: true, get() { return native.get.call(this); }, set(v) { native.set.call(this, v); refresh(this); } });
      new MutationObserver(() => refresh(sel)).observe(sel, { childList: true });
      // a script that hides or shows the select (d-none, hidden) hides or shows its Select2 box with it
      const follow = () => sel.nextElementSibling?.classList.contains("select2-container") && sel.nextElementSibling.classList.toggle("d-none", sel.classList.contains("d-none") || sel.hidden);
      new MutationObserver(follow).observe(sel, { attributes: true, attributeFilter: ["class", "hidden"] });
      follow();
    };
    const scan = (node) => {
      if (node.nodeType !== 1) return;
      if (node.matches("select")) enhance(node);
      node.querySelectorAll("select").forEach(enhance);
    };
    scan(root);
    new MutationObserver((changes) => changes.forEach((c) => c.addedNodes.forEach(scan))).observe(root, { childList: true, subtree: true });
  }
  // Every page: the Bootstrap dropdowns (.form-select) become CloudZoo-styled Select2 lists, because the browser draws the open
  // list of a plain <select> itself, with the system's blue highlight that CSS cannot change. A page with data-cz-auto-select2
  // on its <main> takes every select. Dialogs outside <main> are covered too; data-cz-native keeps a select as it is.
  document.addEventListener("DOMContentLoaded", () => autoSelect2(document.body, document.querySelector("[data-cz-auto-select2]") ? "select" : "select.form-select"));

  return { call, fill, refresh, select2, countries, states, cities, agents, openMaster, quickAdd, bindGeo, bindOpenMaster, bindAgentMaster, bindContactAdd, skuSelect, openSelect, autoSelect2, ADD_NEW };
})();

// Digits-only inputs (mobile / phone numbers): drop anything that is not 0-9 as it is typed or pasted
document.addEventListener("input", (e) => {
  const el = e.target;
  if (el.matches && el.matches("input[data-cz-digits]") && /\D/.test(el.value)) {
    el.value = el.value.replace(/\D/g, "");
  }
  if (el.matches && el.matches("input[data-cz-lowercase]") && el.value !== el.value.toLowerCase()) {
    const pos = el.selectionStart;
    el.value = el.value.toLowerCase();
    try { el.setSelectionRange(pos, pos); } catch (_) { /* type=email has no selection API */ }
  }
});

// Prevent accidental mousewheel value increments on number inputs
document.addEventListener("wheel", (e) => {
  if (e.target.matches && e.target.matches('input[type="number"]:focus')) {
    e.target.blur();
  }
}, { passive: true });


// ============================================================================
// Access Restricted: menus and buttons stay visible; what a privilege profile does not allow opens this screen.
//   - an element with data-cz-locked="<what>" (or inside one) shows the popup when clicked, and the click goes no further
//   - an API answer of 403 forbidden / permission_denied shows it too
// The markup is the #czAccessDenied template printed by includes/footer.php (includes/access-denied.php).
// ============================================================================
window.CloudZoo.accessDenied = (() => {
  let current = null;
  let returnFocus = null;

  function close() {
    if (!current) return;
    current.remove();
    current = null;
    document.body.classList.remove("cz-ar-open");
    if (returnFocus && document.contains(returnFocus)) returnFocus.focus();
    returnFocus = null;
  }

  /** @param {string} [what] the page or action that was refused, shown under the message */
  function show(what = "") {
    const template = document.getElementById("czAccessDenied");
    if (!template) {
      window.CloudZoo.toast("You do not have permission to do this.", "danger", "Access restricted");
      return;
    }
    close();
    returnFocus = document.activeElement;
    current = template.content.firstElementChild.cloneNode(true);

    const row = current.querySelector("[data-cz-ar-what-row]");
    const label = current.querySelector("[data-cz-ar-what]");
    if (row && label) {
      label.textContent = what;
      row.classList.toggle("d-none", what === "");
    }

    current.addEventListener("click", (e) => {
      const home = e.target.closest("[data-cz-ar-home]");
      if (e.target === current || e.target.closest("[data-cz-ar-close]")) close();
      // "Go Back" on the popup leaves the page the person was not allowed to use: it goes to their Dashboard (or Contacts),
      // unless they are already there. The X and a click outside only close the popup.
      if (home && home.dataset.home && new URL(home.dataset.home, window.location.origin).pathname !== window.location.pathname) {
        window.location.href = home.dataset.home;
      }
    });
    document.body.appendChild(current);
    document.body.classList.add("cz-ar-open");
    current.querySelector(".btn")?.focus();
  }

  // Capture phase on window: runs before Bootstrap and the module scripts, so the locked action never starts.
  window.addEventListener("click", (e) => {
    const locked = e.target.closest ? e.target.closest("[data-cz-locked]") : null;
    if (!locked) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    show(locked.dataset.czLocked || "");
  }, true);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });

  // The full page's Go Back goes to the person's Dashboard (or Contacts when they have none), not to the page they came from.
  document.addEventListener("click", (e) => {
    const back = e.target.closest ? e.target.closest("[data-cz-ar-back]") : null;
    if (!back) return;
    window.location.href = back.dataset.home || "/";
  });

  // A refused API call (any fetch on the page) shows the screen; the caller still gets its response untouched.
  if (typeof window.fetch === "function") {
    const nativeFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
      const res = await nativeFetch(...args);
      if (res.status === 403) {
        res.clone().json().then((body) => {
          if (body && (body.error === "forbidden" || body.error === "permission_denied")) show("");
        }).catch(() => {});
      }
      return res;
    };
  }

  const VERB = { create: "Create", update: "Edit", delete: "Delete", approve: "Approve" };

  /**
   * For markup built in JavaScript: the data-cz-locked attribute (with a leading space) when the user lacks the right,
   * so the control stays visible and a click opens the popup. `ability` is create | update | delete | approve.
   */
  function lockAttr(allowed, ability) {
    if (allowed) return "";
    const page = (document.querySelector(".cz-page-title")?.textContent || "").trim();
    const what = `${VERB[ability] || ability} ${page}`.trim().replace(/&/g, "&amp;").replace(/"/g, "&quot;").replace(/</g, "&lt;");
    return ` data-cz-locked="${what}"`;
  }

  return { show, close, lockAttr };
})();
window.CloudZoo.lockAttr = window.CloudZoo.accessDenied.lockAttr;

// ============================================================================
// Drag and drop ordering of table rows (masters, life cycle ...). A row carries a handle
//   <button type="button" class="cz-drag-handle" data-cz-drag>  (see CloudZoo.dragHandle())
// and CloudZoo.sortable(tbody, { rows: "tr", onChange }) makes its rows sortable. Works with mouse, pen and touch (pointer events);
// with the handle focused, the Up / Down arrow keys move the row too. Rows added later are covered (delegated).
// ============================================================================
window.CloudZoo.dragHandle = () => '<button type="button" class="cz-drag-handle" data-cz-drag title="Drag to change the order" aria-label="Drag to change the order (or press the Up and Down arrow keys)"><i class="bi bi-grip-vertical"></i></button>';

window.CloudZoo.sortable = (() => {
  const wired = new WeakSet();

  function sortable(container, { rows = "tr", onChange = () => {} } = {}) {
    if (!container || wired.has(container)) return;
    wired.add(container);
    const rowOf = (el) => { const r = el.closest(rows); return r && r.parentElement === container ? r : null; };
    const all = () => [...container.children].filter((c) => c.matches(rows));

    /**
     * FLIP: remember where every row is, let `change` move rows in the DOM, then slide each row that moved from its old place
     * to its new one. `skip` (the row held by the pointer) is positioned by the drag itself.
     */
    function animate(change, skip = null) {
      const list = all();
      const first = new Map(list.map((r) => [r, r.getBoundingClientRect().top]));
      change();
      list.forEach((r) => {
        if (r === skip) return;
        const delta = first.get(r) - r.getBoundingClientRect().top;
        if (!delta) return;
        r.classList.remove("cz-flip");
        r.style.transform = `translateY(${delta}px)`;
        void r.offsetHeight;                       // apply the starting position before the transition starts
        r.classList.add("cz-flip");
        r.style.transform = "";
      });
    }

    container.addEventListener("transitionend", (e) => { if (e.propertyName === "transform") e.target.classList?.remove("cz-flip"); });

    container.addEventListener("pointerdown", (e) => {
      const handle = e.target.closest("[data-cz-drag]");
      const row = handle && rowOf(handle);
      if (!row || (e.pointerType === "mouse" && e.button !== 0)) return;
      e.preventDefault();
      // The listeners are on the window, not on the handle: moving the row in the DOM drops a pointer capture held by an
      // element inside it, and the drop would then never be seen (the row stayed stuck).
      const before = all().indexOf(row);
      const grab = e.clientY - row.getBoundingClientRect().top;   // where in the row the pointer holds it
      let lastY = e.clientY;
      row.classList.remove("cz-flip");
      row.classList.add("cz-dragging");
      container.classList.add("cz-sorting");

      // the held row follows the pointer; its layout slot is wherever the other rows have left it
      const follow = () => {
        row.style.transform = "none";
        const slotTop = row.getBoundingClientRect().top;
        row.style.transform = `translateY(${lastY - grab - slotTop}px)`;
      };

      const move = (ev) => {
        if (ev.pointerId !== e.pointerId) return;
        if (ev.pointerType === "mouse" && ev.buttons === 0) { end(ev); return; }   // the button was released outside the window
        lastY = ev.clientY;
        const top = container.getBoundingClientRect().top;
        const mid = (r) => r.offsetTop + r.offsetHeight / 2 - container.offsetTop;   // layout position, unaffected by transforms
        const pointer = lastY - grab + row.offsetHeight / 2 - top;                    // middle of the held row
        const others = all().filter((r) => r !== row);
        const next = others.find((r) => pointer < mid(r));
        const inPlace = next ? row.nextElementSibling === next : container.lastElementChild === row;
        if (!inPlace) {
          row.style.transform = "none";
          animate(() => (next ? container.insertBefore(row, next) : container.appendChild(row)), row);
        }
        follow();
        // keep the row in view while dragging near the top or bottom of the window
        if (lastY < 60) window.scrollBy(0, -12); else if (lastY > window.innerHeight - 60) window.scrollBy(0, 12);
      };

      const end = (ev) => {
        if (ev && ev.pointerId !== undefined && ev.pointerId !== e.pointerId) return;
        window.removeEventListener("pointermove", move);
        window.removeEventListener("pointerup", end);
        window.removeEventListener("pointercancel", end);
        window.removeEventListener("blur", end);
        // settle into the slot
        row.classList.add("cz-flip");
        row.style.transform = "";
        row.classList.remove("cz-dragging");
        container.classList.remove("cz-sorting");
        if (all().indexOf(row) !== before) onChange(row);
      };
      window.addEventListener("pointermove", move);
      window.addEventListener("pointerup", end);
      window.addEventListener("pointercancel", end);
      window.addEventListener("blur", end);
      follow();
    });

    container.addEventListener("keydown", (e) => {
      if (e.key !== "ArrowUp" && e.key !== "ArrowDown") return;
      const handle = e.target.closest("[data-cz-drag]");
      const row = handle && rowOf(handle);
      if (!row) return;
      e.preventDefault();
      const list = all();
      const i = list.indexOf(row);
      if (e.key === "ArrowUp" && i > 0) animate(() => container.insertBefore(row, list[i - 1]));
      else if (e.key === "ArrowDown" && i < list.length - 1) animate(() => container.insertBefore(list[i + 1], row));
      else return;
      handle.focus();
      onChange(row);
    });
  }
  return sortable;
})();

// ============================================================================
// Page loader (markup #czPageLoader in includes/header.php, styles .cz-loader in cloudzoo.css)
//   - on every page it stays up until the page has loaded AND the requests it started on its own (the data of an edit
//     form, the lists of its dropdowns ...) have finished, then fades out
//   - when a link or form opens another page it comes back at once, so the click never looks ignored
//   - CloudZoo.loader.show("text") / hide() let a script use it too; data-cz-no-loader on a link or form opts out
// ============================================================================
window.CloudZoo.loader = (() => {
  const el = () => document.getElementById("czPageLoader");
  const MAX_BOOT_MS = 15000;     // never hold a page longer than this
  const QUIET_MS = 220;          // no request running for this long = the page has its data
  const NAV_FAILSAFE_MS = 10000; // a link that turned out to be a download never unloads the page
  let pending = 0;
  let booting = true;
  let quiet = null;
  let failsafe = null;

  // The first page after a sign-in is full screen (.is-first): rotating messages, then the rocket launches before it fades.
  const BOOT_MESSAGES = [
    ["Preparing your workspace...", "Panda is warming up the engines. \u{1F43C}"],
    ["Loading your organisation...", "Making sure everything is in the right place."],
    ["Loading your access and preferences...", "Setting things up just the way you need them."],
    ["Preparing frequently used data...", "Doing some heavy lifting now for a smoother ride."],
    ["Getting your dashboard ready...", "We're almost ready for takeoff."],
    ["Warming things up...", "A few final checks before launch."],
    ["Almost ready...", "Panda says all systems are looking good. \u{1F680}"],
  ];
  const MIN_FIRST_MS = 1800;     // let the full screen loader be seen
  const bornAt = Date.now();
  let messageTimer = null;
  let launching = false;

  function setMessage(status, small) {
    const e = el();
    const a = e && e.querySelector("[data-cz-loader-status]");
    const b = e && e.querySelector("[data-cz-loader-small]");
    [a, b].forEach((n) => n && (n.style.opacity = "0"));
    setTimeout(() => {
      if (a) { a.textContent = status; a.style.opacity = "1"; }
      if (b) { b.textContent = small; b.style.opacity = "1"; }
    }, 180);
  }

  function startMessages() {
    const e = el();
    if (!e || !e.classList.contains("is-first") || messageTimer) return;
    let n = 0;
    messageTimer = setInterval(() => {
      n = Math.min(n + 1, BOOT_MESSAGES.length - 1);
      setMessage(...BOOT_MESSAGES[n]);
      if (n === BOOT_MESSAGES.length - 1) { clearInterval(messageTimer); messageTimer = null; }
    }, 1900);
  }

  function show(text = "Loading") {
    const e = el();
    if (!e) return;
    e.classList.add("is-managed");
    const t = e.querySelector("[data-cz-loader-text]");
    if (t && !e.classList.contains("is-first")) t.textContent = text;
    e.classList.remove("is-hidden");
    e.setAttribute("aria-busy", "true");
  }

  function hide() {
    const e = el();
    clearTimeout(failsafe);
    if (!e) return;
    e.classList.add("is-managed");
    if (e.classList.contains("is-first") && !e.classList.contains("is-hidden")) {
      if (launching) return;
      launching = true;
      const wait = Math.max(0, MIN_FIRST_MS - (Date.now() - bornAt));
      setTimeout(() => {
        clearInterval(messageTimer); messageTimer = null;
        const a = e.querySelector("[data-cz-loader-status]"), b = e.querySelector("[data-cz-loader-small]");
        if (a) a.textContent = "Everything is ready!";
        if (b) b.textContent = "Let's go! \u{1F680}";
        e.classList.add("is-ready");
        setTimeout(() => {
          e.classList.add("is-hidden");
          e.setAttribute("aria-busy", "false");
          // from now on the loader is the compact one
          setTimeout(() => { e.classList.remove("is-first", "is-ready"); launching = false; }, 600);
        }, 850);
      }, wait);
      return;
    }
    e.classList.add("is-hidden");
    e.setAttribute("aria-busy", "false");
  }

  // ---- the page itself: wait for the load event and then for a quiet moment without requests
  function settle() {
    clearTimeout(quiet);
    if (!booting) return;
    quiet = setTimeout(() => { if (pending === 0) { booting = false; hide(); } else settle(); }, QUIET_MS);
  }
  function track(delta) {
    pending = Math.max(0, pending + delta);
    if (booting && delta < 0 && pending === 0 && document.readyState === "complete") settle();
  }

  if (typeof window.fetch === "function") {
    const nativeFetch = window.fetch.bind(window);
    window.fetch = (...args) => {
      track(1);
      return nativeFetch(...args).then((r) => { track(-1); return r; }, (err) => { track(-1); throw err; });
    };
  }
  if (window.XMLHttpRequest) {
    const nativeSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function (...args) {
      track(1);
      this.addEventListener("loadend", () => track(-1), { once: true });
      return nativeSend.apply(this, args);
    };
  }

  startMessages();
  const start = () => settle();
  if (document.readyState === "complete") start(); else window.addEventListener("load", start, { once: true });
  setTimeout(() => { booting = false; hide(); }, MAX_BOOT_MS);

  // ---- opening another page: show it straight away
  function leaving(text) {
    show(text);
    clearTimeout(failsafe);
    failsafe = setTimeout(hide, NAV_FAILSAFE_MS);
  }

  document.addEventListener("click", (e) => {
    const a = e.target.closest ? e.target.closest("a[href]") : null;
    if (!a || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    // let the page's own handlers run first; they may cancel the click
    setTimeout(() => {
      if (e.defaultPrevented) return;
      const href = a.getAttribute("href") || "";
      if (!href || href.startsWith("#") || /^(javascript|mailto|tel|data|blob):/i.test(href)) return;
      if (a.target && a.target !== "_self") return;
      if (a.hasAttribute("download") || a.hasAttribute("data-cz-no-loader") || a.dataset.bsToggle || a.dataset.czLocked !== undefined || a.closest("[data-cz-locked]")) return;
      let url;
      try { url = new URL(a.href, window.location.href); } catch (err) { return; }
      if (url.origin !== window.location.origin) return;
      if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;   // an anchor on this page
      if (/\.(pdf|xlsx?|csv|zip|docx?|png|jpe?g|webp|gif)$/i.test(url.pathname)) return;                          // files are not pages
      leaving("Opening");
    }, 0);
  });

  document.addEventListener("submit", (e) => {
    const f = e.target;
    setTimeout(() => {
      if (e.defaultPrevented || !f.matches || f.hasAttribute("data-cz-no-loader") || (f.target && f.target !== "_self")) return;
      leaving((f.method || "get").toLowerCase() === "get" ? "Loading" : "Saving");
    }, 0);
  });

  // coming back with the browser's Back button restores the page from memory: make sure the loader is not left up
  window.addEventListener("pageshow", (e) => { if (e.persisted) { booting = false; hide(); } });

  return { show, hide };
})();

// ---------------------------------------------------------------------------
// Report helpers: download a table as CSV (opens in Excel), and a read-only dialog (details / a card to print)
// ---------------------------------------------------------------------------
/** Download a <table> (header, body and footer rows, as shown) as a CSV file. */
window.CloudZoo.tableToCsv = function (table, filename = "report") {
  if (!table) return;
  const cell = (c) => `"${String(c.innerText || "").replace(/\s+/g, " ").trim().replace(/"/g, '""')}"`;
  const lines = [...table.querySelectorAll("tr")].filter((tr) => !tr.hasAttribute("data-cz-no-export"))
    .map((tr) => [...tr.children].flatMap((c) => [cell(c), ...Array(Math.max(0, (+c.colSpan || 1) - 1)).fill('""')]).join(","));
  const blob = new Blob(["﻿" + lines.join("\r\n")], { type: "text/csv;charset=utf-8" });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(blob);
  a.download = `${String(filename).replace(/[^\w.-]+/g, "-")}.csv`;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 0);
};

/**
 * Build an Excel workbook (.xlsx, one sheet) and return its bytes. A CSV cannot carry colours; this can.
 *   rows    each row is a list of cells: { v: text or number, s: style } or null for an empty cell
 *   styles  "head" (white on dark blue, bold), "text", "num" (1,234.50), "total" / "totalNum" (bold on light blue), "title" (bold, no border)
 *   merges  [first row, first column, last row, last column], counted from 0
 *   widths  column widths in characters
 * The file is a plain (uncompressed) zip of the workbook's XML parts, so no library is needed.
 */
window.CloudZoo.xlsxFile = function (rows, { merges = [], widths = [], sheet = "Report" } = {}) {
  const STYLE = { text: 2, head: 1, num: 3, total: 4, totalNum: 5, title: 6 };
  const xml = (v) => String(v ?? "").replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c])).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, "");
  const col = (i) => { let s = ""; for (i += 1; i > 0; i = Math.floor((i - 1) / 26)) s = String.fromCharCode(65 + ((i - 1) % 26)) + s; return s; };
  const HEAD = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n';
  const NS = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
  const REL = "http://schemas.openxmlformats.org/officeDocument/2006/relationships";
  const PKG = "http://schemas.openxmlformats.org/package/2006";

  const data = rows.map((cells, r) => `<row r="${r + 1}">${cells.map((c, i) => {
    if (!c) return "";
    const at = `r="${col(i)}${r + 1}" s="${STYLE[c.s] ?? STYLE.text}"`;
    if (typeof c.v === "number" && Number.isFinite(c.v)) return `<c ${at}><v>${c.v}</v></c>`;
    return c.v === "" || c.v == null ? `<c ${at}/>` : `<c ${at} t="inlineStr"><is><t xml:space="preserve">${xml(c.v)}</t></is></c>`;
  }).join("")}</row>`).join("");
  const border = '<border><left style="thin"><color rgb="FFBFBFBF"/></left><right style="thin"><color rgb="FFBFBFBF"/></right><top style="thin"><color rgb="FFBFBFBF"/></top><bottom style="thin"><color rgb="FFBFBFBF"/></bottom><diagonal/></border>';
  const fill = (rgb) => `<fill><patternFill patternType="solid"><fgColor rgb="${rgb}"/><bgColor indexed="64"/></patternFill></fill>`;
  const xf = (font, fillId, borderId, numFmt, align) => `<xf numFmtId="${numFmt}" fontId="${font}" fillId="${fillId}" borderId="${borderId}" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyNumberFormat="1" applyAlignment="1">${align}</xf>`;
  const wrap = '<alignment vertical="top" wrapText="1"/>';
  const right = '<alignment horizontal="right" vertical="top"/>';

  const parts = {
    "[Content_Types].xml": `${HEAD}<Types xmlns="${PKG}/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>` +
      '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
      '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
      '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
    "_rels/.rels": `${HEAD}<Relationships xmlns="${PKG}/relationships"><Relationship Id="rId1" Type="${REL}/officeDocument" Target="xl/workbook.xml"/></Relationships>`,
    "xl/workbook.xml": `${HEAD}<workbook xmlns="${NS}" xmlns:r="${REL}"><sheets><sheet name="${xml(String(sheet).replace(/[\\/?*[\]:]/g, " ").slice(0, 31) || "Report")}" sheetId="1" r:id="rId1"/></sheets></workbook>`,
    "xl/_rels/workbook.xml.rels": `${HEAD}<Relationships xmlns="${PKG}/relationships"><Relationship Id="rId1" Type="${REL}/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="${REL}/styles" Target="styles.xml"/></Relationships>`,
    "xl/styles.xml": `${HEAD}<styleSheet xmlns="${NS}">` +
      '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>' +
      `<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>${fill("FF1F3864")}${fill("FFDDEBF7")}</fills>` +
      `<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>${border}</borders>` +
      '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
      `<cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>${xf(1, 2, 1, 0, '<alignment horizontal="center" vertical="center" wrapText="1"/>')}${xf(0, 0, 1, 0, wrap)}${xf(0, 0, 1, 4, right)}` +
      `${xf(2, 3, 1, 0, wrap)}${xf(2, 3, 1, 4, right)}${xf(2, 0, 0, 0, '<alignment vertical="center"/>')}</cellXfs></styleSheet>`,
    "xl/worksheets/sheet1.xml": `${HEAD}<worksheet xmlns="${NS}"><sheetViews><sheetView workbookViewId="0"/></sheetViews>` +
      (widths.length ? `<cols>${widths.map((w, i) => `<col min="${i + 1}" max="${i + 1}" width="${w}" customWidth="1"/>`).join("")}</cols>` : "") +
      `<sheetData>${data}</sheetData>` +
      (merges.length ? `<mergeCells count="${merges.length}">${merges.map(([r1, c1, r2, c2]) => `<mergeCell ref="${col(c1)}${r1 + 1}:${col(c2)}${r2 + 1}"/>`).join("")}</mergeCells>` : "") + "</worksheet>",
  };

  // a zip with stored (uncompressed) entries: local headers, then the central directory
  const crcTable = Array.from({ length: 256 }, (_, n) => { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1; return c >>> 0; });
  const crc32 = (bytes) => { let c = 0xFFFFFFFF; for (const b of bytes) c = crcTable[(c ^ b) & 0xFF] ^ (c >>> 8); return (c ^ 0xFFFFFFFF) >>> 0; };
  const enc = new TextEncoder();
  const u16 = (n) => [n & 0xFF, (n >>> 8) & 0xFF];
  const u32 = (n) => [n & 0xFF, (n >>> 8) & 0xFF, (n >>> 16) & 0xFF, (n >>> 24) & 0xFF];
  const chunks = [];
  const central = [];
  let offset = 0;
  Object.entries(parts).forEach(([name, text]) => {
    const file = enc.encode(text);
    const path = enc.encode(name);
    const meta = [...u16(20), ...u16(0x0800), ...u16(0), ...u16(0), ...u16(0x21), ...u32(crc32(file)), ...u32(file.length), ...u32(file.length), ...u16(path.length), ...u16(0)];
    const local = new Uint8Array([0x50, 0x4B, 0x03, 0x04, ...meta, ...path]);
    central.push(new Uint8Array([0x50, 0x4B, 0x01, 0x02, ...u16(20), ...meta, ...u16(0), ...u16(0), ...u16(0), ...u32(0), ...u32(offset), ...path]));
    chunks.push(local, file);
    offset += local.length + file.length;
  });
  const size = central.reduce((n, c) => n + c.length, 0);
  const end = new Uint8Array([0x50, 0x4B, 0x05, 0x06, ...u16(0), ...u16(0), ...u16(central.length), ...u16(central.length), ...u32(size), ...u32(offset), ...u16(0)]);
  const out = new Uint8Array(offset + size + end.length);
  let at = 0;
  [...chunks, ...central, end].forEach((c) => { out.set(c, at); at += c.length; });
  return out;
};

/**
 * Download one or more <table>s as an Excel file with a blue header row, borders, amounts as numbers and shaded total rows.
 *   sections  a table, or a list of tables / { title, table }: each goes under the one before, with its title above it
 *   titles    lines written above everything (the report name, who it is for, the period)
 * A header row is a row of <thead>; a total row is a row of <tfoot> or a body row marked data-cz-export="total". A cell is a
 * number when it is right aligned (text-end) and holds only an amount. Rows marked data-cz-no-export are left out.
 */
window.CloudZoo.tableToXlsx = function (sections, filename = "report", { titles = [], sheet = "Report" } = {}) {
  const list = [].concat(sections || []).map((s) => (s instanceof Element ? { title: "", table: s } : s)).filter((s) => s && s.table);
  if (!list.length) return;
  const rows = [];
  const merges = [];
  const widths = [];
  const text = (el) => String(el.innerText || "").replace(/[ \t ]+/g, " ").replace(/ ?\n ?/g, "\n").replace(/\n{2,}/g, "\n").trim();
  const wide = (i, v) => { widths[i] = Math.min(60, Math.max(widths[i] || 10, ...String(v).split("\n").map((l) => l.length + 3))); };
  const columns = Math.max(1, ...list.map((s) => Math.max(0, ...[...s.table.rows].map((tr) => [...tr.cells].reduce((n, c) => n + (+c.colSpan || 1), 0)))));
  const heading = (line) => {
    rows.push([{ v: line, s: "title" }]);
    if (columns > 1) merges.push([rows.length - 1, 0, rows.length - 1, columns - 1]);
  };

  titles.filter(Boolean).forEach(heading);
  list.forEach((section, n) => {
    if (rows.length) rows.push([]);
    if (section.title) heading(section.title);
    const t = section.table;
    const groups = [[t.tHead ? [...t.tHead.rows] : [], "head"], [[...t.tBodies].flatMap((b) => [...b.rows]), "body"], [t.tFoot ? [...t.tFoot.rows] : [], "total"]];
    groups.forEach(([trs, kind]) => trs.filter((tr) => !tr.hasAttribute("data-cz-no-export")).forEach((tr) => {
      const total = kind === "total" || tr.dataset.czExport === "total";
      const cells = [];
      [...tr.cells].forEach((td) => {
        const value = text(td);
        const amount = td.classList.contains("text-end") && /^-?[\d,]+(\.\d+)?$/.test(value) ? parseFloat(value.replace(/,/g, "")) : null;
        const style = kind === "head" ? "head" : amount !== null ? (total ? "totalNum" : "num") : total ? "total" : "text";
        const span = +td.colSpan || 1;
        const first = cells.length;
        cells.push({ v: amount !== null ? amount : value, s: style });
        for (let i = 1; i < span; i++) cells.push({ v: "", s: style });
        if (span > 1) merges.push([rows.length, first, rows.length, first + span - 1]);
        else wide(first, amount !== null ? amount.toFixed(2) + ",,," : value);
      });
      rows.push(cells);
    }));
  });

  const bytes = window.CloudZoo.xlsxFile(rows, { merges, widths: Array.from({ length: columns }, (_, i) => widths[i] || 12), sheet });
  const a = document.createElement("a");
  a.href = URL.createObjectURL(new Blob([bytes], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" }));
  a.download = `${String(filename).replace(/[^\w.-]+/g, "-")}.xlsx`;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 0);
};

/** A read-only dialog with HTML content; print: true adds a Print button that prints the dialog alone. */
window.CloudZoo.infoModal = function ({ title = "", html = "", size = "modal-xl", print = false } = {}) {
  document.getElementById("czInfoModal")?.remove();
  const esc = (v) => String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[c]));
  const el = document.createElement("div");
  el.className = "modal fade";
  el.id = "czInfoModal";
  el.tabIndex = -1;
  el.setAttribute("aria-labelledby", "czInfoModalTitle");
  el.innerHTML = `<div class="modal-dialog modal-dialog-scrollable ${size}"><div class="modal-content border-0 shadow-sm">
      <div class="modal-header border-bottom"><h5 class="modal-title fs-6 fw-semibold" id="czInfoModalTitle">${esc(title)}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body" data-cz-info-body>${html}</div>
      <div class="modal-footer border-top bg-light">${print ? '<button type="button" class="btn btn-outline-secondary btn-sm" data-cz-info-print><i class="bi bi-printer me-1"></i> Print</button>' : ""}
        <button type="button" class="btn btn-dark btn-sm" data-bs-dismiss="modal">Close</button></div>
    </div></div>`;
  document.body.appendChild(el);
  el.querySelector("[data-cz-info-print]")?.addEventListener("click", () => {
    document.body.classList.add("cz-print-modal");
    window.print();
    document.body.classList.remove("cz-print-modal");
  });
  el.addEventListener("hidden.bs.modal", () => el.remove());
  bootstrap.Modal.getOrCreateInstance(el).show();
  return el;
};

// Field errors, on every form: the field is outlined in red and the message is its tooltip, never text under it.
// A message tied to a field (Bootstrap's .invalid-feedback, or .cz-field-error[data-cz-error-for] naming a field) is
// kept only for screen readers (visually-hidden) and, while it is shown, marks its field .is-invalid. A message with no
// field of its own (e.g. "Add at least one item row") stays visible. Select2 dropdowns get the red border too.
(() => {
  "use strict";
  const BOX = ".invalid-feedback, .cz-field-error[data-cz-error-for]";

  function fieldOf(box) {
    const name = box.dataset.czErrorFor;
    if (name === undefined) return box.parentElement?.querySelector("input:not([type=hidden]), select, textarea") || null;
    const scope = box.closest("form") || document;
    return scope.querySelector(`[name="${CSS.escape(name)}"]`) || document.getElementById(name);
  }
  function select2Mark(select) {
    const sel = select.nextElementSibling?.classList.contains("select2-container") ? select.nextElementSibling.querySelector(".select2-selection") : null;
    if (sel) sel.classList.toggle("border-danger", select.classList.contains("is-invalid"));
  }
  function sync(box) {
    const field = fieldOf(box);
    if (!field) return;   // no field of its own: the text stays
    if (!box.classList.contains("visually-hidden")) box.classList.add("visually-hidden");
    if (!box.matches(".cz-field-error")) return;   // .invalid-feedback follows the field's own .is-invalid
    const msg = box.classList.contains("d-none") ? "" : box.textContent.trim();
    if (msg) {
      if (!field.classList.contains("is-invalid")) { field.classList.add("is-invalid"); field.dataset.czAutoInvalid = "1"; }
      if (field.dataset.czTitle === undefined) field.dataset.czTitle = field.getAttribute("title") || "";
      field.title = msg;
    } else if (field.dataset.czTitle !== undefined) {
      if (field.dataset.czAutoInvalid) { field.classList.remove("is-invalid"); delete field.dataset.czAutoInvalid; }
      field.title = field.dataset.czTitle;
      delete field.dataset.czTitle;
    }
  }
  function scan(node) {
    if (node.nodeType !== 1) return;
    if (node.matches(BOX)) sync(node);
    node.querySelectorAll(BOX).forEach(sync);
    if (node.matches("select")) select2Mark(node);
    node.querySelectorAll("select").forEach(select2Mark);
  }

  scan(document.body);
  new MutationObserver((records) => {
    records.forEach((r) => {
      if (r.type === "childList") {
        r.addedNodes.forEach(scan);
        const box = r.target.nodeType === 1 ? r.target.closest(BOX) : null;
        if (box) sync(box);
        if (r.target.matches?.("select")) select2Mark(r.target);
        r.addedNodes.forEach((n) => { if (n.classList?.contains("select2-container") && n.previousElementSibling?.matches("select")) select2Mark(n.previousElementSibling); });
      } else if (r.target.matches(BOX)) {
        sync(r.target);
      } else if (r.target.matches("select")) {
        select2Mark(r.target);
      }
    });
  }).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ["class"] });
})();

// ============================================================================
// Master page opened from a form's "+" (?navigate=1): after a save it closes its own tab a second later, so the user is
// back on the form, whose list reloads (export-erp bank-my.php / chart-of-acc-my.php with navigate_*=true).
// ============================================================================
window.CloudZoo.masterTab = {
  /** The list URL to go to after a save: it carries saved=1 when the page was opened from a form. */
  afterSaveUrl(listUrl) {
    return /[?&]navigate=1(&|$)/.test(listUrl) ? listUrl + "&saved=1" : listUrl;
  },
  /** Was this page opened from a form, and just saved? */
  shouldClose(search) {
    const p = new URLSearchParams(search || "");
    return p.get("navigate") === "1" && p.get("saved") === "1";
  },
  /** Show a note, then close the tab; a browser that refuses to close it leaves the note telling the user to. */
  closeSoon(noteEl) {
    if (noteEl) { noteEl.textContent = "Saved. This tab closes in a moment - the form you came from reloads its list."; noteEl.classList.remove("d-none"); }
    setTimeout(() => {
      window.close();
      setTimeout(() => { if (noteEl) noteEl.textContent = "Saved. You can close this tab and go back to the form - its list reloads by itself."; }, 400);
    }, 1000);
  },
};

// Forms may declare a per-file limit, including dynamically added attachment inputs.
// Common attachment actions for stored files and files selected before saving.
/**
 * Delete button for a saved attachment row. It asks first, then hides the row and marks the button
 * data-cz-file-removed; the form sends the marked ids on Save (the file is deleted with the save).
 * attrs: the module's own data attribute holding the attachment id, e.g. data-jw-remove-file="12".
 */
window.CloudZoo.attachmentDelete = function (name, attrs) {
  const esc = (v) => String(v || "").replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  return `<button type="button" class="btn btn-outline-danger btn-sm" data-cz-file-delete data-cz-file-name="${esc(name)}" ${attrs} title="Delete" aria-label="Delete ${esc(name)}"><i class="bi bi-trash3"></i></button>`;
};
document.addEventListener("click", (event) => {
  const del = event.target.closest('[data-cz-file-delete]');
  if (!del || del.disabled) return;
  if (!window.confirm(`Delete "${del.dataset.czFileName || 'this attachment'}"? It is removed when you save this document.`)) return;
  del.dataset.czFileRemoved = '1';
  del.closest('tr,[data-cz-file-row]')?.classList.add('d-none');
});
window.CloudZoo.attachmentActions = function (url, downloadUrl, name) {
  const esc = (v) => String(v || "").replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  return `<button type="button" class="btn btn-light border btn-sm" data-cz-file-preview="${esc(url)}" data-cz-file-name="${esc(name)}" data-cz-file-download="${esc(downloadUrl)}" title="Preview" aria-label="Preview ${esc(name)}"><i class="bi bi-eye"></i></button> <a class="btn btn-light border btn-sm" href="${esc(downloadUrl)}" download="${esc(name)}" title="Download" aria-label="Download ${esc(name)}"><i class="bi bi-download"></i></a>`;
};
document.addEventListener("click", (event) => {
  const add = event.target.closest('[data-cz-multi-add]');
  if (add) {
    add.closest('[data-cz-multi-attachment]').querySelector('[data-cz-multi-rows]').insertAdjacentHTML('beforeend', `<tr data-cz-multi-row><td><input class="form-control form-control-sm" maxlength="255" placeholder="Description" aria-label="Attachment description" data-cz-multi-description></td><td><input class="form-control form-control-sm" type="file" aria-label="Attachment file" accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx" data-cz-multi-file></td><td class="text-end text-nowrap"><span data-cz-local-actions></span> <button type="button" class="btn btn-light border btn-sm text-danger" data-cz-multi-remove aria-label="Remove attachment" title="Remove"><i class="bi bi-trash3"></i></button></td></tr>`);
  }
  if (add) {
    const owner=add.closest('[data-cz-multi-attachment]');
    if(owner.dataset.czFileAccept) owner.querySelector('[data-cz-multi-rows] tr:last-child [data-cz-multi-file]').accept=owner.dataset.czFileAccept;
  }
  const remove = event.target.closest('[data-cz-multi-remove]');
  if (remove) {
    const row = remove.closest('tr');
    if (row.dataset.localUrl) URL.revokeObjectURL(row.dataset.localUrl);
    row.remove();
  }
  const preview = event.target.closest('[data-cz-file-preview]');
  if (!preview) return;
  event.preventDefault();
  const name = preview.dataset.czFileName;
  const ext = name.split('.').pop().toLowerCase();
  const modal = window.CloudZoo.infoModal({title: name, html: '<div class="cz-attach-preview" data-cz-file-content></div>'});
  const body = modal.querySelector('[data-cz-file-content]');
  if (['jpg','jpeg','png','webp','gif','bmp'].includes(ext)) {
    const img = document.createElement('img'); img.src = preview.dataset.czFilePreview; img.alt = name; body.appendChild(img);
  } else if (['pdf','txt','csv'].includes(ext)) {
    const frame = document.createElement('iframe'); frame.src = preview.dataset.czFilePreview; frame.title = name;
    // Chromium's built-in PDF viewer cannot run inside a sandboxed frame.
    // Text previews stay sandboxed; PDFs are served by the attachment endpoint.
    if (ext !== 'pdf') frame.setAttribute('sandbox', 'allow-same-origin');
    body.appendChild(frame);
  } else body.textContent = 'Preview is unavailable for this file type. Use Download to open it.';
  const link = document.createElement('a'); link.className = 'btn btn-outline-secondary btn-sm'; link.href = preview.dataset.czFileDownload; link.download = name; link.textContent = 'Download';
  modal.querySelector('.modal-footer').prepend(link);
});
document.addEventListener('change', (event) => {
  const input = event.target;
  if (!input.matches('[data-cz-multi-file], [data-cz-attach-file]')) return;
  const row = input.closest('tr');
  if (!row || row.querySelector('td[data-cz-attach-preview]')) return;
  let actions = row.querySelector('[data-cz-local-actions]');
  if (!actions) { actions = document.createElement('span'); actions.dataset.czLocalActions = ''; row.lastElementChild.prepend(actions); }
  if (row.dataset.localUrl) URL.revokeObjectURL(row.dataset.localUrl);
  const maxBytes = Number(input.closest('[data-cz-file-max-bytes]')?.dataset.czFileMaxBytes || 0);
  const file = input.files[0] && (!maxBytes || input.files[0].size <= maxBytes) ? input.files[0] : null;
  row.dataset.localUrl = file ? URL.createObjectURL(file) : '';
  actions.innerHTML = file ? window.CloudZoo.attachmentActions(row.dataset.localUrl, row.dataset.localUrl, file.name) + ' ' : '';
});
document.addEventListener("change", (event) => {
  const input = event.target;
  if (!(input instanceof HTMLInputElement) || input.type !== "file") return;
  const owner = input.closest("[data-cz-file-max-bytes]");
  if (!owner) return;
  const maxBytes = Number(owner.dataset.czFileMaxBytes);
  if (maxBytes > 0 && Array.from(input.files || []).some((file) => file.size > maxBytes)) {
    input.value = "";
    window.alert(`Attachments must be ${maxBytes / 1024 / 1024} MB or smaller.`);
  }
});

// Modern Logout Confirmation Modal Trigger
document.addEventListener("click", (e) => {
  const trigger = e.target.closest('[data-cz-logout], .cz-profile-menu a[href*="/logout"]');
  if (!trigger) return;

  const modalEl = document.getElementById("czLogoutModal");
  if (!modalEl || !window.bootstrap) return; // Fall back to direct link navigation if modal not rendered

  e.preventDefault();

  // Close open profile dropdown if active
  const openDropdown = trigger.closest(".dropdown");
  if (openDropdown && window.bootstrap.Dropdown) {
    const dropdownToggle = openDropdown.querySelector('[data-bs-toggle="dropdown"]');
    if (dropdownToggle) {
      const dd = window.bootstrap.Dropdown.getInstance(dropdownToggle);
      if (dd) dd.hide();
    }
  }

  // Sync confirmation button destination URL if modified
  const confirmBtn = modalEl.querySelector("#czConfirmLogoutBtn");
  if (confirmBtn && trigger.getAttribute("href")) {
    confirmBtn.setAttribute("href", trigger.getAttribute("href"));
  }

  const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
  modalInstance.show();
});

// A print form (cz-print-sheet): its Print button prints the page; the app chrome is hidden by cloudzoo.css @media print
// List rows expose printing beside their action menu, including rows loaded by Ajax.
(() => {
  const enhance = (row) => {
    if (!row.matches('tbody tr') || row.closest('.modal, form, .cz-print-sheet, [data-cz-no-row-print]')) return;
    const cells = [...row.cells];
    const cell = cells[cells.length - 1];
    if (!cell || cell.querySelector('[data-cz-row-print]')) return;
    const menu = cell.querySelector('[data-bs-toggle="dropdown"]');
    if (!menu) return;
    // Keep a module's existing visible print control when one is already present.
    if ([...cell.querySelectorAll('.bi-printer')].some(icon => !icon.closest('.dropdown-menu'))) return;
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'cz-icon-btn light me-1';
    button.dataset.czRowPrint = ''; button.title = 'Print'; button.setAttribute('aria-label', 'Print record');
    button.innerHTML = '<i class="bi bi-printer" aria-hidden="true"></i>';
    const dropdown = menu.closest('.dropdown') || menu;
    dropdown.classList.add('d-inline-block');
    cell.classList.add('text-nowrap');
    dropdown.before(button);
  };
  const scan = (node) => {
    if (node.nodeType !== 1) return;
    if (node.matches('tr')) enhance(node);
    node.querySelectorAll('tbody tr').forEach(enhance);
    const row = node.closest('tbody tr'); if (row) enhance(row);
  };
  const start = () => {
    scan(document.body);
    new MutationObserver(records => records.forEach(r => r.addedNodes.forEach(scan)))
      .observe(document.body, {childList: true, subtree: true});
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-cz-row-print]');
    if (!button) return;
    event.preventDefault(); event.stopPropagation();
    const row = button.closest('tr');
    const jobwork=row.closest('[data-cz-dyeing-jobwork="list"]');
    if(jobwork && ['grn','inward','delivery'].includes(jobwork.dataset.kind)) {
      const record=row.querySelector('[data-jw-check]')?.dataset.jwCheck;
      if(record) { window.open(`${window.CZ_BASE_URL || ''}/jobwork/print-document?kind=${encodeURIComponent(jobwork.dataset.kind)}&id=${encodeURIComponent(record)}`,'_blank','noopener'); return; }
    }
    const existing = [...row.querySelectorAll('.dropdown-menu a, .dropdown-menu button')]
      .find(action => action.querySelector('.bi-printer') || action.textContent.trim().toLowerCase() === 'print');
    if (existing) { existing.click(); return; }
    if (row.dispatchEvent(new CustomEvent('cz:print-record', {bubbles: true, cancelable: true})) === false) return;
    const esc = value => String(value || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const table = row.closest('table');
    const headers = [...(table.tHead?.rows[table.tHead.rows.length - 1]?.cells || [])];
    const values = [...row.cells].map((cell, i) => {
      const label = headers[i]?.textContent.trim();
      if (!label || cell.contains(button) || /^(actions?|select|delete|remove)$/i.test(label)) return '';
      return `<tr><th scope="row">${esc(label)}</th><td>${esc(cell.innerText.trim())}</td></tr>`;
    }).join('');
    const title = document.querySelector('h1')?.textContent.trim() || 'Record';
    window.CloudZoo.infoModal({title, html: `<h2 class="fs-5 mb-3">${esc(title)}</h2><table class="table cz-table"><tbody>${values}</tbody></table>`, print: true});
  }, true);
})();
document.addEventListener("click", (e) => {
  if (e.target.closest("[data-cz-print-page]")) window.print();
});
// No browser suggestions: the browser's own list of earlier entries (and its address autofill) is switched off for every
// text box of the ERP. A field that asks for something itself (autocomplete="...") and the sign-in forms (any form with a
// password box) are left alone. Done when a field takes focus too, so rows and dialogs added later are covered.
(() => {
  const TYPES = ["text", "search", "tel", "email", "url", "number"];
  const quiet = (el) => {
    if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) return;
    if (el instanceof HTMLInputElement && !TYPES.includes(el.type)) return;
    if (el.hasAttribute("autocomplete") && el.getAttribute("autocomplete") !== "on") return;
    if (el.form?.querySelector('input[type="password"]')) return;
    el.setAttribute("autocomplete", "off");
  };
  const sweep = () => document.querySelectorAll("input, textarea").forEach(quiet);
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", sweep); else sweep();
  document.addEventListener("focusin", (e) => quiet(e.target), true);
})();
// Navigation page Smart Search: the box filters the module cards (data-cz-card, matched on data-cz-card-text) as you type,
// hides a card group (data-cz-card-group) with no card left and shows data-cz-card-empty when nothing matches; Esc clears
// it and closes the search (#czSmartSearch). A box marked data-cz-card-search="auto" is started here; another page can
// call CloudZoo.cardSearch() itself.
window.CloudZoo.cardSearch = function (input = document.querySelector("[data-cz-card-search]")) {
  const box = document.getElementById("czSmartSearch");
  if (!input || !box || input.dataset.czCardSearchBound) return;
  input.dataset.czCardSearchBound = "1";
  box.addEventListener("shown.bs.collapse", () => input.focus());
  input.addEventListener("input", () => {
    const q = input.value.trim().toLowerCase();
    let shown = 0;
    document.querySelectorAll("[data-cz-card]").forEach((card) => {
      const on = !q || (card.dataset.czCardText || "").includes(q);
      card.hidden = !on;
      if (on) shown++;
    });
    document.querySelectorAll("[data-cz-card-group]").forEach((group) => {
      group.hidden = !group.querySelector("[data-cz-card]:not([hidden])");
    });
    const empty = document.querySelector("[data-cz-card-empty]");
    if (empty) empty.hidden = shown > 0;
  });
  input.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    input.value = "";
    input.dispatchEvent(new Event("input"));
    window.bootstrap?.Collapse.getOrCreateInstance(box).hide();
  });
};
(() => {
  const start = () => document.querySelectorAll('[data-cz-card-search="auto"]').forEach((el) => window.CloudZoo.cardSearch(el));
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start); else start();
})();
// Type-ahead text box (legacy jQuery UI autocomplete of the SKU and similar fields): CloudZoo.autocomplete(input, {
//   source: () => [{ value, label, meta }]   the items (called on every keystroke, so it can follow another field)
//   onPick: (item) => {}                    an item chosen with the mouse or Enter
//   limit: 50, minChars: 0 })
// The list matches what is typed anywhere in the value or label (any case), opens under the box in a fixed panel (a scrolling table
// does not clip it), and works with Up / Down / Enter / Esc. The box keeps free text: the page decides what an unknown value means.
window.CloudZoo.autocomplete = function (input, { source, onPick, limit = 50, minChars = 0 } = {}) {
  if (!input || input.dataset.czAutocomplete) return;
  input.dataset.czAutocomplete = "1";
  input.setAttribute("autocomplete", "off");
  input.setAttribute("role", "combobox");
  input.setAttribute("aria-autocomplete", "list");
  input.setAttribute("aria-expanded", "false");
  const esc = (v) => String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[c]));
  const menu = document.createElement("div");
  menu.className = "cz-autocomplete d-none";
  menu.id = `czAc${Math.random().toString(36).slice(2, 9)}`;
  menu.setAttribute("role", "listbox");
  input.setAttribute("aria-controls", menu.id);
  document.body.appendChild(menu);
  let items = [];
  let active = -1;

  const place = () => {
    const r = input.getBoundingClientRect();
    menu.style.setProperty("--cz-ac-top", `${r.bottom + 4}px`);
    menu.style.setProperty("--cz-ac-left", `${r.left}px`);
    menu.style.setProperty("--cz-ac-width", `${Math.max(r.width, 260)}px`);
  };
  const close = () => { menu.classList.add("d-none"); input.setAttribute("aria-expanded", "false"); active = -1; };
  const mark = (i) => {
    active = i;
    [...menu.children].forEach((el, n) => el.classList.toggle("is-highlighted", n === i));
    menu.children[i]?.scrollIntoView({ block: "nearest" });
  };
  const open = () => {
    const q = input.value.trim().toLowerCase();
    if (q.length < minChars) { close(); return; }
    const all = (source?.() || []);
    items = all.filter((it) => !q || `${it.value} ${it.label || ""}`.toLowerCase().includes(q)).slice(0, limit);
    menu.innerHTML = items.length
      ? items.map((it, i) => `<div class="cz-select__option" role="option" id="${menu.id}o${i}" data-i="${i}"><span>${esc(it.value)}${it.label ? ` <span class="text-secondary">— ${esc(it.label)}</span>` : ""}</span>${it.meta ? `<small class="text-secondary ms-2">${esc(it.meta)}</small>` : ""}</div>`).join("")
      : '<div class="cz-select__empty">No matching item</div>';
    place();
    menu.classList.remove("d-none");
    input.setAttribute("aria-expanded", "true");
    active = -1;
  };
  const pick = (i) => {
    const it = items[i];
    if (!it) return;
    input.value = it.value;
    close();
    input.dispatchEvent(new Event("change", { bubbles: true }));
    onPick?.(it);
  };
  input.addEventListener("input", open);
  input.addEventListener("focus", open);
  input.addEventListener("keydown", (e) => {
    if (menu.classList.contains("d-none")) { if (e.key === "ArrowDown") open(); return; }
    if (e.key === "ArrowDown") { e.preventDefault(); mark(Math.min(items.length - 1, active + 1)); }
    else if (e.key === "ArrowUp") { e.preventDefault(); mark(Math.max(0, active - 1)); }
    else if (e.key === "Enter" && active >= 0) { e.preventDefault(); pick(active); }
    else if (e.key === "Escape") close();
  });
  input.addEventListener("blur", () => setTimeout(close, 150));
  menu.addEventListener("mousedown", (e) => { e.preventDefault(); const o = e.target.closest("[data-i]"); if (o) pick(+o.dataset.i); });
  window.addEventListener("scroll", () => { if (!menu.classList.contains("d-none")) place(); }, true);
  window.addEventListener("resize", close);
  // a removed box takes its list with it (one observer for every box of the page)
  const reg = (window.CloudZoo.autocomplete._boxes ||= new Map());
  reg.set(input, menu);
  if (!window.CloudZoo.autocomplete._observer) {
    window.CloudZoo.autocomplete._observer = new MutationObserver(() => {
      reg.forEach((m, box) => { if (!box.isConnected) { m.remove(); reg.delete(box); } });
    });
    window.CloudZoo.autocomplete._observer.observe(document.body, { childList: true, subtree: true });
  }
};

// Lab Delivery: shared UI behavior for the Dyeing ERP Lab module.
(() => {
  const root = document.querySelector('[data-cz-lab-delivery]');
  if (!root) return;
  const one = s => root.querySelector(s);
  const all = s => Array.from(root.querySelectorAll(s));
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const error = message => { one('[data-delivery-error]').textContent = message; one('[data-delivery-error]').classList.toggle('d-none', !message); };
  async function api(action, data, query = {}) {
    const url = new URL(root.dataset.api, location.origin);
    url.search = new URLSearchParams({action, ...query});
    const response = await window.czApiFetch(url.pathname + url.search, data === undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Unable to complete request.');
    return result;
  }
  const run = fn => (...args) => Promise.resolve().then(() => fn(...args)).catch(e => error(e.message));
  if (root.dataset.czLabDelivery === 'list') {
    let page = 1, pages = 1, request = 0, total = 0, rowCount = 0, sort='tid', dir='desc';
    const can = root.dataset.abilities.split(',');
    const selected = () => all('[data-delivery-check]:checked').map(x => Number(x.value));
    const count = () => {
      const n=selected().length;
      one('[data-delivery-count]').textContent = `Showing ${total ? (page-1)*25+1 : 0} to ${total ? (page-1)*25+rowCount : 0} of ${total} entries`;
      one('[data-delivery-selected]').textContent=n;
      one('[data-delivery-selection]').classList.toggle('d-none',n===0);
      one('[data-delivery-selection]').classList.toggle('d-flex',n>0);
      const checks=all('[data-delivery-check]');
      one('[data-delivery-all]').checked=checks.length>0 && n===checks.length;
      one('[data-delivery-all]').indeterminate=n>0 && n<checks.length;
    };
    async function load() {
      const token = ++request;
      const query = Object.fromEntries(all('[data-filter]').map(x => [x.dataset.filter,x.value]));
      const r = await api('list',undefined,{...query,page,sort,dir});
      if (token !== request) return;
      error(''); pages = r.pages; total=Number(r.total); rowCount=r.rows.length;
      const activeFilters=all('[data-filter]').filter(x=>x.dataset.filter!=='search' && x.value!=='').length;
      one('[data-delivery-filter-count]').textContent=activeFilters;
      one('[data-delivery-filter-count]').classList.toggle('d-none',activeFilters===0);
      one('[data-delivery-empty]').classList.toggle('d-none',rowCount>0);
      one('[data-delivery-rows]').innerHTML = r.rows.map(d => `<tr><td><input class="form-check-input" type="checkbox" data-delivery-check value="${Number(d.tid)}" aria-label="Select delivery ${esc(d.delivery_no)}"></td><td><div class="fw-semibold">${esc(d.customer_name || 'Deleted requisition')}</div><small class="text-secondary">#${Number(d.tid)}</small></td><td>${esc(d.generate_no ?? d.delivery_no)}</td><td>${esc(d.colour)}</td><td>${esc(d.fabric_name)}</td><td><span class="cz-badge ${Number(d.submission_no) > 1 ? 'warning' : 'neutral'}">${Number(d.submission_no) > 1 ? 'Resubmission' : 'First Submission'}</span></td><td>${esc(d.delivery_date)}</td><td><span class="cz-badge ${['Delivered','Completed','Approved'].includes(d.status) ? 'success' : ['Pending','On Hold','Awaiting Delivery'].includes(d.status) ? 'warning' : ['Rejected','Cancelled','Failed'].includes(d.status) ? 'danger' : 'neutral'}">${esc(d.status)}</span></td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Delivery actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu"><li><button class="cz-action-menu__item" type="button" data-delivery-info="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>${can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li>` : ''}${can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-delivery-remove="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join('') || '';
      one('[data-delivery-all]').checked = false;
      one('[data-delivery-prev]').disabled = page <= 1; one('[data-delivery-next]').disabled = page >= pages; count();
    }
    async function remove(ids) {
      if (!ids.length) throw new Error('Select at least one delivery.');
      if (!window.confirm('Delete the selected lab deliveries? Linked confirmations will be unlinked and lab status recalculated.')) return;
      await api('delete',{ids}); await load();
    }
    root.addEventListener('click',run(async e => {
      const info = e.target.closest('[data-delivery-info]');
      const del = e.target.closest('[data-delivery-remove]');
      if (del) await remove([Number(del.dataset.deliveryRemove)]);
      if (info) {
        const d = (await api('get',undefined,{id:info.dataset.deliveryInfo})).data;
        one('[data-delivery-details]').innerHTML = `<dl>${['delivery_no','customer_name','colour','fabric_name','delivery_date','delivery_through','delivered_by','expected_confirmation_date','delivery_remarks'].map(k => `<dt>${esc(k.replaceAll('_',' '))}</dt><dd>${esc(d[k] || '—')}</dd>`).join('')}</dl><h3 class="fs-6">Shade Options</h3>${d.options.map(o => `<div class="border-bottom py-2"><strong>${esc(o.option_letter)}</strong> · ${esc(o.manual_option_no)} · ${Number(o.no_of_cards)} cards · ${esc(o.delivery_status)}<p>${esc(o.recommendation)}</p><p>${esc(o.remarks)}</p></div>`).join('')}`;
        bootstrap.Modal.getOrCreateInstance(one('#deliveryDetails')).show();
      }
    }));
    let timer;
    all('[data-filter]').forEach(x => x.addEventListener('input',() => { clearTimeout(timer); timer=setTimeout(run(async () => {page=1; await load();}),250); }));
    all('[data-delivery-clear]').forEach(button=>button.addEventListener('click',run(async () => { all('[data-filter]').forEach(x => {x.value=''; if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');}); page=1; await load(); })));
    one('[data-delivery-apply]').addEventListener('click',run(async () => {page=1; await load(); bootstrap.Offcanvas.getOrCreateInstance(one('#deliveryFilters')).hide();}));
    all('[data-delivery-sort]').forEach(button=>button.addEventListener('click',run(async () => {
      dir=sort===button.dataset.deliverySort && dir==='asc' ? 'desc' : 'asc'; sort=button.dataset.deliverySort; page=1;
      all('[data-delivery-sort]').forEach(x=>{x.querySelector('i').className=x===button ? (dir==='asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down') : 'bi bi-arrow-down-up'; x.closest('th').removeAttribute('aria-sort');});
      button.closest('th').setAttribute('aria-sort',dir==='asc' ? 'ascending' : 'descending'); await load();
    })));
    one('[data-delivery-prev]').addEventListener('click',run(async () => {page--; await load();}));
    one('[data-delivery-next]').addEventListener('click',run(async () => {page++; await load();}));
    one('[data-delivery-all]').addEventListener('change',e => {all('[data-delivery-check]').forEach(x => x.checked=e.target.checked); count();});
    root.addEventListener('change',e => {if(e.target.matches('[data-delivery-check]')) count();});
    one('[data-delivery-delete]')?.addEventListener('click',run(() => remove(selected())));
    run(async () => {
      const filters=(await api('filters')).data;
      (filters.customers || []).forEach(name=>one('[data-filter="customer_name"]').add(new Option(name,name)));
      (filters.statuses || []).forEach(status=>one('[data-filter="status"]').add(new Option(status,status)));
      if(window.jQuery?.fn.select2) jQuery(one('[data-filter="status"]')).select2({width:'100%',minimumResultsForSearch:Infinity}).on('change',run(async ()=>{page=1; await load();}));
      if(window.jQuery?.fn.select2) jQuery(one('[data-filter="customer_name"]')).select2({width:'100%',dropdownParent:jQuery(one('#deliveryFilters'))}).on('change',run(async ()=>{page=1; await load();}));
      await load();
    })();
  } else {
    const form=one('[data-delivery-form]'), picker=one('#deliveryLabRef'), tbody=one('[data-delivery-options]');
    const save=one('[data-delivery-save]');
    const customer=one('#deliveryCustomer'), staff=one('#deliveryBy');
    let used=[], closed=false, loading=true, refRequest=0, references=[], colour='', defaultCards=1, recommendationRow=null, loadedReference='';
    const serials=() => all('[data-delivery-serial]').forEach((x,i) => x.textContent=i+1);
    function placeholder(message) {
      tbody.innerHTML=`<tr data-delivery-placeholder><td colspan="9" class="text-center text-secondary py-3">${esc(message)}</td></tr>`;
    }
    function rebuildReferences() {
      const selected=picker.value;
      picker.replaceChildren(new Option('Select Lab Ref',''));
      references.filter(r => !customer.value || r.customer_name===customer.value).forEach(r => picker.add(new Option(r.label,r.tid)));
      picker.value=Array.from(picker.options).some(o => o.value===selected) ? selected : '';
      if(window.jQuery?.fn.select2) jQuery(picker).trigger('change.select2');
      if(picker.value!==selected) run(() => reference(picker.value))();
    }
    function add(o={}) {
      const letter=o.option_letter || 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').find(l => !used.includes(l) && !all('[data-shade="option_letter"]').some(x => x.value===l));
      if (!letter) throw new Error('All shade letters A–Z have been used.');
      one('[data-delivery-placeholder]')?.remove();
      const tr=document.createElement('tr');
      tr.innerHTML = `<td data-delivery-serial></td><td class="fw-semibold">${esc(letter)}<input type="hidden" data-shade="option_letter" value="${esc(letter)}"></td>
        <td><input class="form-control form-control-sm" data-shade="manual_option_no" value="${esc(o.manual_option_no ?? letter)}" readonly aria-label="Manual option number ${esc(letter)}"></td>
        <td class="text-nowrap">${esc([colour,letter].filter(Boolean).join(' '))}</td>
        <td class="text-center"><button class="btn btn-sm ${o.recommendation ? 'btn-secondary' : 'btn-outline-secondary'}" type="button" data-delivery-recommendation aria-label="Recommendation for option ${esc(letter)}"><i class="bi bi-chat-square-text"></i></button><input type="hidden" data-shade="recommendation" value="${esc(o.recommendation || '')}"></td>
        <td><input class="form-control form-control-sm" data-shade="no_of_cards" type="number" min="1" max="100000" required value="${Number(o.no_of_cards) || defaultCards}" aria-label="Number of cards ${esc(letter)}"></td>
        <td><select class="form-select form-select-sm" data-shade="delivery_status" aria-label="Delivery status ${esc(letter)}"><option>Deliver</option><option>Hold</option></select></td>
        <td><input class="form-control form-control-sm" data-shade="remarks" maxlength="255" value="${esc(o.remarks || '')}" placeholder="Remarks if any" aria-label="Remarks ${esc(letter)}"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" data-shade-remove aria-label="Remove shade ${esc(letter)}"><i class="bi bi-trash3"></i></button></td>`;
      tbody.append(tr); tr.querySelector('select').value=o.delivery_status || 'Deliver'; serials();
    }
    async function reference(id, populate=true) {
      // Keep rows entered before the first Lab Ref is chosen.
      const drafts=populate && !loadedReference ? all('[data-delivery-options] tr').filter(tr=>tr.querySelector('[data-shade]')).map(tr=>Object.fromEntries(Array.from(tr.querySelectorAll('[data-shade]')).map(x=>[x.dataset.shade,x.value]))) : [];
      const token=++refRequest; loading=true; save.disabled=true; error('');
      if(populate) tbody.replaceChildren();
      if(!id) { used=[]; colour=''; defaultCards=1; closed=false; one('[data-delivery-banner]').classList.add('d-none'); one('[data-delivery-resubmission]').classList.add('d-none'); one('[data-delivery-submission]').textContent=''; placeholder('Select a Lab Ref above to load its shade options.'); loading=false; return; }
      let r;
      try { r=(await api('reference',undefined,{id})).data; }
      catch (err) { if(token===refRequest) loading=false; throw err; }
      if(token!==refRequest) return;
      loadedReference=String(id);
      used=r.options.map(o => o.option_letter); closed=populate && r.closed;
      colour=r.colour || ''; defaultCards=Number(r.shade_cards_per_option) || 1;
      if(!Array.from(customer.options).some(o => o.value===r.customer_name)) customer.add(new Option(r.customer_name,r.customer_name));
      customer.value=r.customer_name;
      if(window.jQuery?.fn.select2) jQuery(customer).trigger('change.select2');
      const submission=populate ? (r.deliveries.length ? 2 : 1) : Number(root.dataset.submission || 1);
      all('[data-delivery-value]').forEach(x => x.textContent=x.dataset.deliveryValue==='submission' ? '#'+submission : (r[x.dataset.deliveryValue] || '—'));
      one('[data-delivery-banner]').classList.remove('d-none');
      one('[data-delivery-resubmission]').classList.toggle('d-none',submission<=1);
      one('[data-delivery-submission]').textContent = closed ? 'Closed: first submission and resubmission already recorded.' : (populate ? (r.deliveries.length ? 'Resubmission' : 'First Submission') : 'Editing existing delivery');
      if(populate) r.options.filter(o => !['Approved','Rejected'].includes(o.customer_decision)).forEach(o => add({...o,no_of_cards:r.shade_cards_per_option || 1}));
      if(populate && !closed) drafts.forEach(draft=>{
        const row=all('[data-delivery-options] tr').find(tr=>tr.querySelector('[data-shade="option_letter"]')?.value===draft.option_letter);
        if(row) {
          ['no_of_cards','delivery_status','remarks','recommendation'].forEach(key=>{row.querySelector(`[data-shade="${key}"]`).value=draft[key] || '';});
          row.querySelector('[data-delivery-recommendation]').classList.toggle('btn-secondary',!!draft.recommendation);
          row.querySelector('[data-delivery-recommendation]').classList.toggle('btn-outline-secondary',!draft.recommendation);
        } else add({...draft,option_letter:used.includes(draft.option_letter) ? undefined : draft.option_letter});
      });
      if(!tbody.querySelector('[data-shade]')) placeholder(closed ? 'This Lab Ref is closed.' : 'No open shade options. Use Add Row to add a new shade.');
      loading=false; save.disabled=closed;
    }
    if (!window.jQuery?.fn.select2) picker.addEventListener('change',run(() => reference(picker.value)));
    one('[data-delivery-add]').addEventListener('click',run(() => {
      if(loading) throw new Error('Please wait for the Lab Ref to finish loading.');
      if(closed) throw new Error('This Lab Delivery is closed and cannot accept more shade rows.');
      error(''); add();
    }));
    tbody.addEventListener('click',e => {
      if(e.target.closest('[data-shade-remove]')) {e.target.closest('tr').remove(); serials(); if(!tbody.querySelector('[data-shade]')) placeholder('No shade options. Use Add Row to add a shade.');}
      if(e.target.closest('[data-delivery-recommendation]')) {
        recommendationRow=e.target.closest('tr'); one('[data-delivery-recommendation-letter]').textContent=recommendationRow.querySelector('[data-shade="option_letter"]').value;
        one('#deliveryRecommendationText').value=recommendationRow.querySelector('[data-shade="recommendation"]').value;
        bootstrap.Modal.getOrCreateInstance(one('#deliveryRecommendation')).show();
      }
    });
    one('[data-delivery-recommendation-save]').addEventListener('click',() => {
      if(!recommendationRow?.isConnected) return;
      const value=one('#deliveryRecommendationText').value;
      recommendationRow.querySelector('[data-shade="recommendation"]').value=value;
      const button=recommendationRow.querySelector('[data-delivery-recommendation]'); button.classList.toggle('btn-secondary',!!value.trim()); button.classList.toggle('btn-outline-secondary',!value.trim());
      bootstrap.Modal.getOrCreateInstance(one('#deliveryRecommendation')).hide();
    });
    one('#deliveryDocument').addEventListener('change',e => {one('[data-delivery-file-preview]').textContent=e.target.files[0]?.name || '';});
    form.addEventListener('submit',e => { e.preventDefault(); run(async () => {
      form.classList.add('was-validated');
      if(!form.checkValidity() || loading || closed) return;
      save.disabled=true; error('');
      try {
        const data=Object.fromEntries(new FormData(form)); data.main_id_fk=Number(picker.value); data.id=Number(root.dataset.id);
        data.options=Array.from(tbody.rows).filter(tr=>tr.querySelector('[data-shade]')).map(tr => Object.fromEntries(Array.from(tr.querySelectorAll('[data-shade]')).map(x => [x.dataset.shade,x.value])));
        const file=one('#deliveryDocument').files[0];
        if (file) {
          if (file.size>5*1024*1024) throw new Error('Document must be no larger than 5 MB.');
          data.document=await new Promise((resolve,reject) => { const reader=new FileReader(); reader.onload=()=>resolve(String(reader.result).split(',')[1]); reader.onerror=()=>reject(new Error('Cannot read document.')); reader.readAsDataURL(file); });
        }
        await api('save',data); location.assign(root.dataset.list);
      } finally {save.disabled=false;}
    })(); });
    run(async () => {
      save.disabled=true;
      const opts=(await api('options')).data;
      references=opts.references;
      (opts.customers || []).forEach(name => customer.add(new Option(name,name)));
      (opts.staff || []).forEach(s => staff.add(new Option(s.label,s.value)));
      references.forEach(r => picker.add(new Option(r.label,r.tid)));
      if(window.jQuery?.fn.select2) jQuery(picker).select2({width:'100%'}).on('change',run(() => reference(picker.value)));
      if(window.jQuery?.fn.select2) {
        jQuery(customer).select2({width:'100%',placeholder:'All Customers',allowClear:true}).on('change',rebuildReferences);
        jQuery(staff).select2({width:'100%',placeholder:'Select staff',allowClear:true});
      } else customer.addEventListener('change',rebuildReferences);
      if(Number(root.dataset.id)) {
        const d=(await api('get',undefined,{id:root.dataset.id})).data;
        root.dataset.submission=d.submission_no;
        one('[data-delivery-heading]').textContent='— '+(d.generate_no || d.tid);
        if(d.document_path) {const link=one('[data-delivery-document]'); link.href=root.dataset.api+'?action=document&id='+Number(d.tid); link.classList.remove('d-none');}
        if(!Array.from(picker.options).some(o => o.value===String(d.main_id_fk))) picker.add(new Option('LAB-'+d.main_id_fk,d.main_id_fk));
        picker.value=d.main_id_fk;
        if(window.jQuery?.fn.select2) jQuery(picker).trigger('change.select2');
        Array.from(form.elements).forEach(x => {if(x.name && x.name in d) x.value=d[x.name] ?? '';});
        if(d.delivered_by && !Array.from(staff.options).some(o=>o.value===d.delivered_by)) staff.add(new Option(d.delivered_by,d.delivered_by));
        staff.value=d.delivered_by || '';
        if(window.jQuery?.fn.select2) jQuery(staff).trigger('change.select2');
        await reference(d.main_id_fk,false); tbody.replaceChildren(); d.options.forEach(add);
        if(d.locked) {closed=true; save.disabled=true; one('[data-delivery-submission]').textContent='This delivery has a customer confirmation and cannot be edited.';}
      } else {
        const now=new Date(); one('#deliveryDate').value=[now.getFullYear(),String(now.getMonth()+1).padStart(2,'0'),String(now.getDate()).padStart(2,'0')].join('-');
        one('#deliveryExpected').value=one('#deliveryDate').value;
        one('#deliveryNo').value=opts.next_delivery_no || '';
        one('[data-delivery-heading]').textContent='— '+opts.next_generate_no;
        loading=false;
      }
    })();
  }
})();

// Dyeing Lab: Customer Confirmation lists and forms.
(() => {
  const root=document.querySelector('[data-cz-lab-confirmation]'); if(!root) return;
  const one=s=>root.querySelector(s), all=s=>Array.from(root.querySelectorAll(s));
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message='') {one('[data-confirmation-error]').textContent=message; one('[data-confirmation-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {
    const url=new URL(root.dataset.api,location.origin); url.search=new URLSearchParams({action,...query});
    const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const r=await response.json(); if(!response.ok || !r.success) throw new Error(r.message || 'Unable to complete request.'); return r;
  }
  function select2(select,parent) {
    if(window.jQuery?.fn.select2) jQuery(select).select2({width:'100%',...(parent ? {dropdownParent:jQuery(parent)} : {})});
  }
  function onChange(select,fn) {if(window.jQuery?.fn.select2) jQuery(select).on('change',run(fn)); else select.addEventListener('change',run(fn));}
  const pill=value=>`<span class="cz-badge ${value==='Approved' ? 'success' : value==='Rejected' ? 'danger' : value==='On Hold' ? 'warning' : 'neutral'}">${esc(value || '—')}</span>`;
  if(root.dataset.czLabConfirmation==='list') {
    let page=1,pages=1,sort='tid',dir='desc',request=0;
    const can=root.dataset.abilities.split(',');
    const ids=()=>all('[data-confirmation-check]:checked').map(x=>Number(x.value));
    function selection() {
      const n=ids().length, count=all('[data-confirmation-check]').length;
      one('[data-confirmation-selected]').textContent=n;
      one('[data-confirmation-selection]').classList.toggle('d-none',!n); one('[data-confirmation-selection]').classList.toggle('d-flex',!!n);
      one('[data-confirmation-all]').checked=count>0 && n===count; one('[data-confirmation-all]').indeterminate=n>0 && n<count;
    }
    async function load() {
      const token=++request, query=Object.fromEntries(all('[data-confirmation-filter]').map(x=>[x.dataset.confirmationFilter,x.value]));
      const r=await api('list',undefined,{...query,page,sort,dir}); if(token!==request) return;
      error(); pages=r.pages;
      one('[data-confirmation-rows]').innerHTML=r.rows.map(c=>`<tr><td><input class="form-check-input" type="checkbox" data-confirmation-check value="${Number(c.tid)}" aria-label="Select confirmation ${esc(c.confirmation_no)}"></td><td><div class="fw-semibold">${esc(c.customer_name || 'Deleted requisition')}</div><small class="text-secondary">#${Number(c.tid)}</small></td><td>${esc(c.generate_no ?? c.confirmation_no)}</td><td>${esc(c.colour)}</td><td>${esc(c.fabric_name)}</td><td>${esc(c.lab_no)}<small class="text-secondary d-block">${c.submission_no ? 'Submission #'+Number(c.submission_no)+' · '+esc(c.delivery_no) : 'Unlinked delivery'}</small></td><td>${esc(c.confirmation_date)}</td><td>${esc(c.approved_option || '—')}</td><td>${pill(c.overall_confirmation)}</td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Confirmation actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu"><li><button class="cz-action-menu__item" type="button" data-confirmation-info="${Number(c.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>${can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?id=${Number(c.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li>` : ''}${can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-confirmation-remove="${Number(c.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join('');
      one('[data-confirmation-empty]').classList.toggle('d-none',r.rows.length>0);
      one('[data-confirmation-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${r.total ? (page-1)*25+r.rows.length : 0} of ${r.total} entries`;
      one('[data-confirmation-prev]').disabled=page<=1; one('[data-confirmation-next]').disabled=page>=pages;
      const n=all('[data-confirmation-filter]').filter(x=>x.dataset.confirmationFilter!=='search' && x.value).length;
      one('[data-confirmation-filter-count]').textContent=n; one('[data-confirmation-filter-count]').classList.toggle('d-none',!n); selection();
    }
    async function remove(selected) {if(!selected.length) throw new Error('Select a confirmation.'); if(!confirm('Delete the selected confirmations and recalculate the lab dip status?')) return; await api('delete',{ids:selected}); await load();}
    let timer;
    all('[data-confirmation-filter]').filter(x=>x.tagName!=='SELECT').forEach(x=>x.addEventListener('input',()=>{clearTimeout(timer); timer=setTimeout(run(async()=>{page=1; await load();}),250);}));
    all('[data-confirmation-clear]').forEach(b=>b.addEventListener('click',run(async()=>{all('[data-confirmation-filter]').forEach(x=>{x.value=''; if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');}); page=1; await load();})));
    one('[data-confirmation-apply]').addEventListener('click',run(async()=>{page=1; await load(); bootstrap.Offcanvas.getOrCreateInstance(one('#confirmationFilters')).hide();}));
    one('[data-confirmation-prev]').addEventListener('click',run(async()=>{page--; await load();})); one('[data-confirmation-next]').addEventListener('click',run(async()=>{page++; await load();}));
    one('[data-confirmation-all]').addEventListener('change',e=>{all('[data-confirmation-check]').forEach(x=>x.checked=e.target.checked); selection();});
    root.addEventListener('change',e=>{if(e.target.matches('[data-confirmation-check]')) selection();});
    one('[data-confirmation-delete]').addEventListener('click',run(()=>remove(ids())));
    all('[data-confirmation-sort]').forEach(b=>b.addEventListener('click',run(async()=>{dir=sort===b.dataset.confirmationSort && dir==='asc' ? 'desc' : 'asc'; sort=b.dataset.confirmationSort; page=1; all('[data-confirmation-sort]').forEach(x=>{x.querySelector('i').className=x===b ? (dir==='asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down') : 'bi bi-arrow-down-up'; x.closest('th').removeAttribute('aria-sort');}); b.closest('th').setAttribute('aria-sort',dir==='asc' ? 'ascending' : 'descending'); await load();})));
    root.addEventListener('click',run(async e=>{
      const del=e.target.closest('[data-confirmation-remove]'); if(del) await remove([Number(del.dataset.confirmationRemove)]);
      const info=e.target.closest('[data-confirmation-info]'); if(!info) return;
      const c=(await api('get',undefined,{id:info.dataset.confirmationInfo})).data;
      one('[data-confirmation-details]').innerHTML=`<dl class="row">${['confirmation_no','lab_no','customer_name','colour','fabric_name','delivery_no','confirmation_date','received_through','customer_contact','overall_confirmation','approved_option','approved_shade_ref','approval_validity','customer_approved_by','department','production_release','customer_remarks','internal_remarks'].map(k=>`<dt class="col-sm-4">${esc(k.replaceAll('_',' '))}</dt><dd class="col-sm-8">${esc(c[k] || '—')}</dd>`).join('')}<dt class="col-sm-4">Physical card received</dt><dd class="col-sm-8">${Number(c.physical_card_received) ? 'Yes' : 'No'}</dd></dl><h3 class="fs-6">Current Shade Decisions</h3>${c.options.map(o=>`<div class="border-bottom py-2">Option ${esc(o.option_letter)} · ${pill(o.customer_decision)}<p class="mb-0">${esc(o.customer_remarks)}</p></div>`).join('') || '<p class="text-secondary">No linked shade options.</p>'}${c.proof_document_path ? `<a href="${esc(root.dataset.api)}?action=document&id=${Number(c.tid)}">Download proof document</a>` : ''}`;
      bootstrap.Modal.getOrCreateInstance(one('#confirmationDetails')).show();
    }));
    run(async()=>{const filters=(await api('filters')).data; (filters.customers || []).forEach(x=>one('[data-confirmation-filter="customer_name"]').add(new Option(x,x))); filters.statuses.forEach(x=>one('[data-confirmation-filter="overall_confirmation"]').add(new Option(x,x))); all('select[data-confirmation-filter]').forEach(x=>{select2(x,x.dataset.confirmationFilter==='customer_name' ? one('#confirmationFilters') : null); onChange(x,async()=>{page=1; await load();});}); await load();})();
  } else {
    const form=one('[data-confirmation-form]'), picker=one('#confirmationLabRef'), delivery=one('#confirmationDelivery'), save=one('[data-confirmation-save]');
    let ref=null, loading=true, locked=false, request=0;
    const edit=Number(root.dataset.id);
    function sync(derive=true) {
      const approved=all('[data-decision]').filter(x=>x.value==='Approved');
      one('#confirmationApproved').value=approved.map(x=>x.dataset.letter).join(', ');
      if(derive && approved.length) one('#confirmationOverall').value='Approved';
      else if(derive && all('[data-decision]').length && all('[data-decision]').every(x=>x.value==='Rejected')) one('#confirmationOverall').value='Rejected';
      if(window.jQuery?.fn.select2) jQuery(one('#confirmationOverall')).trigger('change.select2');
      all('[data-option-card]').forEach(card=>{const decision=card.querySelector('[data-decision]').value; card.classList.toggle('border-success',decision==='Approved'); card.classList.toggle('border-danger',decision==='Rejected');});
    }
    function shades() {
      const d=ref?.deliveries.find(d=>String(d.tid)===delivery.value);
      one('[data-confirmation-options]').innerHTML=d?.options.length ? d.options.map(o=>`<div class="col-xl-3 col-md-6"><div class="cz-card border p-3 h-100" data-option-card><h3 class="fs-6">Option ${esc(o.option_letter)}</h3><p class="small text-secondary">${esc(ref.colour)} · ${esc(o.recommendation)}</p><label class="form-label" for="decision${Number(o.tid)}">Customer Decision</label><select class="form-select" id="decision${Number(o.tid)}" data-decision data-tid="${Number(o.tid)}" data-letter="${esc(o.option_letter)}"><option value="">Select</option><option>Approved</option><option>Rejected</option><option>Hold</option></select><label class="form-label mt-2" for="optionRemarks${Number(o.tid)}">Customer Remarks</label><input class="form-control" id="optionRemarks${Number(o.tid)}" data-option-remarks maxlength="10000" value="${esc(o.customer_remarks)}"></div></div>`).join('') : '<p class="text-secondary mb-0">This submission has no shade options. Select a delivery containing shades.</p>';
      all('[data-decision]').forEach(x=>{const o=d.options.find(o=>Number(o.tid)===Number(x.dataset.tid)); x.value=o.customer_decision || ''; select2(x); onChange(x,()=>{if(x.value==='Approved') all('[data-decision]').filter(other=>other!==x).forEach(other=>{other.value='Rejected'; if(window.jQuery?.fn.select2) jQuery(other).trigger('change.select2');}); else if(x.value==='Rejected') all('[data-decision]').filter(other=>other!==x && !other.value).forEach(other=>{other.value='Rejected'; if(window.jQuery?.fn.select2) jQuery(other).trigger('change.select2');}); sync();});});
      one('#confirmationApproved').value=''; if(d) sync(false); save.disabled=loading || locked || !d?.options.length;
    }
    async function reference(id,selectedDelivery) {
      const token=++request; loading=true; save.disabled=true; ref=null; delivery.disabled=true; delivery.replaceChildren(new Option('Select submission','')); one('[data-confirmation-banner]').classList.add('d-none'); one('[data-confirmation-options]').innerHTML='<p class="text-secondary">Select a Lab Ref and submission to load shade options.</p>'; error();
      if(!id) {loading=false; return;}
      const r=(await api('reference',undefined,{id})).data; if(token!==request) return; ref=r;
      all('[data-confirmation-value]').forEach(x=>x.textContent=r[x.dataset.confirmationValue] || '—'); one('[data-confirmation-banner]').classList.remove('d-none');
      locked=r.confirmations.some(c=>c.overall_confirmation==='Approved' && Number(c.delivery_id_fk)>0 && Number(c.tid)!==edit);
      const confirmed=r.confirmations.filter(c=>Number(c.tid)!==edit).map(c=>Number(c.delivery_id_fk));
      r.deliveries.filter(d=>!confirmed.includes(Number(d.tid)) || Number(d.tid)===Number(selectedDelivery)).forEach(d=>delivery.add(new Option(`Submission #${d.submission_no} — ${d.delivery_no}`,d.tid)));
      delivery.value=selectedDelivery ? String(selectedDelivery) : (edit ? '' : (delivery.options[1]?.value || '')); delivery.disabled=!!edit || locked;
      if(window.jQuery?.fn.select2) jQuery(delivery).trigger('change.select2');
      loading=false; shades(); if(locked) error('This lab requisition already has an approved confirmation.'); else if(delivery.options.length===1) error('No submissions are awaiting confirmation.');
    }
    onChange(picker,()=>reference(picker.value)); onChange(delivery,shades);
    onChange(one('#confirmationOverall'),()=>{const value=one('#confirmationOverall').value; if(value==='Rejected') all('[data-decision]').forEach(x=>{x.value='Rejected'; if(window.jQuery?.fn.select2) jQuery(x).trigger('change.select2');}); else if(value!=='Approved') all('[data-decision]').filter(x=>x.value==='Approved').forEach(x=>{x.value=''; if(window.jQuery?.fn.select2) jQuery(x).trigger('change.select2');}); sync(false);});
    form.addEventListener('submit',e=>{e.preventDefault(); run(async()=>{
      form.classList.add('was-validated'); if(!form.checkValidity() || loading || locked || !delivery.value) return;
      save.disabled=true; error();
      try {
        const data=Object.fromEntries(new FormData(form)); data.id=edit; data.main_id_fk=Number(picker.value); data.delivery_id_fk=Number(delivery.value); data.physical_card_received=one('#confirmationPhysical').checked;
        data.options=all('[data-option-card]').map(card=>({tid:Number(card.querySelector('[data-decision]').dataset.tid),decision:card.querySelector('[data-decision]').value,remarks:card.querySelector('[data-option-remarks]').value}));
        const file=one('#confirmationProof').files[0]; if(file) {if(file.size>5*1024*1024) throw new Error('Proof document must be no larger than 5 MB.'); data.document=await new Promise((resolve,reject)=>{const reader=new FileReader(); reader.onload=()=>resolve(String(reader.result).split(',')[1]); reader.onerror=()=>reject(new Error('Cannot read proof document.')); reader.readAsDataURL(file);});}
        await api('save',data); location.assign(root.dataset.list);
      } finally {save.disabled=locked;}
    })();});
    run(async()=>{
      save.disabled=true; const opts=(await api('options')).data;
      opts.references.forEach(r=>picker.add(new Option(r.label,r.tid)));
      for(const [id,key] of [['confirmationReceived','received'],['confirmationOverall','statuses'],['confirmationValidity','validity'],['confirmationDepartment','departments'],['confirmationRelease','release']]) opts[key].forEach(x=>one('#'+id).add(new Option(x,x)));
      all('select').forEach(x=>select2(x));
      if(edit) {
        const c=(await api('get',undefined,{id:edit})).data; one('[data-confirmation-heading]').textContent='— '+(c.generate_no || c.confirmation_no);
        if(!Array.from(picker.options).some(x=>x.value===String(c.main_id_fk))) picker.add(new Option(c.lab_no,c.main_id_fk)); picker.value=c.main_id_fk;
        Array.from(form.elements).forEach(x=>{if(x.name && x.name in c && x.type!=='checkbox') {if(x.tagName==='SELECT' && c[x.name] && !Array.from(x.options).some(o=>o.value===c[x.name])) x.add(new Option(c[x.name],c[x.name])); x.value=c[x.name] ?? '';}});
        one('#confirmationPhysical').checked=Number(c.physical_card_received)===1;
        await reference(c.main_id_fk,c.delivery_id_fk);
        if(!c.delivery_id_fk) {locked=true; save.disabled=true; error('This confirmation is unlinked from its delivery and is read-only.');}
        if(c.proof_document_path) {const link=one('[data-confirmation-document]'); link.href=root.dataset.api+'?action=document&id='+Number(c.tid); link.classList.remove('d-none');}
      } else {
        const now=new Date(); one('#confirmationDate').value=[now.getFullYear(),String(now.getMonth()+1).padStart(2,'0'),String(now.getDate()).padStart(2,'0')].join('-'); one('[data-confirmation-heading]').textContent='— '+opts.next_number; loading=false;
        if(Number(root.dataset.reference)) {picker.value=root.dataset.reference; await reference(picker.value);}
      }
      if(window.jQuery?.fn.select2) all('select').forEach(x=>jQuery(x).trigger('change.select2'));
    })();
  }
})();

// Lab Recipe Entry: delivery-option drafts, costing imports, calculations and lists.
(() => {
  const root=document.querySelector('[data-cz-lab-recipe]'); if(!root) return;
  const one=s=>root.querySelector(s), all=s=>Array.from(root.querySelectorAll(s));
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message='') {one('[data-lr-error]').textContent=message; one('[data-lr-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {const url=new URL(root.dataset.api,location.origin); url.search=new URLSearchParams({action,...query}); const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); const r=await response.json(); if(!response.ok || !r.success) throw new Error(r.message || 'Unable to complete request.'); return r;}
  const enhance=(x,parent)=>{if(window.jQuery?.fn.select2) jQuery(x).select2({width:'100%',...(parent ? {dropdownParent:jQuery(parent)} : {})});};
  const change=(x,fn)=>{if(window.jQuery?.fn.select2) jQuery(x).on('change',run(fn)); else x.addEventListener('change',run(fn));};
  const refresh=x=>{if(window.jQuery?.fn.select2) jQuery(x).trigger('change.select2');};
  const pill=s=>`<span class="cz-badge ${s==='Approved' ? 'success' : s==='Rejected' ? 'danger' : s==='On Hold' ? 'warning' : 'neutral'}">${esc(s)}</span>`;
  if(root.dataset.czLabRecipe==='list') {
    let page=1,pages=1,sort='tid',dir='desc',request=0; const can=root.dataset.abilities.split(',');
    const selected=()=>all('[data-lr-check]:checked').map(x=>Number(x.value)).filter(id=>id>0);
    function selection() {
      const boxes=all('[data-lr-check]'), checked=boxes.filter(x=>x.checked), saved=selected();
      const n=checked.length;
      one('[data-lr-selected]').textContent=n;
      one('[data-lr-selection]').classList.toggle('d-none',!n);
      one('[data-lr-selection]').classList.toggle('d-flex',!!n);
      one('[data-lr-all]').checked=boxes.length>0 && n===boxes.length;
      one('[data-lr-all]').indeterminate=n>0 && n<boxes.length;
      const copy=one('[data-lr-copy-selected]'); if(copy) copy.disabled=n!==1 || saved.length!==1;
      const del=one('[data-lr-delete]'); if(del) {del.disabled=!saved.length; del.title=saved.length ? 'Delete selected saved recipes' : 'Unsaved options have no recipe to delete';}
    }
    async function load() {const token=++request; const q=Object.fromEntries(all('[data-lr-filter]').map(x=>[x.dataset.lrFilter,x.value])); const r=await api('list',undefined,{...q,page,sort,dir}); if(token!==request) return; error(); pages=r.pages;
      one('[data-lr-rows]').innerHTML=r.rows.map(d=>`<tr class="${Number(d.option_approved) ? 'cz-lab-recipe-approved' : ''}"><td><input class="form-check-input" type="checkbox" data-lr-check value="${Number(d.tid)}" ${Number(d.is_pending) ? 'title="Recipe not entered; use Enter Recipe in the action menu"' : ''} aria-label="Select recipe ${esc(d.recipe_no)}"></td><td><div class="fw-semibold">${esc(d.customer_name)}</div>${Number(d.tid)>0 ? `<small class="text-secondary">#${Number(d.tid)}</small>` : ''}</td><td>${Number(d.is_pending) ? 'Recipe not entered' : esc(d.generate_no ?? d.recipe_no)}<small class="d-block text-secondary">${esc(d.recipe_no)}</small></td><td>${esc(d.recipe_date || '—')}</td><td><div>${esc(d.delivery_no || '—')}</div><small class="cz-badge neutral">${esc(d.lab_dip_no || '—')}</small></td><td><div class="fw-semibold">${esc(d.colour || '—')}</div><small class="text-secondary">${esc(d.fabric || '—')}</small></td><td>${esc(d.option_letter)}</td><td>${esc(d.costing_no || '—')}</td><td>${esc(d.recipe_version)}</td><td>${pill(d.status)}</td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Recipe actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu">${Number(d.is_pending) ? (can.includes('create') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?delivery_id=${Number(d.lab_dip_delivery_id_fk)}&amp;option_id=${Number(d.delivery_option_id_fk)}">Enter Recipe</a></li>` : '') : `<li><button class="cz-action-menu__item" type="button" data-lr-info="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>`}${!Number(d.is_pending) && can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li>` : ''}${!Number(d.is_pending) && can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-lr-remove="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join(''); one('[data-lr-empty]').classList.toggle('d-none',!!r.rows.length); one('[data-lr-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${r.total ? (page-1)*25+r.rows.length : 0} of ${r.total} entries`; one('[data-lr-prev]').disabled=page<=1; one('[data-lr-next]').disabled=page>=pages; selection();}
    one('[data-lr-copy-selected]')?.addEventListener('click',()=>{const ids=selected(); if(ids.length===1) location.assign(root.dataset.form+'?copy_id='+ids[0]);});
    async function remove(ids) {if(!ids.length) throw new Error('Select a recipe.'); if(!confirm('Delete the selected lab recipes and their ingredient rows?')) return; await api('delete',{ids}); await load();}
    let timer; all('[data-lr-filter]').filter(x=>x.tagName!=='SELECT').forEach(x=>x.addEventListener('input',()=>{clearTimeout(timer); timer=setTimeout(run(async()=>{page=1; await load();}),250);}));
    all('[data-lr-clear]').forEach(x=>x.addEventListener('click',run(async()=>{all('[data-lr-filter]').forEach(x=>{x.value=''; refresh(x);}); page=1; await load();})));
    one('[data-lr-apply]').addEventListener('click',run(async()=>{page=1; await load(); bootstrap.Offcanvas.getOrCreateInstance(one('#labRecipeFilters')).hide();}));
    one('[data-lr-prev]').addEventListener('click',run(async()=>{page--; await load();})); one('[data-lr-next]').addEventListener('click',run(async()=>{page++; await load();}));
    one('[data-lr-all]').addEventListener('change',e=>{all('[data-lr-check]').forEach(x=>x.checked=e.target.checked); selection();}); root.addEventListener('change',e=>{if(e.target.matches('[data-lr-check]')) selection();}); one('[data-lr-delete]').addEventListener('click',run(()=>remove(selected())));
    all('[data-lr-sort]').forEach(b=>b.addEventListener('click',run(async()=>{dir=sort===b.dataset.lrSort && dir==='asc' ? 'desc' : 'asc'; sort=b.dataset.lrSort; page=1; all('[data-lr-sort]').forEach(x=>{x.querySelector('i').className=x===b ? (dir==='asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down') : 'bi bi-arrow-down-up'; x.closest('th').removeAttribute('aria-sort');}); b.closest('th').setAttribute('aria-sort',dir==='asc' ? 'ascending' : 'descending'); await load();})));
    root.addEventListener('click',run(async e=>{const del=e.target.closest('[data-lr-remove]'); if(del) await remove([Number(del.dataset.lrRemove)]); const info=e.target.closest('[data-lr-info]'); if(!info) return; const d=(await api('get',undefined,{id:info.dataset.lrInfo})).data;
      one('[data-lr-details]').innerHTML=`<dl class="row">${['recipe_no','lab_dip_no','delivery_no','option_letter','recipe_version','recipe_date','customer_name','colour','fabric','gsm','process_type','costing_no','costing_rate','fabric_weight','sampling_qty','mlr','water_level','lab_machine','status','total_recipe_weight','remarks'].map(k=>`<dt class="col-sm-4">${esc(k.replaceAll('_',' '))}</dt><dd class="col-sm-8">${esc(d[k] ?? '—')}</dd>`).join('')}</dl><div class="table-responsive"><table class="table cz-table"><thead><tr><th>Item</th><th>Unit</th><th>Costing Qty</th><th>Final Qty</th><th>Final Wt. (KG)</th></tr></thead><tbody>${d.items.map(i=>`<tr><td>${esc(i.product_name)}</td><td>${esc(i.unit)}</td><td>${esc(i.costing_qty)}</td><td>${esc(i.final_qty)}</td><td>${esc(i.final_wgt_kg)}</td></tr>`).join('')}</tbody></table></div>`; bootstrap.Modal.getOrCreateInstance(one('#labRecipeDetails')).show();}));
    run(async()=>{const r=(await api('filters')).data; r.statuses.forEach(s=>one('[data-lr-filter="status"]').add(new Option(s,s))); enhance(one('[data-lr-filter="status"]')); change(one('[data-lr-filter="status"]'),async()=>{page=1; await load();}); await load();})();
  } else {
    const form=one('[data-lr-form]'), picker=one('#lrDelivery'), customer=one('#lrCustomer'), jobCard=one('[data-lr-jobcard]'), tbody=one('[data-lr-items]'), save=one('[data-lr-save]'); let jobCards=[];
    const can=root.dataset.abilities.split(','); let sources=[],products=[],delivery=null,drafts={},active='',loading=true,request=0,costingRequest=0,copyTemplate=null; const copiedOptions=new Set();
    const defaults=()=>({recipe_date:(()=>{const d=new Date(); return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');})(),fabric_weight:1,sampling_qty:0,mlr:6,recipe_version:'V1',status:'Draft',lab_machine:'',remarks:'',lab_costing_id_fk:'',items:[]});
    const weight=(qty,unit,fabric,water)=>Number((unit==='gpl' ? qty*water/1000 : unit==='%' ? qty*fabric/100 : unit==='kg' ? qty : ['gm','ml'].includes(unit) ? qty/1000 : 0).toFixed(5));
    function capture() {if(!active || !drafts[active]) return; const d=drafts[active]; Array.from(form.elements).filter(x=>x.name).forEach(x=>d[x.name]=x.value); d.items=Array.from(tbody.rows).map(tr=>Object.fromEntries(Array.from(tr.querySelectorAll('[data-item]')).map(x=>[x.dataset.item,x.value])));}
    function calculate() {const fabric=Number(one('[name="fabric_weight"]').value)||0,water=Number((fabric*(Number(one('[name="mlr"]').value)||0)).toFixed(4)); one('#lrWater').value=water.toFixed(4); let c=0,f=0; Array.from(tbody.rows).forEach(tr=>{const unit=tr.querySelector('[data-item="unit"]').value.trim().toLowerCase(); const cw=weight(Number(tr.querySelector('[data-item="costing_qty"]').value)||0,unit,fabric,water),fw=weight(Number(tr.querySelector('[data-item="final_qty"]').value)||0,unit,fabric,water); tr.querySelector('[data-cw]').value=cw.toFixed(5); tr.querySelector('[data-fw]').textContent=fw.toFixed(5); tr.querySelector('[data-var]').textContent=cw ? ((fw-cw)/cw*100).toFixed(2) : '0.00'; c+=cw; f+=fw;}); const values={count:tbody.rows.length,costing:c.toFixed(5),final:f.toFixed(5),variance:c ? ((f-c)/c*100).toFixed(2) : '0.00'}; all('[data-lr-summary]').forEach(x=>x.textContent=values[x.dataset.lrSummary]); one('[data-lr-costing-total]').textContent=c.toFixed(5); capture();}
    function item(row={}) {
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${tbody.rows.length+1}</td><td><input class="form-control form-control-sm" data-item="category" value="${esc(row.category || 'Dyes')}" maxlength="20" pattern="[Dd][Yy][Ee][Ss]|[Cc][Hh][Ee][Mm][Ii][Cc][Aa][Ll][Ss]|[Aa][Uu][Xx]" title="Dyes, Chemicals or AUX" aria-label="Type"></td><td><select class="form-select form-select-sm" data-item="product_name" aria-label="Chemical or dye"><option value="">Select chemical or dye</option>${products.map(p=>`<option value="${esc(p.product_name)}">${esc(p.product_name)}</option>`).join('')}</select></td>`+
        ['costing_qty','unit'].map(k=>`<td>${k==='unit' ? `<input class="form-control form-control-sm" data-item="unit" value="${esc(row.unit || '%')}" maxlength="3" pattern="%|[Gg][Pp][Ll]|[Kk][Gg]|[Gg][Mm]|[Mm][Ll]" title="%, gpl, kg, gm or ml" aria-label="Unit">` : `<input class="form-control form-control-sm" data-item="${k}" type="number" min="0" step="${k==='rate' ? '0.0001' : '0.00001'}" value="${esc(row[k] ?? 0)}" aria-label="${esc(k.replaceAll('_',' '))}">`}</td>`).join('')+
        `<td><input class="form-control form-control-sm text-end bg-light" data-cw readonly aria-label="Costing weight in kilograms"><input type="hidden" data-item="rate" value="${esc(row.rate ?? 0)}"><input type="hidden" data-item="final_qty" value="${esc(row.final_qty ?? row.costing_qty ?? 0)}"><span class="d-none" data-fw></span></td><td data-var></td>`+`<td><input class="form-control form-control-sm" data-item="remarks" value="${esc(row.remarks)}" maxlength="255" aria-label="Item remarks"></td><td><button class="btn btn-outline-danger btn-sm" type="button" data-lr-remove-item aria-label="Remove ingredient"><i class="bi bi-trash3"></i></button></td>`;
      tbody.append(tr);
      const product=tr.querySelector('[data-item="product_name"]');
      // Preserve saved and imported names even if the master item is now inactive.
      if(row.product_name && !Array.from(product.options).some(o=>o.value===row.product_name)) product.add(new Option(row.product_name,row.product_name));
      product.value=row.product_name || '';
      window.CloudZoo.masters.select2(product);
      product.addEventListener('change',()=>{
        const p=products.find(p=>p.product_name===product.value);
        if(p) tr.querySelector('[data-item="rate"]').value=Number(p.purchase_price)||Number(p.selling_price)||0;
        calculate();
      });
    }
    function tabs() {
      one('[data-lr-tabs]').innerHTML=delivery.options.map(o=>{
        const d=drafts[o.tid], selected=String(o.tid)===active;
        return `<button class="cz-lab-option-card${selected ? ' is-active' : ''}" type="button" data-lr-option="${Number(o.tid)}" aria-pressed="${selected}"><span class="d-flex justify-content-between align-items-center gap-2"><strong>Option ${esc(o.option_letter)}</strong>${pill(d?.status || o.default_status || 'Draft')}</span><span class="cz-lab-option-card__colour">${esc(delivery.colour || '—')}</span>${o.recommendation || o.remarks ? `<span class="cz-lab-option-card__detail">${esc(o.recommendation || o.remarks)}</span>` : ''}<span class="cz-lab-option-card__detail">Costing: ${esc(d?.costing_no || 'Not linked')}</span>${d?.id ? '<span class="cz-lab-option-card__detail">Saved recipe</span>' : ''}</button>`;
      }).join('');
    }
    function show(id) {capture(); ++costingRequest; active=String(id); const o=delivery.options.find(o=>String(o.tid)===active),d=drafts[active]; if(!o || !d) return; one('[data-lr-add]').classList.toggle('d-none',loading); form.classList.remove('was-validated'); Array.from(form.elements).filter(x=>x.name).forEach(x=>{if(x.tagName==='SELECT' && d[x.name] && !Array.from(x.options).some(o=>o.value===String(d[x.name]))) x.add(new Option(d[x.name],d[x.name])); x.value=d[x.name] ?? ''; refresh(x);}); tbody.replaceChildren(); (d.items.length ? d.items : blankItems()).forEach(item); const info={option:o.option_letter,colour:delivery.colour,costing:d.costing_no || 'Not linked',cost:d.costing_rate || 0,recipe:d.recipe_no || 'RCP/'+delivery.tid+'/'+o.option_letter,remark:o.recommendation || o.remarks || '—',decision:o.customer_decision || 'Not decided'}; all('[data-lr-info]').forEach(x=>x.textContent=info[x.dataset.lrInfo]); tabs(); calculate(); save.disabled=loading || !(d.id ? can.includes('update') : can.includes('create'));}
    async function loadDelivery(id,recipeId) {capture(); one('[data-lr-add]').classList.add('d-none'); const token=++request; loading=true; save.disabled=true; ++costingRequest; active=''; drafts={}; copiedOptions.clear(); delivery=null; tbody.replaceChildren(); one('[data-lr-tabs]').textContent='Loading shade options…'; error(); if(!id) {loading=false; one('#lrLabNo').value=''; all('[data-lr-display]').forEach(x=>x.value=''); all('[data-lr-info]').forEach(x=>x.textContent='—'); all('[data-lr-summary]').forEach(x=>x.textContent='0'); one('#lrWater').value=''; one('#lrCosting').replaceChildren(new Option('Not linked (manual entry)','')); refresh(one('#lrCosting')); one('[data-lr-tabs]').textContent=customer.value ? 'Select a Lab Delivery to load its shade options.' : 'Select a Customer, then a Lab Delivery to load its shade options.'; return;} const d=(await api('delivery',undefined,{id})).data; if(token!==request) return; delivery=d; one('#lrLabNo').value=d.lab_no; one('[data-lr-display="fabric"]').value=d.fabric_name || ''; one('[data-lr-display="process"]').value=[d.gsm,d.process_type].filter(Boolean).join(' / '); one('[data-lr-display="submission"]').value=Number(d.submission_no)>1 ? 'Resubmission' : 'First Submission'; if(!Array.from(customer.options).some(o=>o.value===d.customer_name)) customer.add(new Option(d.customer_name,d.customer_name)); customer.value=d.customer_name; refresh(customer); if(!sources.some(s=>String(s.tid)===String(id))) sources.push({tid:id,customer_name:d.customer_name,label:d.lab_no+' · '+d.delivery_no}); rebuild(String(id),false); const costing=one('#lrCosting'); costing.replaceChildren(new Option('Not linked (manual entry)','')); d.costings.forEach(c=>costing.add(new Option(c.costing_no+' · '+(c.version || '')+' · '+(c.colour || ''),c.tid))); refresh(costing);
      const saved=await Promise.all(d.recipes.map(async r=>(await api('get',undefined,{id:r.tid})).data)); if(token!==request) return;
      d.options.forEach(o=>{const old=saved.find(r=>Number(r.delivery_option_id_fk)===Number(o.tid)); drafts[o.tid]=old ? {...old,id:Number(old.tid),items:old.items.map(x=>({...x}))} : {...defaults(),status:o.default_status};});
      const copyTarget=copyTemplate ? d.options.find(o=>!drafts[o.tid].id) : null;
      if(copyTarget) {
        cloneInto(String(copyTarget.tid),copyTemplate);
        // A costing from another delivery/customer must be chosen again from the target's candidates.
        const copied=drafts[copyTarget.tid];
        if(!d.costings.some(c=>Number(c.tid)===Number(copied.lab_costing_id_fk))) {copied.lab_costing_id_fk='';copied.costing_no='';copied.costing_rate=0;}
        copyTemplate=null;
      } else if(copyTemplate) error('All options in this delivery already have recipes. Select another delivery to create a copy.');
      loading=false; const first=copyTarget?.tid || d.options.find(o=>Number(o.tid)===Number(root.dataset.option))?.tid || saved.find(r=>Number(r.tid)===Number(recipeId))?.delivery_option_id_fk || d.options[0]?.tid; if(first) show(first); else {one('[data-lr-tabs]').textContent='This delivery has no shade options.';}}
    function rebuild(keep=picker.value,reload=true) {
      const previous=picker.value;
      const matches=customer.value ? sources.filter(d=>d.customer_name===customer.value) : [];
      picker.replaceChildren(new Option(!customer.value ? 'Select Customer first' : matches.length ? 'Select Lab Delivery' : 'No deliveries for this customer',''));
      matches.forEach(d=>picker.add(new Option(d.label,d.tid)));
      picker.value=Array.from(picker.options).some(x=>x.value===String(keep)) ? String(keep) : '';
      picker.disabled=!!Number(root.dataset.id) || !customer.value || !matches.length;
      refresh(picker);
      if(reload && (picker.value!==previous || !picker.value)) run(()=>loadDelivery(picker.value))();
    }
    change(picker,()=>loadDelivery(picker.value)); change(customer,()=>{const card=jobCards.find(c=>c.value===jobCard.value); if(card && card.customer!==customer.value) {jobCard.value=''; refresh(jobCard); jobCardNote();} rebuild('');});
    // Inward Cum JobCard No (Jobwork): its customer, and the Lab Delivery of the Lab Dip its GRN names, load into the recipe
    function jobCardNote(text='') {one('[data-lr-jobcard-note]').textContent=text;}
    change(jobCard,async()=>{
      const card=jobCards.find(c=>c.value===jobCard.value); if(!card || Number(root.dataset.id)) {jobCardNote(); return;}
      if(!Array.from(customer.options).some(o=>o.value===card.customer)) {jobCardNote(`No Lab Delivery for ${card.customer} yet.`); return;}
      if(customer.value!==card.customer) {customer.value=card.customer; refresh(customer); rebuild('',false);}
      const matches=sources.filter(d=>d.customer_name===card.customer && card.lab_dip_no && d.label.startsWith(card.lab_dip_no+' — '));
      jobCardNote(card.lab_dip_no ? (matches.length ? `Lab Dip ${card.lab_dip_no} (from the GRN)${matches.length>1 ? ' — choose the Lab Delivery' : ''}.` : `Lab Dip ${card.lab_dip_no} (from the GRN) has no Lab Delivery yet.`) : 'The GRN of this JobCard names no Lab Dip — choose the Lab Delivery.');
      if(matches.length===1 && picker.value!==String(matches[0].tid)) {picker.value=String(matches[0].tid); refresh(picker); await loadDelivery(picker.value);}
    });
    root.addEventListener('click',run(async e=>{const tab=e.target.closest('[data-lr-option]'); if(tab) show(tab.dataset.lrOption); const remove=e.target.closest('[data-lr-remove-item]'); if(remove) {remove.closest('tr').remove(); Array.from(tbody.rows).forEach((tr,i)=>tr.cells[0].textContent=i+1); calculate();}}));
    form.addEventListener('input',event=>{if(!active) return; if(event.target.matches('[data-item="costing_qty"]')) event.target.closest('tr').querySelector('[data-item="final_qty"]').value=event.target.value; calculate();});
    const blankItems=()=>Array.from({length:3},()=>({category:'Dyes',product_name:'',costing_qty:0,final_qty:0,rate:0,unit:'%',remarks:''}));
    async function loadCosting(id) {
      if(!active || loading) return;
      capture();
      const option=active, token=++costingRequest;
      save.disabled=true; error();
      try {
        const c=id ? (await api('costing',undefined,{id})).data : null;
        if(token!==costingRequest || option!==active) return;
        const d=drafts[option];
        d.lab_costing_id_fk=id || ''; d.costing_no=c?.costing_no || ''; d.costing_rate=c?.lp_cost || 0;
        d.fabric_weight=Number(c?.fabric_weight)>0 ? c.fabric_weight : 1;
        d.mlr=Number(c?.mlr)>0 ? c.mlr : 6;
        if(c?.version && /^V(?:[1-9]|10)$/.test(c.version)) d.recipe_version=c.version;
        d.items=c?.items?.length ? c.items.map(row=>({...row})) : blankItems();
        active=''; show(option);
      } catch(err) {
        if(token===costingRequest && option===active) error(err.message);
      } finally {
        if(option===active) save.disabled=loading || !(drafts[active]?.id ? can.includes('update') : can.includes('create'));
      }
    }
    all('select[name]').forEach(x=>change(x,async()=>{
      if(!active || loading) return;
      if(x.id==='lrCosting') {await loadCosting(x.value); return;}
      capture(); tabs();
    }));
    one('[data-lr-add]').addEventListener('click',()=>{if(active && !loading) {item(); calculate();}});
    one('[data-lr-reset]').addEventListener('click',run(async()=>{
      if(!active || !one('#lrCosting').value) throw new Error('Select a Lab Costing first.');
      if(tbody.rows.length && !confirm('Replace the current ingredient rows with dyes from the selected costing?')) return;
      await loadCosting(one('#lrCosting').value);
    }));
    function bindVesselMaster() {
      const select=one('#lrMachine');
      const reload=async()=>{
        try {
          const known=new Set(Array.from(select.options,o=>o.value));
          const selected=select.value;
          const opts=(await api('options')).data;
          const names=opts.vessels.map(v=>v.vessel_name);
          const added=names.find(name=>!known.has(name));
          window.CloudZoo.masters.fill(select,names,added || selected,'Select vessel');
          select.dispatchEvent(new Event('change',{bubbles:true}));
        } catch(err) { error(err.message); }
      };
      window.CloudZoo.masters.bindContactAdd(select,'Vessel',(window.CZ_BASE_URL || '')+'/dyeing/vessels/form',reload);
        const refreshButton=one('[data-lr-refresh-vessels]');
        refreshButton.addEventListener('click',async()=>{
          refreshButton.disabled=true;
          refreshButton.setAttribute('aria-busy','true');
          try { await reload(); }
          finally { refreshButton.disabled=false; refreshButton.removeAttribute('aria-busy'); }
        });
    }
    function cloneInto(target,source) {const identity=drafts[target]; drafts[target]={...identity,...Object.fromEntries(['fabric_weight','sampling_qty','mlr','lab_machine','lab_costing_id_fk','costing_no','costing_rate','remarks'].map(k=>[k,source[k]])),items:source.items.map(x=>({...x}))};}
    one('[data-lr-copy]').addEventListener('click',()=>{if(!active) return; capture(); const sel=one('#lrCopySource'); sel.replaceChildren(); delivery.options.filter(o=>String(o.tid)!==active).forEach(o=>sel.add(new Option('Option '+o.option_letter,o.tid))); if(!sel.options.length) {error('No other option is available to copy.'); return;} bootstrap.Modal.getOrCreateInstance(one('#lrCopyModal')).show();});
    one('[data-lr-copy-apply]').addEventListener('click',()=>{const source=drafts[one('#lrCopySource').value]; if(!source || !active) return; cloneInto(active,source); const id=active; active=''; show(id); bootstrap.Modal.getOrCreateInstance(one('#lrCopyModal')).hide();});
    one('[data-lr-copy-all]').addEventListener('click',()=>{
      if(!active || loading) return;
      if(!can.includes('create')) {error('You do not have permission to create option recipes.'); return;}
      capture();
      const targets=Object.keys(drafts).filter(id=>id!==active && !drafts[id].id);
      if(!targets.length) {error('There are no other unsaved options to copy into.'); return;}
      if(!drafts[active].items.some(row=>String(row.product_name || '').trim())) {error('Enter at least one chemical or dye before copying the recipe.'); return;}
      if(!confirm(`Copy this recipe to ${targets.length} unsaved option(s)? Save will save the selected option and these copies.`)) return;
      targets.forEach(id=>{cloneInto(id,drafts[active]); drafts[id].recipe_date=drafts[active].recipe_date; drafts[id].recipe_version=drafts[active].recipe_version; copiedOptions.add(id);});
      tabs(); error();
      one('[data-lr-success]').textContent=`Copied to ${targets.length} unsaved option(s). Click Save to save all copied recipes.`;
      one('[data-lr-success]').classList.remove('d-none');
    });
    const adjacent=delta=>{if(!delivery || !active) return; const index=delivery.options.findIndex(o=>String(o.tid)===active); const o=delivery.options[index+delta]; if(o) show(o.tid);}; one('[data-lr-option-prev]').addEventListener('click',()=>adjacent(-1)); one('[data-lr-option-next]').addEventListener('click',()=>adjacent(1));
    form.addEventListener('submit',e=>{e.preventDefault(); run(async()=>{
      form.classList.add('was-validated');
      if(!form.checkValidity() || !active || loading) return;
      capture();
      const targets=[...new Set([active,...copiedOptions])];
      if(targets.some(id=>!(drafts[id].id ? can.includes('update') : can.includes('create')))) throw new Error('You do not have permission to save all selected option recipes.');
      const deliveryId=Number(picker.value);
      const payloads=targets.map(id=>({option:id,data:{...drafts[id],items:drafts[id].items.map(row=>({...row})),lab_dip_delivery_id_fk:deliveryId,delivery_option_id_fk:Number(id),jobcard_id:Number(jobCard.value) || 0}}));
      const controls=Array.from(form.elements).map(control=>({control,disabled:control.disabled}));
      controls.forEach(({control})=>control.disabled=true); loading=true; error();
      one('[data-lr-success]').classList.add('d-none');
      let completed=0;
      try {
        for(const {option,data} of payloads) {
          try {
            const r=await api('save',data);
            drafts[option].id=r.id;
            drafts[option].recipe_no='RCP/'+deliveryId+'/'+delivery.options.find(o=>String(o.tid)===option).option_letter;
            copiedOptions.delete(option); ++completed;
          } catch(err) {throw new Error(`${completed} recipe(s) saved. Option ${delivery.options.find(o=>String(o.tid)===option).option_letter}: ${err.message} Remaining copies are kept; correct the error and save again.`);}
        }
        location.assign(root.dataset.list);
      } finally {
        loading=false; controls.forEach(({control,disabled})=>control.disabled=disabled);
        tabs(); save.disabled=!(drafts[active]?.id ? can.includes('update') : can.includes('create'));
      }
    })();});
    run(async()=>{save.disabled=true; const opts=(await api('options')).data; sources=opts.deliveries; products=opts.products; jobCards=opts.jobcards || []; jobCards.forEach(c=>jobCard.add(new Option(c.label,c.value))); [...new Set(sources.map(d=>d.customer_name))].forEach(c=>customer.add(new Option(c,c))); sources.forEach(d=>picker.add(new Option(d.label,d.tid))); opts.vessels.forEach(v=>one('#lrMachine').add(new Option(v.vessel_name,v.vessel_name))); opts.statuses.forEach(s=>one('#lrStatus').add(new Option(s,s))); all('select').forEach(x=>enhance(x,x.id==='lrCopySource' ? one('#lrCopyModal') : null)); bindVesselMaster(); if(Number(root.dataset.copy)) {copyTemplate=(await api('get',undefined,{id:root.dataset.copy})).data; const source=sources.find(d=>Number(d.tid)===Number(copyTemplate.lab_dip_delivery_id_fk)); if(source) {customer.value=source.customer_name; rebuild(String(source.tid),false); await loadDelivery(source.tid);} else {loading=false; rebuild('',false); error('Select a customer and delivery to copy this recipe into.');}} else if(Number(root.dataset.id)) {const r=(await api('get',undefined,{id:root.dataset.id})).data; if(!Array.from(picker.options).some(x=>x.value===String(r.lab_dip_delivery_id_fk))) picker.add(new Option(r.delivery_no,r.lab_dip_delivery_id_fk)); picker.value=r.lab_dip_delivery_id_fk; refresh(picker); if(Number(r.jobcard_id)) {if(!Array.from(jobCard.options).some(x=>x.value===String(r.jobcard_id))) jobCard.add(new Option(r.jobcard_no || String(r.jobcard_id),r.jobcard_id)); jobCard.value=String(r.jobcard_id); refresh(jobCard);} await loadDelivery(picker.value,r.tid);} else if(Number(root.dataset.delivery)) {picker.value=root.dataset.delivery; refresh(picker); await loadDelivery(picker.value);} else {loading=false; rebuild('',false);}})();
  }
})();

// Shade Approval: receipts, approved-recipe import and confirmation-to-bulk comparisons.
(() => {
  const root=document.querySelector('[data-cz-shade-approval]'); if(!root) return;
  const one=s=>root.querySelector(s), all=s=>Array.from(root.querySelectorAll(s));
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message='') {one('[data-sa-error]').textContent=message; one('[data-sa-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {const url=new URL(root.dataset.api,location.origin); url.search=new URLSearchParams({action,...query}); const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); const r=await response.json(); if(!response.ok || !r.success) throw new Error(r.message || 'Unable to complete request.'); return r;}
  const enhance=(x,parent)=>{if(window.jQuery?.fn.select2) jQuery(x).select2({width:'100%',...(parent ? {dropdownParent:jQuery(parent)} : {})});};
  const change=(x,fn)=>{if(window.jQuery?.fn.select2) jQuery(x).on('change',run(fn)); else x.addEventListener('change',run(fn));};
  const refresh=x=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');};
  const pill=s=>`<span class="cz-badge ${s==='OK' ? 'success' : s==='Not OK' ? 'danger' : s==='Hold' ? 'warning' : 'neutral'}">${esc(s || '—')}</span>`;
  function dyeTable(dyes) {return `<div class="table-responsive"><table class="table cz-table"><thead><tr><th>Material</th><th>Approval</th><th>Conf. 1</th><th>Conf. 2</th><th>Unit</th><th>Lab to Bulk</th><th>Diff %</th></tr></thead><tbody>${dyes.map(d=>`<tr>${['material_description','quantity','conf_recipe_1','conf_recipe_2','unit','lab_to_bulk_recipe','diff_percent'].map(k=>`<td>${esc(d[k])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;}
  if(root.dataset.czShadeApproval==='list') {
    let page=1,pages=1,sort='tid',dir='desc',request=0; const can=root.dataset.abilities.split(',');
    const ids=()=>all('[data-sa-check]:checked').map(x=>Number(x.value));
    function selection() {const n=ids().length,count=all('[data-sa-check]').length; one('[data-sa-selected]').textContent=n; one('[data-sa-selection]').classList.toggle('d-none',!n); one('[data-sa-selection]').classList.toggle('d-flex',!!n); one('[data-sa-all]').checked=count>0 && n===count; one('[data-sa-all]').indeterminate=n>0 && n<count;}
    async function load() {const token=++request,q=Object.fromEntries(all('[data-sa-filter]').map(x=>[x.dataset.saFilter,x.value])); const r=await api('list',undefined,{...q,page,sort,dir}); if(token!==request) return; error(); pages=r.pages;
      one('[data-sa-rows]').innerHTML=r.rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-sa-check value="${Number(d.tid)}" aria-label="Select shade approval ${esc(d.entry_no)}"></td><td><div class="fw-semibold">${esc(d.customer_name)}</div><small class="text-secondary">#${Number(d.tid)}</small></td><td>${esc(d.generate_no ?? d.entry_no)}</td><td>${esc(d.entry_date)}</td><td>${esc(d.color)}<small class="text-secondary d-block">${esc(d.color_shade)}</small></td>${['material','pantone','as_no','option_letter','lab_ref_display','approval_date'].map(k=>`<td>${esc(d[k] || '—')}</td>`).join('')}<td>${pill(d.result)}</td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Shade approval actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu"><li><button class="cz-action-menu__item" type="button" data-sa-info="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>${can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li>` : ''}${can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-sa-remove="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join(''); one('[data-sa-empty]').classList.toggle('d-none',!!r.rows.length); one('[data-sa-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${r.total ? (page-1)*25+r.rows.length : 0} of ${r.total} entries`; one('[data-sa-prev]').disabled=page<=1; one('[data-sa-next]').disabled=page>=pages; selection();}
    async function remove(selected) {if(!selected.length) throw new Error('Select a shade approval.'); if(!confirm('Delete the selected shade approvals and their dye rows?')) return; await api('delete',{ids:selected}); await load();}
    let timer; all('[data-sa-filter]').filter(x=>x.tagName!=='SELECT').forEach(x=>x.addEventListener('input',()=>{clearTimeout(timer); timer=setTimeout(run(async()=>{page=1; await load();}),250);})); all('[data-sa-clear]').forEach(x=>x.addEventListener('click',run(async()=>{all('[data-sa-filter]').forEach(x=>{x.value=''; refresh(x);}); page=1; await load();})));
    one('[data-sa-apply]').addEventListener('click',run(async()=>{page=1; await load(); bootstrap.Offcanvas.getOrCreateInstance(one('#saFilters')).hide();})); one('[data-sa-prev]').addEventListener('click',run(async()=>{page--; await load();})); one('[data-sa-next]').addEventListener('click',run(async()=>{page++; await load();}));
    one('[data-sa-all]').addEventListener('change',e=>{all('[data-sa-check]').forEach(x=>x.checked=e.target.checked); selection();}); root.addEventListener('change',e=>{if(e.target.matches('[data-sa-check]')) selection();}); one('[data-sa-delete]').addEventListener('click',run(()=>remove(ids())));
    all('[data-sa-sort]').forEach(b=>b.addEventListener('click',run(async()=>{dir=sort===b.dataset.saSort && dir==='asc' ? 'desc' : 'asc'; sort=b.dataset.saSort; page=1; all('[data-sa-sort]').forEach(x=>{x.querySelector('i').className=x===b ? (dir==='asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down') : 'bi bi-arrow-down-up'; x.closest('th').removeAttribute('aria-sort');}); b.closest('th').setAttribute('aria-sort',dir==='asc' ? 'ascending' : 'descending'); await load();})));
    root.addEventListener('click',run(async e=>{const del=e.target.closest('[data-sa-remove]'); if(del) await remove([Number(del.dataset.saRemove)]); const info=e.target.closest('[data-sa-info]'); if(!info) return; const d=(await api('get',undefined,{id:info.dataset.saInfo})).data; one('[data-sa-details]').innerHTML=`<dl class="row">${['entry_no','entry_date','customer_name','customer_address','color','color_shade','material','pantone','as_no','option_letter','lab_ref_display','approval_date','result','remarks'].map(k=>`<dt class="col-sm-4">${esc(k.replaceAll('_',' '))}</dt><dd class="col-sm-8">${esc(d[k] || '—')}</dd>`).join('')}</dl><h3 class="fs-6">Dyes</h3>${dyeTable(d.dyes)}`; bootstrap.Modal.getOrCreateInstance(one('#saDetails')).show();}));
    run(async()=>{const f=(await api('filters')).data; f.results.forEach(x=>one('[data-sa-filter="result"]').add(new Option(x,x))); f.customers.forEach(x=>one('[data-sa-filter="customer_name"]').add(new Option(x,x))); all('select[data-sa-filter]').forEach(x=>{enhance(x,x.dataset.saFilter==='customer_name' ? one('#saFilters') : null); change(x,async()=>{page=1; await load();});}); await load();})();
  } else {
    const form=one('[data-sa-form]'), customer=one('#saCustomer'), reference=one('#saLabRef'), tbody=one('[data-sa-dyes]'), save=one('[data-sa-save]'); let references=[],products=[],loading=true,request=0,customerRequest=0,loadedReference='';
    function extended() {const visible=one('#saResult').value==='OK'; all('[data-sa-extended]').forEach(x=>x.classList.toggle('d-none',!visible)); one('[data-sa-copy]').classList.toggle('d-none',!visible); one('[data-sa-qty-title]').textContent=visible ? 'Approval Recipe' : 'Quantity';}
    function calculate() {Array.from(tbody.rows).forEach((tr,i)=>{tr.cells[0].textContent=i+1; const value=k=>Number(tr.querySelector(`[data-dye="${k}"]`).value)||0; const qty=value('quantity'),conf=value('conf_recipe_2') || value('conf_recipe_1'); tr.querySelector('[data-diff]').textContent=(qty ? (conf-qty)/qty*100 : 0).toFixed(2);});}
    function add(row={}) {const tr=document.createElement('tr'); tr.innerHTML=`<td></td><td><input class="form-control form-control-sm" data-dye="material_description" value="${esc(row.material_description)}" maxlength="255" placeholder="Enter material description" aria-label="Material description"></td>`+['quantity','conf_recipe_1','conf_recipe_2'].map(k=>`<td ${k!=='quantity' ? 'data-sa-extended' : ''}><input class="form-control form-control-sm" data-dye="${k}" type="number" step="0.00001" min="0" max="999999999.99999" value="${esc(row[k] ?? 0)}" aria-label="${esc(k.replaceAll('_',' '))}"></td>`).join('')+`<td><input class="form-control form-control-sm" data-dye="unit" value="${esc(row.unit || '%')}" maxlength="20" required aria-label="Unit"></td><td data-sa-extended><input class="form-control form-control-sm" data-dye="lab_to_bulk_recipe" type="number" step="0.00001" min="0" max="999999999.99999" value="${esc(row.lab_to_bulk_recipe ?? 0)}" aria-label="Lab to bulk recipe"></td><td data-sa-extended data-diff></td><td><button class="btn btn-outline-danger btn-sm" type="button" data-sa-remove-dye aria-label="Remove dye"><i class="bi bi-trash3"></i></button></td>`; tbody.append(tr); calculate(); extended();}
    function rebuild(keep='') {reference.replaceChildren(new Option('Manual entry (no Lab Ref)','')); references.filter(r=>!customer.value || r.customer_name===customer.value).forEach(r=>reference.add(new Option(r.label,r.value))); reference.value=keep; refresh(reference);}
    async function loadReference() {
      const token=++request; ++customerRequest; error(); one('[data-sa-source-note]').textContent='';
      if(!reference.value) {loadedReference=''; one('[name="option_letter"]').value=''; loading=false; save.disabled=false; return;}
      if(tbody.rows.length && !confirm('Load the approved Lab Recipe? This replaces the current shade details and dye rows.')) {reference.value=loadedReference; refresh(reference); return;}
      loading=true; save.disabled=true;
      try {const r=(await api('reference',undefined,{id:Number(reference.value.slice(4))})).data; if(token!==request) return;
        Object.entries(r.header).forEach(([k,v])=>{const x=form.elements.namedItem(k); if(!x) return; if(x.tagName==='SELECT' && v && !Array.from(x.options).some(o=>o.value===String(v))) x.add(new Option(v,v)); x.value=v ?? ''; refresh(x);}); tbody.replaceChildren(); r.dyes.forEach(add); extended(); one('[data-sa-source-note]').textContent=r.recipe_found ? 'Loaded the approved option and its dye recipe. Review before saving.' : (r.option_letter ? 'An approved shade exists, but no Lab Recipe is saved for it. Enter dyes manually.' : 'This Lab Ref has no customer-approved shade. Enter details manually.');
        loadedReference=reference.value;
      } catch(e) {if(token===request) {reference.value=loadedReference; refresh(reference);} throw e;} finally {if(token===request) {loading=false; save.disabled=false;}}
    }
    change(reference,loadReference);
    change(customer,async()=>{const token=++customerRequest; ++request; loading=false; save.disabled=false; const name=customer.value; loadedReference=''; rebuild(); one('[name="option_letter"]').value=''; one('[data-sa-source-note]').textContent=''; if(!name) {one('[name="customer_address"]').value=''; return;} const r=(await api('customer',undefined,{name})).data; if(token===customerRequest && name===customer.value) one('[name="customer_address"]').value=r.address || '';});
    change(one('#saResult'),extended); tbody.addEventListener('input',calculate); tbody.addEventListener('click',e=>{const b=e.target.closest('[data-sa-remove-dye]'); if(b) {b.closest('tr').remove(); calculate();}}); one('[data-sa-add]').addEventListener('click',()=>add());
    one('[data-sa-copy]').addEventListener('click',()=>bootstrap.Modal.getOrCreateInstance(one('#saCopy')).show()); one('[data-sa-copy-apply]').addEventListener('click',()=>{const key=one('#saCopySource').value; Array.from(tbody.rows).forEach(tr=>tr.querySelector('[data-dye="lab_to_bulk_recipe"]').value=tr.querySelector(`[data-dye="${key}"]`).value); calculate(); bootstrap.Modal.getOrCreateInstance(one('#saCopy')).hide();}); one('[data-sa-print]').addEventListener('click',()=>window.print());
    form.addEventListener('submit',e=>{e.preventDefault(); run(async()=>{form.classList.add('was-validated'); if(!form.checkValidity() || loading) return; save.disabled=true; error(); try {const data=Object.fromEntries(new FormData(form)); data.id=Number(root.dataset.id); data.dyes=Array.from(tbody.rows).map(tr=>Object.fromEntries(Array.from(tr.querySelectorAll('[data-dye]')).map(x=>[x.dataset.dye,x.value]))); await api('save',data); location.assign(root.dataset.list);} finally {save.disabled=false;}})();});
    run(async()=>{save.disabled=true; const opts=(await api('options')).data; products=opts.products; references=opts.references; [...new Set([...opts.customers,...references.map(r=>r.customer_name)])].filter(Boolean).forEach(c=>customer.add(new Option(c,c))); opts.results.forEach(r=>one('#saResult').add(new Option(r,r))); rebuild();
      if(Number(root.dataset.id)) {const d=(await api('get',undefined,{id:root.dataset.id})).data; if(d.customer_name && !Array.from(customer.options).some(x=>x.value===d.customer_name)) customer.add(new Option(d.customer_name,d.customer_name)); customer.value=d.customer_name; loadedReference=d.lab_ref || ''; rebuild(loadedReference); if(d.lab_ref && !Array.from(reference.options).some(x=>x.value===d.lab_ref)) reference.add(new Option(d.lab_ref_display || d.lab_ref,d.lab_ref)); Array.from(form.elements).filter(x=>x.name).forEach(x=>{if(x.name in d) x.value=d[x.name] ?? '';}); d.dyes.forEach(add); one('[data-sa-heading]').textContent='— '+(d.generate_no ?? d.entry_no);}
      else {const now=new Date(),date=[now.getFullYear(),String(now.getMonth()+1).padStart(2,'0'),String(now.getDate()).padStart(2,'0')].join('-'); one('#saDate').value=date; one('#saApprovalDate').value=date; one('[data-sa-heading]').textContent='— '+opts.next_number;}
      all('select').forEach(x=>{if(!x.matches('[data-dye]')) enhance(x,x.id==='saCopySource' ? one('#saCopy') : null); refresh(x);}); extended(); loading=false; save.disabled=false;
    })();
  }
})();

// Lab Costing: five-stage recipe costing, imports, status review and lists.
(() => {
  const root=document.querySelector('[data-cz-lab-costing]'); if(!root) return;
  const one=s=>root.querySelector(s), all=s=>Array.from(root.querySelectorAll(s));
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message='') {one('[data-lc-error]').textContent=message; one('[data-lc-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {const url=new URL(root.dataset.api,location.origin); url.search=new URLSearchParams({action,...query}); const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); const r=await response.json(); if(!response.ok || !r.success) throw new Error(r.message || 'Unable to complete request.'); return r;}
  const enhance=(x,parent)=>{if(window.jQuery?.fn.select2) jQuery(x).select2({width:'100%',...(parent ? {dropdownParent:jQuery(parent)} : {})});};
  const change=(x,fn)=>{if(window.jQuery?.fn.select2) jQuery(x).on('change',run(fn)); else x.addEventListener('change',run(fn));};
  const refresh=x=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');};
  const pill=s=>`<span class="cz-badge ${s==='Approved' ? 'success' : ['Rejected','Cancelled'].includes(s) ? 'danger' : 'warning'}">${esc(s || 'Pending')}</span>`;
  if(root.dataset.czLabCosting==='list') {
    let page=1,pages=1,sort='tid',dir='desc',request=0,statusId=0; const can=root.dataset.abilities.split(',');
    const ids=()=>all('[data-lc-check]:checked').map(x=>Number(x.value));
    function selection() {const n=ids().length,count=all('[data-lc-check]').length; one('[data-lc-selected]').textContent=n; one('[data-lc-selection]').classList.toggle('d-none',!n); one('[data-lc-selection]').classList.toggle('d-flex',!!n); one('[data-lc-all]').checked=count>0 && n===count; one('[data-lc-all]').indeterminate=n>0 && n<count;}
    async function load() {const token=++request,q=Object.fromEntries(all('[data-lc-filter]').map(x=>[x.dataset.lcFilter,x.value])); const r=await api('list',undefined,{...q,page,sort,dir}); if(token!==request) return; error(); pages=r.pages;
      one('[data-lc-rows]').innerHTML=r.rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-lc-check value="${Number(d.tid)}" aria-label="Select costing ${esc(d.costing_no)}"></td><td><div class="fw-semibold">${esc(d.customer_name)}</div><small class="text-secondary">Ref: ${esc(d.lab_reference || "—")}</small></td><td>${esc(d.generate_no ?? d.costing_no)}</td><td class="text-nowrap">${esc(d.costing_date)}</td><td><span class="cz-badge neutral">${esc(d.process_type || "—")}</span></td><td><div class="fw-semibold">${esc(d.colour || "—")}</div><small class="text-secondary">${esc(d.fabric || "—")}</small></td><td class="text-end">${Number(d.fabric_weight || 0).toFixed(3)}</td>${["total_recipe_cost","lp_cost","rp_cost"].map(k=>`<td class="text-end">₹ ${Number(d[k] || 0).toLocaleString("en-IN",{minimumFractionDigits:2,maximumFractionDigits:2})}</td>`).join("")}<td><a class="text-decoration-none" href="${esc(root.dataset.form.replace(/\/form$/, '/approval'))}?id=${Number(d.tid)}" aria-label="Review approval for costing ${esc(d.costing_no)}">${pill(d.status)}</a></td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Costing actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu"><li><button class="cz-action-menu__item" type="button" data-lc-info="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>${can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li><li><button class="cz-action-menu__item" type="button" data-lc-status="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-toggles"></i></span><span>Approval Report</span></button></li>` : ''}${can.includes('create') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?copy=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-copy"></i></span><span>Copy</span></a></li>` : ''}${can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-lc-remove="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join(''); const labels=Array.from(one('.cz-lab-costing-list thead').querySelectorAll('th'),th=>th.textContent.trim()); all('[data-lc-rows] tr').forEach(tr=>Array.from(tr.cells).forEach((cell,i)=>cell.dataset.label=labels[i])); all('[data-lc-total]').forEach(el=>{const key=el.dataset.lcTotal;const value=Number(r.totals?.[key] || 0);el.textContent=key==='fabric_weight' ? value.toFixed(3) : '₹ '+value.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}); one('[data-lc-empty]').classList.toggle('d-none',!!r.rows.length); one('[data-lc-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${r.total ? (page-1)*25+r.rows.length : 0} of ${r.total} entries`; one('[data-lc-prev]').disabled=page<=1; one('[data-lc-next]').disabled=page>=pages; selection();}
    async function remove(selected) {if(!selected.length) throw new Error('Select a costing.'); if(!confirm('Delete the selected costings and their ingredient rows?')) return; await api('delete',{ids:selected}); await load();}
    let timer; all('[data-lc-filter]').filter(x=>x.tagName!=='SELECT').forEach(x=>x.addEventListener('input',()=>{clearTimeout(timer); timer=setTimeout(run(async()=>{page=1; await load();}),250);})); all('[data-lc-clear]').forEach(x=>x.addEventListener('click',run(async()=>{all('[data-lc-filter]').forEach(x=>{x.value=''; refresh(x);}); page=1; await load();})));
    one('[data-lc-apply]').addEventListener('click',run(async()=>{page=1; await load(); bootstrap.Offcanvas.getOrCreateInstance(one('#lcFilters')).hide();})); one('[data-lc-prev]').addEventListener('click',run(async()=>{page--; await load();})); one('[data-lc-next]').addEventListener('click',run(async()=>{page++; await load();})); one('[data-lc-all]').addEventListener('change',e=>{all('[data-lc-check]').forEach(x=>x.checked=e.target.checked); selection();}); root.addEventListener('change',e=>{if(e.target.matches('[data-lc-check]')) selection();}); one('[data-lc-delete]').addEventListener('click',run(()=>remove(ids())));
    all('[data-lc-sort]').forEach(b=>b.addEventListener('click',run(async()=>{dir=sort===b.dataset.lcSort && dir==='asc' ? 'desc' : 'asc'; sort=b.dataset.lcSort; page=1; all('[data-lc-sort]').forEach(x=>{x.querySelector('i').className=x===b ? (dir==='asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down') : 'bi bi-arrow-down-up'; x.closest('th').removeAttribute('aria-sort');}); b.closest('th').setAttribute('aria-sort',dir==='asc' ? 'ascending' : 'descending'); await load();})));
    root.addEventListener('click',run(async e=>{const del=e.target.closest('[data-lc-remove]'); if(del) await remove([Number(del.dataset.lcRemove)]); const status=e.target.closest('[data-lc-status]'); if(status) {location.assign(root.dataset.form.replace(/\/form$/, '/approval')+'?id='+Number(status.dataset.lcStatus)); return;} const info=e.target.closest('[data-lc-info]'); if(!info) return; const d=(await api('get',undefined,{id:info.dataset.lcInfo})).data;
      one('[data-lc-details]').innerHTML=`<dl class="row">${['costing_no','costing_date','customer_name','lab_reference','as_no','version','process_type','colour','fabric','fabric_weight','mlr','water_level','chemical_cost','dyes_cost','total_recipe_cost','cost_per_kg','buffer_percent','buffer_value','overheads','lp_cost','rp_percent','rp_cost','margin','total_recipe_weight','status','status_remarks','approved_by','approved_on','remarks'].map(k=>`<dt class="col-sm-4">${esc(k.replaceAll('_',' '))}</dt><dd class="col-sm-8">${esc(d[k] ?? '—')}</dd>`).join('')}</dl><div class="table-responsive"><table class="table cz-table"><thead><tr><th>Section</th><th>Product</th><th>Qty</th><th>Unit</th><th>Weight (KG)</th><th>Rate</th><th>Amount</th></tr></thead><tbody>${d.items.map(i=>`<tr>${['process_tab','product_name','qty','unit','wgt_kg','rate','amount'].map(k=>`<td>${esc(i[k])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`; bootstrap.Modal.getOrCreateInstance(one('#lcDetails')).show();}));
    one('[data-lc-status-save]').addEventListener('click',run(async()=>{const b=one('[data-lc-status-save]'); b.disabled=true; try {await api('update_status',{id:statusId,status:one('#lcNewStatus').value,remarks:one('#lcStatusRemarks').value}); bootstrap.Modal.getOrCreateInstance(one('#lcStatusModal')).hide(); await load();} finally {b.disabled=false;}}));
    run(async()=>{const f=(await api('filters')).data; f.statuses.forEach(x=>{one('[data-lc-filter="status"]').add(new Option(x,x)); one('#lcNewStatus').add(new Option(x,x));}); f.customers.forEach(x=>one('[data-lc-filter="customer_name"]').add(new Option(x,x))); all('select[data-lc-filter]').forEach(x=>{enhance(x,x.dataset.lcFilter==='customer_name' ? one('#lcFilters') : null); change(x,async()=>{page=1; await load();});}); enhance(one('#lcNewStatus'),one('#lcStatusModal')); await load();})();
  } else {
    const form=one('[data-lc-form]'),customer=one('#lcCustomer'),reference=one('#lcRef'),save=one('[data-lc-save]'); const stages=['Pre Treatment','Dyeing Chemicals','Dyestuff Details','After Treatment','Others']; let products=[],references=[],loading=true,importRequest=0;
    const n=name=>Number(form.elements.namedItem(name)?.value)||0;
    const round=(v,p=2)=>Number(v.toFixed(p));
    function calc() {const fabric=n('fabric_weight'),water=round(fabric*n('mlr'),4); one('#lcWater').value=water.toFixed(4); let chem=0,dyes=0,totalWeight=0;
      stages.forEach((stage,index)=>{let stageWeight=0,stageAmount=0; const tbody=one(`[data-lc-stage="${index}"]`); Array.from(tbody.rows).forEach((tr,i)=>{tr.cells[0].textContent=i+1; const val=k=>tr.querySelector(`[data-cost-item="${k}"]`).value; const qty=Number(val('qty'))||0,unit=val('unit').trim().toLowerCase(); const weight=round(['gpl','g/l','g/ltr','g/lts'].includes(unit) ? qty*water/1000 : ['%','percent','percentage'].includes(unit) ? qty*fabric/100 : ['gm','gms','g','ml'].includes(unit) ? qty/1000 : qty,5); const amount=round(weight*(Number(val('rate'))||0)); tr.querySelector('[data-lc-weight]').textContent=weight.toFixed(5); tr.querySelector('[data-lc-amount]').textContent=amount.toFixed(2); stageWeight+=weight; stageAmount+=amount; if(index===2 || val('category').toLowerCase().includes('dye')) dyes+=amount; else chem+=amount;}); totalWeight+=stageWeight; one(`[data-lc-counter="${index}"]`).textContent=tbody.rows.length; one(`[data-lc-stage-weight="${index}"]`).textContent=stageWeight.toFixed(5); one(`[data-lc-stage-amount="${index}"]`).textContent=stageAmount.toFixed(2);});
      one('[data-lc-counter="1"]').textContent=one('[data-lc-stage="1"]').rows.length+one('[data-lc-stage="2"]').rows.length;
      const total=chem+dyes,per=fabric>0 ? total/fabric : 0,buffer=per*n('buffer_percent')/100,lp=per+buffer+n('overheads'),rp=lp*(1+n('rp_percent')/100); const totals={chemical_cost:chem,dyes_cost:dyes,total_recipe_cost:total,cost_per_kg:per,buffer_value:buffer,lp_cost:lp,rp_cost:rp,margin:rp-lp,total_recipe_weight:totalWeight}; all('[data-lc-total]').forEach(x=>x.textContent=totals[x.dataset.lcTotal].toFixed(x.dataset.lcTotal==='total_recipe_weight' ? 5 : 2));
    }
    function add(index,row={}) {const tbody=one(`[data-lc-stage="${index}"]`),tr=document.createElement('tr'); const available=products.filter(p=>index===4 || (index===2 ? /dye/i.test(p.category) : !/dye/i.test(p.category))); tr.innerHTML=`<td></td><td><input class="form-control form-control-sm" data-cost-item="category" value="${esc(index===2 ? 'Dyes' : row.category || 'Chemicals')}" maxlength="100" aria-label="Type" ${index===2 ? 'readonly' : ''}></td><td><select class="form-select form-select-sm" data-cost-item="product_name" aria-label="Product"><option value="">Select chemical or dye</option>${available.map(p=>`<option value="${esc(p.product_name)}">${esc(p.product_name)}${p.sku && p.sku!==p.product_name ? ' · '+esc(p.sku) : ''}</option>`).join('')}</select></td><td><input class="form-control form-control-sm" data-cost-item="qty" type="number" min="0" step="0.00001" value="${esc(row.qty ?? 0)}" aria-label="Quantity"></td><td><input class="form-control form-control-sm" data-cost-item="unit" maxlength="50" value="${esc(index===2 ? '%' : row.unit || 'gpl')}" aria-label="Unit" ${index===2 ? 'readonly' : ''}></td><td data-lc-weight></td><td><input class="form-control form-control-sm" data-cost-item="rate" type="number" min="0" step="0.01" value="${esc(row.rate ?? 0)}" aria-label="Rate per kilogram"><button class="btn btn-link btn-sm text-secondary p-0 d-none" type="button" data-lc-max></button></td><td data-lc-amount></td><td><button class="btn btn-outline-danger btn-sm" type="button" data-lc-remove-item aria-label="Remove ingredient"><i class="bi bi-trash3"></i></button></td>`; tbody.append(tr); const select=tr.querySelector('select'); const product=products.find(p=>p.product_name===row.product_name || p.sku===row.product_name); const selected=product?.product_name || row.product_name || ''; if(selected && !Array.from(select.options).some(x=>x.value===selected)) select.add(new Option(product ? product.product_name : selected,selected)); select.value=selected;
      function hint() {const p=products.find(p=>p.product_name===select.value),max=Number(p?.max_rate)||Number(p?.purchase_price)||0,b=tr.querySelector('[data-lc-max]'); b.dataset.rate=max; b.textContent='Max: '+max.toFixed(2); b.classList.toggle('d-none',max<=0);}
      enhance(select,root); change(select,()=>{const p=products.find(p=>p.product_name===select.value); if(p) {tr.querySelector('[data-cost-item="rate"]').value=Number(p.last_rate)||Number(p.purchase_price)||Number(p.selling_price)||0; tr.querySelector('[data-cost-item="category"]').value=index===2 ? 'Dyes' : p.category || 'Chemicals'; tr.querySelector('[data-cost-item="unit"]').value=index===2 ? '%' : 'gpl';} hint(); calc();}); hint(); calc();
    }
    function replace(items,onlyDyes=false) {if(onlyDyes) one('[data-lc-stage="2"]').replaceChildren(); else all('[data-lc-stage]').forEach(x=>x.replaceChildren()); items.forEach(row=>{const index=onlyDyes ? 2 : Math.max(0,stages.indexOf(row.process_tab)); add(index,row);}); calc();}
    function rebuild(keep='') {reference.replaceChildren(new Option('Select Lab Reference','')); references.filter(r=>!customer.value || r.customer_name===customer.value).forEach(r=>reference.add(new Option(r.label,r.value))); reference.value=keep; refresh(reference);}
    change(customer,()=>{++importRequest; rebuild();}); change(reference,()=>{++importRequest; const r=references.find(r=>r.value===reference.value); if(!r) return; for(const [key,value] of Object.entries({customer_name:r.customer_name,as_no:r.cs_no,colour:r.colour,fabric:r.fabric_name,process_type:r.process_type})) {const x=form.elements.namedItem(key); if(x.tagName==='SELECT' && value && !Array.from(x.options).some(o=>o.value===value)) x.add(new Option(value,value)); x.value=value || ''; refresh(x);}});
    all('[data-lc-add]').forEach(b=>b.addEventListener('click',()=>{if(!loading) add(Number(b.dataset.lcAdd));})); form.addEventListener('input',calc); form.addEventListener('click',e=>{const remove=e.target.closest('[data-lc-remove-item]'); if(remove) {remove.closest('tr').remove(); calc();} const max=e.target.closest('[data-lc-max]'); if(max) {max.closest('tr').querySelector('[data-cost-item="rate"]').value=max.dataset.rate; calc();}});
    one('[data-lc-load-process]').addEventListener('click',run(async()=>{const name=one('#lcProcess').value; if(!name) throw new Error('Select a Process Type first.'); if(all('[data-cost-item="product_name"]').length && !confirm('Replace all recipe sections with the selected process template?')) return; const token=++importRequest,r=(await api('process',undefined,{name})).data; if(token!==importRequest || name!==one('#lcProcess').value) return; if(!r.items.length) throw new Error('This process has no recipe items.'); r.items.forEach(i=>{const p=products.find(p=>p.product_name===i.product_name || p.sku===i.product_name); if(p) i.rate=Number(p.last_rate)||Number(p.selling_price)||Number(i.rate)||0;}); replace(r.items);}));
    change(one('#lcProcess'),()=>{++importRequest;});
    one('[data-lc-load-recipe]').addEventListener('click',run(async()=>{const ref=reference.value; if(!/^LAB-\d+$/.test(ref)) throw new Error('Select a Lab Reference first.'); if(one('[data-lc-stage="2"]').rows.length && !confirm('Replace Dyestuff Details with the customer-approved recipe quantities?')) return; const token=++importRequest,r=(await api('recipe',undefined,{id:Number(ref.slice(4))})).data; if(token!==importRequest || ref!==reference.value) return; if(!r.items.length) throw new Error('The approved recipe contains no dyes.'); replace(r.items,true); bootstrap.Tab.getOrCreateInstance(one('#lcTab1')).show();}));

    form.addEventListener('submit',e=>{e.preventDefault(); run(async()=>{form.classList.add('was-validated'); if(!form.checkValidity() || loading) return; save.disabled=true; error(); try {const data=Object.fromEntries(new FormData(form)); data.id=Number(root.dataset.id); data.items=all('[data-lc-stage]').flatMap(tbody=>Array.from(tbody.rows).map(tr=>({...Object.fromEntries(Array.from(tr.querySelectorAll('[data-cost-item]')).map(x=>[x.dataset.costItem,x.value])),process_tab:stages[Number(tbody.dataset.lcStage)]}))); await api('save',data); location.assign(root.dataset.list);} finally {save.disabled=false;}})();});
    run(async()=>{save.disabled=true; const opts=(await api('options')).data; products=opts.products; references=opts.references; one('[data-lc-heading]').textContent='— '+opts.next_number; [...new Set([...opts.customers,...references.map(r=>r.customer_name)])].filter(Boolean).forEach(c=>customer.add(new Option(c,c))); opts.processes.forEach(p=>one('#lcProcess').add(new Option(p,p))); rebuild(); const sourceId=Number(root.dataset.id)||Number(root.dataset.copy);
      if(sourceId) {const d=(await api('get',undefined,{id:sourceId})).data; if(d.customer_name && !Array.from(customer.options).some(x=>x.value===d.customer_name)) customer.add(new Option(d.customer_name,d.customer_name)); customer.value=d.customer_name; rebuild(d.lab_reference); if(d.lab_reference && !Array.from(reference.options).some(x=>x.value===d.lab_reference)) reference.add(new Option(d.lab_reference,d.lab_reference)); Array.from(form.elements).filter(x=>x.name).forEach(x=>{if(x.name in d) {if(x.tagName==='SELECT' && d[x.name] && !Array.from(x.options).some(o=>o.value===d[x.name])) x.add(new Option(d[x.name],d[x.name])); x.value=d[x.name] ?? '';}}); if(Number(root.dataset.id)) {one('#lcStatusDisplay').value=d.status; one('[data-lc-heading]').textContent='— '+(d.generate_no ?? d.costing_no);} else {one('[data-lc-heading]').textContent='— Copy'; one('#lcStatusDisplay').value='Pending';} replace(d.items);}
      else {const now=new Date(); one('#lcDate').value=[now.getFullYear(),String(now.getMonth()+1).padStart(2,'0'),String(now.getDate()).padStart(2,'0')].join('-');}
      all('select').filter(x=>!x.matches('[data-cost-item]')).forEach(x=>{if(x.id==='lcRef' && window.jQuery?.fn.select2) jQuery(x).select2({width:'100%',tags:true,createTag:params=>{const value=params.term.trim(); return value && value.length<=100 ? {id:value,text:value} : null;}}); else enhance(x);}); all('select').forEach(refresh); calc(); loading=false; save.disabled=false; all('[data-lc-add]').forEach(b=>b.disabled=false);
    })();
  }
})();

// Dyeing Quotation: record list and status review.
(() => {
  const root=document.querySelector('[data-cz-dyeing-quotation]'); if(!root) return;
  const one=s=>root.querySelector(s), all=s=>Array.from(root.querySelectorAll(s));
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message='') {one('[data-dq-error]').textContent=message; one('[data-dq-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {const url=new URL(root.dataset.api,location.origin); url.search=new URLSearchParams({action,...query}); const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); const r=await response.json(); if(!response.ok || !r.success) throw new Error(r.message || 'Unable to complete request.'); return r;}
  const enhance=(x,parent)=>{if(window.jQuery?.fn.select2) jQuery(x).select2({width:'100%',...(parent ? {dropdownParent:jQuery(parent)} : {})});};
  const change=(x,fn)=>{if(window.jQuery?.fn.select2) jQuery(x).on('change',run(fn)); else x.addEventListener('change',run(fn));};
  const refresh=x=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');};
  const pill=s=>`<span class="cz-badge ${s==='Approved' ? 'success' : ['Rejected','Cancelled'].includes(s) ? 'danger' : 'warning'}">${esc(s || 'Pending')}</span>`;
  if(root.dataset.czDyeingQuotation==='list') {
    let page=1,pages=1,sort='tid',dir='desc',request=0,statusId=0; const can=root.dataset.abilities.split(',');
    const ids=()=>all('[data-dq-check]:checked').map(x=>Number(x.value));
    function selection() {const n=ids().length,count=all('[data-dq-check]').length; one('[data-dq-selected]').textContent=n; one('[data-dq-selection]').classList.toggle('d-none',!n); one('[data-dq-selection]').classList.toggle('d-flex',!!n); one('[data-dq-all]').checked=count>0 && n===count; one('[data-dq-all]').indeterminate=n>0 && n<count;}
    async function load() {const token=++request,q=Object.fromEntries(all('[data-dq-filter]').map(x=>[x.dataset.dqFilter,x.value])); const r=await api('list',undefined,{...q,page,sort,dir}); if(token!==request) return; error(); pages=r.pages;
      one('[data-dq-rows]').innerHTML=r.rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-dq-check value="${Number(d.tid)}" aria-label="Select quotation ${esc(d.quotation_no)}"></td><td><div class="fw-semibold">${esc(d.customer_name)}</div><small class="text-secondary">#${Number(d.tid)}</small></td><td>${esc(d.generate_no ?? d.quotation_no)}</td><td>${esc(d.quotation_date)}</td><td class="text-break"><div>${esc(d.order_reference || "—")}</div><small class="d-block text-secondary">Attn: ${esc(d.kind_attention || "—")}</small></td>${['total_colours','avg_cost','avg_quoted'].map(k=>`<td>${esc(d[k])}</td>`).join('')}<td>${Number(d.avg_margin).toFixed(2)}</td><td><a class="text-decoration-none" href="${esc(root.dataset.form.replace(/\/form$/, '/approval'))}?id=${Number(d.tid)}" aria-label="Open quotation approval report">${pill(d.status)}</a></td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Costing actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu"><li><button class="cz-action-menu__item" type="button" data-dq-info="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>${can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li><li><button class="cz-action-menu__item" type="button" data-dq-status="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-toggles"></i></span><span>Approval Report</span></button></li>` : ''}${can.includes('create') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.form)}?copy=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-copy"></i></span><span>Copy</span></a></li>` : ''}${can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-dq-remove="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join(''); one('[data-dq-empty]').classList.toggle('d-none',!!r.rows.length); one('[data-dq-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${r.total ? (page-1)*25+r.rows.length : 0} of ${r.total} entries`; one('[data-dq-prev]').disabled=page<=1; one('[data-dq-next]').disabled=page>=pages; selection();}
    async function remove(selected) {if(!selected.length) throw new Error('Select a quotation.'); if(!confirm('Delete the selected quotations and their ingredient rows?')) return; await api('delete',{ids:selected}); await load();}
    let timer; all('[data-dq-filter]').filter(x=>x.tagName!=='SELECT').forEach(x=>x.addEventListener('input',()=>{clearTimeout(timer); timer=setTimeout(run(async()=>{page=1; await load();}),250);})); all('[data-dq-clear]').forEach(x=>x.addEventListener('click',run(async()=>{all('[data-dq-filter]').forEach(x=>{x.value=''; refresh(x);}); page=1; await load();})));
    one('[data-dq-apply]').addEventListener('click',run(async()=>{page=1; await load(); bootstrap.Offcanvas.getOrCreateInstance(one('#dqFilters')).hide();})); one('[data-dq-prev]').addEventListener('click',run(async()=>{page--; await load();})); one('[data-dq-next]').addEventListener('click',run(async()=>{page++; await load();})); one('[data-dq-all]').addEventListener('change',e=>{all('[data-dq-check]').forEach(x=>x.checked=e.target.checked); selection();}); root.addEventListener('change',e=>{if(e.target.matches('[data-dq-check]')) selection();}); one('[data-dq-delete]').addEventListener('click',run(()=>remove(ids())));
    all('[data-dq-sort]').forEach(b=>b.addEventListener('click',run(async()=>{dir=sort===b.dataset.dqSort && dir==='asc' ? 'desc' : 'asc'; sort=b.dataset.dqSort; page=1; all('[data-dq-sort]').forEach(x=>{x.querySelector('i').className=x===b ? (dir==='asc' ? 'bi bi-arrow-up' : 'bi bi-arrow-down') : 'bi bi-arrow-down-up'; x.closest('th').removeAttribute('aria-sort');}); b.closest('th').setAttribute('aria-sort',dir==='asc' ? 'ascending' : 'descending'); await load();})));
    root.addEventListener('click',run(async e=>{const del=e.target.closest('[data-dq-remove]'); if(del) await remove([Number(del.dataset.dqRemove)]); const status=e.target.closest('[data-dq-status]'); if(status) {location.assign(root.dataset.form.replace(/\/form$/, '/approval')+'?id='+Number(status.dataset.dqStatus)); return;} const info=e.target.closest('[data-dq-info]'); if(!info) return; const d=(await api('get',undefined,{id:info.dataset.dqInfo})).data;
      one('[data-dq-details]').innerHTML=`<dl class="row">${['quotation_no','quotation_date','customer_name','customer_address','order_reference','kind_attention','price_basis','currency','validity_days','payment_terms','tax','delivery','default_margin','minimum_margin','total_colours','avg_cost','avg_quoted','avg_profit','avg_margin','status','status_remarks','approved_by','approved_on','notes'].map(k=>`<dt class="col-sm-4">${esc(k.replaceAll('_',' '))}</dt><dd class="col-sm-8">${esc(d[k] ?? '—')}</dd>`).join('')}</dl><div class="table-responsive"><table class="table cz-table"><thead><tr><th>Lab Dip</th><th>Costing</th><th>Colour</th><th>Cost Rate</th><th>Quoted Rate</th><th>Confirm Rate</th><th>Profit</th></tr></thead><tbody>${d.lines.map(i=>`<tr>${['lab_dip_no','lab_costing_no','colour','costing_rate','quotation_rate','confirm_rate','profit'].map(k=>`<td>${esc(i[k])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`; bootstrap.Modal.getOrCreateInstance(one('#dqDetails')).show();}));
    one('[data-dq-status-save]').addEventListener('click',run(async()=>{const b=one('[data-dq-status-save]'); b.disabled=true; try {await api('update_status',{id:statusId,status:one('#dqNewStatus').value,remarks:one('#dqStatusRemarks').value}); bootstrap.Modal.getOrCreateInstance(one('#dqStatusModal')).hide(); await load();} finally {b.disabled=false;}}));
    run(async()=>{const f=(await api('filters')).data; f.statuses.forEach(x=>{one('[data-dq-filter="status"]').add(new Option(x,x)); one('#dqNewStatus').add(new Option(x,x));}); f.customers.forEach(x=>one('[data-dq-filter="customer_name"]').add(new Option(x,x))); all('select[data-dq-filter]').forEach(x=>{enhance(x,x.dataset.dqFilter==='customer_name' ? one('#dqFilters') : null); change(x,async()=>{page=1; await load();});}); enhance(one('#dqNewStatus'),one('#dqStatusModal')); await load();})();
  }
})();


// Dyeing Quotation: customer Lab Dip selection and commercial pricing form.
(() => {
  const root=document.querySelector('[data-cz-dyeing-quotation="form"]'); if(!root) return;
  const one=s=>root.querySelector(s), all=s=>Array.from(root.querySelectorAll(s)); const form=one('[data-dq-form]'),tbody=one('[data-dq-lines]'),customer=one('#dqCustomer'),dip=one('#dqDip'),costing=one('#dqCosting'),save=one('[data-dq-save]'); let sources=[],lines=[],loading=true,request=0,loadedCustomer='';
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message='') {one('[data-dq-error]').textContent=message; one('[data-dq-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {const url=new URL(root.dataset.api,location.origin); url.search=new URLSearchParams({action,...query}); const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); const r=await response.json(); if(!response.ok || !r.success) {const e=new Error(r.message || 'Unable to complete request.'); e.code=r.error; throw e;} return r;}
  const enhance=x=>{if(window.jQuery?.fn.select2) jQuery(x).select2({width:'100%'});}; const change=(x,fn)=>{if(window.jQuery?.fn.select2) jQuery(x).on('change',run(fn)); else x.addEventListener('change',run(fn));}; const refresh=x=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');};
  const num=k=>Number(form.elements.namedItem(k)?.value)||0; const round=v=>Number(v.toFixed(2));
  function capture() {Array.from(tbody.rows).forEach((tr,i)=>{tr.querySelectorAll('[data-dq-row]').forEach(x=>lines[i][x.dataset.dqRow]=x.value);});}
  function calc() {capture(); let cost=0,quoted=0,confirmed=0; lines.forEach((r,i)=>{const c=Number(r.costing_rate)||0,q=Number(r.quotation_rate)||0,profit=q-c,margin=c>0 ? profit/c*100 : 0; const tr=tbody.rows[i]; tr.classList.toggle('table-warning',margin<num('minimum_margin')); r.profit=round(profit); r.actual_margin_percent=round(margin); cost+=c; quoted+=q; confirmed+=Number(r.confirm_rate)||0;}); const n=lines.length,values={total_colours:n,avg_cost:n ? cost/n : 0,avg_quoted:n ? quoted/n : 0,avg_confirm:n ? confirmed/n : 0,avg_profit:n ? (quoted-cost)/n : 0,avg_margin:cost>0 ? (quoted-cost)/cost*100 : 0}; all('[data-dq-total]').forEach(x=>x.textContent=x.dataset.dqTotal==='total_colours' ? n : values[x.dataset.dqTotal].toFixed(2)); one('[data-dq-warning]').classList.toggle('d-none',!lines.some(r=>r.actual_margin_percent<num('minimum_margin')));}
  function render() {
    tbody.innerHTML=lines.map((r,i)=>`<tr><td><strong>${esc(r.colour)}</strong></td><td>${esc(r.fabric)}<small class="text-secondary d-block">${esc(r.gsm)}</small></td><td><strong>${esc(r.lab_dip_no)}</strong><small class="d-block text-secondary">${esc(r.lab_costing_no || 'No costing')} · ${esc(r.costing_version || '')}</small></td><td><div class="cz-quotation-process"><span>${esc(r.process_route)}</span><button class="cz-icon-btn light" type="button" data-dq-process="${i}" aria-label="Select process route"><i class="bi bi-plus-lg"></i></button></div></td><td class="text-end">₹ ${Number(r.costing_rate).toFixed(2)}</td>${['quotation_rate','confirm_rate'].map(k=>`<td><input class="form-control form-control-sm" data-dq-row="${k}" type="number" min="0" step="0.01" value="${esc(r[k] ?? 0)}" aria-label="${esc(k.replaceAll('_',' '))}"></td>`).join('')}<td><select class="form-select form-select-sm" data-dq-row="quotation_type" aria-label="Quotation type"><option ${r.quotation_type==='BULK' ? 'selected' : ''}>BULK</option><option ${r.quotation_type==='SAMPLE' ? 'selected' : ''}>SAMPLE</option></select></td><td><input class="form-control form-control-sm" data-dq-row="remarks" value="${esc(r.remarks)}" maxlength="255" aria-label="Line remarks"></td><td><div class="d-flex flex-column align-items-center gap-1"><button class="cz-icon-btn light" type="button" data-dq-break="${i}" aria-label="View lab cost breakup for ${esc(r.lab_dip_no)}" title="Lab Cost Breakup"><i class="bi bi-eye"></i></button><button class="cz-icon-btn light text-danger" type="button" data-dq-remove-line="${i}" aria-label="Remove line"><i class="bi bi-trash3"></i></button></div></td></tr>`).join('');
    const labels=Array.from(tbody.closest('table').querySelectorAll('thead th'),th=>th.textContent.trim());
    Array.from(tbody.rows).forEach(row=>Array.from(row.cells).forEach((cell,index)=>cell.dataset.label=labels[index] || ''));
    all('select[data-dq-row]').forEach(x=>change(x,capture)); calc();
  }
  function costingOptions() {const d=sources.find(d=>String(d.tid)===dip.value); costing.replaceChildren(new Option('No linked costing (zero cost)','0')); if(d) d.costings.forEach(c=>costing.add(new Option((c.costing_no || c.doc_no || c.tid)+' · '+(c.version || 'V1')+' · '+(c.status==='Draft' ? 'Pending' : c.status)+' · '+Number(c.costing_rate).toFixed(2),c.tid))); if(d?.costings.length) costing.value=d.costings[0].tid; refresh(costing); note();}
  function note() {const d=sources.find(d=>String(d.tid)===dip.value),c=d?.costings.find(c=>String(c.tid)===costing.value);
    one('#dqDipDate').value=d?.cs_date || '';one('#dqCostingVersion').value=c?.version || '';
    const summary={colour:d?.colour,fabric:d?.fabric_name,gsm:d?.gsm,process:d?.process_type,cost:c ? Number(c.costing_rate).toFixed(2) : '—',status:d?.status};
    all('[data-dq-source-summary]').forEach(x=>x.textContent=summary[x.dataset.dqSourceSummary] || '—'); one('[data-dq-source-note]').textContent=c ? `Costing ${c.costing_no || c.tid}, version ${c.version}: ${c.status}. Cost rate ${Number(c.costing_rate).toFixed(2)}.` : (d ? 'This Lab Dip has no selected costing. The cost rate will be zero.' : 'Select a Lab Dip and costing version.');}
  async function loadSources(fillAddress=true) {const token=++request,name=customer.value; loading=true; save.disabled=true; sources=[]; dip.replaceChildren(new Option('Select Lab Dip','')); costingOptions();
    try {if(!name) {loadedCustomer=''; return;} const [r,c]=await Promise.all([api('sources',undefined,{customer:name}),api('customer',undefined,{name})]); if(token!==request) return; sources=r.data.dips; sources.forEach(d=>dip.add(new Option(d.lab_no+' · '+d.colour+' · '+d.fabric_name,d.tid))); if(fillAddress) one('#dqAddress').value=c.data.address || ''; loadedCustomer=name; refresh(dip);} finally {if(token===request) {loading=false; save.disabled=false;}}}
  change(customer,async()=>{if(lines.length && customer.value!==loadedCustomer) {if(!confirm('Changing the customer clears the current quotation lines. Continue?')) {customer.value=loadedCustomer; refresh(customer); return;} lines=[]; render();} await loadSources();}); change(dip,costingOptions); change(costing,note);
  one('[data-dq-add]').addEventListener('click',run(()=>{if(loading) return; capture(); const d=sources.find(d=>String(d.tid)===dip.value); if(!d) throw new Error('Select a Lab Dip first.'); const c=d.costings.find(c=>String(c.tid)===costing.value); const id=Number(c?.tid)||0; if(lines.some(r=>Number(r.lab_dip_id_fk)===Number(d.tid) && Number(r.lab_costing_id_fk)===id)) throw new Error('This Lab Dip/costing version is already added.'); const rate=Number(c?.costing_rate)||0,margin=num('default_margin'); lines.push({lab_dip_id_fk:Number(d.tid),lab_costing_id_fk:id,lab_dip_no:d.lab_no,lab_costing_no:c?.costing_no || '',costing_version:c?.version || 'V1',colour:d.colour,fabric:d.fabric_name,gsm:d.gsm,process_route:d.process_type,costing_rate:rate,margin_percent:margin,quotation_rate:round(rate*(1+margin/100)),confirm_rate:0,quotation_type:'BULK',remarks:'',cost_breakup_json:JSON.stringify({chemical_cost:c?.chemical_cost || 0,dyes_cost:c?.dyes_cost || 0,overheads:c?.overheads || 0,materials:c?.materials || []})}); render();}));
  tbody.addEventListener('input',e=>{
    const field=e.target.dataset.dqRow;
    if(field==='margin_percent' || field==='quotation_rate') {
      const tr=e.target.closest('tr'),i=tr.rowIndex-1,cost=Number(lines[i].costing_rate)||0;
      if(field==='margin_percent') tr.querySelector('[data-dq-row="quotation_rate"]').value=round(cost*(1+Number(e.target.value)/100));
      else lines[i].margin_percent=cost>0 ? round((Number(e.target.value)-cost)/cost*100) : 0;
    }
    calc();
  });
  form.addEventListener('input',e=>{if(e.target.name==='minimum_margin') calc();});
  tbody.addEventListener('click',e=>{
    const remove=e.target.closest('[data-dq-remove-line]');
    if(remove) {capture(); lines.splice(Number(remove.dataset.dqRemoveLine),1); render(); return;}
    const button=e.target.closest('[data-dq-break]'); if(!button) return;
    const line=lines[Number(button.dataset.dqBreak)]; if(!line) return;
    let b={}; try {b=JSON.parse(line.cost_breakup_json || '{}') || {};} catch {}
    const materials=Array.isArray(b.materials) ? b.materials : [];
    const money=value=>'₹ '+(Number(value)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});
    const cards=[['Chemical Cost',b.chemical_cost ?? b.chemicalCost],['Dyes Cost',b.dyes_cost ?? b.dyesCost],['Overheads / KG',b.overheads],['Total Cost / KG',line.costing_rate]];
    one('[data-dq-breakup-reference]').textContent=[line.lab_dip_no,line.colour,line.fabric,line.lab_costing_no ? 'Costing '+line.lab_costing_no : 'No linked costing',line.costing_version].filter(Boolean).join(' · ');
    one('[data-dq-breakup]').innerHTML=`<div class="row g-2 mb-3">${cards.map(([label,value])=>`<div class="col-6 col-md-3"><div class="border rounded p-3 h-100 text-center"><small class="text-secondary d-block">${esc(label)}</small><strong class="fs-5">${money(value)}</strong></div></div>`).join('')}</div><div class="table-responsive"><table class="table cz-table cz-table-fit align-middle"><thead><tr><th>Stage</th><th>Material</th><th class="text-end">Quantity</th><th>UOM</th><th class="text-end">Rate</th><th class="text-end">Value</th></tr></thead><tbody>${materials.length ? materials.map(m=>`<tr><td class="text-break">${esc(m.process_tab ?? m.stage)}</td><td class="text-break">${esc(m.product_name ?? m.item)}</td><td class="text-end">${(Number(m.qty)||0).toFixed(5)}</td><td>${esc(m.unit ?? m.uom)}</td><td class="text-end">${money(m.rate)}</td><td class="text-end">${money(m.amount ?? m.value)}</td></tr>`).join('') : '<tr><td colspan="6" class="text-center text-secondary py-4">No ingredient details are available for this quotation line.</td></tr>'}</tbody></table></div>`;
    bootstrap.Modal.getOrCreateInstance(one('#dqBreakup')).show();
  });
  let processNames=[],processLine=-1;
  tbody.addEventListener('click',event=>{
    const button=event.target.closest('[data-dq-process]');if(!button)return;
    capture();processLine=Number(button.dataset.dqProcess);
    const chosen=(lines[processLine].process_route || '').split('+').map(x=>x.trim()).filter(Boolean);
    const names=[...new Set([...processNames,...chosen])];
    one('[data-dq-process-list]').innerHTML=names.map((name,i)=>`<div class="form-check mb-2" data-dq-process-entry><input class="form-check-input" type="checkbox" id="dqProcess-${i}" value="${esc(name)}" ${chosen.includes(name) ? 'checked' : ''}><label class="form-check-label" for="dqProcess-${i}">${esc(name)}</label></div>`).join('') || '<p class="text-secondary">No processes available. Add processes in Process Master.</p>';
    one('#dqProcessSearch').value='';bootstrap.Modal.getOrCreateInstance(one('#dqProcessModal')).show();
  });
  one('#dqProcessSearch').addEventListener('input',event=>{const q=event.target.value.trim().toLowerCase();all('[data-dq-process-entry]').forEach(row=>row.classList.toggle('d-none',!row.textContent.toLowerCase().includes(q)));});
  one('[data-dq-process-apply]').addEventListener('click',()=>{
    if(!lines[processLine])return;
    const route=all('[data-dq-process-list] input:checked').map(x=>x.value).join(' + ');
    if(route.length>255){error('Process route must be at most 255 characters.');return;}
    lines[processLine].process_route=route;render();bootstrap.Modal.getOrCreateInstance(one('#dqProcessModal')).hide();
  });
  one('[data-dq-print]').addEventListener('click',()=>window.print());
  form.addEventListener('submit',e=>{e.preventDefault(); run(async()=>{form.classList.add('was-validated'); if(!form.checkValidity() || loading) return; if(!lines.length) throw new Error('Add at least one Lab Dip.'); capture(); calc(); const data=Object.fromEntries(new FormData(form)); data.id=Number(root.dataset.id); data.lines=lines; if(lines.some(r=>r.actual_margin_percent<num('minimum_margin'))) {if(!confirm('One or more lines are below the minimum margin. Save this quotation anyway?')) return; data.acknowledge_low_margin=true;} save.disabled=true; error(); try {try {await api('save',data);} catch(e) {if(e.code!=='low_margin' || !confirm(e.message+' Save anyway?')) throw e; data.acknowledge_low_margin=true; await api('save',data);} location.assign(root.dataset.list);} finally {save.disabled=false;}})();});
  run(async()=>{save.disabled=true; const opts=(await api('options')).data; one('[data-dq-heading]').textContent='— '+opts.next_number; processNames=opts.processes || []; opts.customers.forEach(c=>customer.add(new Option(c,c))); opts.staff.forEach(s=>one('#dqAttention').add(new Option(s.full_name+' — '+s.emp_code,s.emp_code))); Object.entries(opts.choices).forEach(([k,values])=>{const select=form.elements.namedItem(k); values.forEach(v=>select.add(new Option(v,v))); if(k==='payment_terms') select.value='30 Days Credit';});
    const sourceId=Number(root.dataset.id)||Number(root.dataset.copy); if(sourceId) {const d=(await api('get',undefined,{id:sourceId})).data; if(d.customer_name && !Array.from(customer.options).some(x=>x.value===d.customer_name)) customer.add(new Option(d.customer_name,d.customer_name)); Array.from(form.elements).filter(x=>x.name).forEach(x=>{if(x.name in d) {if(x.tagName==='SELECT' && d[x.name] && !Array.from(x.options).some(o=>o.value===d[x.name])) x.add(new Option(d[x.name],d[x.name])); x.value=d[x.name] ?? '';}}); lines=d.lines.map(r=>({...r,margin_percent:Number(r.margin_percent)||0})); if(Number(root.dataset.id)) {one('#dqStatusDisplay').value=d.status; one('[data-dq-heading]').textContent='— '+d.generate_no;} else one('[data-dq-heading]').textContent='— Copy'; await loadSources(false);}
    else {const now=new Date(); one('#dqDate').value=[now.getFullYear(),String(now.getMonth()+1).padStart(2,'0'),String(now.getDate()).padStart(2,'0')].join('-'); loading=false;}
    all('select').forEach(enhance); render(); all('select').forEach(refresh); save.disabled=false;
  })();
})();

// Dyeing fabric Jobwork: linked GRN, inward and delivery documents.
(() => {
  const root=document.querySelector('[data-cz-dyeing-jobwork]'); if(!root) return;
  const one=s=>root.querySelector(s),all=s=>Array.from(root.querySelectorAll(s)),kind=root.dataset.kind,id=Number(root.dataset.id),abilities=root.dataset.abilities.split(',');
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const error=(message='')=>{one('[data-jw-error]').textContent=message;one('[data-jw-error]').classList.toggle('d-none',!message);};
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {const url=new URL(root.dataset.api,location.origin);url.search=new URLSearchParams({action,...query});const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});const r=await response.json();if(!response.ok || !r.success) throw new Error(r.message || 'Unable to complete request.');return r;}
  const enhance=x=>{if(window.jQuery?.fn.select2) jQuery(x).select2({width:'100%'});};
  const change=(x,fn)=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).on('change',run(fn));else x.addEventListener('change',run(fn));};
  const refresh=x=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT') jQuery(x).trigger('change.select2');};
  if(root.dataset.czDyeingJobwork==='list') {
    const statusTone=value=>({open:'warning',pending:'warning',approved:'success',completed:'success',delivered:'success',billed:'success',cancelled:'danger',rejected:'danger'}[String(value || '').trim().toLowerCase()] || 'neutral');
    // Portal menus outside the scrolling table, retaining delegated row actions.
    const actionMenus=new Map();
    root.addEventListener('show.bs.dropdown',e=>{
      const toggle=e.target;
      if(!toggle.matches('[aria-label="Document actions"]')) return;
      const menu=toggle.parentElement.querySelector('.dropdown-menu');
      actionMenus.set(toggle,{menu,parent:menu.parentElement});root.append(menu);
    });
    root.addEventListener('hidden.bs.dropdown',e=>{
      const entry=actionMenus.get(e.target);if(!entry) return;
      entry.parent.append(entry.menu);actionMenus.delete(e.target);
    });
    root.addEventListener('keydown',e=>{
      const entry=[...actionMenus].find(([,v])=>v.menu.contains(e.target));
      if(!entry || !['Escape','ArrowUp','ArrowDown'].includes(e.key)) return;
      e.preventDefault();e.stopPropagation();
      const [toggle,{menu}]=entry;
      if(e.key==='Escape'){bootstrap.Dropdown.getInstance(toggle).hide();toggle.focus();return;}
      const items=Array.from(menu.querySelectorAll('.cz-action-menu__item:not(:disabled)'));
      const index=items.indexOf(document.activeElement);
      items[(index+(e.key==='ArrowDown'?1:-1)+items.length)%items.length]?.focus();
    });
    let page=1,pages=1,sort='tid',dir='desc',generation=0; const selected=new Set();
    function selection(){one('[data-jw-selected]').textContent=selected.size;one('[data-jw-selection]').classList.toggle('d-none',!selected.size);one('[data-jw-selection]').classList.toggle('d-flex',!!selected.size);}
    async function load() {error();const stamp=++generation;const q={page,sort,dir};all('[data-jw-filter]').forEach(x=>q[x.dataset.jwFilter]=x.value);const r=await api('list',undefined,q);if(stamp!==generation) return;pages=r.pages;selected.clear();selection();one('[data-jw-all]').checked=false;one('[data-jw-empty]').classList.toggle('d-none',!!r.rows.length);
      all('[aria-label="Document actions"]') .forEach(toggle=>{const instance=bootstrap.Dropdown.getInstance(toggle);instance?.hide();instance?.dispose();});
      one('[data-jw-list]').innerHTML=r.rows.length ? r.rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-jw-check="${Number(d.tid)}" aria-label="Select document ${Number(d.generate_no)}"></td>${kind==='inward' ? `<td><div class="fw-semibold">${esc(d.company_name)}</div><div class="small text-secondary">#${esc(d.generate_no)} <span class="cz-badge neutral">${esc(d.a14 || 'Fresh')}</span>${d.a22 ? ` <span class="cz-badge neutral">${esc(d.a22)}</span>` : ''} <span class="cz-badge ${statusTone(d.status)}">${esc(d.status)}</span></div></td><td>${esc(d.batch_numbers || '—')}</td><td>${esc(d.create_date)}</td><td>${esc(d.a27 || '—')}</td><td>${esc(d.counts || '—')}</td><td>${esc(d.colours || '—')}</td><td class="text-end">${Number(d.total_qty).toFixed(3)}</td>` : kind==='delivery' ? `<td><div class="fw-semibold">${esc(d.company_name)}</div><small class="text-secondary">#${esc(d.generate_no)}</small></td><td>${esc(d.create_date)}</td><td>${esc(d.reference_number || '—')}</td><td>${esc(d.order_number || '—')}</td><td class="text-end">${Number(d.total_qty).toFixed(3)}</td><td>${esc(d.staffname || '—')}</td><td><span class="cz-badge ${d.a18==='Completed'?'success':'warning'}">${esc(d.a18 || 'Partial Delivery')}</span></td><td><span class="cz-badge ${statusTone(d.status)}">${esc(d.status)}</span></td>` : `<td>${esc(d.generate_no)}</td><td>${esc(d.company_name)}</td><td>${esc(d.reference_number)}</td><td>${esc(d.create_date)}</td><td><span class="badge text-bg-light border">${esc(d.status)}</span></td><td>${Number(d.total_qty).toFixed(3)}</td><td>${Number(d.total_rolls).toFixed(3)}</td>`}<td class="text-end"><div class="dropdown"><button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Document actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu"><li><button class="cz-action-menu__item" type="button" data-jw-view="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-info-circle"></i></span><span>View Details</span></button></li>${abilities.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.list)}/form?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li>` : ''}${abilities.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-jw-delete-row="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join('') : '';
      if(['inward','delivery'].includes(kind))one('[data-jw-list-total]').textContent=Number(r.total_qty || 0).toFixed(3);
      all('[aria-label="Document actions"]').forEach(toggle=>bootstrap.Dropdown.getOrCreateInstance(toggle,{boundary:'viewport',popperConfig:config=>({...config,strategy:'fixed'})}));
      one('[data-jw-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${Math.min(page*25,r.total)} of ${r.total} entries · 0 selected`;one('[data-jw-prev]').disabled=page<=1;one('[data-jw-next]').disabled=page>=pages;
    }
    let timer;all('[data-jw-filter]').forEach(x=>{const drawer=!!x.closest('#jwFilters');if(x.tagName==='SELECT') {enhance(x);if(!drawer)change(x,()=>{page=1;return load();});} else if(!drawer) x.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(run(()=>{page=1;return load();}),250);});});
    one('[data-jw-apply]')?.addEventListener('click',run(()=>{page=1;bootstrap.Offcanvas.getOrCreateInstance(one('#jwFilters')).hide();return load();}));
    all('[data-jw-clear]').forEach(b=>b.addEventListener('click',run(()=>{all('[data-jw-filter]').forEach(x=>{x.value='';refresh(x);});page=1;return load();})));
    one('[data-jw-prev]').addEventListener('click',run(()=>{page=Math.max(1,page-1);return load();}));one('[data-jw-next]').addEventListener('click',run(()=>{page=Math.min(pages,page+1);return load();}));
    all('[data-jw-sort]').forEach(x=>x.addEventListener('click',run(()=>{dir=sort===x.dataset.jwSort && dir==='asc' ? 'desc' : 'asc';sort=x.dataset.jwSort;page=1;return load();})));
    root.addEventListener('change',e=>{if(e.target.matches('[data-jw-all]')) all('[data-jw-check]').forEach(x=>{x.checked=e.target.checked;x.dispatchEvent(new Event('change',{bubbles:true}));});if(e.target.matches('[data-jw-check]')) {const key=Number(e.target.dataset.jwCheck);e.target.checked ? selected.add(key) : selected.delete(key);selection();one('[data-jw-range]').textContent=one('[data-jw-range]').textContent.replace(/\d+ selected$/,`${selected.size} selected`);}});
    one('[data-jw-delete]')?.addEventListener('click',run(async()=>{if(!selected.size) throw new Error('Select documents to delete.');if(!confirm(`Delete ${selected.size} selected documents?`)) return;await api('delete',{ids:[...selected]});await load();}));
    root.addEventListener('click',run(async e=>{const button=e.target.closest('[data-jw-view]');if(!button) return;const d=(await api('get',undefined,{id:button.dataset.jwView})).data;let modal=document.querySelector('#czJobworkDetails');if(!modal){modal=document.createElement('div');modal.id='czJobworkDetails';modal.className='modal fade';modal.tabIndex=-1;modal.setAttribute('aria-label','Jobwork document details');modal.innerHTML='<div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h2 class="fs-6">Document details</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"></div></div></div>';document.body.append(modal);}modal.querySelector('.modal-body').innerHTML=`<p>${esc(d.company_name)} · ${esc(d.generate_no)} · ${esc(d.create_date)} · ${esc(d.status)}</p><div class="table-responsive"><table class="table cz-table"><thead><tr><th>Fabric</th><th>Colour</th><th>Weight KG</th><th>Rolls</th></tr></thead><tbody>${d.rows.map(r=>`<tr><td>${esc(r.product_name)}</td><td>${esc(r.description)}</td><td>${Number(r.qty).toFixed(3)}</td><td>${esc(r.c7)}</td></tr>`).join('')}</tbody></table></div><p>${esc(d.narration)}</p>`;bootstrap.Modal.getOrCreateInstance(modal).show();}));
    root.addEventListener('click',run(async e=>{
      const button=e.target.closest('[data-jw-delete-row]');if(!button || !abilities.includes('delete'))return;
      const documentId=Number(button.dataset.jwDeleteRow);
      if(!confirm('Delete this document?'))return;
      button.disabled=true;
      try{await api('delete',{ids:[documentId]});await load();}finally{button.disabled=false;}
    }));

    run(load)();return;
  }
  const form=one('[data-jw-form]'),body=one('[data-jw-fabrics]'),customer=form.elements.company_name,source=one('[data-jw-source]'),save=one('[data-jw-save]');let rows=[],options={},locked=false,sourceGeneration=0;
  save.disabled=true;
  function capture(){Array.from(body.rows).forEach((tr,i)=>tr.querySelectorAll('[data-jw-cell]').forEach(x=>rows[i][x.dataset.jwCell]=x.value));}
  function total(){capture();if(kind==='delivery' && one('[data-jw-delivery-loss]')){const sourceTotals=new Map();rows.forEach(r=>{const key=String(r.source_id)+':'+String(r.source_row_id);if(!sourceTotals.has(key))sourceTotals.set(key,{inward:Number(r.source_qty)||0,outward:Number(r.source_used)||0});sourceTotals.get(key).outward+=Number(r.qty)||0;});let inward=0,outward=0;sourceTotals.forEach(v=>{inward+=v.inward;outward+=v.outward;});one('[data-jw-delivery-loss]').textContent=inward>0 ? (Math.max(0,inward-outward)/inward*100).toFixed(2)+'%' : '—';}if(kind==='delivery')rows.forEach((r,i)=>{const cell=body.rows[i]?.querySelector('[data-jw-loss-actual]'),base=Number(r.source_qty)||0;if(cell)cell.textContent=base>0 ? (Math.max(0,base-(Number(r.source_used)||0)-(Number(r.qty)||0))/base*100).toFixed(2)+'%' : '—';});if(kind==='delivery')one('[data-jw-delivery-rolls]').textContent=rows.reduce((s,r)=>s+(Number(r.c7)||0),0).toFixed(3);if(['inward','delivery'].includes(kind)){one('[data-jw-grn-count]').textContent=rows.length;one('[data-jw-grn-qty]').textContent=rows.reduce((s,r)=>s+(Number(r.qty)||0),0).toFixed(3);}if(kind==='grn'){rows.forEach((r,i)=>{r.c16=((Number(r.c11)||0)-(Number(r.qty)||0)).toFixed(3);const x=body.rows[i]?.querySelector('[data-jw-cell="c16"]');if(x)x.value=r.c16;});form.elements.a9.value=rows.reduce((s,r)=>s+(Number(r.qty)||0),0).toFixed(3);one('[data-jw-grn-count]').textContent=rows.length;one('[data-jw-grn-qty]').textContent=form.elements.a9.value;}one('[data-jw-total]').textContent=`${rows.length} fabrics · ${rows.reduce((s,r)=>s+(Number(r.qty)||0),0).toFixed(3)} KG · ${rows.reduce((s,r)=>s+(Number(r.c7)||0),0).toFixed(3)} rolls`;}
  function render(){body.innerHTML=rows.map((r,i)=>{const fields=kind==='grn' ? ['product_name','description','c4','c6','c8','c9','c10','c15','c7','qty','c11','lab_app','process_route','c16','rate'] : kind==='inward' ? ['product_name','description','c4','c6','c8','c9','c10','c11','c7','qty','jw_proess_name','rate'] : ['product_name','description','c6','c7','c8','c9','c10','c11','qty','loss_actual','jw_proess_name','rate','jw_machine'];return `<tr>${fields.map(k=>{const label=k==='qty' ? 'Weight KG' : k==='c7' ? 'Rolls' : k.replaceAll('_',' '),numeric=['qty','c7','rate','c11','c15'].includes(k);if(k==='product_name' && kind==='grn') return `<td><select class="form-select form-select-sm" data-jw-cell="${k}" aria-label="Fabric" required><option value="">Select fabric</option>${options.products.map(p=>`<option value="${esc(p.product_name)}" ${p.product_name===r[k] ? 'selected' : ''}>${esc(p.product_name)}</option>`).join('')}</select></td>`;if((kind==='grn' && ['process_route','c16','rate'].includes(k)) || (kind==='inward' && k==='rate') || (kind==='delivery' && ['rate','jw_machine'].includes(k)))return `<td class="d-none"><input type="hidden" data-jw-cell="${k}" value="${esc(r[k] ?? (numeric ? 0 : ''))}"></td>`;if(k==='jw_proess_name' && kind==='delivery')return `<td class="text-center"><input type="hidden" data-jw-cell="jw_proess_name" value="${esc(r.jw_proess_name)}"><button class="cz-icon-btn light" type="button" data-jw-view-process="${i}" aria-label="View assigned processes for ${esc(r.product_name)}"><i class="bi bi-eye"></i></button></td>`;if(kind==='delivery' && k==='loss_expected')return `<td class="text-end">${r.loss_expected!==undefined && r.loss_expected!==null && r.loss_expected!=='' ? esc(r.loss_expected)+'%' : '—'}</td>`;if(kind==='delivery' && k==='loss_actual')return `<td class="text-end" data-jw-loss-actual="${i}">—</td>`;if(kind==='inward' && ['c1','c2','c3'].includes(k)){const label={c1:'Width',c2:'Dyeing',c3:'Loss %'}[k];return `<td><select class="form-select form-select-sm" data-jw-cell="${k}" aria-label="${label}"><option value="">Select</option>${inwardChoices(k).map(c=>`<option value="${esc(c.value)}" ${String(r[k] ?? '')===c.value ? 'selected' : ''}>${esc(c.label)}</option>`).join('')}</select></td>`;}if(k==='jw_proess_name' && kind==='inward'){const assigned=processList(r.jw_proess_name).length>0;return `<td class="text-center"><input type="hidden" data-jw-cell="jw_proess_name" value="${esc(r.jw_proess_name)}"><button class="btn btn-sm btn-outline-secondary" type="button" data-jw-assign-process="${i}" title="${assigned ? 'View' : 'Assign'}" aria-label="${assigned ? 'View' : 'Assign'} processes for ${esc(r.product_name)}"><i class="bi ${assigned ? 'bi-eye' : 'bi-plus-lg me-1'}" aria-hidden="true"></i>${assigned ? '' : 'Assign'}</button></td>`;}if(k==='jw_proess_name' && kind==='delivery')return `<td><div class="input-group input-group-sm"><input class="form-control" data-jw-cell="jw_proess_name" value="${esc(r.jw_proess_name)}" maxlength="255" aria-label="Process route"><button class="btn btn-outline-secondary" type="button" data-jw-pick-process="${i}" aria-label="Select process route"><i class="bi bi-plus-lg"></i></button></div></td>`;if(k==='process_route' && kind==='grn')return `<td><div class="input-group input-group-sm"><input class="form-control" data-jw-cell="process_route" value="${esc(r.process_route)}" maxlength="10000" aria-label="Process route"><button class="btn btn-outline-secondary" type="button" data-jw-pick-process="${i}" aria-label="Select process route"><i class="bi bi-plus-lg"></i></button></div></td>`;return `<td><input class="form-control form-control-sm" data-jw-cell="${k}" aria-label="${esc(label)}" value="${esc(r[k] ?? (numeric ? 0 : ''))}" ${numeric ? `type="number" min="${k==='qty' ? '0.001' : '0'}" step="${k==='rate' ? '0.01' : '0.001'}" ${['qty','c7','rate'].includes(k) ? 'required' : ''}` : `maxlength="${k==='process_route' ? 10000 : 95}"`} ${k==='c16' ? 'readonly' : ''} ${kind!=='grn' && !numeric && k!=='jw_proess_name' && !(kind==='delivery' && ['c6','c8','c9','c10','c11'].includes(k)) && !(kind==='inward' && k==='description') ? 'readonly' : ''}>${k==='qty' && r.remaining_qty!==undefined ? `<small class="text-secondary">Available ${Number(r.remaining_qty).toFixed(3)} KG</small>` : ''}${k==='product_name' && kind==='inward' && r.jw_proess_name ? `<small class="d-block text-secondary cz-jw-process-names">${esc(processList(r.jw_proess_name).join(', '))}</small>` : ''}</td>`;}).join('')}<td><button class="btn btn-outline-danger btn-sm" type="button" data-jw-remove="${i}" aria-label="Remove fabric row"><i class="bi bi-trash3"></i></button></td></tr>`;}).join('');all('[data-jw-cell="product_name"]').filter(x=>x.tagName==='SELECT').forEach(x=>{enhance(x);change(x,total);});if(locked) all('input,select,textarea,button').filter(x=>!x.matches('[data-jw-view-process],[data-jw-assign-process]') && !x.closest('#jwAssignProcess')).forEach(x=>x.disabled=true);total();}
  if(kind==='delivery')body.addEventListener('click',event=>{
    const button=event.target.closest('[data-jw-view-process]');if(!button)return;
    capture();const row=rows[Number(button.dataset.jwViewProcess)];if(!row)return;
    const processes=[...new Set(String(row.jw_proess_name || '').split(/[+,\r\n]+/).map(x=>x.trim()).filter(Boolean))];
    one('[data-jw-process-fabric]').textContent=row.product_name || '—';
    one('[data-jw-assigned-processes]').innerHTML=processes.map(name=>`<tr><td>${esc(name)}</td></tr>`).join('') || '<tr><td class="text-secondary">No processes assigned.</td></tr>';
    one('[data-jw-process-count]').textContent=processes.length;
    bootstrap.Modal.getOrCreateInstance(one('#jwAssignedProcesses')).show();
  });
  let processRow=-1;
  // Inward Cum JobCard: Assign Process For Fabric (legacy production-jw-inward-add.php) - a mandatory, comma-joined process list per fabric row.
  const processList=value=>[...new Set(String(value || '').split(/[,+]/).map(p=>p.trim()).filter(Boolean))];
  // Delivery loss: an inward row's qty, the qty other deliveries already took from it, and the JobCard's expected loss % (c3)
  const lossSource=r=>kind==='delivery' ? {source_qty:Number(r.qty)||0,source_used:Math.max(0,(Number(r.qty)||0)-(Number(r.remaining_qty)||0)),loss_expected:r.c3 ?? ''} : {};
  // Width / Dyeing / Loss % choices of a fabric row, as the entry dropdowns list them (DyeingJobworkKind::WIDTHS / DYEINGS / LOSSES)
  // (c1 / c2 from the JobCard's Open Width / Tubular and Single / Double Dyeing radios, c3 from the entry line's Loss % dropdown)
  const inwardChoices=k=>k==='c3' ? Array.from(one('[data-jw-inward-option="c3"]')?.options || []).filter(o=>o.value).map(o=>({value:o.value,label:o.textContent})) : all(`[data-jw-row-default="${k}"]`).flatMap(x=>x.tagName==='SELECT' ? Array.from(x.options).filter(o=>o.value).map(o=>({value:o.value,label:o.textContent})) : [{value:x.value,label:x.value}]);
  // a new fabric row takes the JobCard's width and dyeing
  const rowDefaults=()=>Object.fromEntries(['c1','c2'].map(k=>[k,all(`[data-jw-row-default="${k}"]`).find(x=>x.tagName==='SELECT' || x.checked)?.value || '']));
  // changing the JobCard's width or dyeing sets it on every fabric row (a row can still be changed in the table)
  if(kind==='inward')all('[data-jw-row-default]').forEach(x=>change(x,()=>{const k=x.dataset.jwRowDefault;if(locked || !rows.length || (x.type==='radio' && !x.checked))return;capture();rows.forEach(r=>r[k]=x.value);render();}));
  if(kind==='inward') {
    const modal=one('#jwAssignProcess'),list=one('[data-jw-assign-rows]'),picker=one('[data-jw-assign-select]'),lab=one('[data-jw-assign-lab]'),labPreview=one('[data-jw-assign-lab-preview]'),template=one('[data-jw-assign-template]'),templatePreview=one('[data-jw-assign-template-preview]');
    const fail=(message='')=>{one('[data-jw-assign-error]').textContent=message;one('[data-jw-assign-error]').classList.toggle('d-none',!message);};
    const names=()=>all('[data-jw-assign-name]').map(x=>x.dataset.jwAssignName);
    const count=()=>{one('[data-jw-assign-count]').textContent=list.rows.length;one('[data-jw-assign-all]').checked=false;};
    const append=items=>{const have=names(),added=items.filter(n=>!have.includes(n));list.insertAdjacentHTML('beforeend',added.map(n=>`<tr><td><input class="form-check-input" type="checkbox" data-jw-assign-check aria-label="Select ${esc(n)}" ${locked ? 'disabled' : ''}></td><td data-jw-assign-name="${esc(n)}">${esc(n)}</td></tr>`).join(''));count();return items.length-added.length;};
    // Load from Template / Lab Dip adds the processes not in the list yet, in their order (legacy load_template / load_labdip_process)
    const load=(items,source)=>{if(!items.length){fail(`This ${source} has no processes to load.`);return;}const skipped=append(items);fail(skipped===items.length ? `The ${source}'s processes are already added.` : '');};
    const sameCustomer=d=>String(d.customer_name || '').trim().toLowerCase()===String(customer.value || '').trim().toLowerCase();
    const labRoute=()=>options.labs.find(d=>String(d.tid)===lab.value)?.process_route || '';
    const templateProcesses=()=>(options.templates || []).find(t=>t.template_name===template.value)?.processes || [];
    function fillSources(){
      const process=picker.value;picker.replaceChildren(new Option('Select Process',''));(options.processes || []).forEach(p=>picker.add(new Option(p,p)));picker.value=process;refresh(picker);
      const chosen=template.value;template.replaceChildren(new Option((options.templates || []).length ? 'Select Template' : 'No templates yet',''));(options.templates || []).forEach(t=>template.add(new Option(`${t.template_name} (${t.processes.length})`,t.template_name)));template.value=chosen;refresh(template);
      const dip=lab.value,labs=options.labs.filter(sameCustomer);lab.replaceChildren(new Option(customer.value ? (labs.length ? 'Select Lab Dip' : 'No Lab Dips for this company') : 'Select a Company Name first',''));labs.forEach(d=>lab.add(new Option(`${d.label}${d.colour ? ` - ${d.colour}` : ''}${d.process_route ? ` [${d.process_route}]` : ''}`,d.tid)));lab.value=dip;refresh(lab);
      showPreview();
    }
    function showPreview(){
      const route=labRoute();labPreview.textContent=route ? `Process Route: ${route}` : 'This Lab Dip has no Process Route.';labPreview.classList.toggle('d-none',!lab.value);
      const steps=templateProcesses();templatePreview.textContent=steps.length ? `Processes: ${steps.join(', ')}` : 'This template has no processes.';templatePreview.classList.toggle('d-none',!template.value);
    }
    body.addEventListener('click',event=>{
      const button=event.target.closest('[data-jw-assign-process]');if(!button)return;
      capture();processRow=Number(button.dataset.jwAssignProcess);const row=rows[processRow];if(!row)return;
      fail();one('[data-jw-assign-fabric]').textContent=row.product_name || '—';list.replaceChildren();append(processList(row.jw_proess_name));
      one('[data-jw-assign-same]').checked=false;picker.value='';template.value='';lab.value='';fillSources();
      one('[data-jw-assign-locked]').classList.toggle('d-none',!locked);
      modal.querySelectorAll('input,select,button:not([data-bs-dismiss]):not([data-bs-toggle])').forEach(x=>x.disabled=locked);
      bootstrap.Modal.getOrCreateInstance(modal).show();
    });
    one('[data-jw-assign-add]').addEventListener('click',()=>{if(!picker.value){fail('Select a process first.');return;}fail(append([picker.value]) ? 'This process is already added.' : '');picker.value='';refresh(picker);});
    one('[data-jw-assign-delete]').addEventListener('click',()=>{const checked=all('[data-jw-assign-check]:checked');if(!checked.length){fail('Tick the processes to delete.');return;}fail();checked.forEach(x=>x.closest('tr').remove());count();});
    one('[data-jw-assign-all]').addEventListener('change',e=>all('[data-jw-assign-check]').forEach(x=>x.checked=e.target.checked));
    change(lab,showPreview);change(template,showPreview);
    one('[data-jw-assign-lab-load]').addEventListener('click',()=>{if(!lab.value){fail('Select a Lab Dip.');return;}load(processList(labRoute()),'Lab Dip');});
    one('[data-jw-assign-template-load]').addEventListener('click',()=>{if(!template.value){fail('Select a template.');return;}load(templateProcesses(),'template');});
    // Reload: a template or process added in another tab (the "+" link) shows up without leaving the JobCard
    all('[data-jw-assign-reload]').forEach(x=>x.addEventListener('click',async()=>{
      all('[data-jw-assign-reload]').forEach(b=>b.disabled=true);
      try{const fresh=(await api('options')).data;options.processes=fresh.processes;options.templates=fresh.templates;options.labs=fresh.labs;fillSources();fail();}
      catch(e){fail(e.message);}finally{all('[data-jw-assign-reload]').forEach(b=>b.disabled=locked);}
    }));
    one('[data-jw-assign-apply]').addEventListener('click',()=>{
      if(locked || !rows[processRow])return;const value=names().join(',');if(!value){fail('Add at least one process for this fabric.');return;}
      fail();capture();(one('[data-jw-assign-same]').checked ? rows : [rows[processRow]]).forEach(r=>r.jw_proess_name=value);render();bootstrap.Modal.getOrCreateInstance(modal).hide();
    });
  }
  if(kind==='grn') {
    body.addEventListener('click',event=>{
      const button=event.target.closest('[data-jw-pick-process]');if(!button || locked)return;
      capture();processRow=Number(button.dataset.jwPickProcess);if(kind==='delivery')one('[data-jw-process-machine]').value=rows[processRow].jw_machine || '';
      const chosen=String(rows[processRow][kind!=='grn'?'jw_proess_name':'process_route'] || '').split('+').map(p=>p.trim()).filter(Boolean);
      const names=[...new Set([...(options.processes || []),...chosen])];
      one('[data-jw-process-options]').innerHTML=names.map((name,i)=>`<div class="form-check mb-2" data-jw-process-entry><input class="form-check-input" type="checkbox" value="${esc(name)}" id="jwProcessChoice-${i}" ${chosen.includes(name) ? 'checked' : ''}><label class="form-check-label" for="jwProcessChoice-${i}">${esc(name)}</label></div>`).join('') || '<p class="text-secondary">No processes available in Process Master.</p>';
      one('#jwProcessSearch').value='';bootstrap.Modal.getOrCreateInstance(one('#jwProcessPicker')).show();
    });
    one('#jwProcessSearch').addEventListener('input',event=>{const q=event.target.value.trim().toLowerCase();all('[data-jw-process-entry]').forEach(x=>x.classList.toggle('d-none',!x.textContent.toLowerCase().includes(q)));});
    one('[data-jw-process-apply]').addEventListener('click',()=>{if(locked || !rows[processRow])return;if(kind==='delivery')rows[processRow].jw_machine=one('[data-jw-process-machine]').value;rows[processRow][kind!=='grn'?'jw_proess_name':'process_route']=all('[data-jw-process-options] input:checked').map(x=>x.value).join(' + ');render();bootstrap.Modal.getOrCreateInstance(one('#jwProcessPicker')).hide();});
  }
  function labs(){const picker=form.elements.lab_dip_no;if(!picker || picker.tagName!=='SELECT')return;const value=picker.value;picker.replaceChildren(new Option('Select Lab Dip',''));options.labs.filter(d=>d.customer_name===customer.value).forEach(d=>picker.add(new Option(d.label,d.label)));if(value && !Array.from(picker.options).some(o=>o.value===value))picker.add(new Option(value,value));picker.value=value;refresh(picker);}
  async function sources(){labs();if(kind==='inward'){const quotes=(options.quotations || []).filter(q=>q.company_name===customer.value).map(q=>String(q.generate_no));if(!quotes.includes(form.elements.a21.value))form.elements.a21.value=quotes[0] || '';}const person=form.elements.a17;if(person?.tagName==='SELECT'){const value=person.value;person.replaceChildren(new Option('Select in-charge',''));options.people.filter(p=>p.company_name===customer.value).forEach(p=>person.add(new Option(p.person_name,p.person_name)));if(value && !Array.from(person.options).some(o=>o.value===value))person.add(new Option(value,value));person.value=value;refresh(person);}if(!source) return;if(['inward','delivery'].includes(kind)){++inwardGeneration;inwardSource=null;one('[data-jw-inward-fabric]').replaceChildren(new Option('Select source first',''));refresh(one('[data-jw-inward-fabric]'));}const stamp=++sourceGeneration;source.replaceChildren(new Option('Select source',''));if(!customer.value) return;const r=await api('sources',undefined,{customer:customer.value});if(stamp!==sourceGeneration)return;r.data.forEach(d=>source.add(new Option(`${d.generate_no} · ${d.create_date} · ${d.reference_number || ''}`,d.tid)));refresh(source);}
  let inwardSource=null,inwardGeneration=0;
  async function inwardFabrics(){
    if(!['inward','delivery'].includes(kind))return;
    const stamp=++inwardGeneration,picker=one('[data-jw-inward-fabric]');
    inwardSource=null;picker.replaceChildren(new Option('Select source fabric',''));
    all('[data-jw-inward-field]').forEach(x=>x.value='');
    if(!source.value)return;
    const d=(await api('source',undefined,{id:source.value,except:id})).data;
    if(stamp!==inwardGeneration)return;
    if(!d || d.blocked)throw new Error('This source is unavailable or requires allocation reconciliation.');
    inwardSource=d;
    if(kind==='delivery')for(const [target,sourceKey] of Object.entries({a1:'a1',a11:'a11',order_number:'order_number',a19:'a27',a20:'a20'}))form.elements[target].value=d[sourceKey] || '';
    if(kind==='inward' && form.elements.lab_dip_no && !form.elements.lab_dip_no.value && d.lab_dip_no)form.elements.lab_dip_no.value=d.lab_dip_no;   // the GRN's Lab Dip No.
    d.rows.filter(r=>Number(r.remaining_qty)>0).forEach(r=>picker.add(new Option(`${r.product_name} · ${r.description || ''} · ${r.c4 || ''} · ${r.remaining_qty} KG`,r.tid)));
    refresh(picker);
  }
  if(['inward','delivery'].includes(kind)){
    change(source,inwardFabrics);
    change(one('[data-jw-inward-fabric]'),()=>{
      const r=inwardSource?.rows.find(r=>Number(r.tid)===Number(one('[data-jw-inward-fabric]').value));
      all('[data-jw-inward-field]').forEach(x=>x.value=r ? (x.dataset.jwInwardField==='qty'?r.remaining_qty:x.dataset.jwInwardField==='c7'?r.remaining_rolls:x.dataset.jwInwardField==='c11'?(kind==='inward' ? (r.c15 || '') : r.c11):r[x.dataset.jwInwardField] ?? ''):'');
    });
    one('[data-jw-product-entry]')?.addEventListener('click',()=>{
      const picker=one('[data-jw-inward-fabric]');
      one('[data-jw-inward-entry]').scrollIntoView({behavior:'smooth',block:'center'});
      if(window.jQuery?.fn.select2 && jQuery(picker).data('select2')) jQuery(picker).select2('open');
      else picker.focus({preventScroll:true});
    });
    one('[data-jw-inward-add]').addEventListener('click',run(()=>{
      capture();const r=inwardSource?.rows.find(r=>Number(r.tid)===Number(one('[data-jw-inward-fabric]').value));
      if(!r)throw new Error('Select a source fabric.');
      if(rows.some(x=>Number(x.source_id)===Number(inwardSource.tid) && Number(x.source_row_id)===Number(r.tid)))throw new Error('This source fabric is already added. Edit its quantity in the table.');
      const qty=Number(one('[data-jw-inward-field="qty"]').value),rolls=Number(one('[data-jw-inward-field="c7"]').value);
      if(!(qty>0) || qty>Number(r.remaining_qty) || rolls<0 || rolls>Number(r.remaining_rolls))throw new Error('Enter quantity and rolls within the remaining source balance.');
      rows.push({...r,...lossSource(r),tid:0,source_id:inwardSource.tid,source_row_id:r.tid,qty,c7:rolls,c11:kind==='inward' ? (r.c15 || '') : r.c11,jw_proess_name:r.jw_proess_name || '',...(kind==='inward' ? {...rowDefaults(),c3:''} : {})});error();render();
    }));
  }
  async function loadSourceDocuments(ids){
    if(locked)return;
    capture();const company=customer.value;
    // Fetch and validate every selection before adding any rows.
    const documents=[];
    for(const sourceId of ids){
      const d=(await api('source',undefined,{id:sourceId,except:id})).data;
      if(customer.value!==company)return;
      if(!d || d.blocked)throw new Error('A selected source is unavailable or requires allocation reconciliation.');
      documents.push(d);
    }
    for(const d of documents)for(const r of d.rows){
      if(Number(r.remaining_qty)<=0 || rows.some(x=>Number(x.source_id)===Number(d.tid) && Number(x.source_row_id)===Number(r.tid)))continue;
      rows.push({...r,...lossSource(r),tid:0,source_id:d.tid,source_row_id:r.tid,qty:r.remaining_qty,c7:r.remaining_rolls,c11:kind==='inward'?(r.c15 || ''):r.c11,...(kind==='inward' ? {...rowDefaults(),c3:''} : {})});
    }
    if(kind==='delivery' && documents.length){const d=documents[0];for(const [target,sourceKey] of Object.entries({a1:'a1',a11:'a11',order_number:'order_number',a19:'a27',a20:'a20'}))form.elements[target].value=d[sourceKey] || '';}if(documents.length){source.value=String(documents[documents.length-1].tid);refresh(source);}
    error();render();
    if(['inward','delivery'].includes(kind))await inwardFabrics();
  }
  one('[data-jw-load]')?.addEventListener('click',run(async()=>{if(!source.value)throw new Error('Select a source document.');await loadSourceDocuments([source.value]);}));
  if(['inward','delivery'].includes(kind)){
    const modal=one('#jwOpenGrns'),jobs=one('[data-jw-grn-jobs]'),sourceLabel=kind==='delivery'?'Inward':'GRN';let jobsGeneration=0;
    const modalError=message=>{one('[data-jw-grn-error]').textContent=message;one('[data-jw-grn-error]').classList.toggle('d-none',!message);};
    one('[data-jw-open-grns]').addEventListener('click',run(async()=>{
      if(!customer.value)throw new Error('Select a company first.');
      const company=customer.value,stamp=++jobsGeneration;
      modalError('');jobs.innerHTML='<tr><td colspan="10" class="text-center text-secondary">Loading documents…</td></tr>';
      one('[data-jw-grns-all]').checked=false;one('[data-jw-grns-load]').disabled=true;
      ['total','used','balance','percent'].forEach(k=>one(`[data-jw-grns-${k}]`).textContent='—');
      bootstrap.Modal.getOrCreateInstance(modal).show();
      try{
        const result=await api('sources',undefined,{customer:company});if(stamp!==jobsGeneration || customer.value!==company)return;
        const list=result.data.filter(d=>Number(d.total_qty)-Number(d.inward_qty)>0 && !['Billed','Cancelled','Rejected'].includes(d.status));
        let total=0,used=0;
        jobs.innerHTML=list.map(d=>{const qty=Number(d.total_qty),inward=Number(d.inward_qty),balance=Math.max(0,qty-inward);total+=qty;used+=inward;
          return `<tr><td><input class="form-check-input" type="checkbox" data-jw-grn-choice="${Number(d.tid)}" aria-label="Select GRN ${esc(d.generate_no)}"></td><td>${esc(d.generate_no)}</td><td>${esc(d.company_name)}</td><td>${esc(d.create_date)}</td><td><span class="cz-badge neutral">${esc(d.status)}</span></td><td class="text-end">${qty.toFixed(3)}</td><td class="text-end">${inward.toFixed(3)}</td><td class="text-end">${balance.toFixed(3)}</td><td class="text-end">${(qty?balance/qty*100:0).toFixed(2)}</td><td class="text-end"><button class="btn btn-dark btn-sm" type="button" data-jw-grn-load="${Number(d.tid)}">Load</button></td></tr>`;
        }).join('') || '<tr><td colspan="10" class="text-center text-secondary">No open documents for this company.</td></tr>';
        one('[data-jw-grns-total]').textContent=total.toFixed(3);one('[data-jw-grns-used]').textContent=used.toFixed(3);one('[data-jw-grns-balance]').textContent=(total-used).toFixed(3);one('[data-jw-grns-percent]').textContent=(total?(total-used)/total*100:0).toFixed(2);
        one('[data-jw-grns-load]').disabled=!list.length;
      }catch(e){modalError(e.message);jobs.replaceChildren();}
    }));
    one('[data-jw-grns-all]').addEventListener('change',e=>all('[data-jw-grn-choice]').forEach(x=>x.checked=e.target.checked));
    jobs.addEventListener('change',()=>{const checks=all('[data-jw-grn-choice]'),selected=checks.filter(x=>x.checked).length;one('[data-jw-grns-all]').checked=!!checks.length && selected===checks.length;one('[data-jw-grns-all]').indeterminate=selected>0 && selected<checks.length;});
    async function loadJobs(ids){
      modalError('');if(!ids.length){modalError('Select at least one document.');return;}
      const buttons=Array.from(modal.querySelectorAll('button'));buttons.forEach(x=>x.disabled=true);
      try{await loadSourceDocuments(ids);bootstrap.Modal.getOrCreateInstance(modal).hide();}catch(e){modalError(e.message);}finally{buttons.forEach(x=>x.disabled=false);}
    }
    jobs.addEventListener('click',e=>{const button=e.target.closest('[data-jw-grn-load]');if(button)loadJobs([button.dataset.jwGrnLoad]);});
    one('[data-jw-grns-load]').addEventListener('click',()=>loadJobs(all('[data-jw-grn-choice]:checked').map(x=>x.dataset.jwGrnChoice)));
  }
  let dcGeneration=0;
  async function checkDc(){if(kind!=='grn' || !customer.value || !form.elements.reference_number.value)return;const generation=++dcGeneration;const r=await api('dc_check',undefined,{company_name:customer.value,a27:form.elements.a27.value,reference_number:form.elements.reference_number.value,id});if(generation!==dcGeneration)return;form.elements.reference_number.setCustomValidity(r.data.duplicate ? 'This inward DC is already used for this customer and supplier.' : '');error(r.data.duplicate ? 'This inward DC is already used for this customer and supplier.' : '');}
  if(kind==='grn'){form.elements.reference_number.addEventListener('change',run(checkDc));change(form.elements.a27,checkDc);}
  one('[data-jw-entry]')?.addEventListener('input',()=>{const qty=Number(one('[data-jw-entry-field="qty"]').value)||0,actual=Number(one('[data-jw-entry-field="c11"]').value)||0;one('[data-jw-entry-diff]').value=(actual-qty).toFixed(3);});
  one('[data-jw-copy-qty]')?.addEventListener('click',()=>{one('[data-jw-entry-field="c11"]').value=one('[data-jw-entry-field="qty"]').value;one('[data-jw-entry-diff]').value='0.000';});
  one('[data-jw-add]')?.addEventListener('click',()=>{capture();const r={lab_app:options.labs.find(d=>d.label===form.elements.lab_dip_no?.value && d.customer_name===customer.value)?.approved_option || '',c11:0};all('[data-jw-entry-field]').forEach(x=>r[x.dataset.jwEntryField]=x.value);if(!r.product_name || !(Number(r.qty)>0)){error('Select a fabric and enter its quantity before adding a row.');return;}error();rows.push(r);all('[data-jw-entry-field]').filter(x=>x.tagName!=='SELECT').forEach(x=>x.value='');one('[data-jw-entry-diff]').value='';render();});
  body.addEventListener('input',total);body.addEventListener('click',e=>{const b=e.target.closest('[data-jw-remove]');if(!b)return;capture();rows.splice(Number(b.dataset.jwRemove),1);render();});
  const attachmentPanel=one('[data-jw-attachments]');
  const attachmentEmpty=()=>one('[data-jw-attachments-empty]').classList.toggle('d-none',!!attachmentPanel.querySelector('tbody tr'));
  new MutationObserver(attachmentEmpty).observe(attachmentPanel,{childList:true,subtree:true});
  attachmentEmpty();
  form.addEventListener('submit',e=>{e.preventDefault();run(async()=>{if(locked)return;form.classList.add('was-validated');if(!form.checkValidity())return;capture();if(kind==='inward'){const missing=rows.findIndex(r=>!processList(r.jw_proess_name).length);if(missing>=0)throw new Error(`Assign a process for fabric row ${missing+1} (${rows[missing].product_name || 'fabric'}).`);}error();save.disabled=true;try{const payload=Object.fromEntries(new FormData(form));payload.rows=rows;payload.id=id;const pending=all('[data-cz-multi-row]').filter(row=>row.querySelector('[data-cz-multi-file]').files.length);const files=pending.map(row=>row.querySelector('[data-cz-multi-file]').files[0]);if(files.length>10)throw new Error('Attach up to 10 documents per save.');payload.attachments=[];for(let i=0;i<files.length;i++){const file=files[i];if(file.size>5*1024*1024)throw new Error('Each attachment must be up to 5 MB.');const data=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result).split(',')[1]);reader.onerror=()=>reject(new Error('Unable to read attachment.'));reader.readAsDataURL(file);});payload.attachments.push({name:file.name,data,notes:pending[i].querySelector('[data-cz-multi-description]').value});}payload.remove_attachments=all('[data-jw-remove-file][data-cz-file-removed]').map(x=>Number(x.dataset.jwRemoveFile));await api('save',payload);location.href=root.dataset.list;}finally{save.disabled=false;}})();});
  run(async()=>{options=(await api('options')).data;options.customers.forEach(c=>customer.add(new Option(c,c)));options.staff.forEach(s=>form.elements.staffname.add(new Option(s.full_name,s.full_name)));form.elements.create_date.value=new Date().toLocaleDateString('en-CA');if(['grn','inward','delivery'].includes(kind)){if(kind==='grn')options.products.forEach(p=>one('[data-jw-entry-field="product_name"]').add(new Option(p.product_name,p.product_name)));['a27','a15'].forEach(k=>{if(form.elements[k]?.tagName==='SELECT')options.customers.forEach(c=>form.elements[k].add(new Option(c,c)));});await Promise.all(all('[data-jw-master]').map(async x=>{const names=await window.CloudZoo.masters.openMaster(x.dataset.jwMaster);window.CloudZoo.masters.bindOpenMaster(x,x.dataset.jwMaster,x.dataset.jwLabel);window.CloudZoo.masters.fill(x,names,x.value,'Select '+x.dataset.jwLabel);}));}all('select').filter(x=>!x.closest('#jwAssignProcess') && !x.matches('[data-jw-master]')).forEach(enhance);if(kind==='inward'){all('#jwAssignProcess select').forEach(x=>{if(window.jQuery?.fn.select2)jQuery(x).select2({width:'100%',dropdownParent:jQuery('#jwAssignProcess')});});}if(kind==='grn'){const fabric=one('[data-jw-entry-field="product_name"]');window.CloudZoo.masters.bindContactAdd(fabric,'Fabric',(window.CZ_BASE_URL || '')+'/inventory/fabric-master',async()=>{try{const known=new Set(Array.from(fabric.options,o=>o.value));const fresh=(await api('options')).data;options.products=fresh.products;const names=fresh.products.map(p=>p.product_name);const added=names.find(name=>!known.has(name));window.CloudZoo.masters.fill(fabric,names,added || fabric.value,'Select Fabric Name');if(added)fabric.dispatchEvent(new Event('change',{bubbles:true}));}catch(e){error(e.message);}});}change(customer,async()=>{rows=[];render();await sources();await checkDc();});if(id){const d=(await api('get',undefined,{id})).data;for(const [k,v] of Object.entries(d)){const x=form.elements.namedItem(k);if(x){if(x.tagName==='SELECT' && v && !Array.from(x.options).some(o=>o.value===v))x.add(new Option(v,v));x.value=v ?? '';refresh(x);}}rows=d.rows;one('[data-jw-documents]').innerHTML=(d.attachments || []).map(f=>{const url=root.dataset.api+'?action=document&id='+id+'&file='+Number(f.tid);return `<tr><td class="text-break">${esc(f.notes || '—')}</td><td class="text-break"><a class="text-body" href="${esc(url)}">${esc(f.file_name)}</a></td><td class="text-end">${window.CloudZoo.attachmentActions(url,url,f.file_name)} ${window.CloudZoo.attachmentDelete(f.file_name,'data-jw-remove-file="'+Number(f.tid)+'"')}</td></tr>`;}).join('');locked=d.locked;if(locked) error('This document has downstream transactions or is billed. Changes are locked.');}else if(kind==='grn')rows=[];await sources();render();save.disabled=locked;})();
})();

// Production Shortage Trims: legacy order/process/company flow and SKU quantities.
(() => {
  const root=document.querySelector('[data-cz-shortage-trims]');if(!root)return;
  const one=s=>root.querySelector(s),all=s=>Array.from(root.querySelectorAll(s)),id=Number(root.dataset.id),abilities=root.dataset.abilities.split(',');
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function error(message=''){one('[data-st-error]').textContent=message;one('[data-st-error]').classList.toggle('d-none',!message);}
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,q={}){const url=new URL(root.dataset.api,location.origin);url.search=new URLSearchParams({action,...q});const response=await window.czApiFetch(url.pathname+url.search,data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});const r=await response.json();if(!response.ok || !r.success)throw new Error(r.message || 'Unable to complete request.');return r;}
  const enhance=x=>{if(window.jQuery?.fn.select2)jQuery(x).select2({width:'100%'});};
  const change=(x,fn)=>{if(window.jQuery?.fn.select2 && x.tagName==='SELECT')jQuery(x).on('change',run(fn));else x.addEventListener('change',run(fn));};
  const refresh=x=>{if(window.jQuery?.fn.select2)jQuery(x).trigger('change.select2');};
  function fill(x,items,value='',placeholder='Select…'){x.replaceChildren(new Option(placeholder,''));items.forEach(item=>x.add(new Option(typeof item==='string' ? item : item.label,typeof item==='string' ? item : item.value)));if(value && !Array.from(x.options).some(o=>o.value===String(value)))x.add(new Option(value,value));x.value=value;refresh(x);}
  if(root.dataset.czShortageTrims==='list'){
    let page=1,pages=1,sort='tid',dir='desc',generation=0,timer;const selected=new Set();
    function selection(){one('[data-st-selected]').textContent=`Selected (${selected.size})`;one('[data-st-selection]').classList.toggle('d-none',!selected.size);one('[data-st-selection]').classList.toggle('d-flex',!!selected.size);}
    async function load(){error();const stamp=++generation,q={page,sort,dir};all('[data-st-filter]').forEach(x=>q[x.dataset.stFilter]=x.value);const r=await api('list',undefined,q);if(stamp!==generation)return;pages=r.pages;selected.clear();selection();one('[data-st-all]').checked=false;one('[data-st-empty]').classList.toggle('d-none',!!r.rows.length);one('[data-st-list]').innerHTML=r.rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-st-check="${Number(d.tid)}" aria-label="Select shortage ${esc(d.generate_no)}"></td><td>${esc(d.generate_no)}</td><td>${esc(d.order_number)}</td><td>${esc(d.company_name)}</td><td>${esc(d.create_date)}</td><td><span class="badge text-bg-light border">${esc(d.status)}</span></td><td>${esc(d.a1)}</td><td>${Number(d.total_qty).toFixed(3)}</td><td class="text-end"><div class="dropdown"><button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Document actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end"><li><button class="dropdown-item" type="button" data-st-view="${Number(d.tid)}">View details</button></li>${abilities.includes('update') ? `<li><a class="dropdown-item" href="${esc(root.dataset.list)}/form?id=${Number(d.tid)}">Edit</a></li>` : ''}</ul></div></td></tr>`).join('');one('[data-st-range]').textContent=`Showing ${r.total ? (page-1)*25+1 : 0} to ${Math.min(page*25,r.total)} of ${r.total} entries`;one('[data-st-prev]').disabled=page<=1;one('[data-st-next]').disabled=page>=pages;}
    all('[data-st-filter]').forEach(x=>{if(x.tagName==='SELECT'){enhance(x);change(x,()=>{page=1;return load();});}else x.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(run(()=>{page=1;return load();}),250);});});
    one('[data-st-clear]').addEventListener('click',run(()=>{all('[data-st-filter]').forEach(x=>{x.value='';refresh(x);});page=1;return load();}));one('[data-st-prev]').addEventListener('click',run(()=>{page=Math.max(1,page-1);return load();}));one('[data-st-next]').addEventListener('click',run(()=>{page=Math.min(pages,page+1);return load();}));all('[data-st-sort]').forEach(x=>x.addEventListener('click',run(()=>{dir=sort===x.dataset.stSort && dir==='asc' ? 'desc' : 'asc';sort=x.dataset.stSort;page=1;return load();})));
    root.addEventListener('change',e=>{if(e.target.matches('[data-st-all]'))all('[data-st-check]').forEach(x=>{x.checked=e.target.checked;x.dispatchEvent(new Event('change',{bubbles:true}));});if(e.target.matches('[data-st-check]')){const key=Number(e.target.dataset.stCheck);e.target.checked ? selected.add(key) : selected.delete(key);selection();}});
    one('[data-st-delete]')?.addEventListener('click',run(async()=>{if(!selected.size)throw new Error('Select documents to delete.');if(!confirm(`Delete ${selected.size} selected shortage documents?`))return;await api('delete',{ids:[...selected]});await load();}));
    root.addEventListener('click',run(async e=>{const button=e.target.closest('[data-st-view]');if(!button)return;const d=(await api('get',undefined,{id:button.dataset.stView})).data;one('[data-st-details]').innerHTML=`<p><strong>${esc(d.generate_no)}</strong> · ${esc(d.order_number)} · ${esc(d.a1)} · ${esc(d.company_name)} · ${esc(d.create_date)} · ${esc(d.status)}</p><div class="table-responsive"><table class="table cz-table"><thead><tr><th>SKU</th><th>Qty</th><th>Unit</th><th>Rate</th><th>Net amount</th></tr></thead><tbody>${d.rows.map(r=>`<tr><td>${esc(r.product_name)}</td><td>${Number(r.qty).toFixed(3)}</td><td>${esc(r.unit)}</td><td>${Number(r.rate).toFixed(2)}</td><td>${Number(r.amount).toFixed(2)}</td></tr>`).join('')}</tbody></table></div><p>${esc(d.narration)}</p><p class="small text-secondary">Stock effect: ${esc(d.stock_effect || 'none')} · Warehouse: ${esc(d.warehouse)}</p>${d.has_attachment ? `<a class="text-secondary" href="${esc(root.dataset.api)}?action=attachment&amp;id=${Number(d.tid)}">Download attachment</a>` : ''}`;bootstrap.Modal.getOrCreateInstance(one('#stDetails')).show();}));run(load)();return;
  }
  const form=one('[data-st-form]'),body=one('[data-st-lines]'),discountBody=one('[data-st-discounts]'),save=one('[data-st-save]'),order=form.elements.order_number,process=form.elements.a1,company=form.elements.company_name,sourceKind=form.elements.source_kind,sourceId=form.elements.source_id;let options={},rows=[],discounts=[],lockedSource=false,generation=0,loading=true;
  const round=v=>Math.round((v+Number.EPSILON)*100)/100;
  function capture(){Array.from(body.rows).forEach((tr,i)=>tr.querySelectorAll('[data-st-cell]').forEach(x=>rows[i][x.dataset.stCell]=x.value));Array.from(discountBody.rows).forEach((tr,i)=>tr.querySelectorAll('[data-st-discount]').forEach(x=>discounts[i][x.dataset.stDiscount]=x.value));}
  function calculate(){capture();let qty=0,net=0;rows.forEach((r,i)=>{const amount=round((Number(r.qty)||0)*(Number(r.rate)||0)),disc=round(amount*(Number(r.discount_percentage)||0)/100),base=amount-disc,tax=Number(r.tax_percentage ?? r.tax_inc_percentage)||0;const total=round(base+(form.elements.igst.checked ? round(base*tax/100) : 2*round(base*tax/200)));qty+=Number(r.qty)||0;net+=total;body.rows[i]?.querySelector('[data-st-net]')?.replaceChildren(document.createTextNode(total.toFixed(2)));});net=round(net);let taken=0;discounts.forEach(r=>{taken+=Number(r.value)||round((net-taken)*(Number(r.percentage)||0)/100);});const other=Number(form.elements.other_charges.value)||round((net-taken)*(Number(form.elements.a5.value)||0)/100),before=net-taken+other,total=Math.round(before);const values={net,discount:taken,round:round(total-before),total};all('[data-st-total]').forEach(x=>x.textContent=values[x.dataset.stTotal].toFixed(2));one('[data-st-qty]').textContent=`${rows.length} SKUs · ${qty.toFixed(3)} total quantity`;}
  const selectedRows=new Set();let activeLine=-1;
  function render(){selectedRows.clear();one('[data-st-row-all]').checked=false;one('[data-st-remove-selected]').classList.add('d-none');body.innerHTML=rows.map((r,i)=>`<tr><td><input class="form-check-input" type="checkbox" data-st-row-select="${i}" aria-label="Select SKU row ${i+1}"></td><td><select class="form-select form-select-sm" data-st-cell="product_name" required aria-label="SKU"><option value="">Select SKU</option>${options.products.map(p=>`<option value="${esc(p.sku)}" ${p.sku===r.product_name ? 'selected' : ''}>${esc(p.sku)} — ${esc(p.product_name)}</option>`).join('')}</select></td><td><input class="form-control form-control-sm" type="number" min="0.001" step="0.001" required data-st-cell="qty" value="${esc(r.qty ?? '')}" aria-label="Quantity"></td><td class="text-center"><button class="btn btn-outline-secondary btn-sm" type="button" data-st-line-view="${i}">View</button></td><td class="text-center"><button class="btn btn-outline-danger btn-sm" type="button" data-st-remove="${i}" aria-label="Delete SKU row"><i class="bi bi-trash3"></i></button></td></tr>`).join('');all('[data-st-cell="product_name"]').forEach((x,i)=>{enhance(x);change(x,()=>{capture();const p=options.products.find(p=>p.sku===x.value);if(p)rows[i].unit=p.base_uom;calculate();});});calculate();}
  function renderDiscounts(){discountBody.innerHTML=discounts.map((d,i)=>`<tr>${['name','percentage','value'].map(k=>`<td><input class="form-control form-control-sm" data-st-discount="${k}" ${k==='name' ? 'maxlength="45" required' : `type="number" min="0" max="${k==='percentage' ? 100 : 9999999}" step="0.01"`} value="${esc(d[k] ?? '')}" aria-label="Discount ${k}"></td>`).join('')}<td><button class="btn btn-outline-danger btn-sm" type="button" data-st-discount-remove="${i}" aria-label="Remove discount"><i class="bi bi-trash3"></i></button></td></tr>`).join('');calculate();}
  async function loadSources(){if(lockedSource)return;fill(sourceId,[]);if(!sourceKind.value || !order.value || !process.value || !company.value)return;const stamp=++generation,r=await api('sources',undefined,{kind:sourceKind.value,order:order.value,process:process.value,company:company.value});if(stamp!==generation)return;fill(sourceId,r.data.map(d=>({value:String(d.tid),label:`${d.generate_no} · ${d.create_date} · ${d.reference_number || ''}`})));}
  async function loadCompanies(value=''){const stamp=++generation;fill(company,[]);if(!order.value || !process.value)return;const r=await api('companies',undefined,{order:order.value,process:process.value});if(stamp!==generation)return;fill(company,r.data,value);await loadSources();}
  async function loadProcesses(value=''){const stamp=++generation;fill(process,[]);fill(company,[]);if(!order.value)return;const r=await api('order',undefined,{order:order.value});if(stamp!==generation)return;fill(process,r.data.processes,value);}
  async function loadSource(kind,key){const d=(await api('source',undefined,{kind,id:key})).data;if(!d)throw new Error('Source document is unavailable in the active accounting year.');order.value=d.order_number;refresh(order);await loadProcesses(d.a12);await loadCompanies(d.company_name);sourceKind.value=kind;refresh(sourceKind);await loadSources();fill(sourceId,Array.from(sourceId.options).filter(o=>o.value).map(o=>({value:o.value,label:o.textContent})),String(key));rows=d.items.map(r=>({...r,rate:0,discount_percentage:0,tax_percentage:0}));form.elements.reference_number.value=d.generate_no;form.elements.terms.value=d.terms || '';render();}
  change(order,async()=>{if(loading)return;rows=[];render();await loadProcesses();});change(process,async()=>{if(loading)return;rows=[];render();await loadCompanies();});change(company,()=>loading ? undefined : loadSources());change(sourceKind,()=>loading ? undefined : loadSources());
  one('[data-st-load]').addEventListener('click',run(()=>{if(!sourceKind.value || !sourceId.value)throw new Error('Select a production source document.');return loadSource(sourceKind.value,sourceId.value);}));one('[data-st-add]').addEventListener('click',()=>{capture();rows.push({qty:'',rate:0,discount_percentage:0,tax_percentage:0});render();});one('[data-st-discount-add]').addEventListener('click',()=>{capture();discounts.push({name:'',percentage:0,value:0});renderDiscounts();});
  body.addEventListener('click',e=>{const b=e.target.closest('[data-st-remove]');if(b){capture();rows.splice(Number(b.dataset.stRemove),1);render();}});discountBody.addEventListener('click',e=>{const b=e.target.closest('[data-st-discount-remove]');if(b){capture();discounts.splice(Number(b.dataset.stDiscountRemove),1);renderDiscounts();}});form.addEventListener('input',calculate);form.elements.igst.addEventListener('change',calculate);
  root.addEventListener('change',e=>{if(e.target.matches('[data-st-row-all]'))all('[data-st-row-select]').forEach(x=>{x.checked=e.target.checked;x.dispatchEvent(new Event('change',{bubbles:true}));});if(e.target.matches('[data-st-row-select]')){const i=Number(e.target.dataset.stRowSelect);e.target.checked ? selectedRows.add(i) : selectedRows.delete(i);one('[data-st-remove-selected]').classList.toggle('d-none',!selectedRows.size);}});
  one('[data-st-remove-selected]').addEventListener('click',()=>{capture();rows=rows.filter((r,i)=>!selectedRows.has(i));render();});
  body.addEventListener('click',e=>{const b=e.target.closest('[data-st-line-view]');if(!b)return;capture();activeLine=Number(b.dataset.stLineView);const r=rows[activeLine],p=options.products.find(p=>p.sku===r.product_name);one('[data-st-line-details]').innerHTML=`<p><strong>${esc(r.product_name || 'Select a SKU first')}</strong></p><dl class="row small"><dt class="col-4">Product</dt><dd class="col-8">${esc(p?.product_name)}</dd><dt class="col-4">Colour / Size</dt><dd class="col-8">${esc(p?.colour)} / ${esc(p?.size)}</dd><dt class="col-4">Unit / Stock</dt><dd class="col-8">${esc(p?.base_uom)} / ${esc(p?.stock)}</dd></dl><div class="row g-3">${['rate','discount_percentage','tax_percentage'].map(k=>`<div class="col-md-4"><label class="form-label" for="stLine-${k}">${esc(k.replaceAll('_',' '))}</label><input class="form-control" id="stLine-${k}" data-st-line-edit="${k}" type="number" min="0" max="${k==='rate' ? 9999999 : 100}" step="0.01" value="${esc(r[k] ?? 0)}"></div>`).join('')}<div class="col-12"><label class="form-label" for="stLineDescription">Description</label><textarea class="form-control" id="stLineDescription" data-st-line-edit="description" maxlength="10000" rows="3">${esc(r.description || '')}</textarea></div></div>`;bootstrap.Modal.getOrCreateInstance(one('#stLineView')).show();});
  one('[data-st-line-apply]').addEventListener('click',()=>{const inputs=all('[data-st-line-edit]');if(!inputs.every(x=>x.reportValidity()))return;if(rows[activeLine])inputs.forEach(x=>rows[activeLine][x.dataset.stLineEdit]=x.value);calculate();bootstrap.Modal.getOrCreateInstance(one('#stLineView')).hide();});
  function bulk(){const filters=all('[data-st-bulk-filter]');one('[data-st-bulk-all]').checked=false;one('[data-st-bulk-rows]').innerHTML=options.products.filter(p=>filters.every(x=>!x.value || String(p[x.dataset.stBulkFilter] || '')===x.value)).map(p=>`<tr><td><input class="form-check-input" type="checkbox" data-st-bulk-sku="${esc(p.sku)}" aria-label="Select ${esc(p.sku)}" ${rows.some(r=>r.product_name===p.sku) ? 'disabled' : ''}></td><td>${esc(p.sku)}</td><td>${esc(p.product_name)}</td><td>${esc(p.colour)}</td><td>${esc(p.size)}</td><td>${Number(p.stock).toFixed(3)}</td></tr>`).join('');}
  one('[data-st-bulk-all]').addEventListener('change',e=>all('[data-st-bulk-sku]:not(:disabled)').forEach(x=>x.checked=e.target.checked));
  one('[data-st-bulk-add]').addEventListener('click',()=>{capture();const chosen=all('[data-st-bulk-sku]:checked').map(x=>x.dataset.stBulkSku);if(!chosen.length){error('Select SKUs in Bulk Select.');return;}if(rows.length===1 && !rows[0].product_name)rows=[];chosen.forEach(sku=>{if(!rows.some(r=>r.product_name===sku)){const p=options.products.find(p=>p.sku===sku);rows.push({product_name:sku,qty:'',unit:p.base_uom,rate:0,discount_percentage:0,tax_percentage:0});}});error();render();bootstrap.Modal.getOrCreateInstance(one('#stBulk')).hide();});
  one('[data-st-order-view]').addEventListener('click',run(async()=>{if(!order.value)throw new Error('Select an order to view.');const r=(await api('order',undefined,{order:order.value})).data;const d=r.details || {};one('[data-st-order-details]').innerHTML=`<dl class="row">${Object.entries(d).map(([k,v])=>`<dt class="col-5">${esc(k.replaceAll('_',' '))}</dt><dd class="col-7">${esc(v)}</dd>`).join('')}</dl>`;bootstrap.Modal.getOrCreateInstance(one('#stOrderView')).show();}));
  one('#stFiles').addEventListener('change',()=>{one('[data-st-new-files]').innerHTML=Array.from(one('#stFiles').files).map((f,i)=>`<div class="row g-2 mb-2 align-items-center"><div class="col-md-4 small">${esc(f.name)}</div><div class="col-md-8"><label class="visually-hidden" for="stFileNotes-${i}">Description for ${esc(f.name)}</label><input class="form-control" id="stFileNotes-${i}" data-st-file-notes="${i}" maxlength="1000" placeholder="Description" required></div></div>`).join('');});
  form.addEventListener('submit',e=>{e.preventDefault();run(async()=>{form.classList.add('was-validated');if(!form.checkValidity()){all('.collapse').filter(x=>x.querySelector(':invalid')).forEach(x=>bootstrap.Collapse.getOrCreateInstance(x,{toggle:false}).show());return;}error();capture();save.disabled=true;try{const payload=Object.fromEntries(new FormData(form));payload.rows=rows;payload.discounts=discounts;payload.id=id;payload.igst=form.elements.igst.checked;if(lockedSource){payload.source_kind=sourceKind.value;payload.source_id=sourceId.value;}const file=one('#stImage').files[0];if(file){if(file.size>5*1024*1024)throw new Error('Attachment must be up to 5 MB.');const data=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result).split(',')[1]);reader.onerror=()=>reject(new Error('Cannot read attachment.'));reader.readAsDataURL(file);});payload.attachment={name:file.name,data};}const attachments=Array.from(one('#stFiles').files);if(attachments.length>10)throw new Error('Attach up to 10 files per save.');payload.attachments=[];for(let i=0;i<attachments.length;i++){const f=attachments[i];if(f.size>5*1024*1024)throw new Error('Each attachment must be up to 5 MB.');const data=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(String(reader.result).split(',')[1]);reader.onerror=()=>reject(new Error('Cannot read attachment.'));reader.readAsDataURL(f);});payload.attachments.push({name:f.name,data,notes:one(`[data-st-file-notes="${i}"]`).value});}payload.remove_attachments=all('[data-st-remove-file][data-cz-file-removed]').map(x=>Number(x.dataset.stRemoveFile));await api('save',payload);location.href=root.dataset.list;}finally{save.disabled=false;}})();});
  save.disabled=true;run(async()=>{options=(await api('options')).data;fill(order,options.orders.map(o=>({value:o.value,label:o.value+' · '+o.company_name})));fill(form.elements.staffname,options.staff);all('select').filter(x=>x.id!=='stWarehouse' && !x.matches('[data-st-bulk-filter]')).forEach(enhance);all('[data-st-bulk-filter]').forEach(x=>{const names=[...new Set(options.products.map(p=>String(p[x.dataset.stBulkFilter] || '')).filter(Boolean))].sort();fill(x,names,x.dataset.stBulkFilter==='category' && names.includes('Trims') ? 'Trims' : '','All');if(window.jQuery?.fn.select2)jQuery(x).select2({width:'100%',dropdownParent:jQuery(one('#stBulk'))});change(x,bulk);});bulk();const masters=window.CloudZoo.masters;const warehouses=await masters.openMaster('warehouse');fill(form.elements.warehouse,warehouses);masters.bindOpenMaster(form.elements.warehouse,'warehouse','Warehouse');form.elements.create_date.value=new Date().toLocaleDateString('en-CA');if(id){const d=(await api('get',undefined,{id})).data;fill(order,options.orders.map(o=>({value:o.value,label:o.value+' · '+o.company_name})),d.order_number);await loadProcesses(d.a1);await loadCompanies(d.company_name);for(const [key,value] of Object.entries(d)){const x=form.elements.namedItem(key);if(!x || key==='source_id')continue;if(x.type==='checkbox')continue;if(x.tagName==='SELECT' && value && !Array.from(x.options).some(o=>o.value===String(value)))x.add(new Option(value,value));x.value=value ?? '';if(x.tagName==='SELECT')refresh(x);}form.elements.igst.checked=d.a30==='igst';rows=d.rows.map(r=>({...r,tax_percentage:r.tax_inc_percentage}));discounts=d.discounts;one('[data-st-saved-files]').innerHTML=(d.attachments || []).map(f=>`<div class="d-flex align-items-center gap-2 mb-2" data-cz-file-row><a class="small text-secondary" href="${esc(root.dataset.api)}?action=attachment&amp;id=${id}&amp;attachment_id=${Number(f.tid)}">${esc(f.file_name)}</a><span class="small text-secondary">${esc(f.notes)}</span><span class="ms-auto">${window.CloudZoo.attachmentDelete(f.file_name,'data-st-remove-file="'+Number(f.tid)+'"')}</span></div>`).join('');one('[data-st-number]').textContent='#'+d.generate_no;lockedSource=!!d.source_kind;if(lockedSource){sourceKind.value=d.source_kind;fill(sourceId,[{value:String(d.source_id),label:d.reference_number || String(d.source_id)}],String(d.source_id));[order,process,company,sourceKind,sourceId].forEach(x=>x.disabled=true);one('[data-st-load]').disabled=true;}if(d.has_attachment){const link=one('[data-st-attachment]');link.href=root.dataset.api+'?action=attachment&id='+id;link.classList.remove('d-none');one('[data-st-remove-label]').classList.remove('d-none');}}else{const inward=Number(root.dataset.inward),returned=Number(root.dataset.return);if(inward || returned)await loadSource(inward ? 'inward' : 'return',inward || returned);else rows=[{qty:'',rate:0}];}render();renderDiscounts();loading=false;save.disabled=false;})();
})();

// Fabric Master (Dyeing Inventory): the legacy fabric_stock_table register.
(() => {
  const root = document.querySelector('[data-cz-fabric-master]');
  if (!root) return;
  const one = s => root.querySelector(s);
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  async function request(action, payload, query = {}) {
    const url = root.dataset.api + '?' + new URLSearchParams({action, ...query});
    const res = await window.czApiFetch(url, payload === undefined ? {} : {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
    let data;
    try { data = await res.json(); } catch { throw new Error('Unexpected server response.'); }
    if (!res.ok || data.success === false) throw Object.assign(new Error(data.message || 'Request failed.'), {errors:data.errors});
    return data;
  }
  const banner = one('[data-fm-error], [data-cz-form-error]');
  const error = message => { banner.textContent = message || ''; banner.classList.toggle('d-none', !message); };
  if (root.dataset.czFabricMaster === 'list') {
    let page = 1, pages = 1, sequence = 0, timer;
    window.CloudZoo?.masters?.select2(one('[data-fm-type]'));
    async function load() {
      const token = ++sequence;
      try {
        const data = await request('list', undefined, {page, search:one('[data-fm-search]').value, fabric_stock_type:one('[data-fm-type]').value});
        if (token !== sequence) return;
        error(); pages = data.pages;
        one('[data-fm-rows]').innerHTML = data.rows.map(r => `<tr><td><input class="form-check-input" type="checkbox" data-fm-check aria-label="Select ${esc(r.fabric_name)}"></td>${['sku','fabric_name','count','dia','gsm','color','composition'].map(k => `<td>${esc(r[k])}</td>`).join('')}<td><span class="cz-badge neutral">${esc(r.fabric_stock_type || 'general')}</span></td><td class="text-end">${esc(r.current_stock ?? 0)}</td><td>${root.dataset.canUpdate === '1' || root.dataset.canDelete === '1' ? `<div class="dropdown"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" aria-label="Actions for ${esc(r.fabric_name)}"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu">${root.dataset.canUpdate === '1' ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.formUrl)}?id=${Number(r.tid)}">Edit</a></li>` : ''}${root.dataset.canDelete === '1' ? `<li><button class="cz-action-menu__item is-danger" type="button" data-fm-delete="${Number(r.tid)}" data-fm-name="${esc(r.fabric_name)}"><i class="bi bi-trash3 me-1"></i> Delete</button></li>` : ''}</ul></div>` : ''}</td></tr>`).join('') || '<tr><td colspan="11" class="text-center text-secondary py-4">No fabrics found.</td></tr>';
        one('[data-fm-summary]').textContent = `${data.total} fabrics · Page ${page} of ${pages}`;
        selection(); one('[data-fm-prev]').disabled = page <= 1; one('[data-fm-next]').disabled = page >= pages;
      } catch (err) { if (token === sequence) error(err.message); }
    }
    let deleting = false;
    root.addEventListener('click', async event => {
      const button = event.target.closest('[data-fm-delete]');
      if (!button || deleting || root.dataset.canDelete !== '1') return;
      if (!window.confirm(`Delete fabric "${button.dataset.fmName}"? This cannot be undone.`)) return;
      deleting = true; button.disabled = true;
      try {
        await request('delete', {ids:[Number(button.dataset.fmDelete)]});
        // Deleting the only row on a later page returns to the preceding page.
        if (one('[data-fm-rows]').children.length === 1 && page > 1) --page;
        await load();
      } catch (err) { error(err.message); button.disabled = false; }
      finally { deleting = false; }
    });
    function selection() {
      const boxes = Array.from(root.querySelectorAll('[data-fm-check]'));
      const count = boxes.filter(x => x.checked).length;
      one('[data-fm-selected]').textContent = `${count} selected`;
      one('[data-fm-clear-selection]').disabled = count === 0;
      one('[data-fm-check-all]').checked = boxes.length > 0 && count === boxes.length;
      one('[data-fm-check-all]').indeterminate = count > 0 && count < boxes.length;
    }
    root.addEventListener('change', e => { if (e.target.matches('[data-fm-check]')) selection(); });
    one('[data-fm-check-all]').addEventListener('change', e => { root.querySelectorAll('[data-fm-check]').forEach(x => x.checked = e.target.checked); selection(); });
    one('[data-fm-clear-selection]').addEventListener('click', () => { root.querySelectorAll('[data-fm-check]').forEach(x => x.checked = false); selection(); });
    one('[data-fm-search]').addEventListener('input', () => { ++sequence; clearTimeout(timer); timer = setTimeout(() => {page = 1; load();}, 250); });
    one('[data-fm-type]').addEventListener('change', () => {page = 1; load();});
    one('[data-fm-prev]').addEventListener('click', () => {if (page > 1) {--page; load();}});
    one('[data-fm-next]').addEventListener('click', () => {if (page < pages) {++page; load();}});
    load();
    return;
  }
  const fields = root.elements, id = Number(root.dataset.id) || 0, save = one('[data-fm-save]');
  let ready = false;
  const sku = () => { fields.sku.value = ['count','fabric_name','gsm','dia','composition','color'].map(k => fields[k].value.trim()).filter(Boolean).join('-'); };
  root.addEventListener('input', sku);
  window.CloudZoo?.masters?.select2(fields.fabric_stock_type);
  (async () => {
    try {
      if (id) {
        const data = (await request('get', undefined, {id})).data;
        Object.entries(data).forEach(([k,v]) => {const field = fields.namedItem(k); if (field) field.value = v ?? '';});
        window.CloudZoo?.masters?.refresh(fields.fabric_stock_type);
      } else fields.minium_stock.value = '0';
      sku(); ready = true; save.disabled = false;
    } catch (err) { error(err.message); }
  })();
  root.addEventListener('submit', async event => {
    event.preventDefault();
    if (!ready || save.disabled) return;
    root.classList.add('was-validated');
    root.querySelectorAll('[data-cz-error-for]').forEach(x => {x.textContent = ''; x.classList.add('d-none');});
    root.querySelectorAll('.is-invalid').forEach(x => x.classList.remove('is-invalid'));
    error();
    if (!root.checkValidity()) return;
    save.disabled = true;
    try {
      await request('save', {...Object.fromEntries(new FormData(root)), id});
      window.location.assign(root.dataset.listUrl);
    } catch (err) {
      error(err.message);
      Object.entries(err.errors || {}).forEach(([key,value]) => {
        const field = fields.namedItem(key); field?.classList.add('is-invalid');
        const feedback = Array.from(root.querySelectorAll('[data-cz-error-for]')).find(x => x.dataset.czErrorFor === key);
        if (feedback) {feedback.textContent = value; feedback.classList.remove('d-none');}
      });
      save.disabled = false;
    }
  });
})();

// Lab Costing Process Master: legacy process-type-lab-costing templates.
(() => {
  const root=document.querySelector('[data-cz-costing-process]'); if(!root)return;
  const one=s=>root.querySelector(s),all=s=>Array.from(root.querySelectorAll(s));
  const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const can=root.dataset.abilities.split(',');
  const error=m=>{one('[data-cp-error]').textContent=m || '';one('[data-cp-error]').classList.toggle('d-none',!m);};
  const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
  async function api(action,data,query={}) {
    const res=await window.czApiFetch(root.dataset.api+'?'+new URLSearchParams({action,...query}),data===undefined ? {} : {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const r=await res.json();if(!res.ok || !r.success)throw new Error(r.message || 'Request failed.');return r;
  }
  if(root.dataset.czCostingProcess==='list') {
    let page=1,pages=1,sequence=0,timer;
    const selected=()=>all('[data-cp-check]:checked').map(x=>Number(x.value));
    function selection(){const n=selected().length,total=all('[data-cp-check]').length;one('[data-cp-selected]').textContent=n+' selected';one('[data-cp-all]').checked=total>0 && n===total;one('[data-cp-all]').indeterminate=n>0 && n<total;const del=one('[data-cp-delete]');if(del)del.disabled=!n;}
    async function load(){const token=++sequence,r=await api('list',undefined,{page,search:one('[data-cp-search]').value});if(token!==sequence)return;error();pages=r.pages;
      one('[data-cp-rows]').innerHTML=r.rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-cp-check value="${Number(d.tid)}" aria-label="Select ${esc(d.process_type)}"></td><td>${esc(d.process_type)}</td><td>${esc(d.description)}</td><td><span class="cz-badge neutral">${esc(d.status)}</span></td><td class="text-end"><div class="dropdown d-inline-block"><button class="cz-icon-btn light" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" aria-label="Process actions"><i class="bi bi-three-dots"></i></button><ul class="dropdown-menu dropdown-menu-end cz-action-menu">${can.includes('update') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.list)}/form?id=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-pencil"></i></span><span>Edit</span></a></li>` : ''}${can.includes('create') ? `<li><a class="cz-action-menu__item" href="${esc(root.dataset.list)}/form?copy=${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-copy"></i></span><span>Copy</span></a></li>` : ''}${can.includes('delete') ? `<li><hr class="cz-action-menu__divider"></li><li><button class="cz-action-menu__item is-danger" type="button" data-cp-remove="${Number(d.tid)}"><span class="cz-action-menu__icon"><i class="bi bi-trash3"></i></span><span>Delete</span></button></li>` : ''}</ul></div></td></tr>`).join('') || '<tr><td colspan="5" class="text-center text-secondary py-4">No process templates found.</td></tr>';
      one('[data-cp-range]').textContent=`${r.total} processes · Page ${page} of ${pages}`;one('[data-cp-prev]').disabled=page<=1;one('[data-cp-next]').disabled=page>=pages;selection();}
    async function remove(ids){if(!ids.length || !confirm('Delete selected process templates? Existing lab costings retain their ingredients.'))return;await api('delete',{ids});page=1;await load();}
    one('[data-cp-search]').addEventListener('input',()=>{++sequence;clearTimeout(timer);timer=setTimeout(run(async()=>{page=1;await load();}),250);});
    one('[data-cp-all]').addEventListener('change',e=>{all('[data-cp-check]').forEach(x=>x.checked=e.target.checked);selection();});root.addEventListener('change',e=>{if(e.target.matches('[data-cp-check]'))selection();});
    one('[data-cp-delete]')?.addEventListener('click',run(()=>remove(selected())));root.addEventListener('click',run(e=>{const b=e.target.closest('[data-cp-remove]');if(b)return remove([Number(b.dataset.cpRemove)]);}));
    one('[data-cp-prev]').addEventListener('click',run(async()=>{--page;await load();}));one('[data-cp-next]').addEventListener('click',run(async()=>{++page;await load();}));run(load)();return;
  }
  const form=one('[data-cp-form]'),save=one('[data-cp-save]');let products=[],stages=[];
  function updateCounts(){all('[data-cp-stage]').forEach(body=>{one(`[data-cp-count="${body.dataset.cpStage}"]`).textContent=body.rows.length;Array.from(body.rows).forEach((row,i)=>row.querySelector('[data-cp-row-number]').textContent=i+1);});}
  function add(index,row={}) {
    const available=products.filter(p=>index===3 || (/chemical/i.test(String(p.category || '')) || /^(aux|auxiliary)$/i.test(String(p.category || '').trim()))); const tr=document.createElement('tr');tr.innerHTML=`<td data-cp-row-number></td><td><input class="form-control form-control-sm" readonly data-cp-field="category" maxlength="100" value="${esc(row.category || 'Chemicals')}" aria-label="Type"></td><td><select class="form-select form-select-sm" data-cp-field="product_name" aria-label="Chemical"><option value="">Select chemical</option>${available.map(p=>`<option value="${esc(p.product_name)}">${esc(p.product_name)}</option>`).join('')}</select></td><td><input class="form-control form-control-sm" data-cp-field="qty" type="number" min="0" step="0.00001" value="${esc(row.qty ?? 0)}" aria-label="Quantity"></td><td><input class="form-control form-control-sm" data-cp-field="unit" value="${esc(String(row.unit || 'GPL').toUpperCase())}" maxlength="50" aria-label="Unit"></td><td><input type="hidden" data-cp-field="rate" value="${esc(row.rate ?? 0)}"><input type="hidden" data-cp-field="remarks" value="${esc(row.remarks)}"><button class="cz-icon-btn light text-danger" type="button" data-cp-remove-row aria-label="Remove ingredient"><i class="bi bi-trash3"></i></button></td>`;
    one(`[data-cp-stage="${index}"]`).append(tr);const select=tr.querySelector('select');if(row.product_name && !Array.from(select.options).some(o=>o.value===row.product_name))select.add(new Option(row.product_name,row.product_name));select.value=row.product_name || '';window.CloudZoo.masters.select2(select); updateCounts();
    select.addEventListener('change',()=>{const p=products.find(p=>p.product_name===select.value);if(p){tr.querySelector('[data-cp-field="rate"]').value=Number(p.last_rate)||Number(p.purchase_price)||Number(p.selling_price)||0;tr.querySelector('[data-cp-field="category"]').value=p.category || 'Chemicals';}});
  }
  form.addEventListener('input',event=>{if(event.target.matches('[data-cp-field="unit"]'))event.target.value=event.target.value.toUpperCase();});
  one('[data-cp-refresh-products]')?.addEventListener('click',run(async()=>{
    const opts=(await api('options')).data;products=opts.products;
    all('[data-cp-stage]').forEach(body=>Array.from(body.rows).forEach(tr=>{
      const index=Number(body.dataset.cpStage),select=tr.querySelector('select'),value=select.value;
      const available=products.filter(p=>index===3 || (/chemical/i.test(String(p.category || '')) || /^(aux|auxiliary)$/i.test(String(p.category || '').trim())));
      window.CloudZoo.masters.fill(select,available.map(p=>p.product_name),value,'Select chemical');
    }));error();window.CloudZoo?.toast?.('Chemical options refreshed.');
  }));
  all('[data-cp-add]').forEach(b=>b.addEventListener('click',()=>add(Number(b.dataset.cpAdd))));form.addEventListener('click',e=>{const remove=e.target.closest('[data-cp-remove-row]'); if(remove){remove.closest('tr').remove();updateCounts();}});
  form.addEventListener('submit',e=>{e.preventDefault();run(async()=>{form.classList.add('was-validated');if(!form.checkValidity() || save.disabled)return;save.disabled=true;error();try{const data=Object.fromEntries(new FormData(form));data.id=Number(root.dataset.id);data.items=all('[data-cp-stage]').flatMap(body=>Array.from(body.rows).map(tr=>({...Object.fromEntries(Array.from(tr.querySelectorAll('[data-cp-field]')).map(x=>[x.dataset.cpField,x.value])),process_tab:stages[Number(body.dataset.cpStage)]})));await api('save',data);location.assign(root.dataset.list);}finally{save.disabled=false;}})();});
  run(async()=>{const opts=(await api('options')).data;products=opts.products;stages=opts.stages;const source=Number(root.dataset.id)||Number(root.dataset.copy);if(source){const d=(await api('get',undefined,{id:source})).data;if(d.status && !Array.from(form.elements.status.options).some(o=>o.value===d.status)) form.elements.status.add(new Option(d.status,d.status)); ['process_type','description','status'].forEach(k=>form.elements[k].value=d[k] || '');if(Number(root.dataset.copy))form.elements.process_type.value+=' (Copy)';d.items.forEach(row=>{const stage=row.process_tab==='Dyeing' ? 'Dyeing Chemicals' : row.process_tab;add(Math.max(0,stages.indexOf(stage)),row);});}window.CloudZoo.masters.select2(form.elements.status);save.disabled=false;})();
})();

// Lab Costing Approval Report uses the existing permission-checked status endpoint.
(() => {
  const form=document.querySelector('[data-cz-costing-approval], [data-cz-quotation-approval]');if(!form)return;
  window.CloudZoo.masters.select2(form.elements.status);
  const error=document.querySelector('[data-ca-error]'),success=document.querySelector('[data-ca-success]');
  form.addEventListener('submit',async event=>{
    event.preventDefault();form.classList.add('was-validated');if(!form.checkValidity())return;
    const save=form.querySelector('[type="submit"]');if(!save || save.disabled)return;
    save.disabled=true;error.classList.add('d-none');success.classList.add('d-none');
    try {
      const response=await window.czApiFetch(form.dataset.api+'?action=update_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:Number(form.dataset.id),status:form.elements.status.value,remarks:form.elements.remarks.value})});
      const result=await response.json();if(!response.ok || !result.success)throw new Error(result.message || 'Unable to update approval.');
      location.reload();
    }catch(err){error.textContent=err.message;error.classList.remove('d-none');save.disabled=false;}
  });
})();

// List toolbars show search and selection actions; detailed filters stay in drawers.
(() => {
  function simplifyListToolbars() {
    document.querySelectorAll('.cz-toolbar, .cz-panel-head').forEach(toolbar => {
      if (toolbar.closest('.offcanvas, .modal') || !toolbar.querySelector('input[type="search"], .cz-search')) return;
      toolbar.querySelectorAll('select').forEach(select => {
        const filter = Object.entries(select.dataset).find(([key]) => /filter$/i.test(key))?.[1] || '';
        const isStatus = /status|overall_confirmation|^result$/i.test(filter)
          || Array.from(select.options).some(option => /^all statuses$/i.test(option.text.trim()));
        if (!isStatus) return;
        const wrapper = select.closest('.cz-toolbar__select');
        (wrapper || select).classList.add('cz-list-toolbar-removed');
        // Select2 inserts its visual control next to the original select.
        if (!wrapper && select.nextElementSibling?.classList.contains('select2-container')) {
          select.nextElementSibling.classList.add('cz-list-toolbar-removed');
        }
      });
      toolbar.querySelectorAll('button').forEach(button => {
        if (button.textContent.trim().toLowerCase() === 'clear') button.classList.add('cz-list-toolbar-removed');
      });
    });
  }
  function start() {
    simplifyListToolbars();
    // Keep dynamically loaded list pages and enhanced dropdowns consistent.
    let queued = false;
    new MutationObserver(() => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(() => { queued = false; simplifyListToolbars(); });
    }).observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();

// Dyeing Open Jobs: legacy inward/outward/return overview and job-wise billing.
(() => {
 const root=document.querySelector('[data-cz-open-jobs]');if(!root)return;
 const one=s=>root.querySelector(s),all=s=>Array.from(root.querySelectorAll(s));
 const esc=v=>String(v ?? '').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 let page=1,pages=1,rows=[],generation=0;const selected=new Set();
 const error=message=>{one('[data-oj-error]').textContent=message || '';one('[data-oj-error]').classList.toggle('d-none',!message);};
 const run=fn=>(...args)=>Promise.resolve().then(()=>fn(...args)).catch(e=>error(e.message));
 function selection(){one('[data-oj-selected]').textContent=selected.size+' selected';const checks=all('[data-oj-check]:not(:disabled)');one('[data-oj-all]').checked=!!checks.length && selected.size===checks.length;one('[data-oj-all]').indeterminate=selected.size>0 && selected.size<checks.length;}
 async function load(){
  const stamp=++generation,q={action:'list',page};all('[data-oj-filter]').forEach(x=>q[x.dataset.ojFilter]=x.type==='checkbox'?(x.checked?'1':''):x.value);
  const response=await window.czApiFetch(root.dataset.api+'?'+new URLSearchParams(q));const result=await response.json();if(stamp!==generation)return;
  if(!response.ok || !result.success)throw new Error(result.message || 'Unable to load jobs.');
  const data=result.data || result;rows=data.rows;pages=data.pages;selected.clear();error('');
  one('[data-oj-rows]').innerHTML=rows.map(d=>`<tr><td><input class="form-check-input" type="checkbox" data-oj-check="${Number(d.tid)}" ${d.status==='Billed'?'disabled':''} aria-label="Select job ${esc(d.generate_no)}"></td><td>${esc(d.generate_no)}</td><td>${esc(d.reference_number || '—')}</td><td>${esc(d.outward_numbers || '—')}</td><td>${esc(d.order_number || '—')}</td><td>${esc(d.company_name)}</td><td>${esc(d.create_date)}</td>${['inward_qty','out_qty','return_qty','balance'].map(k=>`<td class="text-end">${Number(d[k]).toFixed(3)}</td>`).join('')}<td><span class="cz-badge ${Number(d.balance_percentage)>20?'warning':'success'}">${Number(d.balance_percentage).toFixed(2)}%</span></td><td><span class="cz-badge ${['Completed','Billed'].includes(d.status)?'success':'warning'}">${esc(d.status)}</span></td></tr>`).join('') || '<tr><td colspan="13" class="text-center text-secondary py-4">No open jobs found.</td></tr>';
  all('[data-oj-total]').forEach(x=>x.textContent=Number(data.totals[x.dataset.ojTotal] || 0).toFixed(3));
  one('[data-oj-range]').textContent=`Showing ${data.total?(page-1)*25+1:0} to ${Math.min(page*25,data.total)} of ${data.total} jobs`;
  one('[data-oj-prev]').disabled=page<=1;one('[data-oj-next]').disabled=page>=pages;selection();
 }
 let timer;all('[data-oj-filter]').forEach(x=>x.addEventListener(x.type==='search'?'input':'change',()=>{clearTimeout(timer);timer=setTimeout(run(()=>{page=1;return load();}),x.type==='search'?250:0);}));
 one('[data-oj-clear]')?.addEventListener('click',run(()=>{all('[data-oj-filter]').forEach(x=>{if(x.type==='checkbox')x.checked=false;else x.value='';});page=1;return load();}));
 one('[data-oj-prev]').addEventListener('click',run(()=>{page=Math.max(1,page-1);return load();}));one('[data-oj-next]').addEventListener('click',run(()=>{page=Math.min(pages,page+1);return load();}));
 root.addEventListener('change',e=>{if(e.target.matches('[data-oj-all]'))all('[data-oj-check]:not(:disabled)').forEach(x=>{x.checked=e.target.checked;selected[x.checked?'add':'delete'](Number(x.dataset.ojCheck));});if(e.target.matches('[data-oj-check]'))selected[e.target.checked?'add':'delete'](Number(e.target.dataset.ojCheck));selection();});
 let rateType='pricelist';
 one('[data-oj-convert]')?.addEventListener('click',run(async()=>{
  const docs=rows.filter(d=>selected.has(Number(d.tid)));if(!docs.length)throw new Error('Select at least one job.');
  if(docs.some(d=>d.status==='Billed'))throw new Error('Billed jobs cannot be converted again.');
  if(docs.some(d=>d.company_name!==docs[0].company_name))throw new Error('Select jobs for the same company.');
  if(docs.some(d=>Number(d.out_qty)<=0))throw new Error('Every selected job must have outward quantity.');
  const response=await window.czApiFetch(root.dataset.api+'?'+new URLSearchParams({action:'billing_options',customer:docs[0].company_name}));const result=await response.json();if(!response.ok || !result.success)throw new Error(result.message || 'Unable to load billing options.');
  // as the old Open Jobs: only Bill Against is asked (fixed when the customer already has one); the rate is the price list's when the customer has one, else the quotation's
  one('#ojBasis').value=result.data.basis || 'received';one('#ojBasis').disabled=!!result.data.basis;rateType=result.data.rate_types.includes('pricelist')?'pricelist':'quotation';
  error('');bootstrap.Modal.getOrCreateInstance(one('#ojInvoiceOptions')).show();
 }));
 one('[data-oj-apply]').addEventListener('click',()=>{const q=new URLSearchParams({open_job_ids:[...selected].join(','),billing_basis:one('#ojBasis').value,billing_type:'fabric',rate_type:rateType});location.assign(root.dataset.invoice+'?'+q);});
 run(load)();
})();

// Account documents (Quotation, Proforma Invoice, Invoice): currency, Export To and Convert to Invoice (Modules\Account\Support\
// DocumentLink). Rates and totals are in rupees; a document may name another currency with its rate = rupees for 1 unit, and its
// total in that currency is rupees / rate. Export To / Convert open the destination form filled from the selected documents
// (?from=<kind>&ids=1,2&es=<status>&convert=1): nothing is saved, and the sources change only when that new document is saved.
window.CloudZoo.accountDocs = (() => {
  const BASE = () => window.CZ_BASE_URL || "";
  const esc = (v) => String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[c]));
  const KINDS = {
    quotation: { label: "Quotation", form: "/account/quotation/form" },
    proforma_invoice: { label: "Proforma Invoice", form: "/account/proforma-invoice/form" },
    invoice: { label: "Invoice", form: "/account/invoice/form" },
    dispatch: { label: "Dispatch", form: "/production/dispatch/form" },
    obf: { label: "OBF", form: "/account/obf/form" },
    packing_list: { label: "Packing List", form: "/account/packing-list/form" },
  };
  const SYMBOLS = { INR: "₹", USD: "$", EUR: "€", GBP: "£", AED: "AED " };
  const num = (v) => { const n = parseFloat(String(v ?? "").replace(/,/g, "")); return Number.isFinite(n) ? n : 0; };
  const fmt = (v) => num(v).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const currency = {
    /** the stored rate: above 0, at most 4 decimals ("83.2500"); anything else is 1 */
    rate: (v, decimals = 4) => { const scale = 10 ** decimals; const value = Math.round(num(v) * scale) / scale; return (value > 0 ? value : 1).toFixed(decimals); },
    isForeign: (code) => !!code && String(code).toUpperCase() !== "INR",
    symbol: (code) => SYMBOLS[String(code || "").toUpperCase()] ?? `${code} `,
    /** the rupee total in the document's currency, or null for a rupee document */
    convert(inr, code, rate) { return this.isForeign(code) ? Math.round(num(inr) / num(this.rate(rate)) * 100) / 100 : null; },
    /** "USD 100.00" (escaped HTML) for the list / details, "" for a rupee document */
    text(inr, code, rate) { const c = this.convert(inr, code, rate); return c === null ? "" : `${esc(String(code).toUpperCase())} ${fmt(c)}`; },
    /** the currency section's line under the form: "1 USD = ₹ 83.2500 · Total in USD: $ 100.00" */
    preview(el, inr, code, rate, decimals = 4) {
      if (!el) return;
      if (!this.isForeign(code)) { el.innerHTML = "Base currency: <strong>INR (₹)</strong> · amounts are in rupees"; return; }
      const c = String(code).toUpperCase();
      el.innerHTML = `1 ${esc(c)} = <strong>₹ ${esc(this.rate(rate, decimals))}</strong> · Total in ${esc(c)}: <strong>${esc(this.symbol(c))}${fmt(this.convert(inr, c, this.rate(rate, decimals)))}</strong>`;
    },
  };

  /** the address of a destination form filled from documents of a kind */
  function formUrl(target, from, ids, extra = {}) {
    const q = new URLSearchParams({ from, ids: ids.join(","), ...extra });
    return `${BASE()}${KINDS[target].form}?${q}`;
  }

  /**
   * Wire an Export popup (legacy invoice-my.php "Export"): [data-cz-export-target], [data-cz-export-status],
   * [name=czExportOutput] (single / separate) and [data-cz-export-run]. getIds() gives the ticked documents.
   */
  function bindExport(modalEl, from, getIds, toast) {
    if (!modalEl) return;
    const target = modalEl.querySelector("[data-cz-export-target]");
    const status = modalEl.querySelector("[data-cz-export-status]");
    modalEl.addEventListener("show.bs.modal", () => {
      const n = getIds().length;
      const info = modalEl.querySelector("[data-cz-export-info]");
      if (info) info.innerHTML = n ? `<i class="bi bi-check2-square me-1"></i> ${n} selected for export.` : '<i class="bi bi-info-circle me-1"></i> Tick the documents to export in the list first.';
    });
    modalEl.querySelector("[data-cz-export-run]")?.addEventListener("click", () => {
      const ids = getIds();
      if (!target.value) { toast?.("Choose where to export.", "danger"); return; }
      if (!ids.length) { toast?.("Tick the documents to export in the list.", "danger"); return; }
      const extra = status?.value ? { es: status.value } : {};
      const separate = modalEl.querySelector("[name=czExportOutput]:checked")?.value === "separate";
      // Single file: one new document from every ticked one; Separate files: one new document (tab) each
      (separate ? ids.map((id) => [id]) : [ids]).forEach((group) => window.open(formUrl(target.value, from, group, extra), "_blank", "noopener"));
      window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });
  }

  /**
   * On a NEW destination form: the documents to copy from (?from=&ids=), or null. @return {kind, ids, es, convert, data}|null
   */
  async function loadSource() {
    const q = new URLSearchParams(window.location.search);
    const kind = q.get("from");
    const ids = (q.get("ids") || "").split(",").map((v) => parseInt(v, 10)).filter((n) => n > 0);
    if (!KINDS[kind] || !ids.length || q.get("id")) return null;
    const res = await fetch(`${BASE()}/api/account/document-source?${new URLSearchParams({ kind, ids: ids.join(",") })}`);
    let json;
    try { json = await res.json(); } catch (e) { json = { success: false, message: "Unexpected server response." }; }
    if (!res.ok || !json.success) throw new Error(json.message || "Could not load the documents to copy.");
    return { kind, ids, es: q.get("es") || "", convert: q.get("convert") === "1", data: json.data };
  }

  /** the fields a save sends so the server links / updates the sources (DocumentLink::afterSave) */
  const linkPayload = (src) => (src ? { source_kind: src.kind, source_ids: src.ids, export_status: src.es, convert: src.convert ? 1 : 0 } : {});

  /** the note above a form filled from other documents */
  function sourceNote(el, src, target) {
    if (!el || !src) return;
    const what = `${KINDS[src.kind].label} ${src.data.numbers.join(", ")}`;
    el.innerHTML = src.convert
      ? `<i class="bi bi-arrow-right-circle me-1"></i> Converting ${esc(what)} to ${esc(KINDS[target].label)}. It is marked converted only when you save this ${esc(KINDS[target].label.toLowerCase())}.`
      : `<i class="bi bi-box-arrow-up-right me-1"></i> Copied from ${esc(what)}. Nothing changes until you save.`;
    el.classList.remove("d-none");
  }

  return { KINDS, currency, formUrl, bindExport, loadSource, linkPayload, sourceNote };
})();

/**
 * Multi-select Select2 marked data-cz-backspace-removes (Order No. of the Purchase Bill and the Material Indent, as on the Fabric DC):
 * Backspace in the empty search box removes the last chosen order outright, one order per press, instead of turning it back into
 * text to be erased letter by letter.
 */
(() => {
  window.addEventListener("keydown", (e) => {
    if (e.key !== "Backspace" || !window.jQuery) return;
    const container = e.target.closest && e.target.closest(".select2-container");
    const sel = container && container.previousElementSibling;
    if (!sel || sel.tagName !== "SELECT" || !sel.multiple || !sel.hasAttribute("data-cz-backspace-removes")) return;
    const search = container.querySelector(".select2-search__field");
    if (search && search.value !== "") return;   // text being typed is erased as usual
    e.preventDefault();
    e.stopImmediatePropagation();
    const vals = window.jQuery(sel).val() || [];
    if (!vals.length) return;
    window.jQuery(sel).val(vals.slice(0, -1)).trigger("change");
    if (search) setTimeout(() => search.focus(), 0);
  }, true);
})();

/**
 * Row action menus inside a scrolling table (.table-responsive) are positioned against the screen ("fixed"), so a table with
 * only a row or two no longer clips the menu. Applies to every Bootstrap dropdown in such a table, whichever page builds it.
 */
(() => {
  "use strict";
  document.addEventListener("show.bs.dropdown", (e) => {
    const toggle = e.target;
    if (!toggle.closest?.(".table-responsive") || !window.bootstrap?.Dropdown) return;
    const dropdown = window.bootstrap.Dropdown.getInstance(toggle);
    if (dropdown && dropdown._config && !dropdown._czFixed) {
      dropdown._czFixed = true;
      const base = dropdown._config.popperConfig;
      dropdown._config.popperConfig = (...args) => ({   // Bootstrap merges its own defaults under what this returns
        ...(typeof base === "function" ? base(...args) : (base || {})),
        strategy: "fixed",
      });
    }
  });
})();

/**
 * Number fields inside a container marked data-cz-zero-clear (the Invoice form, the Price List form): a field that only holds a zero
 * (0, 0.00 ...) is emptied when it gets the focus, so the user types the value without first deleting the 0. Left empty, the zero
 * comes back when the field loses the focus, so totals and saving see the same value as before. Read-only and disabled fields stay.
 */
(() => {
  const ZERO = /^-?0*(\.0*)?$/;
  const field = (el) => el instanceof HTMLInputElement && ["text", "number", "tel", ""].includes(el.type) && !el.readOnly && !el.disabled
    && el.closest("[data-cz-zero-clear]");
  document.addEventListener("focusin", (e) => {
    const el = e.target;
    if (!field(el) || el.value === "" || !ZERO.test(el.value.trim()) || !/\d/.test(el.value)) return;
    el.dataset.czZero = el.value;
    el.value = "";
  });
  document.addEventListener("focusout", (e) => {
    const el = e.target;
    if (!(el instanceof HTMLInputElement) || el.dataset.czZero === undefined) return;
    if (el.value.trim() === "") el.value = el.dataset.czZero;
    delete el.dataset.czZero;
  });
})();

/**
 * List pages: clicking a table row opens its page. The page is the row's own edit link (an <a> to ".../form?..." that is not a
 * new-tab link), so every list that offers Edit gets this without its own code. Clicks on buttons, links, fields and menus inside
 * the row keep their own meaning, and rows that already handle their click (data-cz-open) are left alone.
 */
(() => {
  "use strict";
  document.addEventListener("click", (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const row = e.target.closest?.("table tbody tr");
    if (!row || row.hasAttribute("data-cz-open") || !row.closest(".cz-table, .table-responsive")) return;
    if (e.target.closest("a, button, input, select, textarea, label, summary, .dropdown, .dropdown-menu, [data-bs-toggle], [data-cz-action]")) return;
    if (window.getSelection && String(window.getSelection()).length > 0) return;   // text being selected, not a click
    const link = row.querySelector('a[href*="/form?"]:not([target="_blank"])');
    if (link) window.location.href = link.href;
  });
})();
// Sequential invoice-level discounts shared by document forms.
window.CloudZoo = window.CloudZoo || {};
window.CloudZoo.billDiscounts = function (root, onChange) {
  if (!root) return null;
  const body = root.querySelector('[data-cz-discount-rows]');
  const picker = root.querySelector('[data-cz-discount-picker]');
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const round = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
  let options = [];
  function add(d = {}) {
    const row = document.createElement('tr');
    row.innerHTML = `<td><input class="form-control form-control-sm" data-discount-name maxlength="100" required aria-label="Discount name" value="${esc(d.discount_name)}"></td><td><input class="form-control form-control-sm text-end" type="number" min="0" max="100" step="0.01" required data-discount-percent aria-label="Discount percent" value="${esc(d.discount_percent ?? 0)}"></td><td><input class="form-control form-control-sm text-end" data-discount-amount aria-label="Discount value" readonly value="0.00"></td><td><button class="btn btn-outline-danger btn-sm" type="button" data-discount-remove aria-label="Delete discount"><i class="bi bi-trash"></i></button></td>`;
    row.dataset.discountMode = d.calculation_mode || 'percent';
    row.querySelector('[data-discount-amount]').readOnly = false;
    row.querySelector('[data-discount-amount]').type = 'number';
    row.querySelector('[data-discount-amount]').min = '0';
    row.querySelector('[data-discount-amount]').step = '0.01';
    if (row.dataset.discountMode === 'amount') row.querySelector('[data-discount-amount]').value = d.discount_amount || 0;
    body.append(row);
  }
  root.querySelector('[data-cz-discount-add]').addEventListener('click', () => {
    const selected = options.find((d) => d.name === picker.value);
    if (!selected) { picker.focus(); return; }
    add({discount_name: selected.name, discount_percent: selected.percent});
    onChange();
  });
  body.addEventListener('input', (e) => {
    if (e.target.matches('[data-discount-percent]')) e.target.closest('tr').dataset.discountMode = 'percent';
    if (e.target.matches('[data-discount-amount]')) e.target.closest('tr').dataset.discountMode = 'amount';
    onChange();
  });
  body.addEventListener('click', (e) => { const button = e.target.closest('[data-discount-remove]'); if (button) { button.closest('tr').remove(); onChange(); } });
  return {
    options(items) { options = items || []; picker.innerHTML = '<option value="">Select discount</option>' + options.map((d) => `<option value="${esc(d.name)}">${esc(d.name)} (${esc(d.percent)}%)</option>`).join(''); window.CloudZoo.masters?.select2?.(picker); },
    load(items) { body.innerHTML = ''; (items || []).forEach(add); },
    values() { return Array.from(body.rows, (row) => ({ discount_name: row.querySelector('[data-discount-name]').value.trim(), discount_percent: row.querySelector('[data-discount-percent]').value, discount_amount: row.querySelector('[data-discount-amount]').value, calculation_mode: row.dataset.discountMode })); },
    total(base) {
      let total = 0;
      Array.from(body.rows).forEach((row) => {
        const remaining = Math.max(0, round(base - total));
        const percentField = row.querySelector('[data-discount-percent]');
        const amountField = row.querySelector('[data-discount-amount]');
        let percent = Math.min(100, Math.max(0, Number(percentField.value) || 0));
        let amount;
        if (row.dataset.discountMode === 'amount') {
          amount = round(Math.min(remaining, Math.max(0, Number(amountField.value) || 0)));
          percent = remaining > 0 ? round(amount / remaining * 100) : 0;
          percentField.value = percent;
        } else amount = round(remaining * percent / 100);
        if (document.activeElement !== amountField) amountField.value = amount.toFixed(2);
        total += amount;
      });
      return round(total);
    }
  };
};

// Bank account quick-add uses the Accounts service and its normal permission checks.
window.CloudZoo.bindBankMaster = function (select, apiUrl) {
  if (!select) return;
  if (!Array.from(select.options).some((option) => option.value === '__ADD_NEW__')) {
    select.add(new Option('Add New Bank...', '__ADD_NEW__'));
  }
  window.CloudZoo.masters.select2(select, 'Add New Bank');
  select.addEventListener('cz:add-new', () => {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.setAttribute('aria-label', 'Add New Bank');
    const fields = [['account_number', 'Account Number', 95], ['bank_name', 'Bank Name', 191], ['branch_name', 'Branch Name', 95], ['ifc', 'IFSC', 95]];
    modal.innerHTML = `<div class="modal-dialog"><form class="modal-content" novalidate><div class="modal-header"><h2 class="modal-title fs-6">Add New Bank</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="alert alert-danger d-none" role="alert"></div><div class="row g-3">${fields.map(([key, label, length]) => `<div class="col-md-6"><label class="form-label" for="quick_bank_${key}">${label}<span class="cz-required"> *</span></label><input class="form-control" id="quick_bank_${key}" name="${key}" maxlength="${length}" required><div class="invalid-feedback">Enter ${label.toLowerCase()}.</div></div>`).join('')}<div class="col-md-6"><label class="form-label" for="quick_bank_type">Type</label><select class="form-select" id="quick_bank_type" name="type" required><option value="Assets">Assets</option><option value="Liability">Liability</option></select></div></div></div><div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-dark" type="submit">Save Bank</button></div></form></div>`;
    document.body.append(modal);
    const dialog = new bootstrap.Modal(modal);
    modal.addEventListener('hidden.bs.modal', () => { dialog.dispose(); modal.remove(); select.focus(); }, {once: true});
    const form = modal.querySelector('form');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      form.classList.add('was-validated');
      if (!form.checkValidity()) return;
      const button = form.querySelector('[type="submit"]');
      const error = form.querySelector('[role="alert"]');
      button.disabled = true;
      error.classList.add('d-none');
      const values = Object.fromEntries(new FormData(form));
      try {
        const response = await fetch(`${apiUrl}?action=save`, {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(values)});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(Object.values(result.errors || {}).join(' ') || result.message || 'Unable to save bank.');
        if (!Array.from(select.options).some((option) => option.value === values.bank_name)) select.add(new Option(values.bank_name, values.bank_name));
        select.value = values.bank_name;
        window.CloudZoo.masters.refresh(select);
        select.dispatchEvent(new Event('change', {bubbles: true}));
        dialog.hide();
      } catch (err) { error.textContent = err.message; error.classList.remove('d-none'); }
      finally { button.disabled = false; }
    });
    dialog.show();
  });
};

// Auto-close native timepicker popup in Chromium/Chrome once a complete time is selected
(() => {
  let timeCloseTimer = null;
  let timeFieldInitialValue = null;

  document.addEventListener('focusin', (e) => {
    if (e.target && e.target.type === 'time') {
      timeFieldInitialValue = e.target.value;
    }
  });

  document.addEventListener('input', (e) => {
    const el = e.target;
    if (!el || el.type !== 'time') return;

    clearTimeout(timeCloseTimer);

    // Only auto-close if the input has a valid complete time (HH:MM or HH:MM:SS)
    if (!el.value || !/^\d{2}:\d{2}(:\d{2})?$/.test(el.value)) return;

    // If typed with keyboard (insertText / delete), do not disrupt the typing session
    if (e.inputType && (e.inputType.startsWith('insert') || e.inputType.startsWith('delete'))) return;

    // If field was empty before opening, 300ms is ideal as all parts were just completed.
    // If field already had a value and is being modified, provide 450ms debounce so the user can transition between columns.
    const delay = (timeFieldInitialValue === '' || timeFieldInitialValue === null) ? 300 : 450;

    timeCloseTimer = setTimeout(() => {
      if (document.activeElement === el) {
        el.blur();
      }
    }, delay);
  });

  document.addEventListener('keydown', (e) => {
    if (e.target && e.target.type === 'time') {
      clearTimeout(timeCloseTimer);
      if (e.key === 'Enter') {
        e.target.blur?.();
      }
    }
  });

  document.addEventListener('focusout', (e) => {
    if (e.target && e.target.type === 'time') {
      clearTimeout(timeCloseTimer);
      timeFieldInitialValue = null;
    }
  });
})();

