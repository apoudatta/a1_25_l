<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<h5 class="mb-3">Roles</h5>

<?= view('partials/flash_message') ?>

<div class="mb-3">
  <a class="btn btn-primary" href="<?= site_url('admin/roles/create') ?>">Add Role</a>
</div>

<table class="table table-sm table-striped align-middle">
  <thead>
    <tr>
      <th style="width:50px">#</th>
      <th>Name</th>
      <th>Description</th>
      <th class="text-end">Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($roles)): ?>
      <tr><td colspan="4" class="text-center">No roles found.</td></tr>
    <?php else: foreach ($roles as $r): ?>
      <tr>
        <td><?= (int) $r['id'] ?></td>
        <td><?= esc($r['name']) ?></td>
        <td><?= esc($r['description'] ?? '') ?></td>
        <td class="text-end">
          <?php if (function_exists('can') && can('rbac.assign')): ?>
            <a class="btn btn-sm btn-outline-secondary"
               href="<?= site_url('admin/roles/'.$r['id'].'/permissions') ?>">
              Manage Perms
            </a>
          <?php endif; ?>

          <a class="btn btn-sm btn-outline-primary"
             href="<?= site_url('admin/roles/edit/'.$r['id']) ?>">
            Edit
          </a>

          <!-- <form method="post"
                action="<?= site_url('admin/roles/delete/'.$r['id']) ?>"
                class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Delete this role? This will also detach it from users and permissions.');">
              Delete
            </button>
          </form> -->
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>

<?= $this->endSection() ?>
