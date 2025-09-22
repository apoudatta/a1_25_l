<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($holiday); ?>
<h4 class="mb-4"><?= $isEdit ? 'Edit' : 'Add' ?> Holiday</h4>

<form action="<?= $isEdit
    ? site_url("public-holidays/{$holiday['id']}")
    : site_url('public-holidays') ?>"
  method="post" class="row g-3">
  <?= csrf_field() ?>
  <?php if($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="col-md-4">
    <label class="form-label">Date</label>
    <input name="holiday_date"
           type="date"
           class="form-control"
           required
           min="<?= date('Y-m-d') ?>"
           value="<?= $isEdit ? esc($holiday['holiday_date']) : '' ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">Description</label>
    <input name="description"
           type="text"
           class="form-control"
           value="<?= $isEdit ? esc($holiday['description']) : '' ?>">
  </div>

  <?php
    // default: checked (1) on create; on edit use the saved value
    $defaultActive = $isEdit ? (int)($holiday['is_active'] ?? 0) : 1;
    $checked = old('is_active', $defaultActive) ? 'checked' : '';
  ?>
  <div class="col-md-2 form-check align-self-end">
    <input type="checkbox"
          name="is_active"
          value="1"
          id="isActive"
          class="form-check-input"
          <?= $checked ?>>
    <label for="isActive" class="form-check-label">Active</label>
  </div>


  <div class="col-12">
    <button class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('public-holidays')?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<?= $this->endSection() ?>
