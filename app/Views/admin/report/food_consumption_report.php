<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-3"><?= esc($title) ?></h2>

<form class="row row-cols-1 row-cols-md-auto g-2 align-items-end mb-3" method="get" action="">
  <div class="col">
    <label class="form-label small mb-1">Date</label>
    <input type="text" name="date" id="fDate"
           class="form-control form-control-sm datepicker minw-200"
           value="<?= esc($filters['date'] ?? '', 'attr') ?>"
           placeholder="YYYY-MM-DD">
  </div>

  <div class="col">
    <label class="form-label small mb-1">Month</label>
    <select class="form-select form-select-sm minw-160" name="month" id="fMonth">
      <?php $mNow = (int) ($filters['month'] ?? date('n'));
      for ($m=1;$m<=12;$m++): $label=date('F', mktime(0,0,0,$m,1)); ?>
        <option value="<?= $m ?>" <?= $mNow===$m?'selected':'' ?>><?= esc($label) ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col">
    <label class="form-label small mb-1">Year</label>
    <select class="form-select form-select-sm minw-120" name="year" id="fYear">
      <?php $yNow = (int) ($filters['year'] ?? date('Y'));
      for ($y=date('Y')-1; $y<=date('Y')+1; $y++): ?>
        <option value="<?= $y ?>" <?= $yNow===$y?'selected':'' ?>><?= $y ?></option>
      <?php endfor ?>
    </select>
  </div>

  <div class="col">
    <label class="form-label small mb-1">Employment Type</label>
    <?php $eid = (int) ($filters['emp_type_id'] ?? 0) ?>
    <select name="type" id="filterType" class="form-select form-select-sm minw-220">
      <option value="">All</option>
      <?php foreach (($employmentTypes ?? []) as $et): ?>
        <?php $name = (string) $et['name']; ?>
        <option value="<?= (int)$et['id'] ?>" <?= $eid === (int)$et['id'] ? 'selected' : '' ?>>
          <?= esc($name) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>


  <div class="col">
    <label class="form-label small mb-1">Meal Type</label>
    <select name="meal_type_id" id="fMealType" class="form-select form-select-sm minw-220">
      <?php $mid = (int) ($filters['meal_type_id'] ?? 0) ?>
      <option value="">All</option>
      <?php foreach (($mealTypes ?? []) as $mt): ?>
        <option value="<?= (int)$mt['id'] ?>" <?= $mid===(int)$mt['id']?'selected':'' ?>>
          <?= esc($mt['name']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col">
    <label class="form-label small mb-1">Meal Availing Location</label>
    <select name="cafeteria_id" id="fCafeteria" class="form-select form-select-sm minw-220">
      <?php $cid = (int) ($filters['cafeteria_id'] ?? 0) ?>
      <option value="">All</option>
      <?php foreach (($cafeterias ?? []) as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cid===(int)$c['id']?'selected':'' ?>>
          <?= esc($c['name']) ?>
        </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col">
    <button type="submit" class="btn btn-primary btn-sm px-4">Apply</button>
  </div>
</form>

<div class="table-responsive">
  <table id="fcTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>Date</th>
        <th>Month</th>
        <th>Year</th>
        <th class="text-end">Subscription Count</th>
        <th class="text-end">Consumption Count</th>
        <th>Meal Availing Location</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td class="text-nowrap"><?= esc($r['subs_date']) ?></td>
          <td><?= esc($r['month']) ?></td>
          <td><?= esc($r['year']) ?></td>
          <td class="text-end"><?= (int) $r['subscription_count'] ?></td>
          <td class="text-end"><?= (int) $r['consumption_count'] ?></td>
          <td><?= esc($r['location']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Standardized DT header with Search + green Excel
if (typeof window.initReportDT !== 'function') {
  window.initReportDT = function (selector, titleBase, filenameBase, suffixFn) {
    var dt = $(selector).DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [[0,'asc'], [5,'asc']],
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
    const d = $('#fDate').val();
    const m = $('#fMonth option:selected').text();
    const y = $('#fYear').val();
    return d ? d : (m && y ? (m + ' ' + y) : '');
  };
  initReportDT('#fcTable', 'Food Consumption Report', 'food_consumption_report', suffix);
});
</script>

<?= $this->endSection() ?>
