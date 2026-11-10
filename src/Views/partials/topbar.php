<header class="fab-topbar">
  <button class="fab-hamburger" id="sidebarToggle" aria-label="Toggle navigation">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
    </svg>
  </button>
  <a href="<?= BASE_URL ?>/dashboard" class="fab-topbar-brand">
    <span class="fab-logo">
      <span class="fab-logo-glow" aria-hidden="true"></span>
      <img src="<?= BASE_URL ?>/images/fabcam-logo.png" alt="Fabcam Technologies" class="fab-topbar-logo">
      <span class="fab-logo-sheen" aria-hidden="true"></span>
    </span>
  </a>
  <div class="fab-topbar-spacer"></div>
  <div class="fab-ui-switch" role="group" aria-label="Interface style">
    <button type="button" data-ui="modern" aria-pressed="false" title="Modern interface"><i class="bi bi-stars"></i><span>Modern</span></button>
    <button type="button" data-ui="classic" aria-pressed="false" title="Classic interface"><i class="bi bi-window"></i><span>Classic</span></button>
  </div>
  <div class="fab-topbar-user">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
    </svg>
    <span><?= htmlspecialchars($_SESSION['user']['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    <span class="badge badge-<?= $_SESSION['user']['role'] ?? 'sales' ?>">
      <?= htmlspecialchars(ucfirst($_SESSION['user']['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </span>
    <a href="<?= BASE_URL ?>/logout" class="btn btn-sm btn-outline-secondary ms-2">
      <i class="bi bi-box-arrow-right me-1"></i>Logout</a>
  </div>
</header>
