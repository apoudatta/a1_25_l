<ul class="nav nav-pills flex-column mb-auto w-100" id="sidebarMenu">
<?php 
  $navpage = 'partials/sidebar/nav_items';
  $navPageSingle = 'partials/sidebar/nav_item';
?>
<?= view($navpage, [
  'label'=>'Dashboard',
  'icon'=>'bi bi-speedometer2',
  'path'=>'admin/dashboard',
  'perm' => 'admin.dashboard',
]) ?>

<?= view($navpage, [
  'label'=>'Employee Management',
  'icon'=>'bi bi-people',
  'path'=>'admin/users',
  'perm' => 'admin.users',
]) ?>


<?= view($navpage,  [
  'label' => 'Lunch Management',
  'path' => 'admin/subscription',
  'collapseName' => 'collapseLunch',
  'icon' => 'bi bi-person-video3',
  'perm' => 'meal.subscriptions',
  'subMenu' => [
    ['label' => 'Lunch Subscriptions', 'path' => 'admin/subscription/new', 'icon' => 'bi bi-journal-plus', 'perm' => 'admin.subscriptions.new'],
    ['label' => 'My Subscriptions', 'path' => 'admin/subscription', 'icon' => 'bi bi-clock-history', 'perm' => 'admin.subscriptions.history'],
    ['label' => 'All Subscriptions', 'path' => 'admin/subscription/all-subscriptions', 'icon' => 'bi bi-clock-history', 'perm' => 'admin.subscriptions.all-subscriptions'],
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Guest Subscription',
  'path' => 'admin/guest-subscriptions',
  'collapseName' => 'collapseGuestLunch',
  'icon' => 'bi bi-person-fill-add',
  'perm' => 'admin.guest-subscriptions',
  'subMenu' => [
    ['label' => 'Personal Guest Subscription', 'path' => 'admin/guest-subscriptions/new',  'icon' => 'bi bi-journal-plus', 'perm' => 'admin.guest-subscriptions.new'],
    ['label' => 'My Guest List', 'path' => 'admin/guest-subscriptions',  'icon' => 'bi bi-clock-history', 'perm' => 'admin.guest-subscriptions.history'],
    ['label' => 'All Guest List', 'path' => 'admin/guest-subscriptions/all-guest-list',  'icon' => 'bi bi-clock-history', 'perm' => 'admin.guest-subscriptions.all-guest-list'],
    ['label' => 'Bulk Upload', 'path' => 'admin/guest-subscriptions/bulk-upload',  'icon' => 'bi bi-journal-plus', 'perm' => 'admin.guest-subscriptions.bulk-upload'],
    ['label' => 'Bulk Upload list', 'path' => 'admin/guest-subscriptions/bulk-list','icon' => 'bi bi-clock-history', 'perm' => 'admin.guest-subscriptions.bulk-list'],
  ]
]) ?>


<?= view($navpage, [
  'label' => 'Ramadan Meal',
  'path' => 'admin/ifter-subscription',
  'collapseName' => 'ramadanMeal',
  'icon' => 'bi bi-moon-stars',
  'perm' => 'admin.ramadan',
  'subMenu' => [
    ['label' => 'Meal Approvals', 'path' => 'admin/approvals',          'icon' => 'bi bi-check2-square',                 'perm' => 'admin.approvals'],
    ['label' => 'Subscribe Ifter','path' => 'admin/ifter-subscription', 'icon' => 'bi bi-journal-plus',                 'perm' => 'admin.ramadan.ifter-subscription.history'],
    ['label' => 'All Ifter List', 'path' => 'admin/ifter-subscription/all-ifter-list',  'icon' => 'bi bi-clock-history', 'perm' => 'admin.ramadan.ifter-subscription.all-ifter-list'],
    ['label' => 'Subscribe Sehri','path' => 'admin/sehri-subscription', 'icon' => 'bi bi-journal-plus',                 'perm' => 'admin.ramadan.sehri-subscription.history'],
    ['label' => 'All Sehri List', 'path' => 'admin/sehri-subscription/all-sehri-list',  'icon' => 'bi bi-clock-history', 'perm' => 'admin.ramadan.sehri-subscription.all-sehri-list'],
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Eid Meal',
  'path' => 'admin/eid-subscription',
  'collapseName' => 'eidMeal',
  'icon' => 'bi bi-cup-hot',
  'perm' => 'admin.eid-subscription',
  'subMenu' => [
    ['label' => 'Subscribe Meal',  'path' => 'admin/eid-subscription/new', 'icon' => 'bi bi-journal-plus', 'perm' => 'admin.eid-subscription.new'],
    ['label' => 'My Subscriptions',  'path' => 'admin/eid-subscription',  'icon' => 'bi bi-clock-history', 'perm' => 'admin.eid-subscription.history'],
    ['label' => 'All Subscriptions', 'path' => 'admin/eid-subscription/all-eid-subscription-list',  'icon' => 'bi bi-clock-history', 'perm' => 'admin.eid-subscription.all-eid-subscription-list'],
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Intern Subscription',
  'path' => 'admin/intern-requisitions',
  'icon' => 'bi bi-person-plus',
  'collapseName' => 'intern',
  'perm' => 'admin.intern-requisitions',
  'subMenu' => [
    ['label' => 'Bulk Subscription',    'path' => 'admin/intern-requisitions/new', 'icon' => 'bi bi-clock-history', 'perm' => 'admin.intern-requisitions.new'],
    ['label' => 'Subscription List',   'path' => 'admin/intern-requisitions',  'icon' => 'bi bi-journal-plus', 'perm' => 'admin.intern-requisitions.index'],
  ]
]) ?>

<?= view($navpage, [
  'label' => 'Report',
  'path' => 'admin/report',
  'icon' => 'bi bi-person-plus',
  'collapseName' => 'report',
  'perm' => 'admin.report',
  'subMenu' => [
    ['label' => 'Meal Charge list for payroll', 'path' => 'admin/report/meal-charge-list-for-payroll', 'icon' => 'bi bi-clock-history', 'perm' => 'admin.report.meal-charge-list-for-payroll'],
    ['label' => 'Meal Report for billing',      'path' => 'admin/report/meal-report-for-billing',      'icon' => 'bi bi-clock-history', 'perm' => 'admin.report.meal-report-for-billing'],
    ['label' => 'Meal Detail Report',           'path' => 'admin/report/meal-detail-report',           'icon' => 'bi bi-clock-history', 'perm' => 'admin.report.meal-detail-report'],
    ['label' => 'Daily Meal Report',            'path' => 'admin/report/daily-meal-report',            'icon' => 'bi bi-clock-history', 'perm' => 'admin.report.daily-mealreport'],
    ['label' => 'Food Consumption Report',      'path' => 'admin/report/food-consumption-report',      'icon' => 'bi bi-clock-history', 'perm' => 'admin.report.food-consumption-report'],
  ]
]) ?>


<?= view($navpage, [
  'label' => 'Settings',
  'path' => 'settings',
  'collapseName' => 'collapseSettings',
  'icon' => 'bi bi-gear',
  'perm' => 'admin.settings',
  'subMenu' => [
    ['path'=>'admin/meal-cards',      'icon'=>'bi bi-person-vcard',    'label'=>'Meal Cards', 'perm' => 'admin.meal-cards'],
    ['path'=>'admin/approval-flows',  'icon'=>'bi bi-list-check',      'label'=>'Approval Flows', 'perm' => 'admin.approval-flows.index'],
    ['path'=>'admin/cafeterias',      'icon'=>'bi bi-house-fill',      'label'=>'Cafeterias', 'perm' => 'admin.cafeterias.index'],
    ['path'=>'admin/meal-types',      'icon'=>'bi bi-cup',             'label'=>'Meal Types', 'perm' => 'admin.meal-types.index'],
    ['path'=>'admin/employment-types','icon'=>'bi bi-shield-lock',     'label'=>'Employment Types', 'perm' => 'admin.employment-types.index'],
    ['path'=>'admin/cutoff-times',    'icon'=>'bi bi-hourglass-split', 'label'=>'Meal Cut-Off Times', 'perm' => 'admin.cutoff-times.index'],
    ['path'=>'admin/meal-costs',      'icon'=>'bi bi-currency-dollar', 'label'=>'Meal Costs', 'perm' => 'admin.meal-costs.index'],
    ['path'=>'admin/contributions',   'icon'=>'bi bi-percent',         'label'=>'Contributions', 'perm' => 'admin.contributions.index'],
    ['path'=>'admin/public-holidays', 'icon'=>'bi bi-calendar-event',  'label'=>'Public Holidays', 'perm' => 'admin.public-holidays.index'],
    ['path'=>'admin/occasions',       'icon'=>'bi bi-gift',            'label'=>'Occasions', 'perm' => 'admin.occasions.index'],
    ['path'=>'admin/ramadan-periods', 'icon'=>'bi bi-moon-stars',      'label'=>'Ramadan Periods', 'perm' => 'admin.ramadan-periods.index'],
  ]
]) ?>


<?php helper('auth'); ?>
<?php if (has_role('SUPER ADMIN') || can('rbac.manage')): ?>
  <li class="sidebar-label small text-uppercase text-muted mt-3">Access Control</li>

  <li class="nav-item">
    <a class="nav-link <?= (strpos(uri_string(), 'admin/permissions') === 0) ? 'active' : '' ?>"
       href="<?= site_url('admin/permissions') ?>">
      <i class="bi bi-shield-lock me-2"></i> Permissions
    </a>
  </li>

  <li class="nav-item">
    <a class="nav-link <?= (strpos(uri_string(), 'admin/roles') === 0) ? 'active' : '' ?>"
       href="<?= site_url('admin/roles') ?>">
      <i class="bi bi-people me-2"></i> Roles
    </a>
  </li>
<?php endif; ?>




</ul>
