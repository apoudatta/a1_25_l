<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">All Subscriptions - (Ifter)</h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<!-- Filter -->
<div class="row g-2 align-items-end mb-2">
  <div class="col-4 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Meal Date From</label>
    <input
      type="text"
      id="filterDateFrom"
      class="form-control form-control-sm datepicker"
      placeholder="mm/dd/yyyy"
    >
  </div>
  <div class="col-4 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Meal Date To</label>
    <input
      type="text"
      id="filterDateTo"
      class="form-control form-control-sm datepicker"
      placeholder="mm/dd/yyyy"
    >
  </div>
  <div class="col-4 col-md-3 col-lg-3">
    <label class="form-label small mb-1">Cafeteria</label>
    <select id="filterCafeteria" class="form-select form-select-sm">
      <option value="">All</option>
      <?php
        $cafeteriaOptions = array_unique(array_column($subs, 'caffname'));
        foreach ($cafeteriaOptions as $cafeteria):
      ?>
        <option value="<?= esc($cafeteria, 'attr') ?>"><?= esc($cafeteria) ?></option>
      <?php endforeach ?>
    </select>
  </div>

</div>
<form method="post" action="<?= site_url('admin/ifter-subscription/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">
<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
      <th>Emp. ID</th>
      <th>Emp. Name</th>
      <th>Subs/Unsubs date</th>
      <th>Meal Date</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <?= view('partials/admin/subs_list_tbody', [
    'rows' => $subs, 'list'=>'ramadan', 'employee_id' => true, 
    'unsubs' => 'ifter-subscription',
    'showUnsubs' => can('admin.ifter-subscription.unsubscribe'),
  ]) ?>
</table>

  <?php if (can('admin.ifter-subscription.unsubscribe_bulk')): ?>
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
  // Init DataTable and keep a handle
  dataTableInit('#subscriptionTable', 'My_Subscriptions_Ifter');
  const table = $('#subscriptionTable').DataTable();

  // Flatpickr: show "04 Sep 2025", value "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',   // value used for comparisons
    altInput: true,
    altFormat: 'd M Y',    // what the user sees
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // Filter for THIS table only
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = $('#filterDateFrom').val() || '';   // "YYYY-MM-DD"
    const to   = $('#filterDateTo').val()   || '';   // "YYYY-MM-DD"
    const cafeteriaFilter = String($('#filterCafeteria').val() || '')
      .replace(/\u00A0/g, ' ')
      .trim()
      .toLowerCase();

    const $row = $(table.row(dataIndex).node());

    // Meal date from the meal-date cell (prefer data-meal-date, then data-order)
    const $mealCell = $row.find('td.meal-date-cell');
    const isoMeal   = $mealCell.data('mealDate') || $mealCell.attr('data-order') || '';

    // Cafeteria name from the cafeteria cell (prefer data-cafeteria-name, fall back to text)
    const $cafCell = $row.find('td.cafeteria-cell');
    let cafeteria  = $cafCell.data('cafeteriaName');
    if (typeof cafeteria !== 'string' || cafeteria === '') {
      cafeteria = $cafCell.text();
    }
    const cafeteriaKey = String(cafeteria).replace(/\u00A0/g, ' ').trim().toLowerCase();

    // Date range checks
    if (from && (!isoMeal || isoMeal < from)) return false;
    if (to   && (!isoMeal || isoMeal > to))   return false;

    // Cafeteria (case-insensitive exact match)
    if (cafeteriaFilter && cafeteriaKey !== cafeteriaFilter) return false;

    return true;
  });

  // Redraw on changes
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());

  // Existing bulk-logic
  bulkSelectedUnsubscription();
});
</script>

<?= $this->endSection() ?>
