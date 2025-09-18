<?php 
  $menus = [
    ['name' => "Dashboard", 'perm' => "admin.dashboard"],
    [
      'name' => "Employee Management", 'perm' => "admin.users",
      'option'=> [
            ['name' => "Form",   'perm' => "admin.meal-types.new"],
            ['name' => "Save",   'perm' => "admin.meal-types.create"],
            ['name' => "Edit",   'perm' => "admin.meal-types.edit"],
            ['name' => "Update", 'perm' => "admin.meal-types.update"],
          ]
    ],
    ['name' => "Meal Approvals", 'perm' => "admin.approvals"],
    [
      'name' => "Lunch Management", 'perm' => "meal.subscriptions",
      'sub_menu' => [
        ['name' => "Lunch Subscriptions", 'perm' => "admin.subscriptions.new"],
        ['name' => "My Subscriptions", 'perm' => "admin.subscriptions.history"],
        ['name' => "All Subscriptions", 'perm' => "admin.subscriptions.all-subscriptions"],
      ],
    ],
    [
      'name' => "Guest Subscription", 'perm' => "admin.guest-subscriptions",
      'sub_menu' => [
        ['name' => "Personal Guest Subscription", 'perm' => "admin.guest-subscriptions.new"],
        ['name' => "My Guest List", 'perm' => "admin.guest-subscriptions.history"],
        ['name' => "All Guest List", 'perm' => "admin.guest-subscriptions.all-guest-list"],
        ['name' => "Bulk Upload", 'perm' => "admin.guest-subscriptions.bulk-upload"],
        ['name' => "Bulk Upload list", 'perm' => "admin.guest-subscriptions.bulk-list"],
      ],
    ],
    [
      'name' => "Ramadan Meal", 'perm' => "admin.ramadan",
      'sub_menu' => [
        ['name' => "Subscribe Ifter", 'perm' => "admin.ramadan.ifter-subscription.history"],
        ['name' => "All Ifter List", 'perm' => "admin.ramadan.ifter-subscription.all-ifter-list"],
        ['name' => "Subscribe Sehri", 'perm' => "admin.ramadan.sehri-subscription.history"],
        ['name' => "All Sehri List", 'perm' => "admin.ramadan.sehri-subscription.all-sehri-list"],
      ],
    ],
    [
      'name' => "Ramadan Meal", 'perm' => "admin.ramadan",
      'sub_menu' => [
        ['name' => "Subscribe Ifter", 'perm' => "admin.ramadan.ifter-subscription.history"],
        ['name' => "All Ifter List", 'perm' => "admin.ramadan.ifter-subscription.all-ifter-list"],
        ['name' => "Subscribe Sehri", 'perm' => "admin.ramadan.sehri-subscription.history"],
        ['name' => "All Sehri List", 'perm' => "admin.ramadan.sehri-subscription.all-sehri-list"],
      ],
    ],
    [
      'name' => "Eid Meal", 'perm' => "admin.eid-subscription",
      'sub_menu' => [
        ['name' => "Subscribe Meal", 'perm' => "admin.eid-subscription.new"],
        ['name' => "My Subscriptions", 'perm' => "admin.eid-subscription.history"],
        ['name' => "All Subscriptions", 'perm' => "admin.eid-subscription.all-eid-subscription-list"],
      ],
    ],
    [
      'name' => "Intern Subscription", 'perm' => "admin.intern-requisitions",
      'sub_menu' => [
        ['name' => "Bulk Subscription", 'perm' => "admin.intern-requisitions.new"],
        ['name' => "Subscription List", 'perm' => "admin.intern-requisitions.index"],
      ],
    ],
    [
      'name' => "Meal Cards", 'perm' => "admin.meal-cards",
      'option'=> [
        ['name' => "Form",   'perm' => "admin.meal-cards.form"],
        ['name' => "Save",   'perm' => "admin.meal-cards.save"],
        ['name' => "Edit",   'perm' => "admin.meal-cards.edit"],
        ['name' => "Update", 'perm' => "admin.meal-cards.update"],
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
        ['name' => "Approval Flows", 'perm' => "admin.report.meal-charge-list-for-payroll"],
        [
          'name' => "Cafeterias",      'perm' => "admin.cafeterias.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.cafeterias.new"],
            ['name' => "Save",   'perm' => "admin.cafeterias.create"],
            ['name' => "Edit",   'perm' => "admin.cafeterias.edit"],
            ['name' => "Update", 'perm' => "admin.cafeterias.update"],
          ]
        ],
        [
          'name' => "Meal Types",      'perm' => "admin.meal-types.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.meal-types.new"],
            ['name' => "Save",   'perm' => "admin.meal-types.create"],
            ['name' => "Edit",   'perm' => "admin.meal-types.edit"],
            ['name' => "Update", 'perm' => "admin.meal-types.update"],
          ]
        ],
        [
          'name' => "Employment Types",      'perm' => "admin.employment-types.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.employment-types.new"],
            ['name' => "Save",   'perm' => "admin.employment-types.create"],
            ['name' => "Edit",   'perm' => "admin.employment-types.edit"],
            ['name' => "Update", 'perm' => "admin.employment-types.update"],
          ]
        ],
        [
          'name' => "Meal Costs",      'perm' => "admin.meal-costs.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.meal-costs.new"],
            ['name' => "Save",   'perm' => "admin.meal-costs.create"],
            ['name' => "Edit",   'perm' => "admin.meal-costs.edit"],
            ['name' => "Update", 'perm' => "admin.meal-costs.update"],
          ]
        ],
        [
          'name' => "Contributions",      'perm' => "admin.contributions.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.contributions.new"],
            ['name' => "Save",   'perm' => "admin.contributions.create"],
            ['name' => "Edit",   'perm' => "admin.contributions.edit"],
            ['name' => "Update", 'perm' => "admin.contributions.update"],
          ]
        ],
        [
          'name' => "Public Holidays",      'perm' => "admin.public-holidays.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.public-holidays.new"],
            ['name' => "Save",   'perm' => "admin.public-holidays.create"],
            ['name' => "Edit",   'perm' => "admin.public-holidays.edit"],
            ['name' => "Update", 'perm' => "admin.public-holidays.update"],
          ]
        ],
        [
          'name' => "Occasions",      'perm' => "admin.occasions.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.occasions.new"],
            ['name' => "Save",   'perm' => "admin.occasions.create"],
            ['name' => "Edit",   'perm' => "admin.occasions.edit"],
            ['name' => "Update", 'perm' => "admin.occasions.update"],
          ]
        ],
        [
          'name' => "Meal Cut-Off Times",      'perm' => "admin.cutoff-times.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.cutoff-times.new"],
            ['name' => "Save",   'perm' => "admin.cutoff-times.create"],
            ['name' => "Edit",   'perm' => "admin.cutoff-times.edit"],
            ['name' => "Update", 'perm' => "admin.cutoff-times.update"],
          ]
        ],
        [
          'name' => "Ramadan Periods",      'perm' => "admin.ramadan-periods.index",
          'option'=> [
            ['name' => "Form",   'perm' => "admin.ramadan-periods.new"],
            ['name' => "Save",   'perm' => "admin.ramadan-periods.create"],
            ['name' => "Edit",   'perm' => "admin.ramadan-periods.edit"],
            ['name' => "Update", 'perm' => "admin.ramadan-periods.update"],
          ]
        ],
      ],
    ],
  ];
