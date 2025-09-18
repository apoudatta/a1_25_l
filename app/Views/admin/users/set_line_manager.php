<?= $this->extend('layouts/admin') ?>
<?= $this->section('title') ?>User Management – bKash LMS<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php helper('auth'); // ensure can()/has_role() available ?>

<?= view('partials/content_heading', [
  'heading' => 'User Management',
]) ?>

<?= view('partials/flash_message') ?>
<div class="container my-4">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="card shadow-sm">
        <div class="card-body">

          <?php if (session('error')): ?>
            <div class="alert alert-danger"><?= esc(session('error')) ?></div>
          <?php endif; ?>
          <?php if (session('message')): ?>
            <div class="alert alert-success"><?= esc(session('message')) ?></div>
          <?php endif; ?>

          <div class="mb-3">
            <div class="small text-muted">Employee</div>
            <div class="fw-semibold">
              <?= esc($employee['name'] ?? ('User #'.$employee['id'])) ?>
              <span class="text-muted"> — <?= esc($employee['email']) ?></span>
            </div>
          </div>

          <form method="post" action="<?= esc($action) ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label">Line Manager</label>
              <select name="line_manager_id" class="form-select js-lm-select" required>
                <option value="">-- Choose a user --</option>
                <?php foreach ($allUsers as $u): ?>
                  <option value="<?= (int)$u['id'] ?>"
                    <?= (isset($currentLmId) && (int)$currentLmId === (int)$u['id']) ? 'selected' : '' ?>>
                    <?= esc($u['employee_id']) ?> ( <?= esc($u['name']) ?> - <?= esc($u['email']) ?> )
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Only active users are listed.</div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">Save</button>
              <a href="<?= site_url('admin/users') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
  const $sel = $('.js-lm-select');

  // Custom matcher so partials like "08" or "fa" work nicely even with spaces/parentheses
  function containsMatcher(params, data) {
    const term = (params.term || '').toLowerCase().trim();
    if (!term) return data;
    if (!data.text) return null;

    const text = data.text.toLowerCase();
    // normalize by removing spaces, dashes, parentheses
    const normText = text.replace(/[()\-\s]/g, '');
    const normTerm = term.replace(/\s/g, '');

    return (text.indexOf(term) > -1 || normText.indexOf(normTerm) > -1) ? data : null;
  }

  $sel.select2({
    width: '100%',
    placeholder: '-- Choose a user --',
    allowClear: true,
    dropdownParent: $('.card-body').first(), // avoids z-index issues inside card
    matcher: containsMatcher
  });
});
</script>

<?= $this->endSection() ?>
