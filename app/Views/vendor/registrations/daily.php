<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h2 class="mb-3">Registrations on <?= esc($date) ?></h2>

<table class="table table-hover">
  <thead>
    <tr>
      <th>#</th><th>User ID</th><th>Meal Type ID</th>
      <th>Cafeteria ID</th><th>Period</th><th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($subs as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= esc($s['user_id']) ?></td>
      <td><?= esc($s['meal_type_id']) ?></td>
      <td><?= esc($s['cafeteria_id']) ?></td>
      <td><?= esc($s['start_date']) ?> → <?= esc($s['end_date']) ?></td>
      <td><?= esc($s['status']) ?></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
