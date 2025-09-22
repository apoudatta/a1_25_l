<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4>Personal Guest Subscription</h4>
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

<?= form_open('guest-subscriptions/store') ?>
  <?= csrf_field() ?>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label for="employee_name" class="form-label">
        Guest Name
        <span class="text-danger">*</span>
      </label>
      <input name="guest_name"
            type="text"
            class="form-control"
            value="">
    </div>

    <div class="col-md-6">
      <label for="meal_type_id" class="form-label">Meal Type</label>
      <select id="meal_type_id"
              name="meal_type_id"
              class="form-select <?= isset($validation) && $validation->hasError('meal_type_id') ? 'is-invalid' : '' ?>"
              required>
        <option value="">Select meal type…</option>
        <?php foreach($mealTypes as $mt): ?>
          <option value="<?= $mt['id'] ?>" <?= ($mt['id']==1)? "selected":"hidden" ?>
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

  <div class="row mb-3">
    <div class="col-md-6">
      <label for="cafeteria_id" class="form-label">Cafeteria
        <span class="text-danger">*</span>
      </label>
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
      <label
        for="phone"
        class="form-label"
      >
        Phone <span class="text-danger">*</span>
      </label>

      <div class="input-group mb-3">
        <span
          class="input-group-text"
          id="basic-addon1"
        >+88</span>

        <input
          type="tel"
          id="phone"
          name="phone"
          class="form-control"
          placeholder="01XXXXXXXXX"
          aria-describedby="basic-addon1"
          inputmode="numeric"
          pattern="^[0-9]{11}$"
          minlength="11"
          maxlength="11"
          required
          value="<?= esc(set_value('phone'), 'attr') ?>"
        >

        <div class="invalid-feedback">
          Enter exactly 11 digits (e.g., 01XXXXXXXXX).
        </div>
      </div>

      <?php if (isset($validation) && $validation->hasError('phone')): ?>
        <div class="text-danger small mt-1">
          <?= esc($validation->getError('phone')) ?>
        </div>
      <?php endif; ?>
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
  initMealCalendar({
    // start from today
    startDate: '<?= date('Y-m-d') ?>',

    // standard (non-Ramadan) window
    cutoffDays: <?= (int) $cutoffDays ?>,

    // per-meal cut-off logic
    cutOffTime: '<?= esc($cut_off_time) ?>',    // e.g. "17:00:00"
    leadDays:   <?= (int) $lead_days ?>,        // e.g. 1

    // guests usually don’t have “already registered” dates;
    // if you do, pass them from the controller instead of []
    registeredDates: [],

    // blackout days
    publicHolidays: <?= json_encode($publicHolidays, JSON_HEX_TAG) ?>,
    weeklyHolidays: [5, 6], // Fri=5, Sat=6
  });
});
</script>

<script>
  document.getElementById('phone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
  });
</script>

<?= $this->endSection() ?>


