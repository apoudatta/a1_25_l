<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">All Subscriptions - (Sehri)</h4>

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
<form method="post" action="<?= site_url('admin/sehri-subscription/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">
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
    'rows' => $subs, 'list'=>'ramadan', 'employee_id' => true, 'unsubs' => 'sehri-subscription',
    'showUnsubs' => can('admin.sehri-subscription.unsubscribe'),
    ]) ?> 
</table>

  <?php if (can('admin.sehri-subscription.unsubscribe_bulk')): ?>
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
  dataTableInit('#subscriptionTable', 'My_Subscriptions_Sehri');
  const table = $('#subscriptionTable').DataTable();

  // Flatpickr
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd M Y',
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // Filter THIS table only
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = $('#filterDateFrom').val();
    const to   = $('#filterDateTo').val();
    const cafeteriaFilter = ($('#filterCafeteria').val() || '').trim();

    // Column map (with checkbox):
    // 0 chk | 1 # | 2 Employee ID | 3 Reg/deReg | 4 Meal Date | 5 Meal Type | 6 Cafeteria | 7 Status | 8 Action
    const rowNode = table.row(dataIndex).node();
    const $row    = $(rowNode);

    // Date compare using ISO from data-order on Meal Date cell (col 4)
    const isoMealDate =
          $row.find('td.meal-date-cell').data('mealDate')
      || $row.find('td.meal-date-cell').attr('data-order')
      || '';

      let cafeteriaName = $row.find('td.cafeteria-cell').data('cafeteriaName');
      if (typeof cafeteriaName !== 'string' || cafeteriaName === '') {
        cafeteriaName = $row.find('td.cafeteria-cell').text().trim();
      }
      const cafeteriaKey = (cafeteriaName + '').trim().toLowerCase();

      // Date range checks
      if (from && (!isoMealDate || isoMealDate < from)) return false;
      if (to   && (!isoMealDate || isoMealDate > to))   return false;

    if (cafeteriaFilter && cafeteriaText !== cafeteriaFilter) return false;

    return true;
  });

  // Redraw on change/input
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());

  // Bulk logic (if present)
  if (typeof bulkSelectedUnsubscription === 'function') {
    bulkSelectedUnsubscription();
  }
});
</script>


<?= $this->endSection() ?>
