<?= $this->extend('layouts/employee') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">My Subscriptions - (Sehri)</h2>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>

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
        $cafeteriaOptions = array_unique(array_column($subs, 'caffname'));
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
      <th>Reg/deReg date</th>
      <th>Meal Date</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>

  <tbody>
  <?php
      $today = new DateTime('today'); // start of today
      $now   = new DateTime();        // current time
    ?>
    <?php foreach($subs as $index => $s): ?>
      <?php
        // Meal date
        $mealDate = new DateTime($s['subscription_date']);

        // Cutoff deadline = (mealDate - lead_days) @ cut_off_time
        $leadDays = isset($s['lead_days']) ? (int)$s['lead_days'] : 0;
        [$hh,$mm,$ss] = array_map('intval', array_pad(explode(':', $s['cutoff_time'] ?? '00:00:00'), 3, 0));
        $deadline = (clone $mealDate)->modify("-{$leadDays} days")->setTime($hh, $mm, $ss);

        // Show button only if ACTIVE + meal date is strictly future + before deadline
        $canUnsubscribe = ($s['status'] === 'ACTIVE') && ($mealDate > $today) && ($now < $deadline);
      ?>
      <tr id="row-<?= $s['id'] ?>">
        <td><?= esc($index + 1) ?></td>
        <td><?= esc(date('Y-m-d', strtotime(($s['status'] == 'CANCELLED') ? $s['updated_at'] : $s['created_at']))) ?></td>
        <td data-order="<?= esc($s['subscription_date']) ?>"><?= date('d M Y', strtotime($s['subscription_date'])) ?></td>
        <td>Sehri</td>
        <td><?= esc($s['caffname']) ?></td>
        <td class="status-cell"><?= esc($s['status']) ?></td>
        <td>
          <?php if ($canUnsubscribe): ?>
            <?php if (can('employee.sehri.unsubscribe')): ?>
            <form method="post" action="<?= site_url("employee/sehri-subscription/unsubscribe/{$s['id']}") ?>" class="d-inline">
              <?= csrf_field() ?>
              <button type="submit" id="unsubscribe_btn_<?= esc($s['id'], 'attr') ?>" class="btn btn-sm btn-danger">Unsubscribe</button>
            </form>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?><script>
$(function () {
  // Init DT and keep a handle
  dataTableInit('#subscriptionTable', 'My_Subscriptions_Sehri');
  const table = $('#subscriptionTable').DataTable();

  // Flatpickr: show "04 Sep 2025", keep value "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',   // value used for comparisons
    altInput: true,
    altFormat: 'd M Y',    // what the user sees
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // Custom filter (only for this table)
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = ($('#filterDateFrom').val() || '');  // "YYYY-MM-DD"
    const to   = ($('#filterDateTo').val()   || '');  // "YYYY-MM-DD"
    const cafeteriaFilter = ($('#filterCafeteria').val() || '').trim();

    // Column map:
    // 0 # | 1 Reg/deReg | 2 Meal Date | 3 Meal Type | 4 Cafeteria | 5 Status | 6 Action
    const rowNode = table.row(dataIndex).node();

    // Use ISO from data-order on Meal Date cell (col 2)
    const isoMeal = $('td:eq(2)', rowNode).attr('data-order') || '';

    // Date range (string compare works for YYYY-MM-DD)
    if (from && (!isoMeal || isoMeal < from)) return false;
    if (to   && (!isoMeal || isoMeal > to))   return false;

    // Cafeteria text from col 4
    const cafeteriaText = $('td:eq(4)', rowNode).text().trim();
    if (cafeteriaFilter && cafeteriaText !== cafeteriaFilter) return false;

    return true;
  });

  // Redraw when filters change/typed
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());
});
</script>
<?= $this->endSection() ?>
