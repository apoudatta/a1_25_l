<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">Meal Counts for <?= esc($date) ?></h4>

<table class="table table-hover">
  <thead>
    <tr><th>Meal Type</th><th>Count</th></tr>
  </thead>
  <tbody>
    <?php foreach($data as $row): ?>
      <tr>
        <td><?= esc($row['type']) ?></td>
        <td><?= esc($row['count']) ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
