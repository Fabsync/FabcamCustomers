/* Modern / Classic switch + resizable side panel.
   Preferences live in this browser only (localStorage):
     fab-ui   = 'modern' | 'classic'
     fab-sb-w = side panel width in px */
(function () {
  var root   = document.documentElement;
  var MIN_W  = 76;    // icon rail
  var MAX_W  = 320;
  var DEF_W  = 232;
  var SNAP_W = 150;   // narrower than this snaps to the icon rail

  function store(key, val) { try { localStorage.setItem(key, val); } catch (e) {} }
  function read(key)       { try { return localStorage.getItem(key); } catch (e) { return null; } }

  var sidebar = document.getElementById('fabSidebar');
  var handle  = null;

  function clamp(w) { return Math.max(MIN_W, Math.min(MAX_W, Math.round(w))); }
  function currentWidth() {
    return parseInt(getComputedStyle(root).getPropertyValue('--m-sb-w'), 10) || DEF_W;
  }
  function applyWidth(w) {
    w = clamp(w);
    root.style.setProperty('--m-sb-w', w + 'px');
    root.classList.toggle('sb-collapsed', w < SNAP_W);
    if (handle) handle.setAttribute('aria-valuenow', String(w));
    return w;
  }

  /* ---------- Modern / Classic switch ---------- */
  var buttons = document.querySelectorAll('.fab-ui-switch [data-ui]');
  function syncSwitch() {
    var mode = root.classList.contains('ui-modern') ? 'modern' : 'classic';
    buttons.forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.ui === mode)); });
  }
  buttons.forEach(function (b) {
    b.addEventListener('click', function () {
      var modern = b.dataset.ui === 'modern';
      store('fab-ui', modern ? 'modern' : 'classic');
      root.classList.toggle('ui-modern', modern);
      if (modern) {
        applyWidth(parseInt(read('fab-sb-w'), 10) || DEF_W);
      } else {
        root.classList.remove('sb-collapsed');
        root.style.removeProperty('--m-sb-w');
      }
      syncSwitch();
    });
  });
  syncSwitch();

  /* ---------- Resizable side panel ---------- */
  if (!sidebar) return;

  handle = document.createElement('div');
  handle.className = 'fab-sb-resizer';
  handle.setAttribute('role', 'separator');
  handle.setAttribute('aria-orientation', 'vertical');
  handle.setAttribute('aria-label', 'Resize side panel (double-click to collapse or expand)');
  handle.setAttribute('aria-valuemin', String(MIN_W));
  handle.setAttribute('aria-valuemax', String(MAX_W));
  handle.setAttribute('aria-valuenow', String(currentWidth()));
  handle.title = 'Drag to resize · double-click to collapse/expand';
  handle.tabIndex = 0;
  document.body.appendChild(handle);

  var lastOpen = currentWidth() > MIN_W ? currentWidth() : DEF_W;
  var startX = 0, startW = 0, dragging = false;

  function commit(w) {
    w = w < SNAP_W ? MIN_W : w;           // snap to the icon rail
    w = applyWidth(w);
    store('fab-sb-w', String(w));
    if (w > MIN_W) lastOpen = w;
  }

  handle.addEventListener('pointerdown', function (e) {
    if (!root.classList.contains('ui-modern') || e.button !== 0) return;
    dragging = true;
    startX = e.clientX;
    startW = currentWidth();
    root.classList.add('sb-resizing');
    handle.setPointerCapture(e.pointerId);
    e.preventDefault();
  });
  handle.addEventListener('pointermove', function (e) {
    if (dragging) applyWidth(startW + (e.clientX - startX));
  });
  function endDrag() {
    if (!dragging) return;
    dragging = false;
    root.classList.remove('sb-resizing');
    commit(currentWidth());
  }
  handle.addEventListener('pointerup', endDrag);
  handle.addEventListener('pointercancel', endDrag);

  function toggleCollapsed() {
    commit(currentWidth() <= MIN_W ? (lastOpen || DEF_W) : MIN_W);
  }
  handle.addEventListener('dblclick', toggleCollapsed);

  // Keyboard: Left/Right resize (Shift = bigger steps), Enter/Space collapse/expand
  handle.addEventListener('keydown', function (e) {
    var step = e.shiftKey ? 40 : 16;
    if (e.key === 'ArrowLeft')  { commit(currentWidth() - step); e.preventDefault(); }
    if (e.key === 'ArrowRight') { commit(Math.max(SNAP_W, currentWidth() + step)); e.preventDefault(); }
    if (e.key === 'Enter' || e.key === ' ') { toggleCollapsed(); e.preventDefault(); }
  });
})();
