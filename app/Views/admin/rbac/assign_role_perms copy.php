<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5>Role Permissions — <?= esc($role['name']) ?></h5>
<form method="post" action="<?= site_url('admin/roles/'.$role['id'].'/permissions') ?>">
  <?= csrf_field() ?>

  <div class="row row-cols-1 row-cols-md-3 g-2">
    <?php foreach ($permissions as $p): ?>
      <div class="col">
        <div class="form-check">
          <input
            class="form-check-input"
            type="checkbox"
            id="perm<?= $p['id'] ?>"
            name="permission_id[]"
            value="<?= $p['id'] ?>"
            <?= in_array($p['id'], $attached, true) ? 'checked' : '' ?>
          >
          <label
            for="perm<?= $p['id'] ?>"
            class="form-check-label"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            data-bs-container="body"
            title="<?= esc($p['name']) ?>"
          >
            <?= esc($p['description'] ?? '') ?>
          </label>

        </div>
      </div>
    <?php endforeach ?>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary">Save Changes</button>
    <a class="btn btn-light" href="<?= site_url('admin/roles') ?>">Back</a>
  </div>
</form>

<?= $this->endSection() ?>
