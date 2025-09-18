<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-3"><?= esc($title) ?></h2>

<!-- Filters (Month, Year, Employee ID, Type) -->
<form class="row g-2 align-items-end mb-3" method="get" action="">
  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Month</label>
    <select
      class="form-select form-select-sm"
      name="month"
      id="filterMonth"
    >
      <?php
      $mNow = (int) ($filters['month'] ?? date('n'));
      for ($m = 1; $m <= 12; $m++):
        $label = date('F', mktime(0,0,0,$m,1));
      ?>
        <option
          value="<?= $m ?>"
          <?= $mNow === $m ? 'selected' : '' ?>
        ><?= esc($label) ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Year</label>
    <select
      class="form-select form-select-sm"
      name="year"
      id="filterYear"
    >
      <?php
      $yNow   = (int) ($filters['year'] ?? date('Y'));
      $yStart = date('Y') - 2;
      $yEnd   = date('Y') + 1;
      for ($y = $yStart; $y <= $yEnd; $y++):
      ?>
        <option
          value="<?= $y ?>"
          <?= $yNow === (int)$y ? 'selected' : '' ?>
        ><?= $y ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label small mb-1">Employee ID</label>
    <input
      type="text"
      class="form-control form-control-sm"
      name="employee_id"
      id="filterEmpId"
      value="<?= esc($filters['employee_id'] ?? '', 'attr') ?>"
      placeholder="e.g. 1235"
    >
  </div>

  <div class="col-6 col-md-3">
    <label class="form-label small mb-1">Type</label>
    <select
      class="form-select form-select-sm"
      name="type"
      id="filterType"
    >
      <?php $curType = (string) ($filters['type'] ?? '') ?>
      <option value="">All</option>
      <option value="EMPLOYEE" <?= $curType==='EMPLOYEE' ? 'selected':'' ?>>EMPLOYEE</option>
      <option value="INTERN"   <?= $curType==='INTERN'   ? 'selected':'' ?>>INTERN</option>
      <option value="GUEST"    <?= $curType==='GUEST'    ? 'selected':'' ?>>GUEST</option>
      <option value="OS"    <?= $curType==='OS'    ? 'selected':'' ?>>OS</option>
      <option value="Security Guard"    <?= $curType==='Security Guard'    ? 'selected':'' ?>>Security Guard</option>
      <option value="Support Staff"    <?= $curType==='Support Staff'    ? 'selected':'' ?>>Support Staff</option>
    </select>
  </div>

  <div class="col-12 col-md-2">
    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
  </div>
</form>

<div class="table-responsive">
  <table id="reportTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>Emp. ID</th>
        <th>Emp. Name</th>
        <th>Designation</th>
        <th>Division</th>
        <th>Job Location</th>
        <th>Month'Year</th>
        <th class="text-end">Day Count</th>
        <th class="text-end">Meal Charge</th>
      </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= esc($r['emp_id']) ?></td>
            <td><?= esc($r['emp_name']) ?></td>
            <td><?= esc($r['designation']) ?></td>
            <td><?= esc($r['division']) ?></td>
            <td><?= esc($r['job_location']) ?></td>
            <td><?= esc($r['month_year']) ?></td>
            <td class="text-end"><?= (int) $r['day_count'] ?></td>
            <td class="text-end"><?= number_format((float) $r['meal_charge'], 2) ?></td>
          </tr>
        <?php endforeach ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var $table = $('#reportTable');

  var dt = $table.DataTable({
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1],[10, 25, 50, 100, "All"]],
    order: [[0, 'asc']],
    dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
         't' +
         '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    buttons: [
      {
        extend: 'excelHtml5',
        text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
        className: 'btn btn-success btn-sm px-3',
        titleAttr: 'Download as Excel',
        title: function(){
          var m = $('#filterMonth option:selected').text();
          var y = $('#filterYear').val();
          return 'Meal Charge list for payroll - ' + m + ' ' + y;
        },
        filename: function(){
          var y = $('#filterYear').val();
          var m = $('#filterMonth').val();
          return 'meal_charge_list_for_payroll_' + y + '_' + m;
        },
        exportOptions: { columns: ':visible' }
      }
    ]
  });

  // Move the button next to the search input
  var $filter = $('#reportTable_wrapper .dataTables_filter');
  dt.buttons().container().appendTo($filter);
  $filter.addClass('d-flex align-items-center justify-content-end gap-2');
});
</script>

<style>
  /* Optional: tighten the layout a touch */
  #reportTable_wrapper .dataTables_filter input { margin-left: .5rem; }
</style>
<?= $this->endSection() ?>
