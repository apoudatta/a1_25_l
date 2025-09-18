<?= $this->extend('layouts/employee') ?>
<?= $this->section('content') ?>

<h4 class="mb-2">My Subscriptions - (Eid)</h4>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>


<table id="subscriptionTable" class="table table-bordered table-striped nowrap w-100">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Meal Type</th>
      <th>Cafeteria</th>
      <th>Date</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
<?php
  $today = new DateTime('today'); // start of today
  $now   = new DateTime();        // current time
?>
<?php foreach($subs as $index => $s): ?>
  <?php
    // Meal date
    $mealDate = new DateTime($s['subscription_date']);

    // Cutoff = (mealDate - lead_days) @ cut_off_time
    $leadDays = isset($s['lead_days']) ? (int)$s['lead_days'] : 0;
    [$hh,$mm,$ss] = array_map('intval', array_pad(explode(':', $s['cutoff_time'] ?? '00:00:00'), 3, 0));
    $deadline = (clone $mealDate)->modify("-{$leadDays} days")->setTime($hh, $mm, $ss);

    // Button only if ACTIVE + meal date is strictly future + before deadline
    $canUnsubscribe = ($s['status'] === 'ACTIVE') && ($mealDate > $today) && ($now < $deadline);
  ?>
  <tr id="row-<?= $s['id'] ?>">
    <td><?= esc($index + 1) ?></td>
    <td><?= esc($s['meal_type_name']) ?></td>
    <td><?= esc($s['caffname']) ?></td>
    <td data-order="<?= esc($s['subscription_date']) ?>"><?= date('d M Y', strtotime($s['subscription_date'])) ?></td>
    <td class="status-cell"><?= esc($s['status']) ?></td>
    <td>
      <?php if ($canUnsubscribe): ?>
        <?php if (can('employee.eid.unsubscribe')): ?>
          <form method="post" action="<?= site_url("employee/eid-subscription/unsubscribe/{$s['id']}")?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" id="unsubscribe_btn_<?= esc($s['id'], 'attr') ?>" class="btn btn-sm btn-danger">Unsubscribe</button>
          </form>
        <?php endif; ?>
      <?php endif ?>
    </td>
  </tr>
<?php endforeach ?>
</tbody>

</table>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
  // Initialize DataTable and capture instance
  dataTableInit('#subscriptionTable', 'My_Subscriptions_Eid');
</script>
<?= $this->endSection() ?>