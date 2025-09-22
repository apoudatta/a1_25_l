<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php 
  // Grab the validation service
  $validation = \Config\Services::validation();
?>

<div class="container-fluid px-4">
  <h1 class="mt-4"><?= $isNew ? 'New' : 'Edit' ?> Approval Step</h1>

  <?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
  <?php endif ?>

  <form id="stepForm"
        action="<?= site_url($isNew
           ? "approval-flows/{$flowId}/steps"
           : "approval-flows/{$flowId}/steps/{$step['id']}") ?>"
        method="post" novalidate>
    <?= csrf_field() ?>
    <?php if (! $isNew): ?>
      <input type="hidden" name="_method" value="PUT">
    <?php endif ?>

    <!-- Step Order -->
    <div class="mb-3">
      <label class="form-label" for="step_order">Step Order</label>
      <input type="number"
             id="step_order"
             name="step_order"
             min="1"
             class="form-control <?= $validation->hasError('step_order') ? 'is-invalid':'' ?>"
             required
             value="<?= old('step_order', $step['step_order'] ?? '') ?>">
      <div class="invalid-feedback">
        <?= $validation->getError('step_order') ?? 'Please enter a valid step order.' ?>
      </div>
    </div>

    <!-- Approver Type -->
    <div class="mb-3">
      <label class="form-label" for="approverType">Approver Type</label>
      <select name="approver_type"
              id="approverType"
              class="form-select <?= $validation->hasError('approver_type') ? 'is-invalid':'' ?>"
              required>
        <option value="">— Select Type —</option>
        <?php foreach($types as $t): ?>
          <option value="<?= esc($t) ?>"
            <?= old('approver_type', $step['approver_type'] ?? '') === $t ? 'selected':''?>>
            <?= esc($t) ?>
          </option>
        <?php endforeach ?>
      </select>
      <div class="invalid-feedback">
        <?= $validation->getError('approver_type') ?? 'Please choose an approver type.' ?>
      </div>
    </div>

    <!-- ROLE group -->
    <div id="roleGroup" class="mb-3" style="display:none;">
      <label class="form-label" for="approver_role">Approver Role</label>
      <select name="approver_role"
              id="approver_role"
              class="form-select <?= $validation->hasError('approver_role') ? 'is-invalid':'' ?>">
        <option value="">— Select Role —</option>
        <?php foreach($roles as $r): ?>
          <option value="<?= esc($r['id']) ?>"
            <?= old('approver_role', $step['approver_role'] ?? '') == $r['id'] ? 'selected':''?>>
            <?= esc($r['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <div class="invalid-feedback">
        <?= $validation->getError('approver_role') ?? 'Select a role for approval.' ?>
      </div>
    </div>

    <!-- USER group -->
    <div id="userGroup" class="mb-3" style="display:none;">
      <label class="form-label" for="approver_user_id">Approver User</label>
      <select name="approver_user_id"
              id="approver_user_id"
              class="form-select <?= $validation->hasError('approver_user_id') ? 'is-invalid':'' ?>">
        <option value="">— Select User —</option>
        <?php foreach($users as $u): ?>
          <option value="<?= esc($u['id']) ?>"
            <?= old('approver_user_id', $step['approver_user_id'] ?? '') == $u['id'] ? 'selected':''?>>
            <?= esc($u['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <div class="invalid-feedback">
        <?= $validation->getError('approver_user_id') ?? 'Select a user for approval.' ?>
      </div>
    </div>

    <!-- LINE_MANAGER group -->
    <div id="fallbackGroup" class="mb-3" style="display:none;">
      <label class="form-label" for="fallback_role">Fallback Role</label>
      <select name="fallback_role"
              id="fallback_role"
              class="form-select <?= $validation->hasError('fallback_role') ? 'is-invalid':'' ?>">
        <option value="">— Select Fallback Role —</option>
        <?php foreach($roles as $r): ?>
          <option value="<?= esc($r['id']) ?>"
            <?= old('fallback_role', $step['fallback_role'] ?? '') == $r['id'] ? 'selected':''?>>
            <?= esc($r['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
      <div class="invalid-feedback">
        <?= $validation->getError('fallback_role') ?? 'Choose a fallback role.' ?>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">
      <?= $isNew ? 'Add Step' : 'Update Step' ?>
    </button>
    <a href="<?= site_url("approval-flows/{$flowId}/steps") ?>"
       class="btn btn-secondary">Cancel</a>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const form   = document.getElementById('stepForm');
  const typeEl = document.getElementById('approverType');
  const roleG  = document.getElementById('roleGroup');
  const userG  = document.getElementById('userGroup');
  const fallG  = document.getElementById('fallbackGroup');

  // show/hide and enable/disable the three fields
  function toggleGroups() {
    const v = typeEl.value;
    // ROLE
    roleG.style.display     = v==='ROLE'         ? '' : 'none';
    document.getElementById('approver_role').disabled = v!=='ROLE';
    // USER
    userG.style.display     = v==='USER'         ? '' : 'none';
    document.getElementById('approver_user_id').disabled = v!=='USER';
    // LINE_MANAGER
    fallG.style.display     = v==='LINE_MANAGER' ? '' : 'none';
    document.getElementById('fallback_role').disabled = v!=='LINE_MANAGER';
  }

  typeEl.addEventListener('change', toggleGroups);
  toggleGroups(); // initial on page load

  // client-side guard: exactly one approver field must be set
  form.addEventListener('submit', function(e){
    const v = typeEl.value;
    let valid = true;
    if (v==='ROLE' && ! document.getElementById('approver_role').value) {
      valid = false;
    }
    if (v==='USER' && ! document.getElementById('approver_user_id').value) {
      valid = false;
    }
    if (v==='LINE_MANAGER' && ! document.getElementById('fallback_role').value) {
      valid = false;
    }
    if (! valid) {
      e.preventDefault();
      e.stopPropagation();
      alert('Please fill the field matching your chosen Approver Type.');
    }
  });
});
</script>

<?= $this->endSection() ?>