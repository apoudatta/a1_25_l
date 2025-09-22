<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($cafeteria); ?>
<h4 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Cafeteria</h4>

<form action="<?= $isEdit
    ? site_url("cafeterias/{$cafeteria['id']}")
    : site_url('cafeterias') ?>"
  method="post">

  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name"
           class="form-control"
           required
           value="<?= $isEdit ? esc($cafeteria['name']) : '' ?>">
  </div>

  <div class="mb-3">
    <label class="form-label">Location</label>
    <input name="location"
           class="form-control"
           value="<?= $isEdit ? esc($cafeteria['location']) : '' ?>">
  </div>

  <div class="form-check mb-3">
    <input type="checkbox"
           name="is_active"
           value="1"
           id="isActive"
           class="form-check-input"
      <?= $isEdit && $cafeteria['is_active'] ? 'checked' : '' ?>>
    <label class="form-check-label" for="isActive">Active</label>
  </div>

  <button class="btn btn-primary">
    <?= $isEdit ? 'Update' : 'Create' ?>
  </button>
  <a href="<?= site_url('cafeterias') ?>" class="btn btn-outline-secondary">Cancel</a>
</form>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<?= $this->endSection() ?>