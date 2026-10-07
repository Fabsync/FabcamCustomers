<?php
$isEdit = !empty($lead['id']);
$action = $isEdit ? BASE_URL . '/leads/edit/' . (int)$lead['id'] : BASE_URL . '/leads/add';
$h = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$v = fn(string $k) => $h($lead[$k] ?? '');
$sel = fn(string $k, $val) => (string)($lead[$k] ?? '') === (string)$val ? 'selected' : '';
?>
<div class="fab-page-header">
  <h1 class="fab-page-title"><?= $isEdit ? 'Edit ' . $v('lead_number') : 'New Lead / Enquiry' ?></h1>
  <a href="<?= BASE_URL ?><?= $isEdit ? '/leads/view/' . (int)$lead['id'] : '/leads' ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <?php foreach ($errors as $e): ?><div><?= $h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" id="leadForm">
  <input type="hidden" name="_csrf" value="<?= $h(csrfToken()) ?>">

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="fab-card mb-3">
        <div class="fab-section-label">Account</div>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Existing Customer <span class="text-muted-fab fw-normal">(optional — leave empty for a new prospect)</span></label>
            <select name="customer_id" id="leadCustomer" class="form-select" data-searchable>
              <option value="">— New prospect / not a customer yet —</option>
              <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $sel('customer_id', $c['id']) ?>
                      data-company="<?= $h($c['company_name']) ?>" data-contact="<?= $h($c['contact_person']) ?>"
                      data-mobile="<?= $h($c['mobile']) ?>" data-email="<?= $h($c['email']) ?>">
                <?= $h($c['customer_id'] . ' — ' . $c['company_name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Company Name <span class="text-danger">*</span></label>
            <input type="text" name="company_name" class="form-control" value="<?= $v('company_name') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" class="form-control" value="<?= $v('contact_person') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Mobile</label>
            <input type="text" name="mobile" class="form-control" value="<?= $v('mobile') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= $v('email') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Lead Source</label>
            <select name="source" class="form-select">
              <?php foreach (LeadModel::SOURCES as $k => $l): ?>
              <option value="<?= $k ?>" <?= $sel('source', $k) ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="fab-card mb-3">
        <div class="fab-section-label">Enquiry</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Product of Interest</label>
            <select name="product_id" class="form-select">
              <option value="">— Not specified —</option>
              <?php foreach ($products as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= $sel('product_id', $p['id']) ?>><?= $h($p['product_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Expected Value (&#8377;)</label>
            <input type="number" name="expected_value" class="form-control" min="0" step="0.01" value="<?= $v('expected_value') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Requirement / What they asked for</label>
            <textarea name="requirement" class="form-control" rows="4"><?= $v('requirement') ?></textarea>
          </div>
          <?php if (!$isEdit): ?>
          <div class="col-12">
            <label class="form-label">First Discussion Notes <span class="text-muted-fab fw-normal">(saved to the discussion log)</span></label>
            <textarea name="initial_notes" class="form-control" rows="3"><?= $h($_POST['initial_notes'] ?? '') ?></textarea>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="fab-card mb-3">
        <div class="fab-section-label">Status &amp; Follow-up</div>
        <div class="row g-3">
          <div class="col-md-6 col-lg-12 col-xl-6">
            <label class="form-label">Status</label>
            <select name="status" id="leadStatus" class="form-select">
              <?php foreach (LeadModel::STATUSES as $k => $l): ?>
              <option value="<?= $k ?>" <?= $sel('status', $k) ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6 col-lg-12 col-xl-6">
            <label class="form-label">Interest Level</label>
            <select name="interest_level" class="form-select">
              <?php foreach (LeadModel::INTEREST_LEVELS as $k => $l): ?>
              <option value="<?= $k ?>" <?= $sel('interest_level', $k) ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12" data-open-only>
            <label class="form-label">Next Follow-up Date</label>
            <input type="date" name="next_follow_up" class="form-control" value="<?= $v('next_follow_up') ?>">
          </div>
          <div class="col-12" data-closed-only>
            <label class="form-label">Reason / Outcome Notes</label>
            <textarea name="close_reason" class="form-control" rows="3"
                      placeholder="e.g. PO received / went with competitor / budget not approved"><?= $v('close_reason') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Assigned To</label>
            <select name="assigned_to" class="form-select">
              <option value="">— Unassigned —</option>
              <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= $sel('assigned_to', $u['id']) ?>><?= $h($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Update Lead' : 'Save Lead' ?></button>
        <a href="<?= BASE_URL ?>/leads" class="btn btn-outline-secondary"><i class="bi bi-x me-1"></i>Cancel</a>
      </div>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var form   = document.getElementById('leadForm');
  var status = document.getElementById('leadStatus');
  var closed = <?= json_encode(LeadModel::CLOSED_STATUSES) ?>;

  function syncStatus() {
    var isClosed = closed.indexOf(status.value) !== -1;
    form.querySelectorAll('[data-closed-only]').forEach(function (el) { el.style.display = isClosed ? '' : 'none'; });
    form.querySelectorAll('[data-open-only]').forEach(function (el) { el.style.display = isClosed ? 'none' : ''; });
  }
  status.addEventListener('change', syncStatus);
  syncStatus();

  // Picking an existing customer fills in their contact details
  document.getElementById('leadCustomer').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;
    var map = { company_name: 'company', contact_person: 'contact', mobile: 'mobile', email: 'email' };
    Object.keys(map).forEach(function (name) {
      form.elements[name].value = opt.getAttribute('data-' + map[name]) || '';
    });
    var src = form.elements['source'];
    if (src) src.value = 'existing_customer';
  });
});
</script>
