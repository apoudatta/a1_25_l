<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Occasions</h2>

  <?php if (can('admin.occasions.new')): ?>
    <a href="<?= site_url('admin/occasions/new') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> New Occasion
    </a>
  <?php endif; ?>

</div>

<div class="table-responsive">
  <table id="dtOccasions" class="table table-sm table-striped table-hover align-middle" style="width:100%">
    <thead class="table-light">
      <tr>
        <th style="width:80px">ID</th>
        <th>Name</th>
        <th style="min-width:160px">Date</th>
        <th style="width:160px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= esc($r['name']) ?></td>
          <!-- data-order ensures correct sorting while showing formatted date -->
          <td data-order="<?= esc($r['occasion_date']) ?>">
            <?= esc(date('d M Y', strtotime($r['occasion_date']))) ?>
          </td>
          <td>
          <?php if (can('admin.occasions.edit')): ?>
            <a href="<?= site_url("admin/occasions/{$r['id']}/edit") ?>" class="btn btn-sm btn-secondary me-1">Edit</a>
            <?php endif; ?>
            <!-- <form action="<?= site_url("admin/occasions/{$r['id']}") ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this occasion?')">
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
  const dt = $('#dtOccasions').DataTable({
    pageLength: 25,
    lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
    order: [[2, 'desc']], // sort by Date column
    dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
         't' +
         '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    buttons: [{
      extend: 'excelHtml5',
      text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
      className: 'btn btn-success btn-sm px-3',
      title: 'Occasions',
      filename: 'occasions',
      exportOptions: { columns: [0,1,2] } // exclude Actions
    }]
  });

  // Move Excel button beside the search input (right side)
  const $filter = $('#dtOccasions_wrapper .dataTables_filter');
  dt.buttons().container().appendTo($filter).addClass('ms-2');
  $filter.addClass('d-flex align-items-center justify-content-end gap-2');
});
</script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<?= $this->endSection() ?>