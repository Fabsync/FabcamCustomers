<?php
function daysClass(int $d): string {
    if ($d < 0)   return 'days-expired';
    if ($d <= 7)  return 'days-critical';
    if ($d <= 30) return 'days-warning';
    return 'days-ok';
}
?>
<div class="fab-page-header">
  <div>
    <h1 class="fab-page-title"><?= htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8') ?></h1>
    <span class="badge badge-admin"><?= htmlspecialchars($customer['customer_id'], ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>/customers/edit/<?= (int)$customer['id'] ?>" class="btn btn-accent"><i class="bi bi-pencil me-1"></i>Edit</a>
    <a href="<?= BASE_URL ?>/licenses/add?customer_id=<?= (int)$customer['id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-plus me-1"></i>Add License</a>
    <a href="<?= BASE_URL ?>/leads/add?customer_id=<?= (int)$customer['id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-chat-left-text me-1"></i>New Lead</a>
    <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
    <form method="POST" action="<?= BASE_URL ?>/customers/delete/<?= (int)$customer['id'] ?>" class="d-inline">
      <button type="submit" class="btn btn-outline-danger"
              data-confirm="Delete &quot;<?= htmlspecialchars($customer['company_name'], ENT_QUOTES, 'UTF-8') ?>&quot; and ALL their licenses? This cannot be undone."><i class="bi bi-trash3 me-1"></i>Delete Customer</button>
    </form>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/customers" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
</div>

<!-- Details card -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="fab-card h-100">
      <div class="fab-section-label">Contact Information</div>
      <table class="table table-sm table-borderless mb-0">
        <tr><td class="text-muted-fab" width="140">Contact Person</td><td><?= htmlspecialchars($customer['contact_person'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted-fab">Mobile</td><td><?= htmlspecialchars($customer['mobile'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><td class="text-muted-fab">Email</td><td><?= htmlspecialchars($customer['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="fab-card h-100">
      <div class="fab-section-label">Business Details</div>
      <table class="table table-sm table-borderless mb-0">
        <tr><td class="text-muted-fab" width="140">GST Number</td><td><code><?= htmlspecialchars($customer['gst_number'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code></td></tr>
        <tr><td class="text-muted-fab">Address</td><td><?= nl2br(htmlspecialchars($customer['address'] ?? '—', ENT_QUOTES, 'UTF-8')) ?></td></tr>
        <tr><td class="text-muted-fab">Added</td><td><?= htmlspecialchars(substr($customer['created_at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></td></tr>
      </table>
    </div>
  </div>
</div>

<!-- Licenses sub-table -->
<div class="fab-card p-0">
  <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
    <h6 class="mb-0 fw-semibold">Licenses (<?= count($licenses) ?>)</h6>
    <a href="<?= BASE_URL ?>/licenses/add?customer_id=<?= (int)$customer['id'] ?>" class="btn btn-sm btn-accent">+ Add License</a>
  </div>
  <?php if (empty($licenses)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No licenses yet.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Type</th>
          <th>Machine Name</th>
          <th>Purchase Date</th>
          <th>Expiry Date</th>
          <th>Days Left</th>
          <th>Status</th>
          <th>AMC</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($licenses as $lic): ?>
        <?php $days = (int)$lic['days_left']; ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($lic['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-capitalize"><?= htmlspecialchars($lic['license_type'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($lic['machine_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($lic['purchase_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($lic['expiry_date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
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
              <input type="hidden" name="_back" value="/customers/view/<?= (int)$customer['id'] ?>">
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

<!-- Leads / enquiries sub-table -->
<div class="fab-card p-0 mt-4">
  <div class="d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
    <h6 class="mb-0 fw-semibold">Leads &amp; Enquiries (<?= count($leads) ?>)</h6>
    <a href="<?= BASE_URL ?>/leads/add?customer_id=<?= (int)$customer['id'] ?>" class="btn btn-sm btn-accent">+ New Lead</a>
  </div>
  <?php if (empty($leads)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No leads yet.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table">
      <thead>
        <tr><th>Lead #</th><th>Product</th><th>Interest</th><th>Status</th><th>Next Follow-up</th><th>Created</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $ld): $ldClosed = in_array($ld['status'], LeadModel::CLOSED_STATUSES, true); ?>
        <tr class="<?= $ldClosed ? 'lead-row-' . htmlspecialchars($ld['status'], ENT_QUOTES, 'UTF-8') : '' ?>">
          <td><span class="badge badge-admin"><?= htmlspecialchars($ld['lead_number'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars($ld['product_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="badge badge-interest-<?= htmlspecialchars($ld['interest_level'], ENT_QUOTES, 'UTF-8') ?>"><?= LeadModel::INTEREST_LEVELS[$ld['interest_level']] ?? '' ?></span></td>
          <td><span class="badge badge-lead-<?= htmlspecialchars($ld['status'], ENT_QUOTES, 'UTF-8') ?>"><?= LeadModel::STATUSES[$ld['status']] ?? '' ?></span></td>
          <td><?= $ldClosed ? '—' : htmlspecialchars($ld['next_follow_up'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted-fab" style="font-size:12px"><?= htmlspecialchars(substr($ld['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></td>
          <td class="action-links">
            <a href="<?= BASE_URL ?>/leads/view/<?= (int)$ld['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
