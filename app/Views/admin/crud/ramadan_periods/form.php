<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($row); ?>
<h2 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Ramadan Period</h2>

<form action="<?= $isEdit
    ? site_url("admin/ramadan-periods/{$row['id']}")
    : site_url('admin/ramadan-periods') ?>"
  method="post" class="row g-3">
  <?= csrf_field() ?>
  <?php if($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="col-md-2">
    <label class="form-label">Year</label>
    <?php
      $selectedYear = old('year') ?? ($isEdit ? (string) $row['year'] : date('Y'));
      $startYear    = date('Y') - 2; // show 2 previous years
      $endYear      = date('Y') + 2; // show 2 future years
    ?>
    <select
      name="year"
      id="year"
      class="form-select"
      required
    >
      <?php for ($y = $endYear; $y >= $startYear; $y--): ?>
        <option
          value="<?= esc($y, 'attr') ?>"
          <?= ($selectedYear == (string) $y) ? 'selected' : '' ?>
        ><?= esc($y) ?></option>
      <?php endfor ?>
    </select>
  </div>


  <?php $today = date('Y-m-d'); ?>
  <div class="col-md-3">
    <label class="form-label">Start Date</label>
    <input
      id="start_date"
      name="start_date"
      type="date"
      class="form-control"
      required
      min="<?= $isEdit ? '' : $today ?>"
      value="<?= $isEdit ? esc($row['start_date'], 'attr') : esc(set_value('start_date'), 'attr') ?>"
    >
    <div class="invalid-feedback">
      Start Date is required.
    </div>
  </div>

  <div class="col-md-3">
    <label class="form-label">End Date</label>
    <input
      id="end_date"
      name="end_date"
      type="date"
      class="form-control"
      required
      min="<?= $isEdit ? '' : $today ?>"
      value="<?= $isEdit ? esc($row['end_date'], 'attr') : esc(set_value('end_date'), 'attr') ?>"
    >
    <div class="invalid-feedback">
      End Date must be the same or after Start Date.
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-success"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('admin/ramadan-periods')?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= view('partials/flash_message') ?>

<script>
  (function () {
    const start = document.getElementById('start_date');
    const end   = document.getElementById('end_date');
    const form  = start?.closest('form');

    function syncAndValidate() {
      // Keep end.min at least start.value (if provided)
      if (start.value) {
        end.min = start.value;
      }

      // Optionally cap start.max to chosen end (helps keyboard edits)
      if (end.value) {
        start.max = end.value;
      } else {
        start.removeAttribute('max');
      }

      // Clear previous custom messages
      start.setCustomValidity('');
      end.setCustomValidity('');

      // Validate Start ≤ End
      if (start.value && end.value && start.value > end.value) {
        end.setCustomValidity('End Date must be the same or after Start Date.');
      }
    }

    if (start && end) {
      ['input', 'change'].forEach(evt => {
        start.addEventListener(evt, syncAndValidate);
        end.addEventListener(evt,   syncAndValidate);
      });
      // Initial pass
      syncAndValidate();

      if (form) {
        form.addEventListener('submit', function (e) {
          syncAndValidate();
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
          }
          form.classList.add('was-validated');
        });
      }
    }
  })();
</script>

<?= $this->endSection() ?>