<?php
$days = (int)($license['days_left'] ?? 0);
function dBadge(int $d): string {
    if ($d < 0)   return '<span class="days-badge days-expired">'.$d.' days</span>';
    if ($d <= 7)  return '<span class="days-badge days-critical">'.$d.' days</span>';
    if ($d <= 30) return '<span class="days-badge days-warning">'.$d.' days</span>';
    return '<span class="days-badge days-ok">'.$d.' days</span>';
}
function row(string $label, string $value): void {
    echo '<tr><td class="text-muted-fab" style="width:180px">'.$label.'</td><td>'.($value ?: '<span class="text-muted-fab">—</span>').'</td></tr>';
}
function fmtDate(?string $d, string $fmt = 'd/m/Y'): string {
    return $d ? date($fmt, strtotime($d)) : '';
}
?>
<div class="fab-page-header">
  <div>
    <h1 class="fab-page-title">License #<?= (int)$license['id'] ?></h1>
    <span class="badge badge-<?= htmlspecialchars($license['license_status'], ENT_QUOTES, 'UTF-8') ?>">
      <?= ucfirst($license['license_status']) ?>
    </span>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>/licenses/edit/<?= (int)$license['id'] ?>" class="btn btn-accent"><i class="bi bi-pencil me-1"></i>Edit</a>
    <form method="POST" action="<?= BASE_URL ?>/licenses/delete/<?= (int)$license['id'] ?>" class="d-inline">
      <button type="submit" class="btn btn-outline-danger"
              data-confirm="Delete License #<?= (int)$license['id'] ?>? This cannot be undone."><i class="bi bi-trash3 me-1"></i>Delete</button>
    </form>
    <a href="<?= BASE_URL ?>/licenses" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="fab-card">
      <div class="fab-section-label">License Details</div>
      <table class="table table-sm table-borderless mb-0">
        <?php
        row('Customer', '<a href="'.BASE_URL.'/customers/view/'.(int)$license['customer_id'].'">'.htmlspecialchars($license['company_name'],ENT_QUOTES,'UTF-8').'</a> <small class="text-muted-fab">'.htmlspecialchars($license['cust_code'],ENT_QUOTES,'UTF-8').'</small>');
        row('Product',  htmlspecialchars($license['product_name'],ENT_QUOTES,'UTF-8'));
        row('Type',     ucfirst(htmlspecialchars($license['license_type'],ENT_QUOTES,'UTF-8')));
        row('Machine Name', htmlspecialchars($license['machine_name'] ?? '—', ENT_QUOTES, 'UTF-8'));
        row('Server Code', '<code>'.htmlspecialchars($license['server_code']??'',ENT_QUOTES,'UTF-8').'</code>');
        row('Lock Code',   '<code>'.htmlspecialchars($license['lock_code']??'',ENT_QUOTES,'UTF-8').'</code>');
        ?>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="fab-card">
      <div class="fab-section-label">Dates &amp; Pricing</div>
      <table class="table table-sm table-borderless mb-0">
        <?php
        row('Purchase Date',  fmtDate($license['purchase_date'] ?? null));
        row('Expiry Date',    fmtDate($license['expiry_date'] ?? null));
        row('Days Left',      $license['expiry_date'] ? dBadge($days) : '—');
        row('Purchase Price', $license['purchase_price'] ? '₹ '.number_format((float)$license['purchase_price'],2) : '');
        row('AMC Cost',       $license['amc_cost'] ? '₹ '.number_format((float)$license['amc_cost'],2) : '');
        row('Renewal Date',   fmtDate($license['renewal_date'] ?? null));
        $amc = $license['amc_status'];
        $cls = $amc==='active'?'amc-active':($amc==='expired'?'amc-expired':'na');
        row('AMC Status', '<span class="badge badge-'.$cls.'">'.htmlspecialchars(str_replace('_',' ',ucfirst($amc)),ENT_QUOTES,'UTF-8').'</span>');
        ?>
      </table>
    </div>
  </div>
  <?php if (!empty($license['remarks'])): ?>
  <div class="col-12">
    <div class="fab-card">
      <div class="fab-section-label">Remarks</div>
      <p class="mb-0"><?= nl2br(htmlspecialchars($license['remarks'], ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
  </div>
  <?php endif; ?>
  <div class="col-12">
    <div class="fab-card">
      <div class="fab-section-label">Audit</div>
      <table class="table table-sm table-borderless mb-0">
        <?php
        row('Last Updated By', htmlspecialchars($license['updated_by_name']??'—',ENT_QUOTES,'UTF-8'));
        row('Last Updated',    fmtDate($license['last_updated'] ?? null, 'd/m/Y H:i'));
        ?>
      </table>
    </div>
  </div>
</div>
