<?= $this->extend('layouts/admin') ?>
<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<h4 class="mb-3"><?= esc($title) ?></h4>

<?php $validation = session()->getFlashdata('validation'); ?>
<?php if ($validation): ?>
  <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
<?php endif; ?>

<form method="post" action="<?= $mode==='create'
  ? site_url('admin/meal-cards')
  : site_url('admin/meal-cards/'.$row['id'].'/update') ?>">
  <?= csrf_field() ?>

  <div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">User</label>
        <select id="user_id" name="user_id" class="form-select" required>
            <option value="">Select user…</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Employee ID</label>
        <input
            id="employeeId"
            type="text"
            name="employee_id"
            value="<?= esc(set_value('employee_id', $row['employee_id'] ?? '')) ?>"
            class="form-control"
            maxlength="20"
            placeholder="e.g. E12345">
    </div>

    <div class="col-md-4">
      <label class="form-label">Card Code</label>
      <input
        type="text"
        name="card_code"
        value="<?= esc(set_value('card_code', $row['card_code'])) ?>"
        class="form-control"
        maxlength="64"
        required
        <?= $mode==='create' ? '' : 'disabled' ?>
        >
    </div>

    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select" required>
        <option value="ACTIVE"   <?= set_value('status', $row['status'])==='ACTIVE'?'selected':'' ?>>ACTIVE</option>
        <option value="INACTIVE" <?= set_value('status', $row['status'])==='INACTIVE'?'selected':'' ?>>INACTIVE</option>
      </select>
    </div>
  </div>

  <div class="mt-4">
    <button class="btn btn-primary" type="submit">
      <?= $mode==='create' ? 'Create' : 'Update' ?>
    </button>
    <a class="btn btn-outline-secondary" href="<?= site_url('admin/meal-cards') ?>">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>

<script>
$(function () {
  const $user  = $('#user_id');
  const $empId = $('#employeeId');

  // current selection (keeps postback value, else row value)
  const selectedUserId = "<?= esc((string) set_value('user_id', $row['user_id'] ?? '')) ?>";

  $user.empty().append($('<option>', { value: '', text: 'Select employee…' }));

  $.getJSON("<?= site_url('admin/employees/active-list') ?>")
    .done(function (res) {
      res.forEach(function (emp) {
        $user.append(new Option(emp.name, emp.id));
      });

      if (selectedUserId) {
        // If the current user isn’t in the active list (e.g., inactive), append it so it can be selected
        if ($user.find('option[value="'+selectedUserId+'"]').length === 0) {
          // optional: hit a lookup endpoint that returns {id,name}
          $.getJSON("<?= site_url('admin/employees/lookup') ?>/" + selectedUserId)
            .done(function (emp) {
              if (emp && emp.id) $user.append(new Option(emp.name + ' (inactive)', emp.id));
            })
            .always(function () {
              $user.val(selectedUserId).trigger('change');   // populate Employee ID
            });
        } else {
          $user.val(selectedUserId).trigger('change');       // populate Employee ID
        }
      }
    })
    .fail(function () {
      alert('Failed to load employee list.');
    });

  $user.on('change', function () {
    const userId = this.value;
    if (!userId) { $empId.val(''); return; }
    $.getJSON("<?= site_url('admin/user/getEmpId') ?>/" + userId)
      .done(function (res) { $empId.val(res.employee_id || ''); })
      .fail(function () { $empId.val(''); });
  });
});
</script>
<?= $this->endSection() ?>
