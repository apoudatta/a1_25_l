<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">Add Role</h5>

<?= view('partials/flash_message') ?>

<form method="post" action="<?= site_url('admin/roles/store') ?>">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label">Role Name</label>
    <input
      type="text"
      name="name"
      class="form-control"
      placeholder="e.g. EMPLOYEE, VENDOR, SUPER ADMIN"
      value="<?= esc(old('name')) ?>"
      required
    >
  </div>

  <div class="mb-3">
    <label class="form-label">Description (optional)</label>
    <input
      type="text"
      name="description"
      class="form-control"
      placeholder="Short note about this role"
      value="<?= esc(old('description')) ?>"
    >
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary">Save</button>
    <a class="btn btn-light" href="<?= site_url('admin/roles') ?>">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>
