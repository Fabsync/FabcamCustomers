<div class="fab-page-header">
  <h1 class="fab-page-title">Products</h1>
  <?php if ($canManage): ?>
  <a href="<?= BASE_URL ?>/products/add" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
  <?php endif; ?>
</div>

<div class="fab-table-wrap">
  <?php if (empty($products)): ?>
  <div class="px-4 py-4 text-muted-fab text-center">No products found.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="fab-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Product Name</th>
          <th>Module</th>
          <th>Description</th>
          <?php if ($canManage): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td class="text-muted-fab"><?= (int)$p['id'] ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($p['module'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="text-muted-fab"><?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
          <?php if ($canManage): ?>
          <td class="action-links">
            <a href="<?= BASE_URL ?>/products/edit/<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
            <form method="POST" action="<?= BASE_URL ?>/products/delete/<?= (int)$p['id'] ?>" class="d-inline">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                      data-confirm="Delete this product?"><i class="bi bi-trash3"></i></button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
