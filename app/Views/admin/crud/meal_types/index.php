<!-- app/Views/admin/meal_types/index.php -->
<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>


<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <h2 class="mb-0">Meal Types</h2>

  <?php if (can('admin.meal-types.new')): ?>
    <a href="<?= site_url('admin/meal-types/new') ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> New Meal Type
    </a>
  <?php endif; ?>

</div>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>Name</th>
      <th>Description</th>
      <th>Active?</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if(empty($types)): ?>
      <tr>
        <td colspan="4" class="text-center">No meal types defined.</td>
      </tr>
    <?php else: foreach($types as $t): ?>
      <tr>
        <td><?= esc($t['name']) ?></td>
        <td><?= esc($t['description']) ?></td>
        <td><?= $t['is_active'] ? 'Yes' : 'No' ?></td>
        <td>

        <?php if (can('admin.meal-types.edit')): ?>
          <a href="<?= site_url("admin/meal-types/{$t['id']}/edit") ?>"
             class="btn btn-sm btn-secondary">Edit</a>
        <?php endif; ?>

          <!-- <form action="<?= site_url("admin/meal-types/{$t['id']}/delete") ?>"
                method="post"
                class="d-inline"
                onsubmit="return confirm('Delete this meal type?')">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-danger">Delete</button>
          </form> -->
        </td>
      </tr>
    <?php endforeach; endif ?>
  </tbody>
</table>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>
<?= $this->endSection() ?>