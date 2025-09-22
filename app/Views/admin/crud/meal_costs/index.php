<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">Meal Cost Settings</h2>

  <?php if (can('admin.meal-costs.new')): ?>
    <a href="<?= site_url('meal-costs/new') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> New Meal Cost 
    </a>
  <?php endif; ?>

<table id="mealCostsTable" class="table table-hover w-100">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Meal Type</th>
      <th>Base Price</th>
      <th>Effective Date</th>
      <th>Status</th>
      <!-- <th>Actions</th> -->
    </tr>
  </thead>
  <tbody>
    <?php foreach (($costs ?? []) as $c): ?>
      <tr>
        <td><?= esc($c['id']) ?></td>
        <td><?= esc($c['meal_type']) ?></td>
        <td><?= number_format((float)$c['base_price'], 2) ?></td>
        <td data-order="<?= strtotime($c['effective_date']) ?>">
          <?= date('d M Y', strtotime($c['effective_date'])) ?>
        </td>
        <!-- <td><?= $c['is_active'] ? 'Active' : 'Inactive' ?></td> -->
        <td>
          <form action="<?= site_url('meal-costs/'.$c['id'].'/toggle') ?>" method="post" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm <?= $c['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
              <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
            </button>
          </form>
        </td>
        <!-- <td>
          <?php if (can('admin.meal-costs.edit')): ?>
            <a href="<?= site_url("meal-costs/{$c['id']}/edit") ?>"
              class="btn btn-sm btn-secondary">Edit</a>
          <?php endif; ?>
        </td> -->
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<?= view('partials/flash_message') ?>

<script>
$(function () {
  $('#mealCostsTable').DataTable({
    paging: true,
    pageLength: 10,
    lengthChange: true,
    ordering: true,
    order: [[3, 'desc']], // Effective Date (uses data-order timestamp)
    autoWidth: false,
    columnDefs: [
      { targets: -1, orderable: false, searchable: false } // Actions
    ],
    // Search + Excel button aligned to the right (like other lists)
    dom:
      "<'row'<'col-12 d-flex justify-content-md-end align-items-center gap-2'fB>>" +
      "rt" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    buttons: [
      {
        extend: 'excelHtml5',
        text: 'Excel Download',
        titleAttr: 'Download as Excel',
        title: 'Meal Cost Settings',
        filename: 'meal_cost_settings',
        className: 'btn btn-success btn-sm',
        exportOptions: { columns: [0,1,2,3,4] } // exclude Actions
      }
    ],
    language: {
      search: '',
      searchPlaceholder: 'Search by meal type, price…'
    }
  });
});
</script>
<?= $this->endSection() ?>
