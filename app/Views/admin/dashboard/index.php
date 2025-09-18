<?= $this->extend('layouts/admin') ?>
<?= $this->section('title') ?>Dashboard – bKash LMS<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
use CodeIgniter\I18n\Time;
// today in your timezone (change if needed)
$fmt = static fn($d) => Time::parse($d, 'Asia/Dhaka')->toLocalizedString('dd MMM yyyy');
$date_range = ($start_date === $end_date)
  ? $fmt($start_date)
  : $fmt($start_date) . ' to ' . $fmt($end_date);

$not_consumed = $registrations - $consumed;

$shortcutMenus = [
  ['icon' => 'bi-clipboard-check', 'text' => 'Lunch Subscription', 'url' => site_url('admin/subscription/new')],
  ['icon' => 'bi-person-x', 'text' => 'Subscription List', 'url' => site_url('admin/subscription/all-subscriptions')],
  ['icon' => 'bi-brightness-high', 'text' => 'Ifter Subscription', 'url' => site_url('admin/ifter-subscription/all-ifter-list')],
  ['icon' => 'bi-building', 'text' => 'Seheri Subscription', 'url' => site_url('admin/ramadan/sehri-subscription/all-sehri-list')],
  ['icon' => 'bi-moon-stars', 'text' => 'Eid Meal', 'url' => site_url('admin/eid-subscription/all-eid-subscription-list')],
  ['icon' => 'bi-megaphone', 'text' => 'Intern Subscription', 'url' => site_url('admin/intern-requisitions')],
];
?>

<!-- title + Download User Manual -->
<?= view('partials/title_user_manual_bar', 
  ['start_date'   => $start_date, 'end_date'   => $end_date]) ?>
<!-- title + Download User Manual end -->

<!-- Filter input -->
<?= view('partials/dashboard_filters', [
  'employeeTypes' => $employeeTypes,
  'employee_type' => $employee_type,
  'mealTypes'     => $mealTypes,
  'meal_type_id'  => $meal_type_id,
  'cafeterias'    => $cafeterias,
  'cafeteria_id'  => $cafeteria_id,
  'start_date'    => $start_date,
  'end_date'      => $end_date,
]) ?>
<!-- Filter input end -->



<!-- KPI Widgets (Bootstrap-only, compact) -->
<?php
$kpiData = [
  ['title' => 'Registrations', 'icon' => 'bi-people-fill', 'value' => $registrations],
  ['title' => 'Meals Consumed', 'icon' => 'bi-egg-fried', 'value' => $consumed],
  ['title' => 'Pending Approvals', 'icon' => 'bi-clock-history', 'value' => $pending],
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

<?= $this->endSection() ?>