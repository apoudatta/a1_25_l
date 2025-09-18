<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($row); ?>
<h4 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Contribution Rule</h4>

<form action="<?= $isEdit
    ? site_url("admin/contributions/{$row['id']}")
    : site_url('admin/contributions') ?>"
  method="post" class="row g-3">

  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="col-md-3">
    <label class="form-label">Meal Type</label>
    <select name="meal_type_id" id="mealTypeSelect" class="form-select" required>
      <option value="" disabled <?= $isEdit?'':'selected' ?>>– select –</option>
      <?php foreach($mealTypes as $mt): ?>
      <option value="<?= esc($mt['id']) ?>"
        <?= $isEdit && $row['meal_type_id']==$mt['id'] ? 'selected':'' ?>>
        <?= esc($mt['name']) ?>
      </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">User Type</label>
    <select name="user_type" class="form-select" required>
      <option value="" disabled <?= $isEdit?'':'selected' ?>>– select –</option>
      <?php foreach($types as $t): ?>
      <option value="<?= $t ?>"
        <?= $isEdit && $row['user_type']===$t ? 'selected':'' ?>>
        <?= esc(ucfirst(strtolower($t))) ?>
      </option>
      <?php endforeach ?>
    </select>
  </div>
  
  <div class="col-md-3">
    <label class="form-label">Company %</label>
    <input id="companyPercent" 
          name="company_contribution" 
          type="number" 
          class="form-control"
          min="0"
          max="100"
          required 
          value="<?= $isEdit ? esc($row['company_contribution']) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Effective Date</label>
    <input name="effective_date"
           type="date"
           class="form-control"
           min="<?= date('Y-m-d') ?>"
           required
           value="<?= $isEdit ? esc($row['effective_date']) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">User %</label>
    <input id="userPercent" 
          name="user_contribution" 
          type="number" 
          class="form-control" 
          readonly 
          value="<?= $isEdit ? esc($row['user_contribution']) : '0' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Base Price</label>
    <input id="basePrice" 
          name="base_price" 
          type="number" 
          class="form-control" 
          readonly 
          value="<?= $isEdit ? esc($row['base_price']) : '0' ?>">
  </div>


  <div class="col-md-3">
    <label class="form-label">Company TK</label>
    <input id="companyTaka"
          name="company_tk" 
          type="number" 
          class="form-control" 
          readonly 
          value="<?= $isEdit ? esc($row['company_tk']) : '0' ?>">
  </div>

  

  <div class="col-md-3">
    <label class="form-label">User TK</label>
    <input 
          id="userTaka" 
          name="user_tk" 
          type="number" 
          class="form-control" 
          readonly 
          value="<?= $isEdit ? esc($row['user_tk']) : '0' ?>">
  </div>

  <!-- <div class="col-md-4">
    <label class="form-label">Cafeteria</label>
    <select name="cafeteria_id" class="form-select">
      <option value=""
        <?= $isEdit && is_null($row['cafeteria_id']) ? 'selected':'' ?>>
        Global
      </option>
      <?php foreach($cafeterias as $caf): ?>
      <option value="<?= esc($caf['id']) ?>"
        <?= $isEdit && $row['cafeteria_id']==$caf['id'] ? 'selected':'' ?>>
        <?= esc($caf['name']) ?>
      </option>
      <?php endforeach ?>
    </select>
  </div> -->

  

  <div class="col-12">
    <button class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('admin/contributions')?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>

<?= view('partials/flash_message') ?>

<script>
function updateContribution() {
  const basePrice = parseFloat($('#basePrice').val()) || 0;
  const companyPercent = parseFloat($('#companyPercent').val()) || 0;
  const userPercent = 100 - companyPercent;

  const companyTaka = (basePrice * companyPercent / 100).toFixed(2);
  const userTaka = (basePrice * userPercent / 100).toFixed(2);

  $('#userPercent').val(userPercent.toFixed(2));
  $('#companyTaka').val(companyTaka);
  $('#userTaka').val(userTaka);
}

$(document).ready(function () {
  $('#mealTypeSelect').on('change', function () {
    const mealTypeId = $(this).val();
    if (!mealTypeId) return;

    $.get(SITE_URL + 'admin/contributions/get-base-price/' + mealTypeId, function (res) {
      if (res.success) {
        $('#basePrice').val(res.base_price);
        updateContribution(); // recalculate on meal type change
      } else {
        $('#basePrice').val(0);
        updateContribution();
      }
    });
  });

  $('#companyPercent').on('input', function () {
    updateContribution(); // recalculate on percent change
  });
});
</script>


<?= $this->endSection() ?>
