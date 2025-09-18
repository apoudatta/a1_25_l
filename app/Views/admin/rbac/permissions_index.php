<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5>Permissions</h5>
<?= view('partials/flash_message') ?>

<form method="post" action="<?= site_url('admin/permissions') ?>" class="mb-3">
  <?= csrf_field() ?>
  <div class="row g-2">
    <div class="col-md-4"><input name="name" class="form-control" placeholder="e.g. reports.view"></div>
    <div class="col-md-5"><input name="description" class="form-control" placeholder="Description (optional)"></div>
    <div class="col-md-3"><button class="btn btn-primary w-100">Add Permission</button></div>
  </div>
</form>

<table class="table table-sm table-striped">
  <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($permissions as $p): ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><code><?= esc($p['name']) ?></code></td>
      <td><?= esc($p['description'] ?? '') ?></td>
      <td class="text-nowrap">
        <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/permissions/'.$p['id'].'/edit') ?>">Edit</a>
        <form method="post" action="<?= site_url('admin/permissions/'.$p['id'].'/delete') ?>" class="d-inline">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete permission?')">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
