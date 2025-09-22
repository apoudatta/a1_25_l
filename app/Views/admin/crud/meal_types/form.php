<!-- app/Views/admin/meal_types/form.php -->
<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2><?= $type ? 'Edit' : 'New' ?> Meal Type</h2>

<form action="<?= esc($action) ?>" method="post" class="mt-3 col-md-6">
  <?= csrf_field() ?>

  <div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text"
           name="name"
           class="form-control"
           value="<?= esc($type['name'] ?? '') ?>"
           required>
  </div>

  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description"
              class="form-control"
              rows="3"><?= esc($type['description'] ?? '') ?></textarea>
  </div>

  <div class="form-check mb-3">
    <input type="checkbox"
           name="is_active"
           id="isActive"
           class="form-check-input"
           <?= isset($type['is_active']) && $type['is_active'] ? 'checked' : '' ?>>
    <label for="isActive" class="form-check-label">Active</label>
  </div>

  <button class="btn btn-success"><?= $button ?></button>
  <a href="<?= site_url('meal-types') ?>"
     class="btn btn-secondary">Cancel</a>
</form>

<?= $this->endSection() ?>
