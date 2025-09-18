<?php
    helper(['auth','url']);
    $navpage = 'partials/sidebar/nav_items';
    $navPageSingle = 'partials/sidebar/nav_item';
?>
<ul class="nav nav-pills flex-column mb-auto w-100" id="sidebarMenu">

<?= view($navPageSingle, [
  'label'=>'Dashboard',
  'icon'=>'bi bi-speedometer2',
  'path'=>'employee/dashboard',
  'perm' => 'employee.dashboard',
]) ?>

<?= view($navpage, [
  'label'=>'Meal Approvals',
  'icon'=>'bi bi-check2-square',
  'path'=>'employee/approvals',
  'perm' => 'employee.approvals',
]) ?>

<?= view($navpage,  [
  'label' => 'Employee Lunch',
  'path' => 'employee/subscription',
  'collapseName' => 'collapseLunch',
  'icon' => 'bi bi-person-video3',
  'perm' => 'employee.meal.subscriptions',
  'subMenu' => [
    ['label' => 'Lunch Subscriptions', 'path' => 'employee/subscription/new', 'icon' => 'bi bi-journal-plus', 'perm' => 'employee.subscriptions.new'],
    ['label' => 'My Subscriptions', 'path' => 'employee/subscription', 'icon' => 'bi bi-clock-history', 'perm' => 'employee.subscriptions.history']
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Guest Subscription',
  'path' => 'employee/guest-subscriptions',
  'collapseName' => 'collapseGuestLunch',
  'icon' => 'bi bi-person-fill-add',
  'perm' => 'employee.guest-subscriptions',
  'subMenu' => [
    ['label' => 'Personal Guest Subscription', 'path' => 'employee/guest-subscriptions/new',  'icon' => 'bi bi-journal-plus', 'perm' => 'employee.guests.new'],
    ['label' => 'Personal Guest List', 'path' => 'employee/guest-subscriptions',  'icon' => 'bi bi-clock-history', 'perm' => 'employee.guests.index'],
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Ramadan Meal',
  'path' => 'employee/ramadan',
  'collapseName' => 'ramadanMeal',
  'icon' => 'bi bi-moon-stars',
  'perm' => 'employee.ramadan',
  'subMenu' => [
    ['label' => 'Subscribe Ifter', 'path' => 'employee/ifter-subscription/new', 'icon' => 'bi bi-journal-plus', 'perm' => 'employee.ifter.new'],
    ['label' => 'Ifter List', 'path' => 'employee/ifter-subscription', 'icon' => 'bi bi-clock-history', 'perm' => 'employee.ifter.history'],
    ['label' => 'Subscribe Sehri', 'path' => 'employee/sehri-subscription/new', 'icon' => 'bi bi-journal-plus', 'perm' => 'employee.sehri.new'],
    ['label' => 'Sehri List', 'path' => 'employee/sehri-subscription',  'icon' => 'bi bi-clock-history', 'perm' => 'employee.sehri.history'],
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Eid Meal',
  'path' => 'employee/eid-subscription',
  'collapseName' => 'eidMeal',
  'icon' => 'bi bi-cup-hot',
  'perm' => 'employee.eid-subscription',
  'subMenu' => [
    ['label' => 'Subscribe Meal',  'path' => 'employee/eid-subscription/new', 'icon' => 'bi bi-journal-plus', 'perm' => 'employee.eid.new'],
    ['label' => 'Subscriptions List',  'path' => 'employee/eid-subscription',  'icon' => 'bi bi-clock-history', 'perm' => 'employee.eid.history'],
  ]
]) ?>



</ul>
