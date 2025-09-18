<?= $this->extend('layouts/employee') ?>
<?= $this->section('content') ?>

<h2>Guest Subscriptions</h2>

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
        $cafeteriaOptions = array_unique(array_column($rows, 'caffname'));
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
      <th>Guest</th>
      <th>Phone</th>
      <th>Meal Date</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>OTP</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php
    $today = new DateTime('today');
    $now   = new DateTime(); // server tz
  ?>
  <?php foreach ($rows as $index => $r): ?>
    <?php
      // meal date
      $mealDate = new DateTime($r['subscription_date']);

      // cut-off = (mealDate - lead_days) @ cut_off_time
      $leadDays = isset($r['lead_days']) ? (int)$r['lead_days'] : 0;
      [$hh,$mm,$ss] = array_map('intval', array_pad(explode(':', $r['cutoff_time'] ?? '00:00:00'), 3, 0));
      $deadline = (clone $mealDate)->modify("-{$leadDays} days")->setTime($hh,$mm,$ss);

      // show button only if:
      //  - status ACTIVE
      //  - meal date is strictly in the future (no today/past)
      //  - and we’re still before the calculated deadline
      $canUnsubscribe = ($r['status'] === 'ACTIVE')
                        && ($mealDate > $today)
                        && ($now < $deadline);
    ?>
    <tr id="row-<?= $r['id'] ?>">
      <td><?= esc($index + 1) ?></td>
      <td><?= esc(date('d M Y', strtotime(($r['status']=='CANCELLED') ? $r['updated_at'] : $r['created_at']))) ?></td>
      <td><?= esc($r['guest_name']) ?></td>
      <td><?= esc($r['phone']) ?></td>
      <td
        data-order="<?= esc(date('Y-m-d', strtotime($r['subscription_date']))) ?>">
        <?= esc(date('d M Y', strtotime($r['subscription_date']))) ?>
      </td>
      <td><?= esc($r['meal_type_name']) ?></td>
      <td><?= esc($r['caffname']) ?></td>
      <td><?= esc($r['otp']) ?></td>
      <td class="status-cell"><?= esc($r['status']) ?></td>
      <td>
        <?php if ($canUnsubscribe): ?>
          <?php if (can('employee.guests.unsubscribe')): ?>
          <form method="post" action="<?= site_url("employee/guest-subscriptions/unsubscribe/{$r['id']}") ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger unsubscribe-btn" data-id="<?= $r['id'] ?>">Unsubscribe</button>
          </form>
          <?php endif; ?>
        <?php endif ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>

</table>


<?= $this->endSection() ?>




<?= $this->section('scripts') ?>
<script>
$(function () {
  // Init DataTable and keep a handle
  dataTableInit('#subscriptionTable', 'My_Guest_Subscriptions');
  const table = $('#subscriptionTable').DataTable();

  // Datepicker: user sees "04 Sep 2025", value is "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',   // value used for comparisons
    altInput: true,
    altFormat: 'd M Y',    // what the user sees
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // Custom filter for THIS table only
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = ($('#filterDateFrom').val() || '');   // "YYYY-MM-DD"
    const to   = ($('#filterDateTo').val()   || '');   // "YYYY-MM-DD"
    const cafeteriaFilter = ($('#filterCafeteria').val() || '').trim();

    // Column map:
    // 0 # | 1 Reg/deReg | 2 Guest | 3 Phone | 4 Meal Date | 5 Meal Type | 6 Cafeteria | 7 Status | 8 Action
    const rowNode = table.row(dataIndex).node();

    // Use ISO from data-order on Meal Date cell (col 4)
    const isoMeal = $('td:eq(4)', rowNode).attr('data-order') || '';

    // Date range (string compare works for YYYY-MM-DD)
    if (from && isoMeal < from) return false;
    if (to   && isoMeal > to)   return false;

    // Cafeteria: read plain text from col 6
    const cafeteriaText = $('td:eq(6)', rowNode).text().trim();
    if (cafeteriaFilter && cafeteriaText !== cafeteriaFilter) return false;

    return true;
  });

  // Redraw when filters change/typed
  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());
});
</script>

<?= $this->endSection() ?>
