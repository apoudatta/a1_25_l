<?php
    helper(['auth','url']);
    $navpage = 'partials/sidebar/nav_items';
    $navPageSingle = 'partials/sidebar/nav_item';
?>
<ul class="nav nav-pills flex-column mb-auto w-100" id="sidebarMenu">

<?= view($navPageSingle, [
  'label'=>'Dashboard',
  'icon'=>'bi bi-speedometer2',
  'path'=>'vendor/dashboard',
  'perm' => 'vendor.dashboard',
]) ?>

<?= view($navpage,  [
  'label' => 'Registrations',
  'path' => 'vendor/registrations',
  'collapseName' => 'collapseRegistrations',
  'icon' => 'bi bi-person-video3',
  'perm' => 'vendor.registrations',
  'subMenu' => [
    ['label' => 'Daily', 'path' => 'vendor/registrations/daily', 'icon' => 'bi bi-journal-plus', 'perm' => 'vendor.registrations.view'],
    ['label' => 'Monthly', 'path' => 'vendor/registrations/monthly', 'icon' => 'bi bi-clock-history', 'perm' => 'vendor.registrations.monthly']
  ]
]) ?>

<?= view($navPageSingle, [
  'label'=>'Meals',
  'icon'=>'bi bi-speedometer2',
  'path'=>'vendor/meals',
  'perm' => 'vendor.meals.view',
]) ?>

<?= view($navpage,  [
  'label' => 'Reports',
  'path' => 'vendor/report',
  'collapseName' => 'collapseReports',
  'icon' => 'bi bi-person-video3',
  'perm' => 'vendor.reports',
  'subMenu' => [
    ['label' => 'Daily Meal Reports', 'path' => 'vendor/reports', 'icon' => 'bi bi-journal-plus', 'perm' => 'vendor.reports.view'],
    ['label' => 'Order History', 'path' => 'vendor/history', 'icon' => 'bi bi-clock-history', 'perm' => 'vendor.history.view']
  ]
]) ?>

<?= view($navPageSingle, [
  'label'=>'My Profile',
  'icon'=>'bi-person-circle',
  'path'=>'vendor/profile',
  'perm' => 'vendor.profile.view',
]) ?>

</ul>
