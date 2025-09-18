<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isNew = empty($flow) ?>

<h2><?= $isNew ? 'New' : 'Edit' ?> Approval Flow</h2>

<form action="<?= site_url($isNew 
        ? 'admin/approval-flows' 
        : 'admin/approval-flows/'.$flow['id']) ?>" 
      method="post" novalidate>
  <?= csrf_field() ?>
  <?php if (! $isNew): ?>
    <input type="hidden" name="_method" value="PUT">
  <?php endif ?>

  <div class="mb-3">
    <label class="form-label">Meal Type</label>
    <select name="meal_type_id" class="form-select" required>
      <option value="">— Select —</option>
      <?php foreach($mealTypes as $m): ?>
        <option value="<?= $m['id'] ?>"
          <?= ! $isNew && $flow['meal_type_id']==$m['id'] ? 'selected':''?>>
          <?= esc($m['name']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">User Type</label>
    <select name="user_type" class="form-select" required>
      <?php foreach($userTypes as $ut): ?>
        <option value="<?= $ut ?>"
          <?= ! $isNew && $flow['user_type']==$ut ? 'selected':''?>>
          <?= esc($ut) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <label class="form-label">Mode</label>
      <select name="type" class="form-select">
        <option value="MANUAL"
          <?= ! $isNew && $flow['type']=='MANUAL' ? 'selected':''?>>
          MANUAL
        </option>
        <option value="AUTO"
          <?= ! $isNew && $flow['type']=='AUTO' ? 'selected':''?>>
          AUTO
        </option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Effective Date</label>
      <input type="date" name="effective_date" class="form-control"
             value="<?= $isNew ? date('Y-m-d') : esc($flow['effective_date']) ?>">
    </div>
    <div class="col-md-4 d-flex align-items-end">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" name="is_active"
               id="is_active" <?= $isNew || $flow['is_active'] ? 'checked':''?>>
        <label for="is_active" class="form-check-label">Active</label>
      </div>
    </div>
  </div>

  <button class="btn btn-primary"><?= $isNew ? 'Create' : 'Update' ?></button>
  <a href="<?= site_url('admin/approval-flows') ?>" class="btn btn-secondary">Cancel</a>
</form>

<?= $this->endSection() ?>