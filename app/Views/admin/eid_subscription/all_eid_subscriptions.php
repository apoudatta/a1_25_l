<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">All Subscriptions - (Eid)</h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

<!-- Filters -->
<div class="row g-2 align-items-end mb-2">
  <div class="col-md-3">
    <label for="filterEmpId" class="form-label small mb-1">Employee ID</label>
    <input
      type="text"
      id="filterEmpId"
      class="form-control form-control-sm"
      placeholder="Enter Employee ID"
    >
  </div>
  <div class="col-md-3">
    <label for="filterMealType" class="form-label small mb-1">Meal Type</label>
    <select id="filterMealType" class="form-select form-select-sm">
      <option value="">All</option>
      <?php
        $mealTypeOptions = array_unique(array_column($subs, 'meal_type_name'));
        foreach ($mealTypeOptions as $type):
      ?>
        <option value="<?= esc($type, 'attr') ?>"><?= esc($type) ?></option>
      <?php endforeach ?>
    </select>
  </div>
</div>

<form method="post" action="<?= site_url('admin/eid-subscription/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">
<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
      <th>Emp. ID</th>
      <th>Emp. Name</th>
      <th>Subs/Unsubs date</th>
      <th>Date</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <?= view('partials/admin/subs_list_tbody', [
    'rows' => $subs, 'list'=>'ramadan', 'unsubs' => 'eid-subscription', 'employee_id'=>true,
    'showUnsubs' => can('admin.eid-subscription.unsubscribe'),
  ]) ?>
</table>

  <?php if (can('admin.eid-subscription.unsubscribe_bulk')): ?>
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
  // Initialize DataTable
  dataTableInit('#subscriptionTable', 'All_Subscriptions_Eid');
  const table = $('#subscriptionTable').DataTable();

  // Custom filters for THIS table only
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const empId    = ($('#filterEmpId').val() || '').trim().toLowerCase();
    const mealType = ($('#filterMealType').val() || '').trim().toLowerCase();

    // Column map:
    // 0 chk | 1 # | 2 Employee ID | 3 Reg/deReg | 4 Date | 5 Meal Type | 6 Cafeteria | 7 Status | 8 Action
    const rowEmpId    = (data[2] || '').toString().trim().toLowerCase();
    const rowMealType = (data[6] || '').toString().trim().toLowerCase();

    // Employee ID contains match (partial)
    if (empId && !rowEmpId.includes(empId)) return false;

    // Meal Type exact match (use contains if you prefer)
    if (mealType && rowMealType !== mealType) return false;

    return true;
  });

  // Redraw on filter input change
  $('#filterEmpId, #filterMealType').on('keyup change', function () {
    table.draw();
  });

  // Existing bulk logic
  if (typeof bulkSelectedUnsubscription === 'function') {
    bulkSelectedUnsubscription();
  }
</script>

<?= $this->endSection() ?>
