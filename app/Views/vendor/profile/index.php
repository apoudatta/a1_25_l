<?= $this->extend('layouts/vendor') ?>
<?= $this->section('content') ?>

<h4 class="mb-4">My Profile</h4>

<?php if(session()->getFlashdata('success')): ?>
  <div class="alert alert-success">
    <?= session()->getFlashdata('success') ?>
  </div>
<?php endif ?>

<form method="post" action="<?= site_url('vendor/profile/update') ?>">
<?= csrf_field() ?>
  <div class="row gx-3">
    <!-- Vendor ID -->
    <div class="col-md-6 mb-3">
      <label
        for="vendor_id"
        class="form-label"
      >
        Vendor ID
      </label>
      <input
        type="text"
        id="vendor_id"
        name="vendor_id"
        class="form-control"
        value="<?= esc(old('vendor_id', $profile['vendor_id'] ?? ''), 'attr') ?>"
        readonly
      >
    </div>

    <!-- Vendor Name -->
    <div class="col-md-6 mb-3">
      <label
        for="vendor_name"
        class="form-label"
      >
        Vendor Name
      </label>
      <input
        type="text"
        id="vendor_name"
        name="vendor_name"
        class="form-control"
        value="<?= esc(old('vendor_name', $profile['vendor_name'] ?? ''), 'attr') ?>"
      >
      <?= isset($validation) ? esc($validation->getError('vendor_name')) : '' ?>
    </div>
  </div>

  <div class="row gx-3">
    <!-- Operational Contact Name -->
    <div class="col-md-6 mb-3">
      <label
        for="op_contact_name"
        class="form-label"
      >
        Operational Contact Name
      </label>
      <input
        type="text"
        id="op_contact_name"
        name="op_contact_name"
        class="form-control"
        value="<?= esc(old('op_contact_name', $profile['op_contact_name'] ?? ''), 'attr') ?>"
      >
      <?= isset($validation) ? esc($validation->getError('op_contact_name')) : '' ?>
    </div>

    <!-- Operational Contact Phone -->
    <div class="col-md-6 mb-3">
      <label
        for="op_contact_phone"
        class="form-label"
      >
        Operational Contact Phone
      </label>
      <input
        type="tel"
        id="op_contact_phone"
        name="op_contact_phone"
        class="form-control"
        value="<?= esc(old('op_contact_phone', $profile['op_contact_phone'] ?? ''), 'attr') ?>"
      >
      <?= isset($validation) ? esc($validation->getError('op_contact_phone')) : '' ?>
    </div>
  </div>

  <div class="row gx-3">
    <!-- Operational Contact Email -->
    <div class="col-md-6 mb-3">
      <label
        for="op_contact_email"
        class="form-label"
      >
        Operational Contact Email
      </label>
      <input
        type="email"
        id="op_contact_email"
        name="op_contact_email"
        class="form-control"
        value="<?= esc(old('op_contact_email', $profile['op_contact_email'] ?? ''), 'attr') ?>"
      >
      <?= isset($validation) ? esc($validation->getError('op_contact_email')) : '' ?>
    </div>

    <!-- Description / Specialty -->
    <div class="col-md-6 mb-3">
      <label
        for="description"
        class="form-label"
      >
        Description / Specialty
      </label>
      <textarea
        id="description"
        name="description"
        class="form-control"
        rows="3"
      ><?= esc(old('description', $profile['description'] ?? '')) ?></textarea>
      <?= isset($validation) ? esc($validation->getError('description')) : '' ?>
    </div>
  </div>

  <button
    type="submit"
    class="btn btn-primary"
  >
    Save Changes
  </button>
</form>

<?= $this->endSection() ?>