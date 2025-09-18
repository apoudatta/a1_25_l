<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4>New Meal Subscription</h4>
<?php $validation = session()->getFlashdata('validation'); ?>

<?php if ($validation): ?>
  <div class="alert alert-danger">
    <?= $validation->listErrors() ?>
  </div>
<?php endif ?>

<?= view('partials/flash_message') ?>

<?= form_open('admin/subscription/store') ?>
  <?= csrf_field() ?>

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

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="meal_type_id" class="form-label">Meal Type</label>
      <select id="meal_type_id"
              name="meal_type_id"
              class="form-select <?= isset($validation) && $validation->hasError('meal_type_id') ? 'is-invalid' : '' ?>"
              required>
        <option value="">Select meal type…</option>
        <?php foreach($mealTypes as $mt): ?>
          <option value="<?= $mt['id'] ?>" <?= ($mt['id']==1)? "selected":"disabled" ?>
            <?= set_select('meal_type_id', $mt['id']) ?>>
            <?= esc($mt['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <?php if(isset($validation)): ?>
        <div class="invalid-feedback">
          <?= $validation->getError('meal_type_id') ?>
        </div>
      <?php endif ?>
    </div>
  

    <div class="col-md-6">
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
  </div>

  <div class="row gx-3 align-items-start"><!-- <-- row here -->
    
    <div class="col-md-6">
      <label for="meal-calendar" class="form-label">Select meal dates</label>
      <input
        id="meal-calendar"
        name="meal_dates"
        type="text"
        class="form-control <?= isset($validation) && $validation->hasError('meal_dates') ? 'is-invalid' : '' ?>"
        placeholder="Pick one or more dates"
        readonly
      />
      <?php if(isset($validation)): ?>
        <div class="invalid-feedback">
          <?= $validation->getError('meal_dates') ?>
        </div>
      <?php endif ?>
    </div>
  
    <div class="col-md-6">
      <div class="pt-4">
        <ul class="legend d-flex flex-column">
          <li class="legend__item">
            <span class="legend__marker legend__marker--available"></span>
            Available Registered
          </li>
          <li class="legend__item">
            <span class="legend__marker legend__marker--registered"></span>
            Already Registered
          </li>
          <li class="legend__item">
            <span class="legend__marker legend__marker--unavailable"></span>
            Holidays Unavailable Registered
          </li>
          <li class="legend__item">
            <span class="legend__marker weekly_holiday--unavailable"></span>
            Weekly Holidays
          </li>
        </ul>
      </div>
      <div class="">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Sl No</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody id="selected-dates-body">
            <tr>
              <td colspan="2">No date selected yet</td>
            </tr>
          </tbody>
        </table>
      </div>
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
  const calendarConfig = <?= json_encode([
    'startDate'       => date('Y-m-d'),
    'cutoffDays'      => $cutoffDays,     // e.g. 30
    'cutOffTime'      => $cutOffTime,
    'leadDays'        => $leadDays,
    'registeredDates' => $registeredDates,
    'publicHolidays'  => $publicHolidays,
    'weeklyHolidays'  => [5,6],
  ], JSON_HEX_TAG) ?>;
  initMealCalendar(calendarConfig);
</script>

<script>
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

      $.getJSON("<?= site_url('admin/employees/active-list') ?>", function (res) {
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



</script>


<?= $this->endSection() ?>

<?= $this->section('styles') ?>

<?= $this->endSection() ?>

