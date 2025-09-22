<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Cut-Off Times</h2>
  <?php if (can('admin.cutoff-times.new')): ?>
    <a href="<?= site_url('cutoff-times/new') ?>" class="btn btn-primary">+ New Entry</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
  <table id="dtCutoff" class="table table-sm table-striped table-hover align-middle" style="width:100%">
    <thead class="table-light">
      <tr>
        <th style="width:70px">#</th>
        <th>Meal Type</th>
        <th>Cut-Off Time</th>
        <th>Lead Days</th>
        <th>Horizon Days</th>
        <th style="width:100px">Active?</th>
        <th style="width:150px">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= esc($r['meal_type']) ?></td>
          <?php
            $tm = (string)($r['cut_off_time'] ?? '');
            $dt = DateTime::createFromFormat('H:i:s', $tm) ?: DateTime::createFromFormat('H:i', $tm);
            $nice = $dt ? $dt->format('g:i A') : $tm; // e.g., "4:00 PM"
          ?>
          <td><?= esc($nice) ?></td>
          <td><?= (int)$r['lead_days'] ?></td>
          <td><?= (int)$r['max_horizon_days'] ?></td>
          <td>
            <?php if ((int)$r['is_active'] === 1): ?>
              <span class="badge bg-success">Yes</span>
            <?php else: ?>
              <span class="badge bg-danger">No</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (can('admin.cutoff-times.edit')): ?>
              <a href="<?= site_url("cutoff-times/{$r['id']}/edit") ?>" class="btn btn-sm btn-secondary me-1">Edit</a>
            <?php endif; ?>
            <!-- <form action="<?= site_url("cutoff-times/{$r['id']}")?>" method="post" class="d-inline" onsubmit="return confirm('Delete this?')">
              <?= csrf_field() ?>
              <input type="hidden" name="_method" value="DELETE">
              <button class="btn btn-sm btn-danger">Del</button>
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
  const dt = $('#dtCutoff').DataTable({
    pageLength: 25,
    lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
    order: [[0,'desc']], // by ID
    dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
         't' +
         '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    buttons: [{
      extend: 'excelHtml5',
      text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
      className: 'btn btn-success btn-sm px-3',
      title: 'Cut-Off Times',
      filename: 'cutoff_times',
      exportOptions: { columns: [0,1,2,3,4,5] } // exclude Actions
    }]
  });

  // Put Excel button next to the search input (right side)
  const $filter = $('#dtCutoff_wrapper .dataTables_filter');
  dt.buttons().container().appendTo($filter).addClass('ms-2');
  $filter.addClass('d-flex align-items-center justify-content-end gap-2');
});
</script>
<?= $this->endSection() ?>
