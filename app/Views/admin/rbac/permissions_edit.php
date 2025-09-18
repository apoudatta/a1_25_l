<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5>Edit Permission</h5>
<form method="post" action="<?= site_url('admin/permissions/'.$perm['id']) ?>">
  <?= csrf_field() ?>
  <div class="mb-2">
    <label class="form-label">Name</label>
    <input name="name" class="form-control" value="<?= esc($perm['name']) ?>">
  </div>
  <div class="mb-2">
    <label class="form-label">Description</label>
    <input name="description" class="form-control" value="<?= esc($perm['description'] ?? '') ?>">
  </div>
  <button class="btn btn-primary">Save</button>
  <a class="btn btn-light" href="<?= site_url('admin/permissions') ?>">Cancel</a>
</form>

<?= $this->endSection() ?>
