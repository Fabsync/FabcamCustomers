<?php
$h       = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
$closed  = in_array($lead['status'], LeadModel::CLOSED_STATUSES, true);
$today   = date('Y-m-d');
$fu      = $lead['next_follow_up'];
$outcome = [
    'won'            => ['bi-trophy-fill',   'Case Won'],
    'lost'           => ['bi-x-octagon-fill', 'Case Lost'],
    'not_interested' => ['bi-dash-circle-fill', 'Customer Not Interested'],
];
?>
<div class="fab-page-header">
  <div>
    <h1 class="fab-page-title"><?= $h($lead['company_name']) ?></h1>
    <span class="badge badge-admin"><?= $h($lead['lead_number']) ?></span>
    <span class="badge badge-lead-<?= $h($lead['status']) ?>"><?= LeadModel::STATUSES[$lead['status']] ?? '' ?></span>
    <span class="badge badge-interest-<?= $h($lead['interest_level']) ?>"><?= LeadModel::INTEREST_LEVELS[$lead['interest_level']] ?? '' ?> interest</span>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>/leads/edit/<?= (int)$lead['id'] ?>" class="btn btn-accent"><i class="bi bi-pencil me-1"></i>Edit</a>
    <?php if ($isAdmin): ?>
    <form method="POST" action="<?= BASE_URL ?>/leads/delete/<?= (int)$lead['id'] ?>" class="d-inline">
      <button type="submit" class="btn btn-outline-danger"
              data-confirm="Delete <?= $h($lead['lead_number']) ?> and its discussion history? This cannot be undone.">
        <i class="bi bi-trash3 me-1"></i>Delete</button>
    </form>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/leads" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
</div>

