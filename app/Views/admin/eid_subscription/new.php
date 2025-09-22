<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4>Eid Meal Subscription</h4>
<?php $validation = session()->getFlashdata('validation'); ?>

<?php if ($validation): ?>
  <div class="alert alert-danger">
    <?= $validation->listErrors() ?>
  </div>
<?php endif ?>


<?php if(session()->getFlashdata('error')): ?>
  <div class="alert alert-danger">
    <?= session()->getFlashdata('error') ?>
  </div>
<?php endif ?>

<?= form_open('eid-subscription/store') ?>
  <?= csrf_field() ?>

  <?php if(!has_role('EMPLOYEE')): ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label for="lunch_for" class="form-label">Lunch For</label>
      <select
        id="lunch_for"
        name="lunch_for"
        class="form-select <?= isset($validation) && $validation->hasError('lunch_for') ? 'is-invalid' : '' ?>"
        required>
        <option value="SELF">Self</option>
        <option value="OTHER">Other</option>
      </select>
    </div>

    <div class="col-md-6">
      <label for="employee_id" class="form-label">Employee Name</label>
      <select id="employee_id"
        name="employee_id"
        class="form-select"
        required>
        <option value="">Select employee…</option>
      </select>
      <div id="employee_loader" style="display: none;">
        <span class="spinner-border spinner-border-sm"></span>
        Loading employees...
      </div>
      <?php if(isset($validation)): ?>
        <div class="invalid-feedback">
          <?= $validation->getError('employee_id') ?>
        </div>
      <?php endif ?>
    </div>
  </div>
  
  <?php else: ?>

  <div class="col-md-6">
    <label for="employee_name" class="form-label">Employee Name</label>
    <select 
      name="employee_id" 
      class="form-select"
    >
      <option value="<?= session('user_id') ?>"><?= session('user_name') ?></option>
    </select>
  </div>
  <?php endif ?>

  <div class="row g-3">
    <div class="col-md-6">
      <label for="eid_type" class="form-label">Eid Type</label>
      <select name="eid_type" id="eid_type" class="form-select" required>
        <option value="">Select eid type…</option>
        <option value="eid_al_fitr">Eid al‑Fitr</option>
        <option value="eid_al_adha">Eid al‑Adha</option>
      </select>
      <?php if(isset($validation) && $validation->hasError('eid_type')): ?>
        <div class="invalid-feedback d-block">
          <?= esc($validation->getError('eid_type')) ?>
        </div>
      <?php endif ?>
    </div>

    <div class="col-md-6">
      <label for="eid_day" class="form-label">Eid Day</label>
      <select name="eid_day" id="eid_day" class="form-select" required>
        <option value="">Select day…</option>
        <option value="EID_DAY">Eid Day</option>
        <option value="EID_NEXT_DAY">Eid Next Day</option>
      </select>
      <?php if(isset($validation) && $validation->hasError('eid_day')): ?>
        <div class="invalid-feedback d-block">
          <?= esc($validation->getError('eid_day')) ?>
        </div>
      <?php endif ?>
    </div>
  </div>

  
  <div class="row g-3 mb-3">
    <div class="col-md-12">
      <label for="meal_type_id" class="form-label">Meal Type</label></br>

      <?php if (!empty($mealTypes)): ?>
        <?php foreach ($mealTypes as $type):  
          $id = (int) $type['id'];
        ?>
          <div class="form-check form-check-inline">
            <input class="form-check-input"
                  type="checkbox"
                  id="meal_type_<?= $id ?>"
                  name="meal_type_id[]"
                  value="<?= $id ?>"
                  <?= in_array($id, old('meal_type_id', [])) ? 'checked' : '' ?>>
            <label class="form-check-label" for="meal_type_<?= $id ?>">
              <?= esc(str_replace("Eid ", "", $type['name'])) ?>
            </label>
          </div>
        <?php endforeach ?>
      <?php endif ?>

      <?php if(isset($validation) && $validation->hasError('meal_type_id')): ?>
        <div class="invalid-feedback d-block">
          <?= esc($validation->getError('meal_type_id')) ?>
        </div>
      <?php endif ?>
    </div>

  </div>
  
  <div class="row gx-3 align-items-start"><!-- <-- row here -->
    <div class="col-md-6 mb-3">
      <label for="cafeteria_id" class="form-label">Cafeteria</label>
      <select id="cafeteria_id"
              name="cafeteria_id"
              class="form-select <?= isset($validation) && $validation->hasError('cafeteria_id') ? 'is-invalid' : '' ?>"
              required>
        <option value="">Select cafeteria…</option>
        <?php foreach($cafeterias as $caf): ?>
          <option value="<?= $caf['id'] ?>"
            <?= set_select('cafeteria_id', $caf['id']) ?>>
            <?= esc($caf['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <?php if(isset($validation)): ?>
        <div class="invalid-feedback">
          <?= $validation->getError('cafeteria_id') ?>
        </div>
      <?php endif ?>
    </div>

    
    <div class="col-md-6">
      <label for="meal-calendar" class="form-label">Select meal dates</label>
      <input type="hidden" name="meal_date" value="<?= $occasion_date ?>">
      <input
        id="meal-calendar"
        type="date"
        class="form-control <?= isset($validation) && $validation->hasError('meal_dates') ? 'is-invalid' : '' ?>"
        value="<?= $occasion_date ?>"
        disabled
      />
      <?php if(isset($validation)): ?>
        <div class="invalid-feedback">
          <?= $validation->getError('meal_dates') ?>
        </div>
      <?php endif ?>
    </div>
  </div>

  <div class="mb-3">
    <label class="employee_name">Remarks (Optional)</label>
    <textarea name="remark" class="form-control"></textarea>
  </div>

  


  <button type="submit" class="btn btn-primary">Submit Subscription</button>

<?= form_close() ?>

<?= $this->endSection() ?>



<?= $this->section('scripts') ?>
<script>
  // Meal for ajax
$(document).ready(function () {
  const selfEmployee = <?= json_encode([
    'id'   => session('user_id'),
    'name' => session('user_name')
  ]) ?>;

  const $employee = $('#employee_id');
  const $loader = $('#employee_loader');

  $('#lunch_for').on('change', function () {
    const selected = $(this).val();
    $employee.empty();

    if (selected === 'SELF') {
      // Add user as selected option
      $employee.append(
        $('<option>', {
          value: selfEmployee.id,
          text: selfEmployee.name,
          selected: true
        })
      );
      $loader.hide();
    } else if (selected === 'OTHER') {
      $loader.show();

      $.getJSON("<?= site_url('employees/active-list') ?>", function (res) {
        $employee.append($('<option>', { value: '', text: 'Select employee…' }));
        res.forEach(function (emp) {
          $employee.append(
            $('<option>', {
              value: emp.id,
              text: emp.name
            })
          );
        });
      }).fail(function () {
        alert('Failed to load employee list.');
      }).always(function () {
        $loader.hide();
      });
    }
  });

  // Trigger once on load
  $('#lunch_for').trigger('change');
});


$(document).ready(function () {
  const $mealDate = $('#meal-calendar');
  const $hiddenDate = $('input[name="meal_date"]');
  let baseOccasionDate = null;

  // Fetch date on eid_type change
  $('#eid_type').on('change', function () {
    const type = $(this).val();
    if (!type) return;

    $.getJSON(`<?= site_url('eid-subscription/get-occasion-date/') ?>${type}`, function (res) {
      if (res.success) {
        baseOccasionDate = res.date;
        applyEidDayOffset();
      } else {
        baseOccasionDate = null;
        $mealDate.val('');
        $hiddenDate.val('');
        alert(res.message || 'Eid date not found');
      }
    });
  });

  // Adjust date on eid_day change
  $('#eid_day').on('change', function () {
    applyEidDayOffset();
  });

  function applyEidDayOffset() {
    if (!baseOccasionDate) return;

    let finalDate = new Date(baseOccasionDate);
    const day = $('#eid_day').val();

    if (day === 'EID_NEXT_DAY') {
      finalDate.setDate(finalDate.getDate() + 1);
    }

    const formatted = finalDate.toISOString().split('T')[0]; // YYYY-MM-DD
    $mealDate.val(formatted);
    $hiddenDate.val(formatted);
  }
});
</script>

<?= $this->endSection() ?>



