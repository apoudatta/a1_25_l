<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">Edit Role</h5>

<?= view('partials/flash_message') ?>

<form method="post" action="<?= site_url('roles/update/'.$role['id']) ?>">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label">Role Name</label>
    <input
      type="text"
      name="name"
      class="form-control"
      value="<?= esc($role['name']) ?>"
      required
    >
  </div>

  <div class="mb-3">
    <label class="form-label">Description (optional)</label>
    <input
      type="text"
      name="description"
      class="form-control"
      value="<?= esc($role['description'] ?? '') ?>"
    >
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary">Update</button>
    <a class="btn btn-light" href="<?= site_url('roles') ?>">Back</a>
  </div>
</form>

<?= $this->endSection() ?>
