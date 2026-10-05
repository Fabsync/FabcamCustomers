<?php
$sortUrl = function(string $col) use ($sort, $dir, $search): string {
    $newDir = ($col === $sort && $dir === 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query(array_filter(['search' => $search, 'sort' => $col, 'dir' => $newDir]));
};
$sortIcon = function(string $col) use ($sort, $dir): string {
    if ($col !== $sort) return '<i class="bi bi-arrow-down-up" style="font-size:10px;opacity:.25"></i>';
    return $dir === 'asc'
        ? '<i class="bi bi-caret-up-fill" style="font-size:10px;color:var(--accent)"></i>'
        : '<i class="bi bi-caret-down-fill" style="font-size:10px;color:var(--accent)"></i>';
};
?>
<div class="fab-page-header">
  <h1 class="fab-page-title">Customers</h1>
  <a href="<?= BASE_URL ?>/customers/add" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Customer</a>
</div>

<!-- Search bar -->
<form method="GET" action="<?= BASE_URL ?>/customers" class="fab-search-bar mb-3">
  <input type="search" name="search" class="form-control" placeholder="Search by name, ID or contact…"
         autocomplete="off" data-live-filter="customerResults"
         value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="sort" value="<?= htmlspecialchars($sort, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="dir" value="<?= htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') ?>">
  <button type="submit" class="btn btn-outline-secondary">Search</button>
  <?php if ($search): ?>
  <a href="<?= BASE_URL ?>/customers" class="btn btn-outline-secondary">Clear</a>
  <?php endif; ?>
</form>

<?php $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin'; ?>
<?php if ($isAdmin): ?>
<form method="POST" action="<?= BASE_URL ?>/customers/bulk-delete" id="customerBulkForm" class="mb-2"
      data-bulk-form data-bulk-confirm="Delete {n} selected customer(s) and ALL their licenses? This cannot be undone.">
  <button type="submit" class="btn btn-sm btn-outline-danger" data-bulk-submit disabled>
    <i class="bi bi-trash3 me-1"></i>Delete selected (<span data-bulk-count>0</span>)
  </button>
</form>
<?php endif; ?>

<div id="customerResults">
<div class="fab-table-wrap">
  <?php if (empty($customers)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No customers found.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table" id="customerTable">
      <thead>
        <tr>
          <?php if ($isAdmin): ?>
          <th style="width:32px"><input type="checkbox" class="form-check-input" data-bulk-all="customerBulkForm" title="Select all"></th>
          <?php endif; ?>
          <th><a href="<?= $sortUrl('customer_id') ?>" class="fab-sort-th <?= $sort==='customer_id'?'is-sorted':'' ?>">Customer ID <?= $sortIcon('customer_id') ?></a></th>
          <th><a href="<?= $sortUrl('company_name') ?>" class="fab-sort-th <?= $sort==='company_name'?'is-sorted':'' ?>">Company Name <?= $sortIcon('company_name') ?></a></th>
          <th><a href="<?= $sortUrl('contact_person') ?>" class="fab-sort-th <?= $sort==='contact_person'?'is-sorted':'' ?>">Contact <?= $sortIcon('contact_person') ?></a></th>
          <th>Mobile</th>
          <th>Email</th>
          <th>GST Number</th>
          <th><a href="<?= $sortUrl('created_at') ?>" class="fab-sort-th <?= $sort==='created_at'?'is-sorted':'' ?>">Added <?= $sortIcon('created_at') ?></a></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c): ?>
        <tr>
          <?php if ($isAdmin): ?>
          <td><input type="checkbox" class="form-check-input" name="ids[]" value="<?= (int)$c['id'] ?>" form="customerBulkForm" data-bulk-item></td>
          <?php endif; ?>
          <td><span class="badge badge-admin"><?= htmlspecialchars($c['customer_id'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td>
            <a href="<?= BASE_URL ?>/customers/view/<?= (int)$c['id'] ?>" class="fw-semibold">
              <?= htmlspecialchars($c['company_name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
          <td><?= htmlspecialchars($c['contact_person'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($c['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($c['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td><code><?= htmlspecialchars($c['gst_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
          <td class="text-muted-fab" style="font-size:12px"><?= htmlspecialchars(substr($c['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
          <td class="action-links">
            <a href="<?= BASE_URL ?>/customers/view/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
            <a href="<?= BASE_URL ?>/customers/edit/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= BASE_URL ?>/customers/delete/<?= (int)$c['id'] ?>" class="d-inline">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                      data-confirm="Delete &quot;<?= htmlspecialchars($c['company_name'], ENT_QUOTES, 'UTF-8') ?>&quot; and ALL their licenses? This cannot be undone."><i class="bi bi-trash3"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php
$paginationParams = array_filter(['search' => $search, 'sort' => $sort, 'dir' => $dir]);
require __DIR__ . '/../partials/pagination.php';
?>
</div>
