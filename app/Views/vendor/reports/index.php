<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h2>Daily Meal Report</h2>

<!-- Filter -->
<div class="row g-2 align-items-end mb-2">
  <div class="col-4 col-md-4 col-lg-2">
    <label class="form-label small mb-1">Meal Date From</label>
    <input
      type="text"
      id="filterDateFrom"
      class="form-control form-control-sm datepicker"
      placeholder="yyyy-mm-dd"
    >
  </div>

  <div class="col-4 col-md-4 col-lg-2">
    <label class="form-label small mb-1">Meal Date To</label>
    <input
      type="text"
      id="filterDateTo"
      class="form-control form-control-sm datepicker"
      placeholder="yyyy-mm-dd"
    >
  </div>

  <div class="col-4 col-md-4 col-lg-2">
    <label class="form-label small mb-1">Employee Type</label>
    <select id="filterEmpType" class="form-select form-select-sm">
      <option value="">All</option>
      <?php
        $empTypeOptions = array_unique(
          array_map('trim', array_filter(array_column($employee, 'emp_type_name')))
        );
        foreach ($empTypeOptions as $emp):
      ?>
        <option value="<?= esc($emp, 'attr') ?>"><?= esc($emp) ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-4 col-md-4 col-lg-3">
    <label class="form-label small mb-1">Meal Type</label>
    <select id="filterMealType" class="form-select form-select-sm">
      <option value="">All</option>
      <?php
        $mealTypeOptions = array_unique(
          array_map('trim', array_filter(array_column($employee, 'meal_type_name')))
        );
        foreach ($mealTypeOptions as $mt):
      ?>
        <option value="<?= esc($mt, 'attr') ?>"><?= esc($mt) ?></option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-4 col-md-4 col-lg-3">
    <label class="form-label small mb-1">Meal Availing Location</label>
    <select id="filterCafeteria" class="form-select form-select-sm">
      <option value="">All</option>
      <?php
        $cafeteriaOptions = array_unique(
          array_map('trim', array_filter(array_column($employee, 'cafeteria_name')))
        );
        foreach ($cafeteriaOptions as $cafeteria):
      ?>
        <option value="<?= esc($cafeteria, 'attr') ?>"><?= esc($cafeteria) ?></option>
      <?php endforeach ?>
    </select>
  </div>
</div>



<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Emp. Id</th>
      <th>Emp Name</th>
      <th>Meal Date</th>
      <th>Employment Type</th>
      <th>Meal Type</th>
      <th>Meal Availing Location</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($employee as $index => $r): ?>
      <tr id="row-<?= $r['id'] ?>">
        <td><?= esc($index + 1) ?></td>
        <td><?= esc($r['employee_id'] ?? '') ?></td>
        <td><?= esc($r['name'] ?? '') ?></td>
        <td><?= date('d M Y', strtotime($r['meal_date'])) ?></td>
        <td><?= esc($r['emp_type_name'] ?? '') ?></td>
        <td><?= esc($r['meal_type_name'] ?? '') ?></td>
        <td><?= esc($r['cafeteria_name'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h5>Guest</h5>
<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Guest Name</th>
      <th>Meal Date</th>
      <th>Meal Availing Location</th>
      <th>Requester Emp. Id</th>
      <th>Requester Name</th>
      <th>Guest Type</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($guest as $index => $r): ?>
      <tr id="row-<?= $r['id'] ?>">
        <td><?= esc($index + 1) ?></td>
        <td><?= esc($r['ref_name'] ?? '') ?></td>
        <td><?= date('d M Y', strtotime($r['meal_date'])) ?></td>
        <td><?= esc($r['cafeteria_name'] ?? '') ?></td>
        <td><?= esc($r['employee_id'] ?? '') ?></td>
        <td><?= esc($r['name'] ?? '') ?></td>
        <td><?= esc($r['emp_type_name'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>


<?= $this->endSection() ?>




<?= $this->section('scripts') ?>
<script>
  flatpickr('.datepicker', { dateFormat: 'Y-m-d' });

  function parseISOToUTC(ts){ if(!ts) return NaN; const [y,m,d]=ts.split('-').map(Number); return Date.UTC(y,m-1,d); }
  function parseDisplayDMYToUTC(disp){
    if (!disp) return NaN;
    const p = disp.trim().split(/\s+/); // "22 Sep 2025"
    const mon = {Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11};
    if (p.length===3 && mon[p[1]]!==undefined) return Date.UTC(parseInt(p[2],10), mon[p[1]], parseInt(p[0],10));
    const t = Date.parse(disp); return isNaN(t) ? NaN : new Date(t).setUTCHours(0,0,0,0);
  }

  $.fn.dataTable.ext.search.push(function (settings, data) {
    const dateFromVal   = $('#filterDateFrom').val();
    const dateToVal     = $('#filterDateTo').val();
    const cafeteriaVal  = $('#filterCafeteria').val();
    const empTypeVal    = $('#filterEmpType').val();
    const mealTypeVal   = $('#filterMealType').val();

    const mealDateDisp  = data[3]; // "22 Sep 2025"
    const empTypeTxt    = data[4]; // Employee Type
    const mealTypeTxt   = data[5]; // Meal Type
    const cafeteriaTxt  = data[6]; // Cafeteria

    const mealTs = parseDisplayDMYToUTC(mealDateDisp);
    const fromTs = parseISOToUTC(dateFromVal);
    const toTs   = parseISOToUTC(dateToVal);

    if (!isNaN(fromTs) && !isNaN(mealTs) && mealTs < fromTs) return false;
    if (!isNaN(toTs)   && !isNaN(mealTs) && mealTs > toTs)   return false;

    if (cafeteriaVal && $.trim(cafeteriaTxt) !== $.trim(cafeteriaVal)) return false;
    if (empTypeVal   && $.trim(empTypeTxt)   !== $.trim(empTypeVal))   return false;
    if (mealTypeVal  && $.trim(mealTypeTxt)  !== $.trim(mealTypeVal))  return false;

    return true;
  });

  const dt = dataTableInit('#subscriptionTable', 'daily_meal_report');

  $('#filterDateFrom, #filterDateTo, #filterCafeteria, #filterEmpType, #filterMealType')
    .on('change input', function () {
      $('#subscriptionTable').DataTable().draw();
    });
</script>

<?= $this->endSection() ?>
