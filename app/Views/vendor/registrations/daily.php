<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h2 class="mb-3">Registrations on <?= esc($date) ?></h2>

<table class="table table-hover">
  <thead>
    <tr>
      <th>#</th>
      <th>Meal Type Name</th>
      <th>Cafeteria Name</th>
      <th>Meal Date</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($subs as $s): ?>
    <tr>
      <td><?= $s['id'] ?></td>
      <td><?= esc($s['meal_type_name']) ?></td>
      <td><?= esc($s['cafeteria_name']) ?></td>
      <td><?= esc($s['subs_date']) ?></td>
      <td><?= esc($s['status']) ?></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
