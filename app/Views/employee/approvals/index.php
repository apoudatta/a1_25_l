<?= $this->extend('layouts/employee') ?>
<?= $this->section('content') ?>

<h2 class="mb-3">Approval Queue</h2>
<?= view('partials/flash_message') ?>

<?php
  // Build unique cafeteria list from current rows
  $cafes = [];
  foreach ($approvals as $row) {
      $c = trim((string)($row['disp_cafe'] ?? ''));
      if ($c !== '' && !isset($cafes[$c])) $cafes[$c] = 1;
  }
  ksort($cafes);
?>

<!-- Filters -->
<div class="row g-2 align-items-end mb-2">
  <div class="col-6 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Meal Date From</label>
    <input type="date" id="filterDateFrom" class="form-control form-control-sm datepicker">
  </div>
  <div class="col-6 col-md-3 col-lg-2">
    <label class="form-label small mb-1">Meal Date To</label>
    <input type="date" id="filterDateTo" class="form-control form-control-sm datepicker">
  </div>
  <div class="col-12 col-md-4 col-lg-3">
    <label class="form-label small mb-1">Cafeteria</label>
    <select id="filterCafe" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach (array_keys($cafes) as $c): ?>
        <option value="<?= esc($c) ?>"><?= esc($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12 col-md d-flex justify-content-md-end gap-2 mt-2 mt-md-0">
    <form id="bulkApproveForm" method="post" action="<?= site_url('employee/approvals/bulk-approve') ?>" class="d-none">
      <?= csrf_field() ?>
    </form>
    <form id="bulkRejectForm"  method="post" action="<?= site_url('employee/approvals/bulk-reject')  ?>" class="d-none">
      <?= csrf_field() ?>
    </form>

    <button id="btnBulkApprove" class="btn btn-success btn-sm" disabled>
      Approve Selected
    </button>
    <button id="btnBulkReject" class="btn btn-danger btn-sm" disabled>
      Reject Selected
    </button>
  </div>
</div>

<table id="approvalTable" class="table table-sm table-hover w-100">
  <thead class="table-light">
    <tr>
      <th style="width:34px;">
        <input type="checkbox" id="checkAll" class="form-check-input">
      </th>
      <th>#</th>
      <th>Employee ID</th>
      <th>Type</th>
      <th>Meal Type</th>
      <th>Meal Date</th>
      <th>Cafeteria</th>
      <th>Subs/DeSubs Date</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($approvals as $index => $a): ?>
      <?php
        $status = $a['disp_status'] ?? '';
        $badge  = 'bg-secondary';
        if ($status === 'PENDING')                                  $badge = 'bg-warning text-dark';
        if ($status === 'ACTIVE' || $status === 'APPROVED')         $badge = 'bg-success';
        if ($status === 'CANCELLED' || $status === 'REJECTED')      $badge = 'bg-danger';
      ?>
      <tr 
        data-detail-id="<?= (int)$a['detail_id'] ?>"
        data-subscription-type="<?= strtolower($a['subscription_type']) ?>"
      >
        <td>
          <?php if (($a['approval_status'] ?? '') === 'PENDING' && ($a['disp_status'] ?? '') === 'PENDING'): ?>
            <input type="checkbox" class="row-check form-check-input">
          <?php endif; ?>
        </td>
        <td><?= esc($index + 1) ?></td>
        <td><?= esc($a['disp_emp_id']) ?></td>
        <td><?= esc($a['subscription_type']) ?></td>
        <td><?= esc($a['disp_meal_type']) ?></td>
        <td class="col-mealdate"
          data-order="<?= esc(date('Y-m-d', strtotime($a['disp_meal_date']))) ?>">
          <?= esc(date('d M Y', strtotime($a['disp_meal_date']))) ?>
        </td>
        <td class="col-cafe"><?= esc($a['disp_cafe']) ?></td>
        <td data-order="<?= esc($a['disp_event_at']) ?>">
          <?= date('d M Y h:i A', strtotime($a['disp_event_at'])) ?>
        </td>
        <td><span class="badge <?= $badge ?>"><?= esc($status) ?></span></td>
        <td>
          <?php if (($a['approval_status'] ?? '') === 'PENDING' && ($a['disp_status'] ?? '') === 'PENDING'): ?>
            <form action="<?= site_url('employee/approvals/approve/'.strtolower($a['subscription_type']).'/'.$a['detail_id']) ?>"
                  method="post" class="d-inline action-form">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-success btn-approve">Approve</button>
            </form>

            <form action="<?= site_url('employee/approvals/reject/'.strtolower($a['subscription_type']).'/'.$a['detail_id']) ?>"
                  method="post" class="d-inline action-form">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-danger btn-reject">Reject</button>
            </form>
          <?php endif ?>
        </td>

      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  // NOTE: each <tr> must have:
  // data-detail-id="<detail_id>" data-subscription-type="<employee|guest|intern>"
  const $tbl = $('#approvalTable');

  // ---------- DataTable init (keep old search + Excel)
  const dtOpts = {
    order: [[7, 'desc']],                 // Subs/DeSubs Date
    pageLength: 25,
    columnDefs: [
      { targets: [0, 9], orderable: false, searchable: false } // checkbox + Action
    ],
    dom: 'Bfrtip', // fallback UI (search + Excel) if your helper isn't present
    buttons: [
      {
        extend: 'excel',
        title: 'Approval_Queue',
        text: 'Download Excel',
        className: 'btn btn-success btn-sm'
      }
    ],
    language: { emptyTable: 'No approvals queued.' }
  };

  let table;
  if ($.fn.DataTable.isDataTable($tbl)) {
    table = $tbl.DataTable();                 // already initialized
  } else if (typeof dataTableInit === 'function') {
    dataTableInit('#approvalTable', 'Approval_Queue', dtOpts);
    table = $tbl.DataTable();                 // get instance (no re-init)
  } else {
    table = $tbl.DataTable(dtOpts);           // init ourselves
  }

  // ---------- Client-side filters (Meal Date range & Cafeteria)
  function ymdToInt(s) {
    if (!s) return null;
    const slash = /^(\d{2})\/(\d{2})\/(\d{4})$/; // mm/dd/yyyy
    if (slash.test(s)) {
      const m = slash.exec(s);
      s = `${m[3]}-${m[1]}-${m[2]}`;
    }
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
    return m ? parseInt(m[1] + m[2] + m[3], 10) : null;
  }

  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable !== document.getElementById('approvalTable')) return true;

    const api  = new $.fn.dataTable.Api(settings);
    const $row = $(api.row(dataIndex).node());

    // pull ISO date from data-order, NOT from the display text
    const mealISO = $row.find('td.col-mealdate').data('order') || '';

    const cafeFilter = ($('#filterCafe').val() || '').toLowerCase();
    const cafeText   = (data[6] || '').toLowerCase(); // Cafeteria column text

    if (cafeFilter && cafeText !== cafeFilter) return false;

    const meal = ymdToInt(mealISO);
    const from = ymdToInt($('#filterDateFrom').val());
    const to   = ymdToInt($('#filterDateTo').val());

    if (from && meal && meal < from) return false;
    if (to   && meal && meal > to)   return false;

    return true;
  });

  $('#filterCafe, #filterDateFrom, #filterDateTo').on('change input', function () {
    table.draw();
  });

  // ---------- Selection & bulk buttons (detail_id + subscription_type)
  const $btnApprove = $('#btnBulkApprove');
  const $btnReject  = $('#btnBulkReject');

  // store as Map to dedupe: key = `${type}|${id}`, value = {id, type}
  const selected = new Map();

  function refreshButtons() {
    const on = selected.size > 0;
    $btnApprove.prop('disabled', !on);
    $btnReject.prop('disabled',  !on);
  }

  // Row checkbox
  $tbl.on('change', '.row-check', function () {
    const $tr  = $(this).closest('tr');

    // Read attributes robustly with .attr(); also support old data-approval-id fallback
    const idAttr   = $tr.attr('data-detail-id') || $tr.attr('data-approval-id') || '';
    const typeAttr = $tr.attr('data-subscription-type') || '';

    const id   = String(idAttr).trim();
    const type = String(typeAttr).toLowerCase().trim();
    const key  = `${type}|${id}`;

    if (!id || !type) { // bad row, undo selection just in case
      this.checked = false;
      return;
    }

    if (this.checked) selected.set(key, { id, type });
    else              selected.delete(key);

    refreshButtons();
  });

  // Header "select all" (applies to all filtered rows)
  $('#checkAll').on('change', function () {
    const checked = this.checked;
    table.rows({ search: 'applied' }).every(function () {
      const $row = $(this.node());
      const $chk = $row.find('.row-check');
      if ($chk.length && !$chk.prop('disabled')) {
        $chk.prop('checked', checked).trigger('change');
      }
    });
  });

  function doBulk(action) {
    if (selected.size === 0) return;

    // Build aligned arrays: detail_ids[] and types[]
    const detailIds = [];
    const types = [];
    selected.forEach(v => { detailIds.push(v.id); types.push(v.type); });

    Swal.fire({
      title: (action === 'approve' ? 'Approve' : 'Reject') + ' selected?',
      text:  `You are about to ${action} ${detailIds.length} item(s). Continue?`,
      icon:  'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, continue',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then(res => {
      if (!res.isConfirmed) return;
      Swal.fire({
        title: 'Enter remark',
        input: 'textarea',
        inputPlaceholder: 'Type your remark here…',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then(inputRes => {
        if (!inputRes.isConfirmed) return;

        const formId = (action === 'approve') ? '#bulkApproveForm' : '#bulkRejectForm';
        const $form  = $(formId).empty();
        $form.append('<?= csrf_field() ?>');

        for (let i = 0; i < detailIds.length; i++) {
          $form.append($('<input>', { type:'hidden', name:'detail_ids[]', value: detailIds[i] }));
          $form.append($('<input>', { type:'hidden', name:'types[]',      value: types[i]      }));
        }
        $form.append($('<input>', { type:'hidden', name:'remark', value: inputRes.value || '' }));
        $form.trigger('submit');
      });
    });
  }

  $('#btnBulkApprove').on('click', () => doBulk('approve'));
  $('#btnBulkReject').on('click',  () => doBulk('reject'));
})();
</script>
<?= $this->endSection() ?>


