<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$validation = session()->getFlashdata('validation');
if (! $validation) {
    $validation = \Config\Services::validation();
}
?>

<?php $validation1 = session()->getFlashdata('validation'); ?>

<?php if ($validation1): ?>
  <div class="alert alert-danger">
    <?= $validation1->listErrors() ?>
  </div>
<?php endif ?>

<h2><?= $isNew ? 'New' : 'Edit' ?> Cut‐Off Time</h2>


<form action="<?= site_url($isNew 
        ? 'cutoff-times' 
        : "cutoff-times/{$row['id']}") ?>"
      method="post" novalidate
      class="col-md-6">
  <?= csrf_field() ?>
  <?php if (! $isNew): ?>
    <input type="hidden" name="_method" value="PUT">
  <?php endif ?>

  <div class="mb-3">
    <label for="meal_type_id" class="form-label">Meal Type</label>
    <select
      id="meal_type_id"
      name="meal_type_id"
      class="form-select <?= isset($validation) && $validation->hasError('meal_type_id') ? 'is-invalid' : '' ?>"
      required
    >
      <option value="">Select meal type…</option>
      <?php foreach($mealTypes as $mt): ?>
        <option
          value="<?= $mt['id'] ?>"
          <?= set_select(
              'meal_type_id',
              $mt['id'],
              // if no old() value, default to the row’s value when editing:
              (!old('meal_type_id') && ! $isNew && $row['meal_type_id'] == $mt['id'])
                ? true
                : old('meal_type_id') == $mt['id']
            ) ?>
        >
          <?= esc($mt['name']) ?>
        </option>
      <?php endforeach ?>
    </select>
    <?php if (isset($validation) && $validation->hasError('meal_type_id')): ?>
      <div class="invalid-feedback">
        <?= $validation->getError('meal_type_id') ?>
      </div>
    <?php endif ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Cut‐Off Time</label>
    <?php
      $cutVal = old('cut_off_time', $row['cut_off_time'] ?? '17:00');
      if (strlen($cutVal) === 8) { $cutVal = substr($cutVal, 0, 5); } // HH:MM:SS -> HH:MM
    ?>
    <input type="time" name="cut_off_time" required
          class="form-control <?= isset($validation) && $validation->hasError('cut_off_time') ? 'is-invalid' : '' ?>"
          value="<?= esc($cutVal) ?>">
    <?php if (isset($validation) && $validation->hasError('cut_off_time')): ?>
      <div class="invalid-feedback"><?= esc($validation->getError('cut_off_time')) ?></div>
    <?php endif ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Lead Days</label>
    <input type="number" name="lead_days" min="0" required
           class="form-control <?= $validation->hasError('lead_days')?'is-invalid':''?>"
           value="<?= old('lead_days', $row['lead_days'] ?? 1) ?>">
    <div class="invalid-feedback">
      <?= $validation->getError('lead_days') ?>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Max Horizon Days</label>
    <input type="number" name="max_horizon_days" min="1" required
           class="form-control <?= $validation->hasError('max_horizon_days')?'is-invalid':''?>"
           value="<?= old('max_horizon_days', $row['max_horizon_days'] ?? 30) ?>">
    <div class="invalid-feedback">
      <?= $validation->getError('max_horizon_days') ?>
    </div>
  </div>

  <div class="form-check mb-4">
    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
           <?= old('is_active', $row['is_active'] ?? 1) ? 'checked':''?>>
    <label for="is_active" class="form-check-label">Active</label>
  </div>

  <button class="btn btn-primary"><?= $isNew ? 'Create' : 'Update' ?></button>
  <a href="<?= site_url('cutoff-times') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<?= $this->endSection() ?>