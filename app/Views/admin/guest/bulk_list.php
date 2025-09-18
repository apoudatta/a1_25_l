<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?= view('partials/flash_message') ?>

<h2>Bulk Guest Subscriptions List</h2>

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
        $cafeteriaOptions = array_unique(array_column($rows, 'caffname'));
        foreach ($cafeteriaOptions as $cafeteria):
      ?>
        <option value="<?= esc($cafeteria, 'attr') ?>"><?= esc($cafeteria) ?></option>
      <?php endforeach ?>
    </select>
  </div>
</div>

<form method="post" action="<?= site_url('admin/guest-subscriptions/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">

<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>      
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
      <th>Emp. ID</th>
      <th>Emp. Name</th>
      <th>Guest Type</th>
      <th>Subs/Unsubs date</th>
      <th>Guest Name</th>
      <th>Phone</th>
      <th>Meal Date</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>OTP</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <?= view('partials/admin/subs_list_tbody', [
    'rows' => $rows, 
    'list'=>'guest', 
    'guest_type' => true, 
    'employee_id' => true, 
    'unsubs' => 'guest-subscriptions',
    'showUnsubs' => can('admin.guest-subscriptions.unsubscribe'),
  ]) ?>
</table>

  <?php if (can('admin.guest-subscriptions.unsubscribe_bulk')): ?>
  <?= csrf_field() ?>
    <div class="mb-2">
      <button type="submit" class="btn btn-danger btn-sm" id="bulkUnsubscribeBtn" disabled>
        Unsubscribe Selected
      </button>
    </div>
  <?php endif; ?>
</form>

<?= $this->endSection() ?>




<?= $this->section('scripts') ?>
<script>
$(function () {
  // 1) Init DataTable and keep a handle
  dataTableInit('#subscriptionTable', 'My_Guest_Subscriptions');
  const table = $('#subscriptionTable').DataTable();

  // 2) Flatpickr – show "04 Sep 2025", keep value "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',   // value used for comparisons
    altInput: true,
    altFormat: 'd M Y',    // what the user sees
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // 3) Custom filter: read ISO date from the Meal Date cell's data-order
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = $('#filterDateFrom').val(); // "YYYY-MM-DD" or ""
    const to   = $('#filterDateTo').val();   // "YYYY-MM-DD" or ""
    const cafeteriaFilter = (($('#filterCafeteria').val() ?? '') + '').trim().toLowerCase();

    const $row = $(table.row(dataIndex).node());

    // Meal date from data-* (prefer data-meal-date, then data-order)
    const isoMealDate =
          $row.find('td.meal-date-cell').data('mealDate')
      || $row.find('td.meal-date-cell').attr('data-order')
      || '';

    // Cafeteria name from data-* (fallback to text)
    let cafeteriaName = $row.find('td.cafeteria-cell').data('cafeteriaName');
    if (typeof cafeteriaName !== 'string' || cafeteriaName === '') {
      cafeteriaName = $row.find('td.cafeteria-cell').text().trim();
    }
    const cafeteriaKey = (cafeteriaName + '').trim().toLowerCase();

    // Date range checks
    if (from && (!isoMealDate || isoMealDate < from)) return false;
    if (to   && (!isoMealDate || isoMealDate > to))   return false;

    // Cafeteria check (exact match, case-insensitive)
    if (cafeteriaFilter && cafeteriaKey !== cafeteriaFilter) return false;

    return true;
  });


  // 4) Redraw on change/input
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());

  // Existing bulk logic
  bulkSelectedUnsubscription();
});
</script>

<?= $this->endSection() ?>
