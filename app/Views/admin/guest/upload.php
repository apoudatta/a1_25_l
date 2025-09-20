<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
  <h2 class="mb-0">Bulk Guest Subscription Upload</h2>

  <a
    href="<?= base_url('templates/guest_bulk_upload.xlsx') ?>"
    class="ms-sm-auto px-3 py-2 rounded-pill bg-primary text-white text-decoration-none"
  >
    Download XLSX Template
  </a>
</div>

<?php $validation = session()->getFlashdata('validation'); ?>

<?php if ($validation): ?>
  <div class="alert alert-danger">
    <?= $validation->listErrors() ?>
  </div>
<?php endif ?>


<form action="<?= site_url('admin/guest-subscriptions/process-upload') ?>"
      method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div class="row mb-3">
    <div class="col-md-3">
      <label for="meal_type_id" class="form-label">Meal Type</label>
      <select id="meal_type_id"
              name="meal_type_id"
              class="form-select <?= isset($validation) && $validation->hasError('meal_type_id') ? 'is-invalid' : '' ?>"
              required>
        <option value="">Select meal type…</option>
        <?php foreach($mealTypes as $mt): ?>
          <option value="<?= $mt['id'] ?>" <?= ($mt['id']==1)? "selected":"" ?>
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

    <div class="col-md-3">
      <label for="guest_type_id" class="form-label">Guest Type</label>
      <select id="guest_type_id"
              name="guest_type_id"
              class="form-select <?= isset($validation) && $validation->hasError('guest_type_id') ? 'is-invalid' : '' ?>"
              required>
        <option value="">Select guest type…</option>
        <?php foreach ($guestTypes as $gt): ?>
          <option value="<?= (int) $gt['id'] ?>"
            <?= set_select('guest_type_id', (string) $gt['id']) ?>>
            <?= esc($gt['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($validation)): ?>
        <div class="invalid-feedback">
          <?= $validation->getError('guest_type_id') ?>
        </div>
      <?php endif ?>
    </div>


    <div class="col-md-6">
      <label for="xlsx_file" class="form-label">XLSX File</label>
      <input type="file" name="xlsx_file" id="xlsx_file" 
        class="form-control" accept=".xlsx,.xls" required>

        <?php if(isset($validation)): ?>
          <div class="invalid-feedback">
            <?= $validation->getError('xlsx_file') ?>
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
        required
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


  
  <button type="submit" class="btn btn-primary">Upload</button>
</form>


<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // initial config from PHP
  const baseCfg = {
    startDate: '<?= date('Y-m-d') ?>',
    cutoffDays: <?= (int)($cutoffDays ?? 0) ?>,
    cutOffTime: '<?= esc($cut_off_time ?? '17:00:00') ?>',
    leadDays:   <?= (int)($lead_days ?? 0) ?>,

    // bulk guest upload has no “already registered” dates
    registeredDates: [],

    publicHolidays: <?= json_encode($publicHolidays ?? [], JSON_HEX_TAG) ?>,
    weeklyHolidays: [5, 6], // Fri, Sat
  };

  // 1) first render
  initMealCalendar(baseCfg);

  // 2) when meal type changes, fetch rules and re-init the calendar
  document.getElementById('meal_type_id').addEventListener('change', async (e) => {
    const id = e.target.value;
    if (!id) return;

    try {
      // use the endpoint you already have that returns {cutoffDays, leadDays, cutOffTime}
      const res = await fetch('<?= site_url('admin/intern-subscriptions/cutoffinfo') ?>/' + id);
      const cfg = await res.json();

      // destroy current flatpickr, then rebuild with new rules
      const fp = document.querySelector('#meal-calendar')._flatpickr;
      if (fp) fp.destroy();

      initMealCalendar({
        startDate: baseCfg.startDate,
        cutoffDays: (cfg.cutoffDays ?? baseCfg.cutoffDays),
        cutOffTime: (cfg.cutOffTime ?? baseCfg.cutOffTime),
        leadDays:   parseInt(cfg.leadDays ?? baseCfg.leadDays, 10) || 0,
        registeredDates: [],
        publicHolidays: baseCfg.publicHolidays,
        weeklyHolidays: baseCfg.weeklyHolidays,
      });
    } catch (err) {
      console.error(err);
      alert('Failed to load meal-type cut-off info.');
    }
  });
});
</script>
<?= $this->endSection() ?>
