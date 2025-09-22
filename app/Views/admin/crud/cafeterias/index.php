<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?= view('partials/flash_message') ?>

<!-- Heading row with action button on the right -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Cafeterias</h2>

  <?php if (can('admin.cafeterias.new')): ?>
    <a href="<?= site_url('cafeterias/new') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> New Cafeteria
    </a>
  <?php endif; ?>

</div>

<table id="cafeteriasTable" class="table table-hover w-100">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Location</th>
      <th>Active</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach (($cafeterias ?? []) as $c): ?>
      <tr>
        <td><?= esc($c['id']) ?></td>
        <td><?= esc($c['name']) ?></td>
        <td><?= esc($c['location']) ?></td>
        <td><?= $c['is_active'] ? 'Yes' : 'No' ?></td>
        <td>
          <?php if (can('admin.cafeterias.edit')): ?>
            <a href="<?= site_url("cafeterias/{$c['id']}/edit") ?>" class="btn btn-sm btn-secondary">Edit</a>
          <?php endif; ?>

          <form action="<?= site_url("cafeterias/{$c['id']}") ?>" method="post" class="d-inline"
                onsubmit="return confirm('Delete this cafeteria?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>

<script>
$(function () {
  $('#cafeteriasTable').DataTable({
    paging: true,
    pageLength: 10,
    lengthChange: true,
    ordering: true,
    order: [[0, 'desc']],
    autoWidth: false,
    columnDefs: [{ targets: -1, orderable: false, searchable: false }],
    dom:
      "<'row'<'col-12 d-flex justify-content-md-end align-items-center gap-2'fB>>" +
      "rt" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    buttons: [
      {
        extend: 'excelHtml5',
        text: 'Excel Download',          
        titleAttr: 'Download as Excel',    
        title: 'Cafeterias',
        filename: 'cafeterias',            
        className: 'btn btn-success btn-sm',
        exportOptions: { columns: [0,1,2,3] }
      }
    ],
    language: {
      search: '',
      searchPlaceholder: 'Search by name or location…'
    }
  });
});
</script>
<?= $this->endSection() ?>
