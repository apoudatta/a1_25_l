<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5>User Roles — <?= esc($user['employee_id']) ?> (<?= esc($user['email'] ?? '') ?>)</h5>
<form method="post" action="<?= site_url('users/'.$user['id'].'/roles') ?>">
  <?= csrf_field() ?>

  <?php $selected = $attached[0] ?? null; ?> <!-- current role id, if any -->

  <div class="row row-cols-1 row-cols-md-3 g-2">
    <?php foreach ($roles as $r): ?>
      <div class="col">
        <div class="form-check">
          <input
            class="form-check-input"
            type="radio"                             
            id="role<?= $r['id'] ?>"
            name="role_id"                         
            value="<?= $r['id'] ?>"
            <?= ((int)$selected === (int)$r['id']) ? 'checked' : '' ?>
            required                                 
          >
          <label for="role<?= $r['id'] ?>" class="form-check-label">
            <?= esc($r['name']) ?>
          </label>
        </div>
      </div>
    <?php endforeach ?>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary">Save Changes</button>
    <a class="btn btn-light" href="<?= site_url('users') ?>">Back</a>
  </div>
</form>

<?= $this->endSection() ?>
