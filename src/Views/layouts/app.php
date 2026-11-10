<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Fabcam', ENT_QUOTES, 'UTF-8') ?> — Fabcam Technologies</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/images/fabcam-logo.png">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/bootstrap-icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css?v=<?= (int)@filemtime(__DIR__ . '/../../../public/css/app.css') ?>">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/ui-modern.css?v=<?= (int)@filemtime(__DIR__ . '/../../../public/css/ui-modern.css') ?>">
  <script>
    // Apply the saved interface style before first paint (no flash). Default: modern.
    (function () {
      var d = document.documentElement, ui = 'modern', w = null;
      try { ui = localStorage.getItem('fab-ui') || 'modern'; w = localStorage.getItem('fab-sb-w'); } catch (e) {}
      if (ui !== 'modern') return;
      d.classList.add('ui-modern');
      // Logo intro animation: once per browser session, not on every page
      try { if (!sessionStorage.getItem('fab-logo-intro')) { d.classList.add('logo-intro'); sessionStorage.setItem('fab-logo-intro', '1'); } } catch (e) {}
      if (w && +w >= 72 && +w <= 320) {
        d.style.setProperty('--m-sb-w', w + 'px');
        if (+w < 150) d.classList.add('sb-collapsed');
      }
    })();
  </script>
</head>
<body>

<?php require __DIR__ . '/../partials/topbar.php'; ?>

<div class="fab-body">
  <?php require __DIR__ . '/../partials/sidebar.php'; ?>
  <main class="fab-content">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <?php require $viewPath; ?>
  </main>
</div>

<div class="fab-sidebar-overlay" id="sidebarOverlay"></div>
<script src="<?= BASE_URL ?>/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
<script src="<?= BASE_URL ?>/js/ui-modern.js?v=<?= (int)@filemtime(__DIR__ . '/../../../public/js/ui-modern.js') ?>"></script>
</body>
</html>
