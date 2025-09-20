<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Approval Flows</h4>
  <a href="<?= site_url('admin/approval-flows/new') ?>" class="btn btn-primary btn-sm">New</a>
</div>

<form method="get" class="mb-3">
  <div class="input-group">
    <input type="text" name="search" class="form-control" placeholder="Search by meal type, employment type, type…" value="<?= esc($search) ?>">
    <button class="btn btn-outline-secondary" type="submit">Search</button>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th style="width: 70px;">ID</th>
        <th>Meal Type</th>
        <th>User Type</th>
        <th>Flow Type</th>
        <th>Effective Date</th>
        <th>Active?</th>
        <th style="width: 220px;">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" class="text-center">No flows found.</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?= esc($r['id']) ?></td>
        <td><?= esc($r['meal_type_name'] ?? (new \App\Models\MealTypeModel())->find($r['meal_type_id'])['name'] ?? 'Unknown') ?></td>
        <td><?= esc($r['emp_type_name'] ?? 'ALL') ?></td>
        <td><?= esc($r['type']) ?></td>
        <td><?= esc($r['effective_date']) ?></td>
        <td><?= !empty($r['is_active']) ? 'Yes' : 'No' ?></td>
        <td>
          <a href="<?= site_url("admin/approval-flows/{$r['id']}/edit") ?>" class="btn btn-sm btn-secondary">Edit</a>
          <a href="<?= site_url("admin/approval-flows/{$r['id']}/steps") ?>" class="btn btn-sm btn-info">Steps</a>

          <form action="<?= site_url('admin/approval-flows/'.$r['id']) ?>" method="post" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this flow?')">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?= $pager->links('group1', 'bootstrap_pagination') ?>

<?= $this->endSection() ?>
