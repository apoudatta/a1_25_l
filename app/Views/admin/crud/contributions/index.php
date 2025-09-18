<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Contribution Rules</h2>

  <?php if (can('admin.contributions.new')): ?>
  <a href="<?= site_url('admin/contributions/new') ?>" class="btn btn-primary">
    <i class="bi bi-plus-lg"></i> New Rule
  </a>
  <?php endif; ?>
</div>


<table id="dtContrib" class="table table-sm table-striped table-hover align-middle" style="width:100%">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>Meal Type</th>
      <th>User Type</th>
      <th>Base Price</th>
      <th>Company %</th>
      <th>User %</th>
      <th>Company TK</th>
      <th>User TK</th>
      <th>Effective Date</th>
      <th style="width:140px">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= esc($r['meal_type']) ?></td>
        <td><?= esc($r['user_type']) ?></td>
        <td><?= number_format((float)$r['base_price'], 2) ?></td>
        <td><?= number_format((float)$r['company_contribution'], 2) ?>%</td>
        <td><?= number_format((float)$r['user_contribution'], 2) ?>%</td>
        <td><?= number_format((float)$r['company_tk'], 2) ?></td>
        <td><?= number_format((float)$r['user_tk'], 2) ?></td>
        <td><?= esc(date('d M Y', strtotime($r['effective_date']))) ?></td>
        <td>
          <?php if (can('admin.contributions.edit')): ?>
            <a href="<?= site_url("admin/contributions/{$r['id']}/edit") ?>" class="btn btn-sm btn-secondary me-1">Edit</a>
          <?php endif; ?>

          <form action="<?= site_url("admin/contributions/{$r['id']}") ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this rule?')">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button class="btn btn-sm btn-danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(function () {
    const dt = $('#dtContrib').DataTable({
      pageLength: 25,
      lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
      order: [[0, 'desc']], // ID desc
      // Header layout: left = length dropdown, right = search box
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
        exportOptions: { columns: [0,1,2,3,4,5,6,7,8] } // exclude Actions
      }]
    });

    // Move the Excel button next to the search input (right side)
    const $filter = $('#dtContrib_wrapper .dataTables_filter');
    dt.buttons().container().appendTo($filter).addClass('ms-2');
    $filter.addClass('d-flex align-items-center justify-content-end gap-2');
  });
</script>
<?= $this->endSection() ?>
