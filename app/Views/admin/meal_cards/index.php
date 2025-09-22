<?= $this->extend('layouts/admin') ?>
<?= $this->section('title') ?>Meal Cards<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('partials/content_heading', [
  'heading' => 'Meal Cards',
  'add_btn' => can('admin.meal-cards.form') ? ['Add Meal Card', 'meal-cards/new'] : null
]) ?>

<?= view('partials/flash_message') ?>

<div class="row g-2 mb-3">
  <div class="col-md-3">
    <input id="fCardCode" type="text" class="form-control form-control-sm" placeholder="Filter: Card Code">
  </div>
  <div class="col-md-3">
    <input id="fUser" type="text" class="form-control form-control-sm" placeholder="Filter: User (name/email)">
  </div>
  <div class="col-md-3">
    <input id="fEmpId" type="text" class="form-control form-control-sm" placeholder="Filter: Employee ID">
  </div>
  <div class="col-md-3">
    <button id="btnReset" class="btn btn-outline-secondary btn-sm">Reset</button>
  </div>
</div>

<div class="table-responsive">
<table id="cardsTable" class="table table-hover align-middle w-100">
  <thead>
    <tr>
      <th>#</th>
      <th>Card Code</th>
      <th>User</th>
      <th>Employee ID</th>
      <th>Status</th>
      <th>Created</th>
      <th class="text-end">Actions</th>
    </tr>
  </thead>
  <tbody>

    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= $r['id'] ?></td>
        <td><code><?= esc($r['card_code']) ?></code></td>
        <td>
          <?php if (!empty($r['user_id'])): ?>
            <?= esc($r['user_name'] ?? 'User #'.$r['user_id']) ?>
            <div class="small text-muted"><?= esc($r['user_email'] ?? '') ?></div>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td><?= esc($r['employee_id'] ?: '—') ?></td>
        <td>
          <span class="badge <?= $r['status']==='ACTIVE'?'bg-success':'bg-secondary' ?>">
            <?= esc($r['status']) ?>
          </span>
        </td>
        <td><?= date('d M Y h:i A', strtotime($r['created_at'])) ?></td>
        <td class="text-end">
          <?php if (can('admin.meal-cards.form')): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('meal-cards/'.$r['id'].'/edit') ?>">
              <i class="bi bi-pencil-square"></i> Edit
            </a>
          <?php endif; ?>
          <?php if (can('meal.cards.delete')): ?>
            <!-- <form class="d-inline" method="post" action="<?= site_url('meal-cards/'.$r['id'].'/delete') ?>" onsubmit="return confirm('Delete this meal card?')">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger" type="submit">
                <i class="bi bi-trash"></i> Delete
              </button>
            </form> -->
          <?php endif; ?>
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
    const table = $('#cardsTable').DataTable({
    responsive: true,
    pageLength: 25,
    lengthMenu: [10,25,50,100, -1],
    order: [[0, 'desc']], // by id
    columnDefs: [
      { targets: 6, orderable: false, searchable: false, className: 'text-end' }, // actions
    ],
    // ⬇⬇ added for Excel export + top‑right placement
    dom:
      "<'row'<'col-sm-12 col-md-6'l>" +
      "<'col-sm-12 col-md-6 d-flex justify-content-end align-items-center gap-2'fB>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    buttons: [{
      extend: 'excelHtml5',
      text: '<i class="bi bi-file-earmark-excel"></i> Download Excel',
      className: 'btn btn-success btn-sm',
      titleAttr: 'Export to Excel',
      title: 'Meal Cards',
      exportOptions: {
        // export everything except the Actions column
        columns: [0,1,2,3,4,5],
        // strip HTML (name + email cell) for clean Excel text
        format: {
          body: function (data/*, row, column, node*/) {
            return $('<div>').html(data).text().trim().replace(/\s+/g, ' ');
          }
        }
      }
    }]
  });


  // small debounce helper so we don't redraw on every keystroke
  function debounce(fn, wait){ let t; return function(){ clearTimeout(t); t=setTimeout(() => fn.apply(this, arguments), wait); }; }


  // Column indexes: 0=id, 1=card_code, 2=user, 3=employee_id
  const $fCard = $('#fCardCode');
  const $fUser = $('#fUser');
  const $fEmp  = $('#fEmpId');

  $fCard.on('keyup change', debounce(function(){
    table.column(1).search(this.value).draw();
  }, 200));

  $fUser.on('keyup change', debounce(function(){
    // User column contains name + email (HTML). DataTables searches text content, so plain search works.
    table.column(2).search(this.value).draw();
  }, 200));

  $fEmp.on('keyup change', debounce(function(){
    table.column(3).search(this.value).draw();
  }, 200));

  $('#btnReset').on('click', function(){
    $fCard.val(''); $fUser.val(''); $fEmp.val('');
    table.search('').columns().search('').draw();
  });
});
</script>
<?= $this->endSection() ?>
