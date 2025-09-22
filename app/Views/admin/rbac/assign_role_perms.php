<?php 
  $adminMenus = [
    ['name' => "Dashboard", 'perm' => "admin.dashboard"],
    ['name' => "Employee Dashboard", 'perm' => "admin.employee-dashboard"],
    [
      'name' => "Employee Management", 'perm' => "admin.users",
      'option'=> [
            ['name' => "Add User",        'perm' => "admin.users.new"],
            ['name' => "Set Rules",       'perm' => "admin.user.set-rule"],
            ['name' => "Edit",            'perm' => "admin.users.edit"],
            ['name' => "Active",          'perm' => "admin.users.active"],
            ['name' => "Inactive",        'perm' => "admin.users.inactive"],
            ['name' => "Set Line Manager", 'perm' => "admin.users.line-manager-set"],
      ]
    ],
    [
      'name' => "Lunch Management", 'perm' => "meal.subscriptions",
      'sub_menu' => [
        ['name' => "Lunch Subscriptions", 'perm' => "admin.subscriptions.new"],
        [
          'name' => "My Subscriptions", 'perm' => "admin.subscriptions.history",
        ],
        [
          'name' => "All Subscriptions", 'perm' => "admin.subscriptions.all-subscriptions",
          'option'=> [
            ['name' => "Unsubscribe",        'perm' => "admin.subscriptions.unsubscribe_single"],
            ['name' => "Bulk Unsubscribe",   'perm' => "admin.subscriptions.unsubscribe_bulk"],
          ]
        ],
      ],
    ],
    [
      'name' => "Guest Subscription", 'perm' => "admin.guest-subscriptions",
      'sub_menu' => [
        ['name' => "Personal Guest Subscription", 'perm' => "admin.guest-subscriptions.new"],
        ['name' => "My Guest List", 'perm' => "admin.guest-subscriptions.history"],
        [
          'name' => "All Guest List", 'perm' => "admin.guest-subscriptions.all-guest-list",
          // 'option'=> [
          //   ['name' => "Unsubscribe",        'perm' => "admin.guest-subscriptions.unsubscribe"],
          //   ['name' => "Bulk Unsubscribe",   'perm' => "admin.guest-subscriptions.unsubscribe_bulk"],
          // ]
        ],
        ['name' => "Bulk Upload", 'perm' => "admin.guest-subscriptions.bulk-upload"],
        [
          'name' => "Bulk Upload list", 'perm' => "admin.guest-subscriptions.bulk-list",
          'option'=> [
            ['name' => "Unsubscribe",        'perm' => "admin.guest-subscriptions.unsubscribe"],
            ['name' => "Bulk Unsubscribe",   'perm' => "admin.guest-subscriptions.unsubscribe_bulk"],
          ]
        ],
      ],
    ],
    [
      'name' => "Ramadan Meal", 'perm' => "admin.ramadan",
      'sub_menu' => [
        ['name' => "Meal Approvals", 'perm' => "admin.approvals"],
        [
          'name' => "Subscribe Ifter", 'perm' => "admin.ramadan.ifter-subscription.history",
          'option'=> [
            ['name' => "New Subscription",   'perm' => "admin.ramadan.ifter-subscription.new"]
          ]
        ],
        [
          'name' => "All Ifter List", 'perm' => "admin.ramadan.ifter-subscription.all-ifter-list",
          'option'=> [
            ['name' => "Unsubscribe",        'perm' => "admin.ifter-subscription.unsubscribe"],
            ['name' => "Bulk Unsubscribe",   'perm' => "admin.ifter-subscription.unsubscribe_bulk"],
          ]
        ],
        [
          'name' => "Subscribe Sehri", 'perm' => "admin.ramadan.sehri-subscription.history",
          'option'=> [
            ['name' => "New Subscription",   'perm' => "admin.ramadan.sehri-subscription.new"]
          ]
        ],
        [
          'name' => "All Sehri List", 'perm' => "admin.ramadan.sehri-subscription.all-sehri-list",
          'option'=> [
            ['name' => "Unsubscribe",        'perm' => "admin.sehri-subscription.unsubscribe"],
            ['name' => "Bulk Unsubscribe",   'perm' => "admin.sehri-subscription.unsubscribe_bulk"],
          ]
        ],
      ],
    ],
    [
      'name' => "Eid Meal", 'perm' => "admin.eid-subscription",
      'sub_menu' => [
        ['name' => "Subscribe Meal", 'perm' => "admin.eid-subscription.new"],
        ['name' => "My Subscriptions", 'perm' => "admin.eid-subscription.history"],
        [
          'name' => "All Subscriptions", 'perm' => "admin.eid-subscription.all-eid-subscription-list",
          'option'=> [
            ['name' => "Unsubscribe",        'perm' => "admin.eid-subscription.unsubscribe"],
            ['name' => "Bulk Unsubscribe",   'perm' => "admin.eid-subscription.unsubscribe_bulk"],
          ]
        ],
      ],
    ],
    [
      'name' => "Intern Subscription", 'perm' => "admin.intern-requisitions",
      'sub_menu' => [
        ['name' => "Bulk Subscription", 'perm' => "admin.intern-requisitions.new"],
        [
          'name' => "Subscription List", 'perm' => "admin.intern-requisitions.index",
          'option'=> [
            ['name' => "Unsubscribe",        'perm' => "admin.intern-subscriptions.unsubscribe_single"],
            ['name' => "Bulk Unsubscribe",   'perm' => "admin.intern-subscriptions.unsubscribe_bulk"],
          ]
        ],
      ],
    ],
    [
      'name' => "Meal Cards", 'perm' => "admin.meal-cards",
      'option'=> [
        ['name' => "Create",   'perm' => "admin.meal-cards.form"],
        ['name' => "Edit",   'perm' => "admin.meal-cards.edit"],
      ]
    ],
    [
      'name' => "Report", 'perm' => "admin.report",
      'sub_menu' => [
        ['name' => "Meal Charge list for payroll", 'perm' => "admin.report.meal-charge-list-for-payroll"],
        ['name' => "Meal Report for billing",      'perm' => "admin.report.meal-report-for-billing"],
        ['name' => "Meal Detail Report",           'perm' => "admin.report.meal-detail-report"],
        ['name' => "Daily Meal Report",            'perm' => "admin.report.daily-mealreport"],
        ['name' => "Food Consumption Report",      'perm' => "admin.report.food-consumption-report"],
      ],
    ],
    [
      'name' => "Settings", 'perm' => "admin.settings",
      'sub_menu' => [
        ['name' => "Approval Flows", 'perm' => "admin.approval-flows.index"],
        [
          'name' => "Cafeterias",      'perm' => "admin.cafeterias.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.cafeterias.new"],
            ['name' => "Edit",   'perm' => "admin.cafeterias.edit"],
          ]
        ],
        [
          'name' => "Meal Types",      'perm' => "admin.meal-types.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.meal-types.new"],
            ['name' => "Edit",   'perm' => "admin.meal-types.edit"],
          ]
        ],
        [
          'name' => "Employment Types",      'perm' => "admin.employment-types.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.employment-types.new"],
            ['name' => "Edit",   'perm' => "admin.employment-types.edit"],
          ]
        ],
        [
          'name' => "Meal Costs",      'perm' => "admin.meal-costs.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.meal-costs.new"],
            ['name' => "Edit",   'perm' => "admin.meal-costs.edit"],
          ]
        ],
        [
          'name' => "Contributions",      'perm' => "admin.contributions.index",
          'option'=> [
            ['name' => "Create", 'perm' => "admin.contributions.new"],
            ['name' => "Edit",   'perm' => "admin.contributions.edit"],
          ]
        ],
        [
          'name' => "Public Holidays",      'perm' => "admin.public-holidays.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.public-holidays.new"],
            ['name' => "Edit",   'perm' => "admin.public-holidays.edit"],
          ]
        ],
        [
          'name' => "Occasions",      'perm' => "admin.occasions.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.occasions.new"],
            ['name' => "Edit",   'perm' => "admin.occasions.edit"],
          ]
        ],
        [
          'name' => "Meal Cut-Off Times",      'perm' => "admin.cutoff-times.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.cutoff-times.new"],
            ['name' => "Edit",   'perm' => "admin.cutoff-times.edit"],
          ]
        ],
        [
          'name' => "Ramadan Periods",      'perm' => "admin.ramadan-periods.index",
          'option'=> [
            ['name' => "Create",   'perm' => "admin.ramadan-periods.create"],
            ['name' => "Edit",   'perm' => "admin.ramadan-periods.edit"],
          ]
        ],
      ],
    ],
  ];



  $vendorMenus = [
    ['name' => "Dashboard", 'perm' => "vendor.dashboard"],
    [
      'name' => "Registrations", 'perm' => "vendor.registrations",
      'sub_menu' => [
        ['name' => "Daily", 'perm' => "vendor.registrations.view"],
        ['name' => "Monthly", 'perm' => "vendor.registrations.monthly"],
      ],
    ],
    ['name' => "Meals", 'perm' => "vendor.meals.view"],
    [
      'name' => "Reports", 'perm' => "vendor.reports",
      'sub_menu' => [
        ['name' => "Daily Meal Reports", 'perm' => "vendor.reports.view"],
        ['name' => "Order History", 'perm' => "vendor.history.view"],
      ],
    ],
    ['name' => "My Profile", 'perm' => "vendor.profile.view"],
  ];

  if($role['id'] == '4') {
    $menus = $vendorMenus;
  } else {
    $menus = $adminMenus;
  }
