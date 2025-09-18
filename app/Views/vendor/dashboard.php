<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h2 class="mb-4">Vendor Dashboard</h2>

<div class="widgets-row">
  <div class="card-widget">
    <div class="card-icon"><i class="bi bi-journal-check"></i></div>
    <div class="card-title">Registrations Today</div>
    <div class="card-value"><?= esc($registrations) ?></div>
  </div>
  <div class="card-widget">
    <div class="card-icon"><i class="bi bi-check2-circle"></i></div>
    <div class="card-title">Meals Redeemed</div>
    <div class="card-value"><?= esc($redeemed) ?></div>
  </div>
  <div class="card-widget">
    <div class="card-icon"><i class="bi bi-hourglass-split"></i></div>
    <div class="card-title">Pending Tokens</div>
    <div class="card-value"><?= esc($pending) ?></div>
  </div>
</div>

<?= $this->endSection() ?>
