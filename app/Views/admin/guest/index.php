<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2>Personal Guest Subscriptions</h2>

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

<form method="post" action="<?= site_url('guest-subscriptions/unsubscribe_bulk') ?>" id="bulkUnsubscribeForm">

<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th><input type="checkbox" id="checkAll"></th>
      <th>#</th>
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
    'rows' => $rows, 'list'=>'guest', 'subs_type' => true, 'unsubs' => 'guest-subscriptions',
    'showUnsubs'=> true,
    ]) ?>
</table>

<?= csrf_field() ?>
  <div class="mb-2">
    <button type="submit" class="btn btn-danger btn-sm" id="bulkUnsubscribeBtn" disabled>
      Unsubscribe Selected
    </button>
  </div>
</form>
<?= $this->endSection() ?>




<?= $this->section('scripts') ?>
<script>
$(function () {
  dataTableInit('#subscriptionTable', 'My_Guest_Subscriptions');
  const table = $('#subscriptionTable').DataTable();

  // Show "04 Sep 2025" to user, but keep real value "2025-09-04"
  flatpickr('.datepicker', {
    dateFormat: 'Y-m-d',   // real value used for compare
    altInput: true,
    altFormat: 'd M Y',    // display format
    allowInput: true,
    onChange: () => table.draw(),
    onClose:  () => table.draw()
  });

  // helper: parse "04 Sep 2025" -> "2025-09-04" (fallback if data-order missing)
  function toISO(dstr) {
    const t = Date.parse(dstr);         // handles "04 Sep 2025"
    if (isNaN(t)) return '';
    const d = new Date(t);
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
  }

  // Only filter this table
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== 'subscriptionTable') return true;

    const from = $('#filterDateFrom').val();   // "YYYY-MM-DD"
    const to   = $('#filterDateTo').val();     // "YYYY-MM-DD"
    const cafeteriaFilter = $('#filterCafeteria').val();

    // Column indexes for this table:
    // 0 chk | 1 # | 2 Guest Type | 3 Reg/deReg | 4 Guest Name | 5 Phone |
    // 6 Meal Date | 7 Meal Type | 8 Cafeteria | 9 Status | 10 Action
    const rowNode = table.row(dataIndex).node();

    // ISO meal date from data-order; fallback: parse the visible text in col 6
    const tdMeal   = $('td:eq(6)', rowNode);
    const isoMeal  = tdMeal.attr('data-order') || toISO(tdMeal.text().trim());
    const cafeteria = data[8];

    if (from && isoMeal < from) return false;
    if (to   && isoMeal > to)   return false;
    if (cafeteriaFilter && cafeteria !== cafeteriaFilter) return false;

    return true;
  });

  $('#filterDateFrom, #filterDateTo, #filterCafeteria').on('change input', () => table.draw());

  // your existing bulk handler
  bulkSelectedUnsubscription();
});
</script>

<?= $this->endSection() ?>