?>

<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5>Role Permissions — <?= esc($role['name']) ?></h5>

<form method="post" action="<?= site_url('roles/'.$role['id'].'/permissions') ?>">
  <?= csrf_field() ?>


  <?php
// Index permissions by name for O(1)
$permByName = [];
foreach ($permissions as $p) { $permByName[$p['name']] = $p; }

// Checkbox helper
$renderPerm = function (string $permKey, string $label) use ($permByName, $attached) {
  if (!isset($permByName[$permKey])) {
    $id = 'missing_' . md5($permKey); ?>
    <div class="form-check my-1">
      <input class="form-check-input" type="checkbox" id="<?= esc($id,'attr') ?>" disabled>
      <label class="form-check-label text-muted" for="<?= esc($id,'attr') ?>" title="<?= esc($permKey,'attr') ?>">
        <?= esc($label) ?> <small class="text-muted">(perm missing)</small>
      </label>
    </div>
    <?php return;
  }
  $p = $permByName[$permKey];
  $id = 'perm'.$p['id'];
  $checked = in_array($p['id'], $attached, true) ? 'checked' : ''; ?>
  <div class="form-check my-1">
    <input class="form-check-input" type="checkbox"
           id="<?= esc($id,'attr') ?>" name="permission_id[]"
           value="<?= esc($p['id'],'attr') ?>" <?= $checked ?>>
    <label class="form-check-label" for="<?= esc($id,'attr') ?>"
           data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
           title="<?= esc($p['name'],'attr') ?>">
      <?= esc($label) ?>
    </label>
  </div>
<?php };

