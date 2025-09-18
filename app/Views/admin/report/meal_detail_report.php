<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-3"><?= esc($title) ?></h2>

<form class="row g-2 align-items-end mb-3" method="get" action="">
  <div class="col-12 col-md-3">
    <label class="form-label small mb-1">Employee ID</label>
    <input
      type="text"
      name="employee_id"
      id="filterEmpId"
      class="form-control form-control-sm"
      value="<?= esc($filters['employee_id'] ?? '', 'attr') ?>"
      placeholder="e.g. 1235"
    >
  </div>

  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Month</label>
    <select class="form-select form-select-sm" name="month" id="filterMonth">
      <option value="">-- Select --</option>
      <?php $mSel = (int) ($filters['month'] ?? 0);
      for ($m = 1; $m <= 12; $m++): $label = date('F', mktime(0,0,0,$m,1)); ?>
        <option value="<?= $m ?>" <?= $mSel===$m?'selected':'' ?>><?= esc($label) ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-6 col-md-2">
    <label class="form-label small mb-1">Year</label>
    <select class="form-select form-select-sm" name="year" id="filterYear">
      <?php $ySel = (int) ($filters['year'] ?? date('Y'));
      for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
        <option value="<?= $y ?>" <?= $ySel===$y?'selected':'' ?>><?= $y ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col-12 col-md-2">
    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
  </div>
</form>

<div class="table-responsive">
  <table id="detailTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>Emp. ID</th>
        <th>Month</th>
        <?php foreach (($headerDates ?? []) as $hd): ?>
          <th class="text-nowrap"><?= esc($hd) ?></th>
        <?php endforeach ?>
      </tr>
    </thead>
    <tbody>
      <?php if ($row): ?>
        <tr>
          <td><?= esc($row['emp_id']) ?></td>
          <td><?= esc($row['month_name']) ?></td>
          <?php foreach ($row['dates'] as $d): ?>
            <td class="text-nowrap"><?= esc($d) ?></td>
          <?php endforeach ?>
        </tr>
      <?php endif ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Fallback init (if not already defined globally)
if (typeof window.initReportDT !== 'function') {
  window.initReportDT = function (selector, titleBase, filenameBase, suffixFn) {
    var dt = $(selector).DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [], // no default ordering when headers are dynamic
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
  const suffix = () => {
    const emp = $('#filterEmpId').val() || '';
    const m   = $('#filterMonth option:selected').text() || '';
    const y   = $('#filterYear').val() || '';
    return [emp ? ('Emp ' + emp) : '', (m && y) ? (m + ' ' + y) : ''].filter(Boolean).join(' - ');
  };

  initReportDT('#detailTable', 'Meal Detail Report', 'meal_detail_report', suffix);
});
</script>

<style>
  /* keep the search + excel tight like other pages */
  #detailTable_wrapper .dataTables_filter input { margin-left: .5rem; }
</style>
<?= $this->endSection() ?>
