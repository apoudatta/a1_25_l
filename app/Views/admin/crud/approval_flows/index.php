<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2>Approval Flows</h2>

<p>
  <a href="<?= site_url('admin/approval-flows/new') ?>" class="btn btn-primary">
    + New Flow
  </a>
</p>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>Meal Type</th>
      <th>User Type</th>
      <th>Mode</th>
      <th>Effective Date</th>
      <th>Active?</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7" class="text-center">No flows found.</td></tr>
    <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?= esc($r['id']) ?></td>
        <td><?= esc((new \App\Models\MealTypeModel())->find($r['meal_type_id'])['name']) ?></td>
        <td><?= esc($r['user_type']) ?></td>
        <td><?= esc($r['type']) ?></td>
        <td><?= esc($r['effective_date']) ?></td>
        <td><?= $r['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
          <a href="<?= site_url("admin/approval-flows/{$r['id']}/edit") ?>"
             class="btn btn-sm btn-secondary">Edit</a>
          <a href="<?= site_url("admin/approval-flows/{$r['id']}/steps") ?>"
             class="btn btn-sm btn-info">Steps</a>
          <form action="<?= site_url("admin/approval-flows/{$r['id']}") ?>"
                method="post" class="d-inline"
                onsubmit="return confirm('Delete this flow?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif ?>
  </tbody>
</table>

<?= $pager->links('group1','bootstrap_pagination') ?>

<?= $this->endSection() ?>