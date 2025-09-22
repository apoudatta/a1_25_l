<?= $this->extend('layouts/admin') ?>
<?= $this->section('title') ?>Employee Management – bKash LMS<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php helper('auth'); // ensure can()/has_role() available ?>

<?= view('partials/content_heading', [
  'heading' => 'Employee Management',
  'add_btn' => can('admin.users.new')? ['Add User', 'users/create'] : null
]) ?>

<?= view('partials/flash_message') ?>

<table class="table table-hover">
  <?= view('partials/table_heading', [
    'columns' => ['ID','Emp. ID', 'Name', 'Email', 'Type', 'Status', 'Actions']]) ?>
 
  <tbody>
    <?php foreach($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><?= esc($u['employee_id']) ?></td>
      <td><?= esc($u['name']) ?></td>
      <td><?= esc($u['email']) ?></td>
      <td><?= esc($u['user_type']) ?></td>
      <td><?= esc($u['status']) ?></td>
      <td>
      <?php if (can('admin.user.set-rule')): ?>
        <?php if (function_exists('can') && can('rbac.assign')): ?>
          <a href="<?= site_url("users/{$u['id']}/roles") ?>"
             class="btn btn-sm"
             title="Set Roles">
            <i class="bi bi-person-gear"></i>
          </a>
        <?php endif; ?>
      <?php endif; ?>
        
        <?php if ($u['login_method'] == 'LOCAL'): ?>
          <?php if (can('admin.users.edit')): ?>
          <a href="<?= site_url("users/edit/{$u['id']}") ?>" class="btn btn-sm" title="Edit">
            <i class="bi bi-pencil-fill"></i>
          </a>
          <?php endif; ?>
          
          <?php if ($u['status'] == 'ACTIVE'): ?>
            <?php if (can('admin.users.inactive')): ?>
            <a href="<?= site_url("users/inactive/{$u['id']}") ?>" class="btn btn-sm" title="Inactive">
              <i class="bi bi-person-fill-dash color-red"></i>
            </a>
            <?php endif; ?>
          <?php else: ?>
            <?php if (can('admin.users.active')): ?>
            <a href="<?= site_url("users/active/{$u['id']}") ?>" class="btn btn-sm" title="Active">
              <i class="bi bi-person-fill-check color-green"></i>
            </a>
            <?php endif; ?>
          <?php endif; ?>

        <?php else: ?>
          <?php if (can('admin.users.line-manager-set')): ?>
          <a href="<?= site_url('users/'.$u['id'].'/line-manager') ?>"
            class="btn btn-success btn-sm" title="Set Line Manager" aria-label="Set Line Manager">
            <i class="bi bi-person-check me-1"></i>
          </a>
          <?php endif; ?>

        <?php endif; ?>

      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<?= $this->endSection() ?>
