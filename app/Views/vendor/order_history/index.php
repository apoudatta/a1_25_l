<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">Order History</h4>

<form id="orderHistoryForm" class="row g-3 mb-4" method="get"
      action="<?= site_url('vendor/history') ?>">
  <div class="col-auto">
    <label class="form-label">From</label>
    <input type="text" name="start_date"
           class="form-control datepicker"
           value="<?= esc($start) ?>" required>
  </div>
  <div class="col-auto">
    <label class="form-label">To</label>
    <input type="text" name="end_date"
           class="form-control datepicker"
           value="<?= esc($end) ?>" required>
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary">View</button>
  </div>
  <!-- <div class="col-auto align-self-end">
    <form method="post" action="<?= site_url('vendor/history/export') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="start_date" value="<?= esc($start) ?>">
      <input type="hidden" name="end_date"   value="<?= esc($end) ?>">
      <button class="btn btn-outline-secondary">
        Export CSV
      </button>
    </form>
  </div> -->
</form>

<div id="orderHistoryResults">
  <?php if (empty($rows)): ?>
    <div class="alert alert-info">No data for the selected period.</div>
  <?php else: ?>
    <table class="table table-hover">
      <thead>
        <tr><th>Date</th><th>Meal Type Name</th><th>Count</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r->day) ?></td>
          <td><?= esc($r->meal_type_name) ?></td>
          <td><?= esc($r->cnt) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  <?php endif ?>
</div>

<?= $this->endSection() ?>
