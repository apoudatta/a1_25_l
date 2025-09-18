<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">Daily Registrations – <?= esc($monthLabel) ?></h4>
<canvas id="monthlyChart" height="200"></canvas>

<script>
  // Data rendered server-side into JS variables
  const labels = <?= json_encode(array_map(fn($r)=>substr($r->day,8), $rows)) ?>;
  const data   = <?= json_encode(array_map(fn($r)=>(int)$r->cnt, $rows)) ?>;

  new Chart($('#monthlyChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Registrations',
        data,
        fill: false
      }]
    }
  });
</script>

<?= $this->endSection() ?>
