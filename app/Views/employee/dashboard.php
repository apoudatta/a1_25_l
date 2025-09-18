<?= $this->extend('layouts/employee') ?>
<?= $this->section('title') ?>Employee Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
$not_consumed = $registrations - $consumed;

$shortcutMenus = [
  ['icon' => 'bi-clipboard-check', 'text' => 'Lunch Registration', 'url' => site_url('employee/subscription/new')],
  ['icon' => 'bi-person-x', 'text' => 'Registration List', 'url' => site_url('employee/subscription')],
  ['icon' => 'bi-brightness-high', 'text' => 'Ifter Registration', 'url' => site_url('employee/ifter-subscription')],
  ['icon' => 'bi-building', 'text' => 'Seheri', 'url' => site_url('employee/ramadan/sehri-subscription')],
  ['icon' => 'bi-moon-stars', 'text' => 'Eid Meal', 'url' => site_url('employee/eid-subscription')],
];
?>

<!-- title + Download User Manual -->
<?= view('partials/title_user_manual_bar', 
  ['start_date'   => $start_date, 'end_date'   => $end_date]) ?>
<!-- title + Download User Manual end -->


<!-- Filter input -->
<?= view('partials/dashboard_filters', [
  'cafeterias'   => $cafeterias,
  'cafeteria_id' => $cafeteria_id,
  'start_date'   => $start_date,
  'end_date'     => $end_date,
]) ?>
<!-- Filter input end -->


<!-- KPI Widgets (Bootstrap-only, compact) -->
<?php
$kpiData = [
  ['title' => 'Total Registration this Month', 'icon' => 'bi-people-fill', 'value' => $registrations],
  ['title' => 'Total Consumed this Month', 'icon' => 'bi-egg-fried', 'value' => $consumed],
  ['title' => 'Guest Subs', 'icon' => 'bi-clock-history', 'value' => $guest],
];
?>
<div class="row g-1 mb-2">
  <?php foreach ($kpiData as $kpi): ?>
    <div class="col-12 col-sm-6 col-lg-4">
      <div class="card shadow-sm border-1 h-100">
        <div class="card-body py-2 px-2 d-flex flex-column align-items-center text-center">
          <i class="bi <?= $kpi['icon'] ?> text-warning fs-2 mb-1"></i>
          <div class="text-muted small mb-1"><?= $kpi['title'] ?></div>
          <div class="fw-bold fs-2 lh-1 mb-0"><?= esc($kpi['value']) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>
<!-- KPI Widgets end -->


<!-- Charts -->
<div class="row">
  <?= view(
    'partials/dashboard_chart',
    [
      'total_registrations' => $registrations,
      'meal_consumed' => $consumed,
      'not_consumed' => $not_consumed,
    ]
  ) ?>
  
  <?= view('partials/dashboard_menu_shortcuts', ['shortcutMenus' => $shortcutMenus]) ?>
</div>
<!-- Charts end -->


<?= $this->endSection() ?>