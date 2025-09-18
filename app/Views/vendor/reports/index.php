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
      placeholder="mm/dd/yyyy"
    >
  </div>
  <div class="col-4 col-md-4 col-lg-2">
    <label class="form-label small mb-1">Meal Date To</label>
    <input
      type="text"
      id="filterDateTo"
      class="form-control form-control-sm datepicker"
      placeholder="mm/dd/yyyy"
    >
  </div>
  <div class="col-4 col-md-4 col-lg-3">
    <label class="form-label small mb-1">Cafeteria</label>
    <select id="filterCafeteria" class="form-select form-select-sm">
      <option value="">All</option>
      <?php
        $cafeteriaOptions = array_unique(array_column($rows, 'location'));
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
      <th>User Name</th>
      <th>Meal Date</th>
      <th>Employment Type</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
    </tr>
  </thead>
  <tbody>
    <?php if(empty($rows)): ?>
      <tr><td colspan="6" class="text-center">No records.</td></tr>
    <?php else: foreach($rows as $index => $r): ?>
      <tr id="row-<?= $r['id'] ?>">
        <td><?= esc($index + 1) ?></td>
        <td><?= esc($r['name'] ?? '') ?></td>
        <td><?= date('d M Y', strtotime($r['meal_date'])) ?></td>
        <td><?= esc($r['employment_type'] ?? '') ?></td>
        <td><?= esc($r['meal_type_name'] ?? '') ?></td>
        <td><?= esc($r['location'] ?? '') ?></td>
      </tr>
    <?php endforeach; endif ?>
  </tbody>
</table>


<?= $this->endSection() ?>




<?= $this->section('scripts') ?>
<script>
  // Include DataTables
  dataTableInit('#subscriptionTable', 'daily_meal_report');



// Initialize Flatpickr
flatpickr('.datepicker', { dateFormat: 'Y-m-d' });

// Custom filter for Meal Date Range and Cafeteria
$.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
  const dateFrom = $('#filterDateFrom').val();
  const dateTo = $('#filterDateTo').val();
  const cafeteria = $('#filterCafeteria').val();
  const mealDate = data[2]; // column index for Meal Date
  const caf = data[5];      // column index for Cafeteria

  // Date range filter
  if ((dateFrom && mealDate < dateFrom) || (dateTo && mealDate > dateTo)) {
    return false;
  }

  // Cafeteria filter
  if (cafeteria && caf !== cafeteria) {
    return false;
  }

  return true;
});

// Trigger redraw on filter input change
$('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change', function () {
  $('#subscriptionTable').DataTable().draw();
});

</script>
<?= $this->endSection() ?>
