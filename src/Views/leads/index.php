<?php
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$today = date('Y-m-d');
$cardUrl = fn(string $status) => BASE_URL . '/leads' . ($filters['status'] === $status ? '' : '?status=' . $status);
$cards = [
    ['open',           'Open Leads',      $summary['open_count']           ?? 0, ''],
    ['due',            'Follow-ups Due',  $summary['due_count']            ?? 0, 'stat-expiring'],
    ['won',            'Won',             $summary['won_count']            ?? 0, 'stat-active'],
    ['lost',           'Lost',            $summary['lost_count']           ?? 0, 'stat-lost'],
    ['not_interested', 'Not Interested',  $summary['not_interested_count'] ?? 0, 'stat-ni'],
];
?>
<div class="fab-page-header">
  <h1 class="fab-page-title">Leads &amp; Enquiries</h1>
  <a href="<?= BASE_URL ?>/leads/add" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i>New Lead</a>
</div>

<!-- Summary (click to filter) -->
<div class="row g-3 mb-3">
  <?php foreach ($cards as [$key, $label, $count, $cls]): ?>
  <div class="col-6 col-md">
    <a href="<?= $cardUrl($key) ?>" class="fab-stat-card <?= $cls ?> <?= $filters['status'] === $key ? 'is-selected' : '' ?>">
      <div class="fab-stat-value"><?= (int)$count ?></div>
      <div class="fab-stat-label"><?= $label ?></div>
      <?php if ($key === 'won' && (float)($summary['won_value'] ?? 0) > 0): ?>
      <div class="text-success fw-semibold" style="font-size:12px">&#8377; <?= number_format((float)$summary['won_value'], 0) ?></div>
      <?php endif; ?>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>/leads" class="d-flex gap-2 mb-3 flex-wrap">
  <select name="status" class="form-select" style="width:auto">
    <option value="">All Statuses</option>
    <option value="open" <?= $filters['status'] === 'open' ? 'selected' : '' ?>>All Open</option>
    <option value="due"  <?= $filters['status'] === 'due'  ? 'selected' : '' ?>>Follow-up Due</option>
    <?php foreach (LeadModel::STATUSES as $v => $l): ?>
    <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <select name="interest" class="form-select" style="width:auto">
    <option value="">All Interest</option>
    <?php foreach (LeadModel::INTEREST_LEVELS as $v => $l): ?>
    <option value="<?= $v ?>" <?= $filters['interest'] === $v ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <input type="search" name="q" class="form-control" style="width:auto;min-width:240px"
         placeholder="Search company, contact, lead # or mobile…" autocomplete="off"
         value="<?= $h($filters['q']) ?>" data-live-filter="leadResults">
  <button type="submit" class="btn btn-accent"><i class="bi bi-funnel me-1"></i>Filter</button>
  <a href="<?= BASE_URL ?>/leads" class="btn btn-filter-clear"><i class="bi bi-x-lg me-1"></i>Clear</a>
</form>

<div id="leadResults">
<div class="fab-table-wrap">
  <?php if (empty($leads)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No leads found.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table">
      <thead>
        <tr>
          <th>Lead #</th>
          <th>Company / Contact</th>
          <th>Product</th>
          <th>Interest</th>
          <th>Status</th>
          <th>Next Follow-up</th>
          <th class="text-end">Value</th>
          <th>Assigned</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $l):
          $closed = in_array($l['status'], LeadModel::CLOSED_STATUSES, true);
          $fu     = $l['next_follow_up'];
        ?>
        <tr class="<?= $closed ? 'lead-row-' . $h($l['status']) : '' ?>">
          <td><span class="badge badge-admin"><?= $h($l['lead_number']) ?></span></td>
          <td>
            <a href="<?= BASE_URL ?>/leads/view/<?= (int)$l['id'] ?>" class="fw-semibold"><?= $h($l['company_name']) ?></a>
            <div class="text-muted-fab" style="font-size:12px">
              <?= $h($l['contact_person']) ?><?= $l['contact_person'] && $l['mobile'] ? ' · ' : '' ?><?= $h($l['mobile']) ?>
            </div>
          </td>
          <td><?= $h($l['product_name'] ?? '—') ?></td>
          <td><span class="badge badge-interest-<?= $h($l['interest_level']) ?>"><?= LeadModel::INTEREST_LEVELS[$l['interest_level']] ?? '' ?></span></td>
          <td>
            <span class="badge badge-lead-<?= $h($l['status']) ?>">
              <?php if ($l['status'] === 'won'): ?><i class="bi bi-trophy-fill me-1"></i><?php elseif ($closed): ?><i class="bi bi-x-circle-fill me-1"></i><?php endif; ?>
              <?= LeadModel::STATUSES[$l['status']] ?? $h($l['status']) ?>
            </span>
          </td>
          <td>
            <?php if ($closed): ?>
              <span class="text-muted-fab" style="font-size:12px">Closed <?= $h(substr($l['closed_at'] ?? '', 0, 10)) ?></span>
            <?php elseif ($fu): ?>
              <span class="days-badge <?= $fu < $today ? 'days-critical' : ($fu === $today ? 'days-warning' : 'days-ok') ?>">
                <?= $fu < $today ? 'Overdue · ' : ($fu === $today ? 'Today · ' : '') ?><?= $h($fu) ?>
              </span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-end"><?= $l['expected_value'] !== null ? '&#8377; ' . number_format((float)$l['expected_value'], 0) : '—' ?></td>
          <td style="font-size:13px"><?= $h($l['assigned_to_name'] ?? '—') ?></td>
          <td class="action-links">
            <a href="<?= BASE_URL ?>/leads/view/<?= (int)$l['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View / Log discussion"><i class="bi bi-chat-left-text"></i></a>
            <a href="<?= BASE_URL ?>/leads/edit/<?= (int)$l['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <form method="POST" action="<?= BASE_URL ?>/leads/delete/<?= (int)$l['id'] ?>" class="d-inline">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                      data-confirm="Delete <?= $h($l['lead_number']) ?> and its discussion history? This cannot be undone."><i class="bi bi-trash3"></i></button>
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
$paginationParams = array_filter($filters);
require __DIR__ . '/../partials/pagination.php';
?>
</div>
