<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Public Holidays</h2>

  <?php if (can('admin.public-holidays.new')): ?>
    <a href="<?= site_url('public-holidays/new') ?>" class="btn btn-primary">
      <i class="bi bi-calendar-plus me-2"></i>Add Holiday
    </a>
  <?php endif; ?>

</div>

<div class="table-responsive">
  <table id="dtHolidays" class="table table-sm table-striped table-hover align-middle" style="width:100%">
    <thead class="table-light">
      <tr>
        <th style="width:80px">ID</th>
        <th style="min-width:160px">Date</th>
        <th>Description</th>
        <th style="width:100px">Active</th>
        <th style="width:160px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $h): ?>
        <tr>
          <td><?= (int) $h['id'] ?></td>
          <!-- use data-order so DT sorts correctly even with formatted date -->
          <td data-order="<?= esc($h['holiday_date']) ?>">
            <?= esc(date('d M Y', strtotime($h['holiday_date']))) ?>
          </td>
          <td><?= esc($h['description']) ?></td>
          <td>
            <?php if ((int)$h['is_active'] === 1): ?>
              <span class="badge bg-success">Yes</span>
            <?php else: ?>
              <span class="badge bg-secondary">No</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (can('admin.public-holidays.edit')): ?>
              <a href="<?= site_url("public-holidays/{$h['id']}/edit") ?>" class="btn btn-sm btn-secondary me-1">Edit</a>
            <?php endif; ?>
              <!-- <form action="<?= site_url("public-holidays/{$h['id']}") ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this holiday?')">
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
    const dt = $('#dtHolidays').DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [[1,'desc']], // by Date
      // Header: left = length, right = search
      dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>'
         + 't'
         + '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      buttons: [{
        extend: 'excelHtml5',
        text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
        className: 'btn btn-success btn-sm px-3',
        titleAttr: 'Download as Excel',
        title: 'Public Holidays',
        filename: 'public_holidays',
        exportOptions: { columns: [0,1,2,3] } // exclude Actions
      }]
    });

    // Place Excel button beside the search box
    const $filter = $('#dtHolidays_wrapper .dataTables_filter');
    dt.buttons().container().appendTo($filter).addClass('ms-2');
    $filter.addClass('d-flex align-items-center justify-content-end gap-2');
  });
</script>
<?= $this->endSection() ?>
