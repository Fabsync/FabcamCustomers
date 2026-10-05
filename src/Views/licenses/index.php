<?php
$sortUrl = function(string $col) use ($sort, $dir, $filters): string {
    $newDir = ($col === $sort && $dir === 'asc') ? 'desc' : 'asc';
    $activeFilters = array_filter($filters, fn($v) => $v !== '' && $v !== 0);
    return '?' . http_build_query(array_merge($activeFilters, ['sort' => $col, 'dir' => $newDir]));
};
$sortIcon = function(string $col) use ($sort, $dir): string {
    if ($col !== $sort) return '<i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.25"></i>';
    return $dir === 'asc'
        ? '<i class="bi bi-caret-up-fill" style="font-size:10px;color:var(--accent)"></i>'
        : '<i class="bi bi-caret-down-fill" style="font-size:10px;color:var(--accent)"></i>';
};

function daysClass(int $d): string {
    if ($d < 0)   return 'days-expired';
    if ($d <= 7)  return 'days-critical';
    if ($d <= 30) return 'days-warning';
    return 'days-ok';
}
?>
<div class="fab-page-header">
  <h1 class="fab-page-title">Licenses</h1>
  <a href="<?= BASE_URL ?>/licenses/add" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i>Add License</a>
</div>

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>/licenses" class="d-flex gap-2 mb-3 flex-wrap">
  <select name="status" class="form-select" style="width:auto">
    <option value="">All Statuses</option>
    <?php foreach (['active','expired','grace','revoked'] as $s): ?>
    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="amc_status" class="form-select" style="width:auto">
    <option value="">All AMC Statuses</option>
    <?php foreach (['active' => 'AMC Active', 'expired' => 'AMC Expired', 'not_applicable' => 'Not Applicable'] as $v => $l): ?>
    <option value="<?= $v ?>" <?= ($filters['amc_status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <select name="product_id" class="form-select" style="width:auto">
    <option value="">All Products</option>
    <?php foreach ($products as $p): ?>
    <option value="<?= (int)$p['id'] ?>" <?= (int)($filters['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
      <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
    </option>
    <?php endforeach; ?>
  </select>
  <input type="search" name="q" class="form-control" style="width:auto;min-width:220px"
         placeholder="Customer or machine name" autocomplete="off"
         value="<?= htmlspecialchars($filters['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
         data-live-filter="licenseResults">
  <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="dir" value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">
  <button type="submit" class="btn btn-accent"><i class="bi bi-funnel me-1"></i>Filter</button>
  <a href="<?= BASE_URL ?>/licenses" class="btn btn-filter-clear"><i class="bi bi-x-lg me-1"></i>Clear</a>
</form>

<form method="POST" action="<?= BASE_URL ?>/licenses/bulk-delete" id="licenseBulkForm" class="mb-2"
      data-bulk-form data-bulk-confirm="Delete {n} selected license(s)? This cannot be undone.">
  <button type="submit" class="btn btn-sm btn-outline-danger" data-bulk-submit disabled>
    <i class="bi bi-trash3 me-1"></i>Delete selected (<span data-bulk-count>0</span>)
  </button>
</form>

<div id="licenseResults">
<div class="fab-table-wrap">
  <?php if (empty($licenses)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No licenses found.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table">
      <thead>
        <tr>
          <th style="width:32px"><input type="checkbox" class="form-check-input" data-bulk-all="licenseBulkForm" title="Select all"></th>
          <th><a href="<?= $sortUrl('company_name') ?>" class="fab-sort-th <?= $sort==='company_name'?'is-sorted':'' ?>">Customer <?= $sortIcon('company_name') ?></a></th>
          <th><a href="<?= $sortUrl('product_name') ?>" class="fab-sort-th <?= $sort==='product_name'?'is-sorted':'' ?>">Product <?= $sortIcon('product_name') ?></a></th>
          <th><a href="<?= $sortUrl('license_type') ?>" class="fab-sort-th <?= $sort==='license_type'?'is-sorted':'' ?>">Type <?= $sortIcon('license_type') ?></a></th>
          <th>Server Code</th>
          <th>Machine Name</th>
          <th><a href="<?= $sortUrl('expiry_date') ?>" class="fab-sort-th <?= $sort==='expiry_date'?'is-sorted':'' ?>">Expiry Date <?= $sortIcon('expiry_date') ?></a></th>
          <th><a href="<?= $sortUrl('days_left') ?>" class="fab-sort-th <?= $sort==='days_left'?'is-sorted':'' ?>">Days Left <?= $sortIcon('days_left') ?></a></th>
          <th><a href="<?= $sortUrl('license_status') ?>" class="fab-sort-th <?= $sort==='license_status'?'is-sorted':'' ?>">Status <?= $sortIcon('license_status') ?></a></th>
          <th><a href="<?= $sortUrl('amc_status') ?>" class="fab-sort-th <?= $sort==='amc_status'?'is-sorted':'' ?>">AMC <?= $sortIcon('amc_status') ?></a></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($licenses as $lic): ?>
        <?php $days = (int)$lic['days_left']; ?>
        <tr>
          <td><input type="checkbox" class="form-check-input" name="ids[]" value="<?= (int)$lic['id'] ?>" form="licenseBulkForm" data-bulk-item></td>
          <td>
            <a href="<?= BASE_URL ?>/customers/view/<?= (int)$lic['customer_id'] ?>" class="fw-semibold">
              <?= htmlspecialchars($lic['company_name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <div class="text-muted-fab" style="font-size:12px"><?= htmlspecialchars($lic['cust_code'], ENT_QUOTES, 'UTF-8') ?></div>
          </td>
          <td><?= htmlspecialchars($lic['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-capitalize"><?= htmlspecialchars($lic['license_type'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-nowrap">
            <?php $code = (string)($lic['server_code'] ?? ''); ?>
            <?php if ($code === ''): ?>—<?php else: ?>
            <code title="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(mb_substr($code, 0, 4), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($code) > 4 ? '…' : '' ?></code>
            <?php endif; ?>
          </td>
          <td class="text-nowrap">
            <?php $machine = (string)($lic['machine_name'] ?? ''); ?>
            <?php if ($machine === ''): ?>—<?php else: ?>
            <span title="<?= htmlspecialchars($machine, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(mb_substr($machine, 0, 15), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($machine) > 15 ? '…' : '' ?></span>
            <?php endif; ?>
          </td>
          <td><?= $lic['expiry_date'] ? date('d/m/Y', strtotime($lic['expiry_date'])) : '—' ?></td>
          <td>
            <?php if ($lic['expiry_date']): ?>
            <span class="days-badge <?= daysClass($days) ?>"><?= $days ?> days</span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><span class="badge badge-<?= htmlspecialchars($lic['license_status'], ENT_QUOTES, 'UTF-8') ?>"><?= ucfirst($lic['license_status']) ?></span></td>
          <td>
            <?php
            $amc = $lic['amc_status'];
            $cls = $amc === 'active' ? 'amc-active' : ($amc === 'expired' ? 'amc-expired' : 'na');
            echo '<span class="badge badge-'.$cls.'">'.htmlspecialchars(str_replace('_',' ',ucfirst($amc)), ENT_QUOTES, 'UTF-8').'</span>';
            ?>
          </td>
          <td class="action-links">
            <a href="<?= BASE_URL ?>/licenses/view/<?= (int)$lic['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
            <a href="<?= BASE_URL ?>/licenses/edit/<?= (int)$lic['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
            <form method="POST" action="<?= BASE_URL ?>/licenses/delete/<?= (int)$lic['id'] ?>" class="d-inline">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                      data-confirm="Delete this license? This cannot be undone."><i class="bi bi-trash3"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php
$paginationParams = array_filter(array_merge($filters, ['sort' => $sort, 'dir' => $dir]), fn($v) => $v !== '' && $v !== 0);
require __DIR__ . '/../partials/pagination.php';
?>
</div>
