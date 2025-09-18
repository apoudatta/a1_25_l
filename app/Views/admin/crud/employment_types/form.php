<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-3">
  <?= isset($row['id']) ? 'Edit' : 'Create' ?> Employment Type
</h2>

<?php if ($errors = session('errors')): ?>
  <div class="alert alert-danger small">
    <?php foreach ($errors as $e): ?>
      <div>- <?= esc($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form
  method="post"
  action="<?= site_url('admin/employment-types/store') ?>"
  class="row g-3"
>
  <?= csrf_field() ?>
  <input
    type="hidden"
    name="id"
    value="<?= esc($row['id'] ?? '', 'attr') ?>"
  >

  <div class="col-md-6">
    <label class="form-label small">Name</label>
    <input
      type="text"
      name="name"
      class="form-control form-control-sm"
      value="<?= esc(set_value('name', $row['name'] ?? ''), 'attr') ?>"
      placeholder="e.g., Intern, Security Guard"
      required
    >
  </div>

  <div class="col-md-3">
    <label class="form-label small">Active?</label>
    <select
      name="is_active"
      class="form-select form-select-sm"
    >
      <?php $active = (int) (set_value('is_active', $row['is_active'] ?? 1)); ?>
      <option
        value="1"
        <?= $active === 1 ? 'selected' : '' ?>
      >Yes</option>
      <option
        value="0"
        <?= $active === 0 ? 'selected' : '' ?>
      >No</option>
    </select>
  </div>

  <div class="col-12">
    <label class="form-label small">Description</label>
    <textarea
      name="description"
      rows="3"
      class="form-control form-control-sm"
      placeholder="Optional short note"
    ><?= esc(set_value('description', $row['description'] ?? ''), 'attr') ?></textarea>
  </div>

  <div class="col-12">
    <button
      type="submit"
      class="btn btn-sm btn-primary"
    >Save</button>

    <a
      href="<?= site_url('admin/employment-types') ?>"
      class="btn btn-sm btn-secondary"
    >Back</a>
  </div>
</form>

<?= $this->endSection() ?>
