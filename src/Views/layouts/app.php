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
</body>
</html>
