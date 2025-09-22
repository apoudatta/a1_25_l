<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-3"><?= $flow ? 'Edit Approval Flow' : 'New Approval Flow' ?></h4>

<?php $isEdit = !empty($flow); ?>
<form method="post" action="<?= $isEdit ? site_url('approval-flows/'.$flow['id']) : site_url('approval-flows') ?>">
  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="PUT">
  <?php endif; ?>

  <div class="mb-3">
    <label class="form-label">Meal Type <span class="text-danger">*</span></label>
    <select name="meal_type_id" class="form-select" required>
      <option value="">Select…</option>
      <?php foreach ($mealTypes as $mt): ?>
        <option value="<?= (int) $mt['id'] ?>" <?= (isset($flow['meal_type_id']) && (int)$flow['meal_type_id']===(int)$mt['id'])?'selected':'' ?>>
          <?= esc($mt['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Employment Type</label>
    <select name="emp_type_id" class="form-select">
      <option value="<?= (int) $ALL_VALUE ?>" <?= (isset($flow['emp_type_id']) ? (int)$flow['emp_type_id']===(int)$ALL_VALUE : true) ? 'selected' : '' ?>>
        ALL
      </option>
      <?php foreach ($employmentTypes as $et): ?>
        <option value="<?= (int) $et['id'] ?>" <?= (isset($flow['emp_type_id']) && (int)$flow['emp_type_id']===(int)$et['id'])?'selected':'' ?>>
          <?= esc($et['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Choose a specific type or leave as <strong>ALL</strong>.</div>
  </div>

  <div class="mb-3">
    <label class="form-label">Flow Type <span class="text-danger">*</span></label>
    <?php $currentType = $flow['type'] ?? 'MANUAL'; ?>
    <select name="type" class="form-select" required>
      <option value="MANUAL" <?= $currentType==='MANUAL'?'selected':'' ?>>MANUAL</option>
      <option value="AUTO"   <?= $currentType==='AUTO'  ?'selected':'' ?>>AUTO</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">Effective Date</label>
    <input type="date" name="effective_date" class="form-control" value="<?= esc($flow['effective_date'] ?? '') ?>">
  </div>

  <div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= !empty($flow['is_active'])?'checked':'' ?>>
    <label class="form-check-label" for="is_active">Active</label>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Save' ?></button>
    <a href="<?= site_url('approval-flows') ?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>
