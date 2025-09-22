<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
  helper('form'); // for set_value()
  $isEdit  = ! empty($user);
  $errors  = session('errors') ?? [];   // from redirect()->back()->with('errors', ...)
  $success = session('success') ?? null;
?>

<h4><?= $isEdit ? 'Edit' : 'Add' ?> User</h4>

<?php if ($success): ?>
  <div class="alert alert-success"><?= esc($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger mb-3">
    Please fix the errors below.
  </div>
<?php endif; ?>

<form
  action="<?= site_url($isEdit ? "users/update/{$user['id']}" : 'users/store') ?>"
  method="post"
>
  <?= csrf_field() ?>

  <!-- Name -->
  <div class="mb-3">
    <label class="form-label">Name</label>
    <input
      name="name"
      type="text"
      required
      class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
      value="<?= esc(set_value('name', $isEdit ? ($user['name'] ?? '') : ''), 'attr') ?>"
    >
    <?php if (isset($errors['name'])): ?>
      <div class="invalid-feedback"><?= esc($errors['name']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Email -->
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input
      name="email"
      type="email"
      class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
      value="<?= esc(set_value('email', $isEdit ? ($user['email'] ?? '') : ''), 'attr') ?>"
    >
    <?php if (isset($errors['email'])): ?>
      <div class="invalid-feedback"><?= esc($errors['email']) ?></div>
    <?php endif; ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Phone</label>
    <input
      name="phone"
      type="text"
      class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
      value="<?= esc(set_value('phone', $isEdit ? ($user['phone'] ?? '') : ''), 'attr') ?>"
    >
    <?php if (isset($errors['phone'])): ?>
      <div class="invalid-feedback"><?= esc($errors['phone']) ?></div>
    <?php endif; ?>
  </div>

  <!-- User Type -->
  <?php
    // $userTypeOptions = ['EMPLOYEE','ADMIN','VENDOR'];
    $userTypeOptions = ['VENDOR'];
    $userTypeVal = set_value('user_type', $isEdit ? ($user['user_type'] ?? '') : ($userTypeOptions[0] ?? ''));
  ?>
  <div class="mb-3">
    <label class="form-label">User Type</label>
    <select
      name="user_type"
      class="form-select <?= isset($errors['user_type']) ? 'is-invalid' : '' ?>"
      required
    >
      <?php foreach ($userTypeOptions as $type): ?>
        <option
          value="<?= esc($type) ?>"
          <?= ($userTypeVal === $type) ? 'selected' : '' ?>
        ><?= ucfirst(strtolower($type)) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['user_type'])): ?>
      <div class="invalid-feedback"><?= esc($errors['user_type']) ?></div>
    <?php endif; ?>
  </div>

  <!-- local_user_type -->
  <?php
    $localTypes = ['SYSTEM','VENDOR']; // adjust if you use SYSTEM_USER etc.
    $localVal   = set_value('local_user_type', $isEdit ? ($user['local_user_type'] ?? '') : ($localTypes[0] ?? ''));
  ?>
  <div class="mb-3">
    <label class="form-label">Local User Type</label>
    <select
      name="local_user_type"
      class="form-select <?= isset($errors['local_user_type']) ? 'is-invalid' : '' ?>"
      required
    >
      <?php foreach ($localTypes as $st): ?>
        <option
          value="<?= esc($st) ?>"
          <?= ($localVal === $st) ? 'selected' : '' ?>
        ><?= ucfirst(strtolower($st)) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['local_user_type'])): ?>
      <div class="invalid-feedback"><?= esc($errors['local_user_type']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Password -->
  <div class="mb-3">
    <label class="form-label">
      Password <?= $isEdit ? '(leave blank to keep)' : '' ?>
    </label>

    <div class="position-relative">
      <input
        id="password"
        name="password"
        type="password"
        class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?> pe-5"
        <?= $isEdit ? '' : 'required' ?>
        autocomplete="new-password"
      >

      <button
        type="button"
        class="btn btn-link text-muted p-0 border-0 position-absolute top-50 end-0 translate-middle-y me-2 toggle-password"
        data-target="#password"
        aria-label="Show password"
        style="line-height:1"
      >
        <i class="bi bi-eye fs-5"></i>
      </button>
    </div>

    <?php if (isset($errors['password'])): ?>
      <div class="invalid-feedback"><?= esc($errors['password']) ?></div>
    <?php endif; ?>
  </div>


  <button type="submit" class="btn btn-primary">
    <?= $isEdit ? 'Update' : 'Create' ?>
  </button>
  <a href="<?= site_url('users') ?>" class="btn btn-outline-secondary">Cancel</a>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).on('click', '.toggle-password', function () {
  const $btn   = $(this);
  const $icon  = $btn.find('i');
  const $input = $($btn.data('target'));
  const isPwd  = $input.attr('type') === 'password';

  $input.attr('type', isPwd ? 'text' : 'password');
  $icon.toggleClass('bi-eye bi-eye-slash');
  $btn.attr('aria-label', isPwd ? 'Hide password' : 'Show password');
});
</script>
<?= $this->endSection() ?>
