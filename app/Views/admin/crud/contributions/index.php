<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?= view('partials/flash_message') ?>


<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Contributions</h4>
  <a href="<?= site_url('contributions/new') ?>" class="btn btn-primary btn-sm">New</a>
</div>


<div class="table-responsive">
  <table id="dtContrib" class="table table-striped align-middle">
    <thead>
      <tr class="table-danger">
        <th style="width:80px;">ID</th>
        <th>Meal Type</th>
        <th>User Type</th>
        <th>Cafeteria</th>
        <th class="text-end">Base Price</th>
        <th class="text-end">Company Tk</th>
        <th class="text-end">User Tk</th>
        <th style="width:140px;">Active?</th>
        <!-- <th style="width:220px;">Actions</th> -->
      </tr>
    </thead>
    <tbody>

      <?php foreach ($rows as $r): ?>
        <tr data-id="<?= (int) $r['id'] ?>">
          <td><?= esc($r['id']) ?></td>
          <td><?= esc($r['meal_type_name'] ?? (new \App\Models\MealTypeModel())->find($r['meal_type_id'])['name'] ?? 'Unknown') ?></td>
          <td><?= esc($r['emp_type_name'] ?? 'ALL') ?></td>
          <td><?= esc($r['cafeteria_name'] ?? 'All Cafeterias') ?></td>
          <td class="text-end"><?= number_format((float)$r['base_price'], 2) ?></td>
          <td class="text-end"><?= number_format((float)$r['company_tk'], 2) ?></td>
          <td class="text-end"><?= number_format((float)$r['user_tk'], 2) ?></td>
          <td>
            <!-- Toggle button (works with JS or plain POST) -->
            <form action="<?= site_url('contributions/'.$r['id'].'/toggle') ?>" method="post" class="d-inline js-toggle-form">
              <?= csrf_field() ?>
              <button type="submit"
                class="btn btn-sm <?= $r['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?> js-toggle-btn">
                <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
              </button>
            </form>
          </td>
          <!-- <td>
            <a href="<?= site_url('contributions/'.$r['id'].'/edit') ?>" class="btn btn-sm btn-secondary">Edit</a>
            <form action="<?= site_url('contributions/'.$r['id']) ?>" method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="_method" value="DELETE">
              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this contribution?')">Delete</button>
            </form>
          </td> -->
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?= $pager->links('group1', 'bootstrap_pagination') ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(function () {
    // Only run if DataTables is available
    if (!$.fn.DataTable) return;

    const dt = $('#dtContrib').DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [[0, 'desc']], // ID desc
      // keep your header layout; we’ll manually move the button next to the search above
      dom: '<"row mb-2"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
           't' +
           '<"row mt-2"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
      buttons: [{
        extend: 'excelHtml5',
        text: '<i class="bi bi-file-earmark-spreadsheet me-2"></i> Download Excel',
        className: 'btn btn-success btn-sm px-3',
        titleAttr: 'Download as Excel',
        title: 'Contribution Rules',
        filename: 'contribution_rules',
        // 9 columns total (0..8); exclude Actions (index 8)
        exportOptions: { columns: [0,1,2,3,4,5,6,7] }
      }]
    });

    // Put the Excel button next to the server-side search bar you already have
    const $filterRowRight = $('.input-group').parent(); // right column holding your "Search" button
    dt.buttons().container().appendTo($filterRowRight).addClass('ms-2');
  });
</script>
<?= $this->endSection() ?>