// Options list helper (grid)
$renderOptions = function (array $opts) use ($renderPerm) {
  if (empty($opts)) { echo '<span class="text-muted">—</span>'; return; }
  echo '<div class="perm-opt-grid">';
  foreach ($opts as $opt) { $renderPerm($opt['perm'], $opt['name']); }
  echo '</div>';
};
?>

<div class="table-responsive">
  <table class="table table-sm align-middle text-nowrap perm-table mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:28%">Main Menu</th>
        <th style="width:36%">Sub Menu</th>
        <th style="width:36%">Option</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($menus as $main): ?>
      <?php
        $subs = isset($main['sub_menu']) && is_array($main['sub_menu']) ? $main['sub_menu'] : [];
        $hasMainOpts = !empty($main['option']) && is_array($main['option']);
        // total rows this main will occupy
        $rowsNeeded = max(1, count($subs)) + ($hasMainOpts && count($subs) ? 1 : 0);
        $printedMainCell = false;
      ?>

      <?php if ($subs): ?>
        <?php foreach ($subs as $idx => $sub): ?>
          <tr class="perm-row">
            <?php if (!$printedMainCell): $printedMainCell = true; ?>
              <td class="perm-main" rowspan="<?= (int)$rowsNeeded ?>">
                <?php $renderPerm($main['perm'], $main['name']); ?>
              </td>
            <?php endif; ?>

            <td>
              <?php $renderPerm($sub['perm'], $sub['name']); ?>
            </td>
            <td>
              <?php $renderOptions($sub['option'] ?? []); // ✅ sub options ON SAME ROW ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if ($hasMainOpts): // extra row for main-level options ?>
          <tr class="perm-row">
            <td><span class="text-muted">—</span></td>
            <td><?php $renderOptions($main['option']); ?></td>
          </tr>
        <?php endif; ?>

      <?php else: // no sub menus ?>
        <tr class="perm-row">
          <td class="perm-main">
            <?php $renderPerm($main['perm'], $main['name']); ?>
          </td>
          <td><span class="text-muted">—</span></td>
          <td><?php $renderOptions($main['option'] ?? []); ?></td>
        </tr>
      <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<style>
  .perm-table thead th { position: sticky; top: 0; z-index: 2; }
  .perm-row { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: .5rem; }
  .perm-row > td { padding: .75rem 1rem !important; vertical-align: top; }
  .perm-main .form-check-label { font-weight: 600; }
  .perm-opt-grid { display:grid; grid-template-columns:repeat(2,minmax(220px,1fr)); gap:.25rem 1rem; }
  @media (min-width:1400px){ .perm-opt-grid{ grid-template-columns:repeat(3,minmax(220px,1fr)); } }
  .form-check.my-1{ margin-top:.25rem!important; margin-bottom:.25rem!important; }
</style>




  <div class="mt-3">
    <button class="btn btn-primary">Save Changes</button>
    <a class="btn btn-light" href="<?= site_url('roles') ?>">Back</a>
  </div>
</form>

<?= $this->endSection() ?>

