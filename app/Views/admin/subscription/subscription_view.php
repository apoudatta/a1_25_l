<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">My Subscriptions - (Lunch)</h4>

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
      <th>Emp. Id</th>
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
    $now   = new DateTime();        // current time
  ?>
  <?php foreach ($subs as $index => $s): ?>
    <?php
      $mealDate = new DateTime($s['subscription_date']);

      // Cut-off = (mealDate - lead_days) @ cut_off_time
      $leadDays = isset($s['lead_days']) ? (int)$s['lead_days'] : 0;
      $cutoff   = $s['cutoff_time'] ?? '23:59:59';
      [$hh,$mm,$ss] = array_map('intval', array_pad(explode(':', $cutoff), 3, 0));
      $deadline = (clone $mealDate)->modify("-{$leadDays} days")->setTime($hh, $mm, $ss);

      // Show button only if ACTIVE + meal date strictly future + before deadline
      $canUnsubscribe = ($s['status'] === 'ACTIVE') && ($mealDate > $today) && ($now < $deadline);
    ?>
    <tr id="row-<?= $s['id'] ?>">
      <td><?= esc($index + 1) ?></td>
      <td><?= esc($s['employee_id']) ?></td>
      <td><?= esc($s['name']) ?></td>
      <td><?= esc(date('d M Y', strtotime(($s['status']=='CANCELLED') ? $s['updated_at'] : $s['created_at']))) ?></td>
      <td data-order="<?= esc($s['subscription_date']) ?>"><?= date('d M Y', strtotime($s['subscription_date'])) ?></td>
      <td><?= esc($s['meal_type_name']) ?></td>
      <td><?= esc($s['caffname']) ?></td>
      <td class="status-cell"><?= esc($s['status']) ?></td>
      <td>
        <?php if ($canUnsubscribe): ?>
          <form method="post" action="<?= site_url("admin/subscription/unsubscribe_single/{$s['id']}") ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" id="unsubscribe_btn_<?= esc($s['id'], 'attr') ?>" class="btn btn-sm btn-danger">Unsubscribe</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>

</table>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
  // Init DataTable first
  dataTableInit('#subscriptionTable', 'My_Subscriptions_Lunch');
  const table = $('#subscriptionTable').DataTable();

  // Flatpickr (show the same format you compare against)
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',
    allowInput: true,
    onChange: () => table.draw(),
    onClose: () => table.draw()
  });

  // Custom filter: use ISO date from data-order and correct column indexes
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true; // only this table

    const from = $('#filterDateFrom').val();  // 'YYYY-MM-DD'
    const to   = $('#filterDateTo').val();    // 'YYYY-MM-DD'
    const cafeteriaFilter = $('#filterCafeteria').val();

    // Get ISO meal date from the 4th column's data-order attribute
    const rowNode = table.row(dataIndex).node();
    const isoMealDate = $('td:eq(3)', rowNode).attr('data-order') || ''; // col 3 = Meal Date
    const cafeteria   = data[5]; // col 5 = Cafeteria

    // Date range (string compare is fine for YYYY-MM-DD)
    if (from && isoMealDate < from) return false;
    if (to   && isoMealDate > to)   return false;

    // Cafeteria match
    if (cafeteriaFilter && cafeteria !== cafeteriaFilter) return false;

    return true;
  });

  // Redraw when filters change/typed
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());
</script>

<?= $this->endSection() ?>