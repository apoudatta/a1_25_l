<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-3"><?= esc($title) ?></h2>

<form class="row row-cols-1 row-cols-md-auto g-2 align-items-end mb-3" method="get" action="" id="dailyFilters">
  <div class="col">
    <label class="form-label small mb-1">Date</label>
    <input
      type="text"
      name="date"
      id="filterDate"
      class="form-control form-control-sm datepicker minw-220"
      value="<?= esc($filters['date'] ?? '', 'attr') ?>"
      placeholder="YYYY-MM-DD"
    >
  </div>

  <div class="col">
    <label class="form-label small mb-1">Employment Type</label>
    <?php $t = (string) ($filters['type'] ?? '') ?>
    <select name="type" id="filterType" class="form-select form-select-sm minw-220">
      <option value="">All</option>
      <?php foreach (($employmentTypes ?? []) as $et): ?>
        <?php $name = (string) $et['name']; ?>
        <option value="<?= esc($name) ?>" <?= $t === $name ? 'selected' : '' ?>>
          <?= esc($name) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>


  <div class="col">
    <label class="form-label small mb-1">Meal Type</label>
    <select name="meal_type_id" id="filterMealType" class="form-select form-select-sm minw-220">
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
    <select name="cafeteria_id" id="filterCafeteria" class="form-select form-select-sm minw-220">
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
  <table id="dailyMealTable" class="table table-sm table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Date</th>
        <th>Employment Type</th>
        <th>Meal Type</th>
        <th>Meal Availing Location</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><?= esc($r['id']) ?></td>
          <td><?= esc($r['name']) ?></td>
          <td class="text-nowrap"><?= esc(date('d-M-Y', strtotime($r['date_val']))) ?></td>
          <td><?= esc(mb_convert_case($r['emp_type'], MB_CASE_TITLE, 'UTF-8')) ?></td>
          <td><?= esc($r['meal_type']) ?></td>
          <td><?= esc($r['location']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// If you don't already have a global initializer, this provides the same Search + green Excel button UI.
if (typeof window.initReportDT !== 'function') {
  window.initReportDT = function (selector, titleBase, filenameBase, suffixFn) {
    var dt = $(selector).DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [[1,'asc']],
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
  // (Optional) initialize your datepicker here if not global
  // flatpickr('.datepicker', { dateFormat: 'Y-m-d', allowInput: true });

  const suffix = () => {
    const d = $('#filterDate').val() || '';
    const t = $('#filterType option:selected').text() || '';
    return [d, t !== 'All' ? t : ''].filter(Boolean).join(' - ');
  };

  initReportDT('#dailyMealTable', 'Daily Meal Report', 'daily_meal_report', suffix);
});
</script>

<style>
  #dailyMealTable_wrapper .dataTables_filter input { margin-left: .5rem; }
</style>
<?= $this->endSection() ?>
