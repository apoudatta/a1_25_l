<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2>Steps for Flow #<?= esc($flowId) ?></h2>

<p>
  <a href="<?= site_url("approval-flows/$flowId/steps/new") ?>"
     class="btn btn-primary">+ New Step</a>
  <a href="<?= site_url('approval-flows') ?>"
     class="btn btn-secondary">← Back to Flows</a>
</p>

<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>Order</th>
      <th>Type</th>
      <th>By Role/User</th>
      <th>Fallback Role</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if(empty($steps)): ?>
      <tr><td colspan="5" class="text-center">No steps defined.</td></tr>
    <?php else: foreach($steps as $s): ?>
      <tr>
        <td><?= esc($s['step_order']) ?></td>
        <td><?= esc($s['approver_type']) ?></td>
        <td>
          <?php if($s['approver_type']=='ROLE'): ?>
            <?= esc($roles[$s['approver_role']] ?? '—') ?>
          <?php elseif($s['approver_type']=='USER'): ?>
            <?= esc((new \App\Models\UserModel())->find($s['approver_user_id'])['name'] ?? '—') ?>
          <?php else: ?>
            Line Manager
          <?php endif ?>
        </td>
        <td>
          <?= $s['fallback_role']
             ? esc($roles[$s['fallback_role']]) 
             : '—' ?>
        </td>
        <td>
          <a href="<?= site_url("approval-flows/$flowId/steps/{$s['id']}/edit") ?>"
             class="btn btn-sm btn-secondary">Edit</a>
          <form action="<?= site_url("approval-flows/$flowId/steps/{$s['id']}")?>"
                method="post" class="d-inline"
                onsubmit="return confirm('Delete this step?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif ?>
  </tbody>
</table>



<?= $this->endSection() ?>