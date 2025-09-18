<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($row); ?>
<h2 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Occasion</h2>

<form action="<?= $isEdit
    ? site_url("admin/occasions/{$row['id']}")
    : site_url('admin/occasions') ?>"
  method="post" class="row g-3">

  <?= csrf_field() ?>
  <?php if($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="col-md-4">
    <label class="form-label">Name</label>
    <input name="name" type="text"
           class="form-control"
           required
           value="<?= $isEdit ? esc($row['name']) : '' ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">Date</label>
    <input name="occasion_date" type="date"
           class="form-control"
           required
           min="<?= date('Y-m-d') ?>"
           value="<?= $isEdit ? esc($row['occasion_date']) : '' ?>">
  </div>

  <div class="col-12">
    <button class="btn btn-success"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('admin/occasions')?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<?= $this->endSection() ?>