<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-3"><?= esc($title) ?></h2>

<!-- Shared filter bar: Month, Year, Type -->
<form class="row g-2 align-items-end mb-3" method="get" action="">
  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Month</label>
    <select class="form-select form-select-sm" name="month" id="filterMonth">
      <?php $mNow = (int) ($filters['month'] ?? date('n'));
      for ($m=1; $m<=12; $m++): $label = date('F', mktime(0,0,0,$m,1)); ?>
        <option value="<?= $m ?>" <?= $mNow===$m?'selected':'' ?>><?= esc($label) ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Year</label>
    <select class="form-select form-select-sm" name="year" id="filterYear">
      <?php $yNow=(int)($filters['year']??date('Y'));
      for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
        <option value="<?= $y ?>" <?= $yNow===(int)$y?'selected':'' ?>><?= $y ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-12 col-md-3">
    <label class="form-label small mb-1">Type</label>
    <?php $curType = (string) ($filters['type'] ?? '') ?>
    <select class="form-select form-select-sm" name="type" id="filterType">
      <option value="">All</option>
      <option value="EMPLOYEE"       <?= $curType==='EMPLOYEE'?'selected':'' ?>>Employee</option>
      <option value="OS"             <?= $curType==='OS'?'selected':'' ?>>OS</option>
      <option value="Security Guard" <?= $curType==='Security Guard'?'selected':'' ?>>Security Guard</option>
      <option value="Support Staff"  <?= $curType==='Support Staff'?'selected':'' ?>>Support Staff</option>
      <option value="INTERN"         <?= $curType==='INTERN'?'selected':'' ?>>Intern</option>
      <option value="GUEST"          <?= $curType==='GUEST'?'selected':'' ?>>Guest</option>
    </select>
  </div>

  <div class="col-12 col-md-2">
    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
  </div>
</form>

<!-- ==================== EMPLOYEE ==================== -->
<h5 class="mt-3 mb-2">Employee</h5>
<div class="table-responsive">
  <table id="empTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>Emp. ID</th>
        <th>Emp. Name</th>
        <th>Designation</th>
        <th>Division</th>
        <th>Month'Year</th>
        <th>Mobile</th>
        <th class="text-end">Day Count</th>
        <th class="text-end">Full Meal Cost</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rowsEmp ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['emp_id']) ?></td>
          <td><?= esc($r['emp_name']) ?></td>
          <td><?= esc($r['designation']) ?></td>
          <td><?= esc($r['division']) ?></td>
          <td><?= esc($r['month_year']) ?></td>
          <td><?= esc($r['mobile']) ?></td>
          <td class="text-end"><?= (int) $r['day_count'] ?></td>
          <td class="text-end"><?= number_format((float) $r['full_meal_cost'], 2) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<!-- ==================== INTERN ==================== -->
<h5 class="mt-4 mb-2">Intern</h5>
<div class="table-responsive">
  <table id="internTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>Intern ID</th>
        <th>Intern Name</th>
        <th>Month'Year</th>
        <th class="text-end">Day Count</th>
        <th class="text-end">Full Meal Cost</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rowsIntern ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['intern_id']) ?></td>
          <td><?= esc($r['intern_name']) ?></td>
          <td><?= esc($r['month_year']) ?></td>
          <td class="text-end"><?= (int) $r['day_count'] ?></td>
          <td class="text-end"><?= number_format((float) $r['full_meal_cost'], 2) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<!-- ==================== GUEST ==================== -->
<h5 class="mt-4 mb-2">Guest</h5>
<div class="table-responsive">
  <table id="guestTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>Guest Name</th>
        <th>Location</th>
        <th>Requestor Emp ID</th>
        <th>Requestor Name</th>
        <th>Requestor Division</th>
        <th>Guest Type</th>
        <th>Month'Year</th>
        <th class="text-end">Day Count</th>
        <th class="text-end">Full Meal Cost</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rowsGuest ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['guest_name']) ?></td>
          <td><?= esc($r['location']) ?></td>
          <td><?= esc($r['requestor_emp_id']) ?></td>
          <td><?= esc($r['requestor_name']) ?></td>
          <td><?= esc($r['requestor_division']) ?></td>
          <td><?= esc(mb_convert_case((string)($r['guest_type'] ?? ''), MB_CASE_TITLE, 'UTF-8')) ?></td>
          <td><?= esc($r['month_year']) ?></td>
          <td class="text-end"><?= (int) $r['day_count'] ?></td>
          <td class="text-end"><?= number_format((float) $r['full_meal_cost'], 2) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Small helper (works even if you didn't add the global one)
if (typeof window.initReportDT !== 'function') {
  window.initReportDT = function (selector, titleBase, filenameBase, suffixFn) {
    var dt = $(selector).DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [[0, 'asc']],
      dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           't' +
           '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      buttons: [{
        extend: 'excelHtml5',
        text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
        className: 'btn btn-success btn-sm px-3',
        titleAttr: 'Download as Excel',
        title: function(){ return titleBase + (suffixFn ? ' - ' + suffixFn() : ''); },
        filename: function(){ return filenameBase + (suffixFn ? '_' + suffixFn().replaceAll(' ','_') : ''); },
        exportOptions: { columns: ':visible' }
      }]
    });
    var $filter = $(selector + '_wrapper .dataTables_filter');
    dt.buttons().container().appendTo($filter).addClass('ms-2');
    $filter.addClass('d-flex align-items-center justify-content-end gap-2');
    return dt;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const getSuffix = () => {
    const m = $('#filterMonth option:selected').text() || '';
    const y = $('#filterYear').val() || '';
    return (m && y) ? `${m} ${y}` : (m || y);
  };

  initReportDT('#empTable',    'Meal Billing - Employee', 'meal_billing_employee',    getSuffix);
  initReportDT('#internTable', 'Meal Billing - Intern',   'meal_billing_intern',      getSuffix);
  initReportDT('#guestTable',  'Meal Billing - Guest',    'meal_billing_guest',       getSuffix);
});
</script>

<style>
  .dataTables_filter input { margin-left: .5rem; }
</style>
<?= $this->endSection() ?>
