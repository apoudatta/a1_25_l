<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?= view('partials/flash_message') ?>

<h2>Intern Subscriptions</h2>


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
    // Support both keys: 'caffname' (used in tbody) or 'cafeteria_name'
    $cafeteriaOptions = array_values(array_unique(array_filter(array_map(function($r){
      return trim((string)($r['caffname'] ?? $r['cafeteria_name'] ?? ''));
    }, $subs))));
    foreach ($cafeteriaOptions as $cafeteria):
  ?>
    <option value="<?= esc($cafeteria, 'attr') ?>"><?= esc($cafeteria) ?></option>
  <?php endforeach ?>
</select>

  </div>
</div>

<form method="post" action="<?= site_url('intern-subscriptions/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">
<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
      <th>Subscription Type</th>
      <th>Emp. ID</th>
      <th>Intern Name</th>
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
    'rows' => $subs, 'subs_type' => true, 'list'=>'intern', 'unsubs' => 'intern-subscriptions',
    'showUnsubs' => can('admin.intern-subscriptions.unsubscribe_single'),
  ]) ?>
  
</table>

  <?php if (can('admin.intern-subscriptions.unsubscribe_bulk')): ?>
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
  <?= view('partials/flash_message') ?>

  <script>
$(function () {
  // Init DataTable and keep a handle
  dataTableInit('#subscriptionTable', 'Intern_Subscriptions');
  const table = $('#subscriptionTable').DataTable();

  // Flatpickr: show "04 Sep 2025", value "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd M Y',
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // Custom filter for THIS table only
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = $('#filterDateFrom').val() || '';  // "YYYY-MM-DD"
    const to   = $('#filterDateTo').val()   || '';  // "YYYY-MM-DD"
    const cafeteriaFilter = String($('#filterCafeteria').val() || '').trim();

    const $row = $(table.row(dataIndex).node());

    // Meal Date cell: prefer data-meal-date, fallback to data-order
    const $mealCell = $row.find('td.meal-date-cell');
    const isoMeal   = ($mealCell.data('mealDate') || $mealCell.attr('data-order') || '').trim();

    // Date range (YYYY-MM-DD compares lexicographically)
    if (from && (!isoMeal || isoMeal < from)) return false;
    if (to   && (!isoMeal || isoMeal > to))   return false;

    // Cafeteria cell: prefer data-cafeteria-name, fallback to text
    const $cafCell = $row.find('td.cafeteria-cell');
    let cafeteria  = $cafCell.data('cafeteriaName');
    if (typeof cafeteria !== 'string' || cafeteria === '') {
      cafeteria = $cafCell.text();
    }
    cafeteria = String(cafeteria).trim();

    if (cafeteriaFilter && cafeteria !== cafeteriaFilter) return false;

    return true;
  });



  // Redraw on changes
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());

  // Existing bulk logic
  if (typeof bulkSelectedUnsubscription === 'function') {
    bulkSelectedUnsubscription();
  }
});
</script>

<?= $this->endSection() ?>