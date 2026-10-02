<div class="fab-page-header">
  <h1 class="fab-page-title">Estimates</h1>
  <a href="<?= BASE_URL ?>/estimates/add" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i>New Estimate</a>
</div>

<!-- Filters -->
<form method="GET" action="<?= BASE_URL ?>/estimates" class="d-flex gap-2 mb-3 flex-wrap">
  <select name="status" class="form-select" style="width:auto">
    <option value="">All Statuses</option>
    <?php foreach (['draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','cancelled'=>'Cancelled'] as $v=>$l): ?>
    <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <input type="search" name="q" class="form-control" style="width:auto;min-width:220px"
         placeholder="All Customers" autocomplete="off"
         value="<?= htmlspecialchars($filters['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
         data-live-filter="estimateResults">
  <button type="submit" class="btn btn-accent"><i class="bi bi-funnel me-1"></i>Filter</button>
  <a href="<?= BASE_URL ?>/estimates" class="btn btn-filter-clear"><i class="bi bi-x-lg me-1"></i>Clear</a>
</form>

<div id="estimateResults">
<div class="fab-table-wrap">
  <?php if (empty($estimates)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No estimates found.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table">
      <thead>
        <tr>
          <th>Estimate #</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Valid Until</th>
          <th>Grand Total</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $statusClass = [
            'draft'     => 'badge-revoked',
            'sent'      => 'badge-admin',
            'accepted'  => 'badge-active',
            'cancelled' => 'badge-expired',
        ];
        foreach ($estimates as $est):
        $sc = $statusClass[$est['status']] ?? 'badge-revoked';
        ?>
        <tr>
          <td><span class="badge badge-admin"><?= htmlspecialchars($est['estimate_number'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td>
            <a href="<?= BASE_URL ?>/customers/view/<?= (int)$est['customer_id'] ?>" class="fw-semibold">
              <?= htmlspecialchars($est['company_name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <div class="text-muted-fab" style="font-size:12px"><?= htmlspecialchars($est['cust_code'], ENT_QUOTES, 'UTF-8') ?></div>
          </td>
          <td><?= htmlspecialchars($est['estimate_date'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($est['valid_until'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="fw-semibold">&#8377; <?= number_format((float)$est['grand_total'], 2) ?></td>
          <td><span class="badge <?= $sc ?>"><?= ucfirst($est['status']) ?></span></td>
          <td class="action-links">
            <a href="<?= BASE_URL ?>/estimates/view/<?= (int)$est['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
            <a href="<?= BASE_URL ?>/estimates/edit/<?= (int)$est['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
            <a href="<?= BASE_URL ?>/estimates/pdf/<?= (int)$est['id'] ?>" class="btn btn-sm btn-outline-success" title="Download PDF" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <form method="POST" action="<?= BASE_URL ?>/estimates/delete/<?= (int)$est['id'] ?>" class="d-inline">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                      data-confirm="Delete <?= htmlspecialchars($est['estimate_number'], ENT_QUOTES, 'UTF-8') ?>? This cannot be undone."><i class="bi bi-trash3"></i></button>
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
</div>