?>

<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5>Role Permissions — <?= esc($role['name']) ?></h5>
<form method="post" action="<?= site_url('admin/roles/'.$role['id'].'/permissions') ?>">
  <?= csrf_field() ?>



  <div class="table-responsive">
  <table class="table table-sm table-striped align-middle text-nowrap mb-0">
    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
      <tr>
        <th>Main Menu</th>
        <th>Sub Menu</th>
        <th>Option</th>
      </tr>
    </thead>
    <tbody>
      
      <?php foreach($menus as $main_menu): ?>
        <!-- All permission Loop -->
        <?php foreach ($permissions as $p)
        {
          if($p['name'] === $main_menu['perm'])
          {
            $id = "perm".$p['id'];
            $value = $p['id'];
            $checked = in_array($p['id'], $attached, true) ? 'checked' : '';
            $title=$p['name'];
            break;
          }
        }
        ?>

      <tr>  
        <td>
          <!-- Main Menu -->
          <div class="ps-2 my-2">
            <input
              class="form-check-input"
              type="checkbox"
              id="<?= $id ?>"
              name="permission_id[]"
              value="<?= $value ?>"
              <?= $checked ?>
            >
            <label
              for="<?= $id ?>"
              class="form-check-label"
              data-bs-toggle="tooltip"
              data-bs-placement="top"
              data-bs-container="body"
              title="<?= esc($title) ?>"
            >
              <?= esc($main_menu['name'] ?? '') ?>
            </label>
          </div>
        </td>

        <td>
          <!-- Sub Menu -->
          <?php if(isset($main_menu['sub_menu'])): ?>
              
            <?php foreach($main_menu['sub_menu'] as $sub_menu): ?>
              <?php foreach ($permissions as $p)
              {
                if($p['name'] === $sub_menu['perm'])
                {
                  $id = "perm".$p['id'];
                  $value = $p['id'];
                  $checked = in_array($p['id'], $attached, true) ? 'checked' : '';
                  $title=$p['name'];
                  break;
                }
              }
              ?>
              <div class="my-2">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="<?= $id ?>"
                  name="permission_id[]"
                  value="<?= $value ?>"
                  <?= $checked ?>
                >
                <label
                  for="<?= $id ?>"
                  class="form-check-label"
                  data-bs-toggle="tooltip"
                  data-bs-placement="top"
                  data-bs-container="body"
                  title="<?= esc($title) ?>"
                >
                  <?= esc($sub_menu['name'] ?? '') ?>
                </label>
              </div>
            <?php endforeach ?>

          <?php endif ?>
        </td>
        <!-- Sub Menu End -->


        

        <td>
          <!-- Options -->
          <input type="checkbox"> Form
          <input type="checkbox"> Save
          <input type="checkbox"> Edit
          <input type="checkbox"> Update
        </td>
        <!-- Sub Menu End -->

      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>


  <div class="mt-3">
    <button class="btn btn-primary">Save Changes</button>
    <a class="btn btn-light" href="<?= site_url('admin/roles') ?>">Back</a>
  </div>
</form>

<?= $this->endSection() ?>
