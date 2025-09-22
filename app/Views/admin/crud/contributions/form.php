<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?= view('partials/flash_message') ?>

<?php $isEdit = isset($contrib); ?>
<h4 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Contribution</h4>

<form action="<?= $isEdit ? site_url('contributions/'.$contrib['id']) : site_url('contributions') ?>"
      method="post" class="row g-3">
  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="PUT">
  <?php endif; ?>

  <select name="meal_type_id" id="mealTypeSelect" class="form-select" required>
    <option value="">Select…</option>
    <?php foreach($mealTypes as $mt): ?>
      <option value="<?= (int) $mt['id'] ?>" <?= $isEdit && (int)$contrib['meal_type_id']===(int)$mt['id'] ? 'selected' : '' ?>>
        <?= esc($mt['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>


  <div class="col-md-3">
    <label class="form-label">User Type</label>
    <select name="emp_type_id" class="form-select">
      <option value="<?= (int) $ALL_VALUE ?>" <?= $isEdit ? ((int)$contrib['emp_type_id']===(int)$ALL_VALUE ? 'selected':'') : 'selected' ?>>ALL</option>
      <?php foreach($employmentTypes as $et): ?>
        <option value="<?= (int) $et['id'] ?>" <?= $isEdit && (int)$contrib['emp_type_id']===(int)$et['id'] ? 'selected' : '' ?>>
          <?= esc($et['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="form-text">Choose a specific type or leave as <strong>ALL</strong>.</div>
  </div>

  <div class="col-md-3">
    <label class="form-label">Base Price <span class="text-danger">*</span></label>
    <input type="number" step="0.01" name="base_price" id="basePrice"
          class="form-control" required
          value="<?= $isEdit ? esc($contrib['base_price']) : '' ?>"
          readonly>
    <div class="form-text">Auto-filled from <strong>Meal Costs</strong> after selecting Meal Type.</div>
  </div>


  <div class="col-md-3">
    <label class="form-label">Company Tk <span class="text-danger">*</span></label>
    <input type="number" step="0.01" name="company_tk" id="companyTk" class="form-control" required
           value="<?= $isEdit ? esc($contrib['company_tk']) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">User Tk <span class="text-danger">*</span></label>
    <input type="number" step="0.01" name="user_tk" id="userTk" class="form-control" required
           value="<?= $isEdit ? esc($contrib['user_tk']) : '' ?>">
  </div>

  <div class="col-md-3 form-check align-self-end">
    <input type="checkbox" name="is_active" id="isActive" value="1" class="form-check-input"
           <?= $isEdit ? ($contrib['is_active'] ? 'checked' : '') : 'checked' ?>>
    <label class="form-check-label" for="isActive">Active</label>
  </div>

  <div class="col-12">
    <button class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('contributions') ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function ($) {
  const ENDPOINT = '<?= site_url('contributions/get-base-price') ?>';
  const $meal    = $('#mealTypeSelect');
  const $base    = $('#basePrice');
  const $company = $('#companyTk');
  const $user    = $('#userTk');

  const to2 = n => (Number(n || 0)).toFixed(2);

  function recalcFromCompany() {
    const base = parseFloat($base.val()) || 0;
    let c = parseFloat($company.val()) || 0;
    if (c < 0) c = 0;
    if (c > base) c = base;
    $company.val(to2(c));
    $user.val(to2(base - c));
  }

  function recalcFromUser() {
    const base = parseFloat($base.val()) || 0;
    let u = parseFloat($user.val()) || 0;
    if (u < 0) u = 0;
    if (u > base) u = base;
    $user.val(to2(u));
    $company.val(to2(base - u));
  }

  function clearMoneyFields() {
    $base.val('');
    $company.val('');
    $user.val('');
  }

  function loadBase(mealTypeId) {
    if (!mealTypeId) { clearMoneyFields(); return; }

    $.getJSON(ENDPOINT + '/' + mealTypeId)
      .done(function (res) {
        console.log(res);
        if (res && res.success && parseFloat(res.base_price) > 0) {
          $base.val(to2(res.base_price));
          recalcFromCompany();
        } else {
          clearMoneyFields();
          alert(res && res.message ? res.message : 'Please insert meal cost first');
        }
      })
      .fail(function () {
        // Even on AJAX failure, show the requested message
        clearMoneyFields();
        alert('Please insert meal cost first');
      });
  }

  $meal.on('change', function () {
    loadBase($(this).val());
  });

  // keep totals in sync if the user edits one side
  $company.on('input', recalcFromCompany);
  $user.on('input', recalcFromUser);

  // initial load for edit or preselected option
  if ($meal.val()) loadBase($meal.val());

})(jQuery);
</script>
<?= $this->endSection() ?>
