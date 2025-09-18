<?= $this->extend('layouts/employee') ?>
<?= $this->section('content') ?>

<h4>New Meal Subscription</h4>
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

<?= form_open('employee/ifter-subscription/store') ?>
  <?= csrf_field() ?>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="employee_name" class="form-label">Employee Name</label>
      <input name="employee_name"
            type="text"
            class="form-control"
            disabled
            value="<?= session('user_name') ?>">
    </div>

    <div class="col-md-6">
      <label for="meal_type_id" class="form-label">Meal Type</label>
      <select id="meal_type_id"
              name="meal_type_id"
              class="form-select <?= isset($validation) && $validation->hasError('meal_type_id') ? 'is-invalid' : '' ?>"
              required>
        <?php foreach($mealTypes as $mt): ?>
          <option value="<?= $mt['id'] ?>" <?= ($mt['id']==2)? "selected":"disabled" ?>
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
  </div>

  <div class="col-md-12 mb-3 mx-2">
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
document.addEventListener('DOMContentLoaded', () => {
  // Ramadan/fixed-window mode → pass both startDate & endDate
  const cfg = {
    startDate: '<?= esc($reg_start_date) ?>',               // e.g. "2025-03-01"
    endDate:   '<?= esc($reg_end_date) ?>',                 // e.g. "2025-03-29"
    cutoffDays: null,                                       // ignored when endDate is set
    leadDays:   <?= (int) $lead_days ?>,
    cutOffTime: '<?= esc($cut_off_time) ?>',                // "HH:MM:SS"
    registeredDates: <?= json_encode($registeredDates, JSON_HEX_TAG) ?>,
    publicHolidays:  <?= json_encode($publicHolidays,  JSON_HEX_TAG) ?>,
    weeklyHolidays: [5, 6],                                 // Fri/Sat
  };

  // Ensure #meal-calendar exists and initMealCalendar is loaded globally
  initMealCalendar(cfg);
});
</script>
<?= $this->endSection() ?>