<?php if ($closed): [$icon, $title] = $outcome[$lead['status']]; ?>
<div class="lead-outcome lead-outcome-<?= $h($lead['status']) ?>">
  <i class="bi <?= $icon ?>"></i>
  <div>
    <div class="lead-outcome-title"><?= $title ?></div>
    <div style="font-size:13px;opacity:.9">
      Closed on <?= $h(substr($lead['closed_at'] ?? '', 0, 10)) ?>
      <?php if ($lead['status'] === 'won' && $lead['expected_value'] !== null): ?>· Value &#8377; <?= number_format((float)$lead['expected_value'], 2) ?><?php endif; ?>
    </div>
    <?php if ($lead['close_reason']): ?><div class="mt-1"><?= nl2br($h($lead['close_reason'])) ?></div><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="fab-card h-100">
      <div class="fab-section-label">Contact</div>
      <table class="table table-sm table-borderless mb-0">
        <tr><td class="text-muted-fab" width="140">Customer</td><td>
          <?php if ($lead['customer_id']): ?>
            <a href="<?= BASE_URL ?>/customers/view/<?= (int)$lead['customer_id'] ?>"><?= $h($lead['cust_code']) ?></a>
          <?php else: ?><span class="text-muted-fab">New prospect</span><?php endif; ?>
        </td></tr>
        <tr><td class="text-muted-fab">Contact Person</td><td><?= $h($lead['contact_person'] ?: '—') ?></td></tr>
        <tr><td class="text-muted-fab">Mobile</td><td><?= $h($lead['mobile'] ?: '—') ?></td></tr>
        <tr><td class="text-muted-fab">Email</td><td><?= $h($lead['email'] ?: '—') ?></td></tr>
        <tr><td class="text-muted-fab">Source</td><td><?= LeadModel::SOURCES[$lead['source']] ?? '—' ?></td></tr>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="fab-card h-100">
      <div class="fab-section-label">Enquiry</div>
      <table class="table table-sm table-borderless mb-0">
        <tr><td class="text-muted-fab" width="140">Product</td><td><?= $h($lead['product_name'] ?? '—') ?></td></tr>
        <tr><td class="text-muted-fab">Expected Value</td><td><?= $lead['expected_value'] !== null ? '&#8377; ' . number_format((float)$lead['expected_value'], 2) : '—' ?></td></tr>
        <tr><td class="text-muted-fab">Next Follow-up</td><td>
          <?php if ($closed): ?>—
          <?php elseif ($fu): ?>
            <span class="days-badge <?= $fu < $today ? 'days-critical' : ($fu === $today ? 'days-warning' : 'days-ok') ?>">
              <?= $fu < $today ? 'Overdue · ' : ($fu === $today ? 'Today · ' : '') ?><?= $h($fu) ?></span>
          <?php else: ?><span class="text-muted-fab">Not scheduled</span><?php endif; ?>
        </td></tr>
        <tr><td class="text-muted-fab">Assigned To</td><td><?= $h($lead['assigned_to_name'] ?? '—') ?></td></tr>
        <tr><td class="text-muted-fab">Created</td><td><?= $h(substr($lead['created_at'], 0, 10)) ?> by <?= $h($lead['created_by_name'] ?? '—') ?></td></tr>
      </table>
      <?php if ($lead['requirement']): ?>
      <div class="fab-section-label mt-3">Requirement</div>
      <p class="mb-0" style="font-size:14px"><?= nl2br($h($lead['requirement'])) ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Log a discussion -->
  <div class="col-lg-5">
    <div class="fab-card">
      <div class="fab-section-label">Log Discussion &amp; Update Status</div>
      <form method="POST" action="<?= BASE_URL ?>/leads/<?= (int)$lead['id'] ?>/activity" id="activityForm">
        <input type="hidden" name="_csrf" value="<?= $h(csrfToken()) ?>">
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label">Date</label>
            <input type="date" name="activity_date" class="form-control" value="<?= $today ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label">Type</label>
            <select name="activity_type" class="form-select">
              <?php foreach (LeadModel::ACTIVITY_TYPES as $k => $l): ?>
              <option value="<?= $k ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">What was discussed? <span class="text-danger">*</span></label>
            <textarea name="notes" class="form-control" rows="4" required></textarea>
          </div>
          <div class="col-6">
            <label class="form-label">Status</label>
            <select name="status" id="activityStatus" class="form-select">
              <?php foreach (LeadModel::STATUSES as $k => $l): ?>
              <option value="<?= $k ?>" <?= $lead['status'] === $k ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Interest</label>
            <select name="interest_level" class="form-select">
              <?php foreach (LeadModel::INTEREST_LEVELS as $k => $l): ?>
              <option value="<?= $k ?>" <?= $lead['interest_level'] === $k ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12" data-open-only>
            <label class="form-label">Next Follow-up</label>
            <input type="date" name="next_follow_up" class="form-control" value="<?= $h($fu) ?>">
          </div>
          <div class="col-12" data-closed-only>
            <label class="form-label">Reason / Outcome</label>
            <input type="text" name="close_reason" class="form-control" value="<?= $h($lead['close_reason']) ?>"
                   placeholder="e.g. PO received / chose competitor / no budget">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-accent"><i class="bi bi-journal-plus me-1"></i>Save Discussion</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Discussion history -->
  <div class="col-lg-7">
    <div class="fab-card">
      <div class="fab-section-label">Discussion History (<?= count($activities) ?>)</div>
      <?php if (empty($activities)): ?>
      <div class="text-muted-fab text-center py-3">No discussions logged yet.</div>
      <?php else: ?>
      <ul class="lead-timeline">
        <?php foreach ($activities as $a):
          $changed = $a['status_after'] && $a['status_after'] !== $a['status_before'];
        ?>
        <li class="<?= $changed && in_array($a['status_after'], LeadModel::CLOSED_STATUSES, true) ? 'tl-' . $h($a['status_after']) : '' ?>">
          <div class="d-flex justify-content-between gap-2">
            <div class="tl-meta">
              <strong><?= $h($a['activity_date']) ?></strong>
              · <?= LeadModel::ACTIVITY_TYPES[$a['activity_type']] ?? '' ?>
              · <?= $h($a['created_by_name'] ?? '—') ?>
            </div>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= BASE_URL ?>/leads/<?= (int)$lead['id'] ?>/activity/delete/<?= (int)$a['id'] ?>" class="d-inline">
              <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete entry"
                      data-confirm="Delete this discussion entry?"><i class="bi bi-trash3"></i></button>
            </form>
            <?php endif; ?>
          </div>
          <?php if ($changed || $a['interest_level'] || $a['next_follow_up']): ?>
          <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
            <?php if ($changed): ?>
              <?php if ($a['status_before']): ?><span class="badge badge-lead-<?= $h($a['status_before']) ?>"><?= LeadModel::STATUSES[$a['status_before']] ?? '' ?></span> <i class="bi bi-arrow-right text-muted-fab"></i><?php endif; ?>
              <span class="badge badge-lead-<?= $h($a['status_after']) ?>"><?= LeadModel::STATUSES[$a['status_after']] ?? '' ?></span>
            <?php endif; ?>
            <?php if ($a['interest_level']): ?><span class="badge badge-interest-<?= $h($a['interest_level']) ?>"><?= LeadModel::INTEREST_LEVELS[$a['interest_level']] ?? '' ?></span><?php endif; ?>
            <?php if ($a['next_follow_up']): ?><span class="tl-meta ms-1"><i class="bi bi-calendar-event me-1"></i>Follow-up <?= $h($a['next_follow_up']) ?></span><?php endif; ?>
          </div>
          <?php endif; ?>
          <div class="tl-notes"><?= $h($a['notes']) ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var form   = document.getElementById('activityForm');
  var status = document.getElementById('activityStatus');
  var closed = <?= json_encode(LeadModel::CLOSED_STATUSES) ?>;
  function sync() {
    var isClosed = closed.indexOf(status.value) !== -1;
    form.querySelectorAll('[data-closed-only]').forEach(function (el) { el.style.display = isClosed ? '' : 'none'; });
    form.querySelectorAll('[data-open-only]').forEach(function (el) { el.style.display = isClosed ? 'none' : ''; });
  }
  status.addEventListener('change', sync);
  sync();
});
</script>
