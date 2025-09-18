<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">All Subscriptions - (Lunch)</h4>

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

<form method="post" action="<?= site_url('admin/subscription/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">
  

<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
      <th>Emp. ID</th>
      <th>User Name</th>
      <th>Subs/Unsubs date</th>
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
      $now   = new DateTime();        // current timestamp
    ?>
    <?php foreach($subs as $index => $s): ?>
      <?php
        // Meal date
        $mealDate = new DateTime($s['subscription_date']);

        // Deadline = (mealDate - lead_days) at cut_off_time (defaults if not configured)
        $leadDays = isset($s['lead_days']) ? (int)$s['lead_days'] : 0;
        $cutoff   = $s['cutoff_time'] ?? '23:59:59';
        [$hh,$mm,$ss] = array_map('intval', array_pad(explode(':', $cutoff), 3, 0));
        $deadline = (clone $mealDate)->modify("-{$leadDays} days")->setTime($hh, $mm, $ss);

        // Allowed only when ACTIVE + strictly future meal date + before deadline
        $canUnsubscribe = ($s['status'] === 'ACTIVE') && ($mealDate > $today) && ($now < $deadline);
      ?>
      <tr id="row-<?= $s['id'] ?>">
        <td>
          <?php if ($canUnsubscribe): ?>
            <input type="checkbox" name="subscription_ids[]" value="<?= $s['id'] ?>" class="row-checkbox">
          <?php else: ?>
            <input type="checkbox" disabled>
          <?php endif; ?>
        </td>
        <td><?= esc($index + 1) ?></td>
        <td><?= esc($s['employee_id']) ?></td>
        <td><?= esc($s['name']) ?></td>
        <td><?= esc(date('d M Y', strtotime(($s['status']=='CANCELLED') ? $s['updated_at'] : $s['created_at']))) ?></td>
        <td 
          data-order="<?= esc(date('Y-m-d', strtotime($s['subscription_date']))) ?>">
          <?= esc(date('d M Y', strtotime($s['subscription_date']))) ?>
        </td>
        <td><?= esc($s['meal_type_name']) ?></td>
        <td><?= esc($s['caffname']) ?></td>
        <td class="status-cell"><?= esc($s['status']) ?></td>
        <td>
          <?php if ($canUnsubscribe): ?>
            <?php if (can('admin.subscriptions.unsubscribe_single')): ?>
              <button
                type="submit"
                class="btn btn-sm btn-danger"
                formaction="<?= site_url("admin/subscription/unsubscribe_single/{$s['id']}") ?>"
                formmethod="post"
                data-bs-toggle="tooltip"
                title="Unsubscribe"
                id="unsubscribe_btn"
              >
                <i class="bi bi-x-circle"></i>
              </button>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>

  </table>

  <?php if (can('admin.subscriptions.unsubscribe_bulk')): ?>
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
  // Init DT and keep a handle
  dataTableInit('#subscriptionTable', 'All_Subscriptions_Lunch', true);
  const table = $('#subscriptionTable').DataTable();

  // Show "04 Sep 2025" to the user but keep value as "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',   // value used for filtering
    altInput: true,
    altFormat: 'd M Y',    // what the user sees
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // Custom filter (only for this table)
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = $('#filterDateFrom').val();   // "YYYY-MM-DD"
    const to   = $('#filterDateTo').val();     // "YYYY-MM-DD"
    const cafeteriaFilter = $('#filterCafeteria').val();

    // Column indexes in this table:
    // 0 chk | 1 # | 2 User | 3 Reg/deReg | 4 Meal Date | 5 Meal Type | 6 Cafeteria | 7 Status | 8 Action
    const rowNode     = table.row(dataIndex).node();
    const isoMealDate = $('td:eq(4)', rowNode).attr('data-order') || ''; // <-- use ISO from data-order
    const cafeteria   = data[6]; // <-- "Cafeteria" text

    if (from && isoMealDate < from) return false;
    if (to   && isoMealDate > to)   return false;
    if (cafeteriaFilter && cafeteria !== cafeteriaFilter) return false;

    return true;
  });

  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());

  // existing logic
  bulkSelectedUnsubscription();
});
</script>

<?= $this->endSection() ?>