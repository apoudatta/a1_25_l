<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
  <h2 class="mb-0">Bulk Other User Subscription</h2>

  <a
    href="<?= base_url('templates/intern_and_other_bulk_upload.xlsx') ?>"
    class="ms-sm-auto px-3 py-2 rounded-pill bg-primary text-white text-decoration-none"
  >
    Download XLSX Template
  </a>
</div>



<?php $validation = session()->getFlashdata('validation');
  if ($validation): ?>
  <div class="alert alert-danger">
    <?= $validation->listErrors() ?>
  </div>
<?php endif ?>




<form id="csvForm" action="<?= site_url('admin/intern-requisitions/process-upload') ?>"
      method="post" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>

  <div class="row mb-3">
    <div class="col-md-6">
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

    <div class="col-md-6">
      <label for="xlsx_file" class="form-label">XLSX File (Intern and Other User List)</label>
      <input type="file" name="xlsx_file" id="xlsx_file"
            class="form-control" accept=".xlsx" required>
      <div class="invalid-feedback">Please select a XLSX file.</div>
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


  
  <button type="submit" class="btn btn-primary">Upload</button>
</form>
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Initial config (rolling window mode)
  let cfg = {
    startDate: new Date().toISOString().slice(0,10),   // today
    endDate:   null,                                   // no fixed window
    cutoffDays: <?= (int)($cutoffDays ?? 0) ?> || null,
    leadDays:   <?= (int)($lead_days   ?? 0) ?>,
    cutOffTime: '<?= esc($cut_off_time) ?>',
    registeredDates: [], // none for bulk upload
    publicHolidays:  <?= json_encode($publicHolidays, JSON_HEX_TAG) ?>,
    weeklyHolidays:  [5, 6], // Fri, Sat
  };

  // First render
  initMealCalendar(cfg);

  // On meal type change, fetch new settings and re-init the picker
  document.getElementById('meal_type_id').addEventListener('change', async (e) => {
    const id = e.target.value;
    if (!id) return;

    try {
      const res  = await fetch('<?= base_url('admin/intern-subscriptions/cutoffinfo') ?>/' + id);
      if (!res.ok) throw new Error(res.statusText);
      const json = await res.json();

      // Update config from API
      cfg.cutoffDays = (Number(json.cutoffDays) > 0) ? Number(json.cutoffDays) : null;
      cfg.leadDays   = parseInt(json.leadDays || 0, 10);
      cfg.cutOffTime = json.cutOffTime || '00:00:00';

      // Destroy old flatpickr instance and re-init with new rules
      const input = document.getElementById('meal-calendar');
      if (input && input._flatpickr) input._flatpickr.destroy();
      initMealCalendar(cfg);
    } catch (err) {
      console.error(err);
      alert('Failed to refresh calendar rules for the selected meal type.');
    }
  });
});
</script>
<?= $this->endSection() ?>
