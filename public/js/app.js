document.addEventListener('DOMContentLoaded', function () {

  // 1. CSRF auto-injection + 2. Confirm dialogs (re-run on content swapped in later)
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  function enhance(root) {
    if (csrfMeta) {
      const token = csrfMeta.getAttribute('content');
      root.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
        if (!form.querySelector('input[name="_csrf"]')) {
          const input = document.createElement('input');
          input.type  = 'hidden';
          input.name  = '_csrf';
          input.value = token;
          form.appendChild(input);
        }
      });
    }

    root.querySelectorAll('[data-confirm]').forEach(function (el) {
      el.addEventListener('click', function (e) {
        const msg = el.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(msg)) {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    });
  }
  enhance(document);

  // 3. Client-side table search
  const searchInput = document.querySelector('[data-search-table]');
  if (searchInput) {
    const tableId = searchInput.getAttribute('data-search-table');
    const table   = document.getElementById(tableId);
    if (table) {
      searchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        table.querySelectorAll('tbody tr').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
      });
    }
  }

  // 4. Searchable select (data-searchable attribute)
  document.querySelectorAll('select[data-searchable]').forEach(function (sel) {
    var isRequired  = sel.hasAttribute('required');
    var emptyOption = sel.querySelector('option[value=""]');
    var placeholderText = emptyOption ? emptyOption.textContent.trim() : 'Search...';

    // For required selects: skip the empty option in the list.
    // For optional selects (filters): include it so the user can clear the selection.
    var opts = [];
    sel.querySelectorAll('option').forEach(function (o) {
      if (!isRequired || o.value !== '') {
        opts.push({ value: o.value, label: o.textContent.trim() });
      }
    });

    // Wrapper — inherit inline style (e.g. width:auto on filter selects)
    var wrap = document.createElement('div');
    wrap.className = 'fab-ss-wrap';
    var inlineStyle = sel.getAttribute('style');
    if (inlineStyle) wrap.setAttribute('style', inlineStyle);
    sel.parentNode.insertBefore(wrap, sel);
    sel.removeAttribute('style');
    sel.style.display = 'none';
    wrap.appendChild(sel);

    // Visible search input
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control fab-ss-input';
    input.placeholder = placeholderText;
    input.setAttribute('autocomplete', 'off');
    wrap.insertBefore(input, sel);

    // Dropdown panel
    var panel = document.createElement('div');
    panel.className = 'fab-ss-panel';
    wrap.appendChild(panel);

    // Set initial label for edit/filter-active state
    var initial = opts.find(function (o) { return o.value !== '' && o.value == sel.value; });
    if (initial) input.value = initial.label;

    function renderList(q) {
      panel.innerHTML = '';
      var lower = q.toLowerCase();
      var filtered = opts.filter(function (o) {
        return !q || o.label.toLowerCase().includes(lower);
      });
      if (!filtered.length) {
        var noRes = document.createElement('div');
        noRes.className = 'fab-ss-no-result';
        noRes.textContent = 'No results';
        panel.appendChild(noRes);
        return;
      }
      filtered.forEach(function (o) {
        var item = document.createElement('div');
        item.className = 'fab-ss-item' + (o.value == sel.value ? ' is-selected' : '');
        item.textContent = o.label;
        item.addEventListener('mousedown', function (e) {
          e.preventDefault();
          sel.value = o.value;
          // For optional selects the empty option label becomes the placeholder
          input.value = o.value === '' ? '' : o.label;
          input.setCustomValidity('');
          closePanel();
          sel.dispatchEvent(new Event('change', { bubbles: true }));
        });
        panel.appendChild(item);
      });
    }

    function openPanel() {
      renderList('');
      panel.classList.add('open');
      input.select();
    }

    function closePanel() {
      panel.classList.remove('open');
      var cur = opts.find(function (o) { return o.value !== '' && o.value == sel.value; });
      input.value = cur ? cur.label : '';
    }

    input.addEventListener('focus', openPanel);
    input.addEventListener('input', function () { renderList(this.value); panel.classList.add('open'); });
    input.addEventListener('blur', function () { setTimeout(closePanel, 160); });

    // Required-only: validate on submit since the hidden <select> is invisible to the browser
    if (isRequired) {
      var form = sel.closest('form');
      if (form) {
        form.addEventListener('submit', function (e) {
          if (!sel.value) {
            input.setCustomValidity('Please select a customer.');
            input.reportValidity();
            e.preventDefault();
          } else {
            input.setCustomValidity('');
          }
        });
      }
      input.addEventListener('input', function () { input.setCustomValidity(''); });
    }
  });

  // 5. Live filter: re-query the server as the user types and swap in the results
  document.querySelectorAll('input[data-live-filter]').forEach(function (input) {
    var form   = input.closest('form');
    var target = document.getElementById(input.getAttribute('data-live-filter'));
    if (!form || !target) return;

    var timer, controller;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var params = new URLSearchParams();
        new FormData(form).forEach(function (v, k) { if (v !== '') params.append(k, v); });
        var url = form.getAttribute('action') + (params.toString() ? '?' + params : '');

        if (controller) controller.abort();
        controller = new AbortController();
        fetch(url, { signal: controller.signal, credentials: 'same-origin' })
          .then(function (res) { return res.text(); })
          .then(function (html) {
            var doc   = new DOMParser().parseFromString(html, 'text/html');
            var fresh = doc.getElementById(target.id);
            if (!fresh) return;
            target.innerHTML = fresh.innerHTML;
            enhance(target);
            refreshBulkForms();
            history.replaceState(null, '', url);
          })
          .catch(function (err) { if (err.name !== 'AbortError') console.error(err); });
      }, 300);
    });
  });

  // 5b. Bulk select + delete. Row checkboxes live in the table and point at a
  // form outside it via form="…", so they survive live-filter swaps.
  function bulkItems(formId) {
    return document.querySelectorAll('input[data-bulk-item][form="' + formId + '"]');
  }
  function refreshBulkForms() {
    document.querySelectorAll('form[data-bulk-form]').forEach(function (form) {
      var items   = bulkItems(form.id);
      var checked = Array.prototype.filter.call(items, function (cb) { return cb.checked; }).length;
      var count   = form.querySelector('[data-bulk-count]');
      var submit  = form.querySelector('[data-bulk-submit]');
      if (count)  count.textContent = checked;
      if (submit) submit.disabled = checked === 0;
      var all = document.querySelector('input[data-bulk-all="' + form.id + '"]');
      if (all) {
        all.checked       = items.length > 0 && checked === items.length;
        all.indeterminate = checked > 0 && checked < items.length;
      }
    });
  }
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches('input[data-bulk-all]')) {
      bulkItems(t.getAttribute('data-bulk-all')).forEach(function (cb) { cb.checked = t.checked; });
      refreshBulkForms();
    } else if (t.matches('input[data-bulk-item]')) {
      refreshBulkForms();
    }
  });
  document.querySelectorAll('form[data-bulk-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var n = Array.prototype.filter.call(bulkItems(form.id), function (cb) { return cb.checked; }).length;
      var msg = (form.getAttribute('data-bulk-confirm') || 'Delete {n} selected item(s)?').replace('{n}', n);
      if (n === 0 || !window.confirm(msg)) e.preventDefault();
    });
  });
  refreshBulkForms();

  // 6. Mobile sidebar toggle
  const sidebarToggle  = document.getElementById('sidebarToggle');
  const fabSidebar     = document.getElementById('fabSidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (sidebarToggle && fabSidebar) {
    function openSidebar() {
      fabSidebar.classList.add('open');
      if (sidebarOverlay) sidebarOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
      fabSidebar.classList.remove('open');
      if (sidebarOverlay) sidebarOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    sidebarToggle.addEventListener('click', function () {
      fabSidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', closeSidebar);
    }

    fabSidebar.querySelectorAll('.fab-nav-link').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 768) closeSidebar();
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768) closeSidebar();
    });
  }

});
