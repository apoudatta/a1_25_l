<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Ramadan Periods</h2>
  <?php if (can('admin.ramadan-periods.create')): ?>
    <a href="<?= site_url('ramadan-periods/new') ?>" class="btn btn-primary">
      <i class="bi bi-calendar-plus me-2"></i> New Period
    </a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table id="dtRamadan" class="table table-sm table-striped table-hover align-middle" style="width:100%">
    <thead class="table-light">
      <tr>
        <th style="width:80px">ID</th>
        <th style="width:100px">Year</th>
        <th style="min-width:160px">Start Date</th>
        <th style="min-width:160px">End Date</th>
        <th style="width:160px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= (int)$r['year'] ?></td>
          <!-- Use data-order so DT sorts by raw YYYY-MM-DD while showing pretty date -->
          <td data-order="<?= esc($r['start_date']) ?>">
            <?= esc(date('d M Y', strtotime($r['start_date']))) ?>
          </td>
          <td data-order="<?= esc($r['end_date']) ?>">
            <?= esc(date('d M Y', strtotime($r['end_date']))) ?>
          </td>
          <td>
            <?php if (can('admin.ramadan-periods.edit')): ?>
              <a href="<?= site_url("ramadan-periods/{$r['id']}/edit") ?>" class="btn btn-sm btn-secondary me-1">Edit</a>
            <?php endif; ?>
            <!-- <form action="<?= site_url("ramadan-periods/{$r['id']}") ?>"
                  method="post" class="d-inline"
                  onsubmit="return confirm('Delete this period?')">
              <?= csrf_field() ?>
              <input type="hidden" name="_method" value="DELETE">
              <button class="btn btn-sm btn-danger">Delete</button>
            </form> -->
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
  const dt = $('#dtRamadan').DataTable({
    pageLength: 25,
    lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
    order: [[1,'desc'], [2,'asc']], // Year desc, then Start Date
    dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
         't' +
         '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    buttons: [{
      extend: 'excelHtml5',
      text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
      className: 'btn btn-success btn-sm px-3',
      title: 'Ramadan Periods',
      filename: 'ramadan_periods',
      exportOptions: { columns: [0,1,2,3] } // exclude Actions
    }]
  });

  // Move Excel button next to the search input
  const $filter = $('#dtRamadan_wrapper .dataTables_filter');
  dt.buttons().container().appendTo($filter).addClass('ms-2');
  $filter.addClass('d-flex align-items-center justify-content-end gap-2');
});
</script>
<?= $this->endSection() ?>
