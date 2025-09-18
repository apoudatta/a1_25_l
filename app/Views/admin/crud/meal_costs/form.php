<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($cost); ?>
<h4 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Meal Cost</h4>

<form action="<?= $isEdit
    ? site_url("admin/meal-costs/{$cost['id']}")
    : site_url('admin/meal-costs') ?>"
  method="post" class="row g-3">

  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="col-md-3">
    <label class="form-label">Meal Type</label>
    <select name="meal_type_id" class="form-select" required>
      <?php foreach($mealTypes as $mt): ?>
      <option value="<?= esc($mt['id']) ?>"
        <?= $isEdit && $cost['meal_type_id']==$mt['id'] ? 'selected' : '' ?>>
        <?= esc($mt['name']) ?>
      </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Effective Date</label>
    <?php $today = date('Y-m-d'); ?>
    <input
      name="effective_date"
      type="date"
      class="form-control"
      required
      min="<?= $today ?>"
      value="<?= $isEdit ? esc($cost['effective_date']) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Base Price</label>
    <input name="base_price"
           type="number"
           step="0.01"
           class="form-control"
           required
           id="basePrice"
           value="<?= $isEdit ? esc($cost['base_price']) : '' ?>">
  </div>

  <div class="col-md-3 form-check align-self-end">
    <input type="checkbox"
           name="is_active"
           value="1"
           id="isActive"
           class="form-check-input"
           <?= !$isEdit ? 'checked' : '' ?>
        <?= $isEdit && $cost['is_active'] ? 'checked' : '' ?>>
    <label class="form-check-label" for="isActive">Active</label>
  </div>

  <div class="col-12">
    <button class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('admin/meal-costs')?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<script>
// auto‐calculate final price
document.addEventListener('DOMContentLoaded', function(){
  const base = document.getElementById('basePrice');
  const pct  = document.getElementById('subsidyPct');
  const out  = document.getElementById('finalPrice');

  function calc(){
    const b = parseFloat(base.value) || 0;
    const p = parseFloat(pct.value)  || 0;
    out.value = (b * (1 - p/100)).toFixed(2);
  }

  base.addEventListener('input', calc);
  pct.addEventListener('input', calc);
  calc();
});
</script>

<?= $this->endSection() ?>
