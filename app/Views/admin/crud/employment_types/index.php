<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Employment Types</h2>

  <?php if (can('admin.employment-types.new')): ?>
    <a href="<?= site_url('employment-types/create') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> New Employment Types
    </a>
  <?php endif; ?>

</div>

<div class="table-responsive">
  <table
    id="dtEmploymentTypes"
    class="table table-sm table-striped table-bordered align-middle"
    style="width:100%"
  >
    <thead class="table-light">
      <tr>
        <th style="width:70px">ID</th>
        <th style="min-width:220px">Name</th>
        <th>Description</th>
        <th style="width:110px">Status</th>
        <th style="width:160px">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= esc($r['name']) ?></td>
          <td class="text-truncate" style="max-width:480px">
            <?= esc($r['description'] ?? '') ?>
          </td>
          <td>
            <?php if ((int)$r['is_active'] === 1): ?>
              <span class="badge bg-success">Active</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (can('admin.employment-types.edit')): ?>
              <a
                class="btn btn-sm btn-outline-primary"
                href="<?= site_url('employment-types/'.$r['id'].'/edit') ?>"
              >Edit</a>
            <?php endif; ?>

            <!-- <a
              class="btn btn-sm btn-outline-danger"
              href="<?= site_url('employment-types/'.$r['id'].'/delete') ?>"
              onclick="return confirm('Delete this type? This cannot be undone.')"
            >Delete</a> -->
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<script>
  $(function () {
    const dt = $('#dtEmploymentTypes').DataTable({
      pageLength: 25,
      lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
      searching: true,
      ordering:  true,
      responsive: true,

      // Header layout: left = length, right = search
      dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           't' +
           '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',

      buttons: [{
        extend: 'excelHtml5',
        text: 'Excel',
        className: 'btn btn-secondary btn-sm',
        title: 'employment_types',
        exportOptions: { columns: [0,1,2,3] }
      }]
    });

    // Move the button beside the search input (right side)
    const $filter = $('#dtEmploymentTypes_wrapper .dataTables_filter');
    dt.buttons().container().appendTo($filter).addClass('ms-2');
    $filter.addClass('d-flex align-items-center justify-content-end gap-2');
  });
</script>


<?= $this->endSection() ?>

