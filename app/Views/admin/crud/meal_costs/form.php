<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php $isEdit = isset($cost); ?>
<h4 class="mb-4"><?= $isEdit ? 'Edit' : 'New' ?> Meal Cost</h4>

<form action="<?= $isEdit
    ? site_url("meal-costs/{$cost['id']}")
    : site_url('meal-costs') ?>"
  method="post" class="row g-3">

  <?= csrf_field() ?>
  <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="POST">
  <?php endif ?>

  <div class="col-md-3">
    <label class="form-label">Meal Type</label>
    <select name="meal_type_id" id="mealTypeSelect" class="form-select" required>
      <?php foreach($mealTypes as $mt): ?>
      <option value="<?= esc($mt['id']) ?>"
        <?= $isEdit && $cost['meal_type_id']==$mt['id'] ? 'selected' : '' ?>>
        <?= esc($mt['name']) ?>
      </option>
      <?php endforeach ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Effective Date</label>
    <input
      name="effective_date"
      id="effectiveDateInput"
      type="text"            
      class="form-control"
      required
      value="<?= $isEdit ? esc($cost['effective_date']) : '' ?>">
  </div>

  <div class="col-md-3">
    <label class="form-label">Base Price</label>
    <input name="base_price"
           type="number"
           step="0.01"
           class="form-control"
           required
           id="basePrice"
           value="<?= $isEdit ? esc($cost['base_price']) : '' ?>">
  </div>

  <div class="col-md-3 form-check align-self-end">
    <input type="checkbox"
           name="is_active"
           value="1"
           id="isActive"
           class="form-check-input"
           <?= !$isEdit ? 'checked' : '' ?>
           <?= $isEdit && $cost['is_active'] ? 'checked' : '' ?>>
    <label class="form-check-label" for="isActive">Active</label>
  </div>

  <div class="col-12">
    <button class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    <a href="<?= site_url('meal-costs')?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- JS: flatpickr + AJAX logic -->
<script>
(function () {
  // REQUIREMENT: flatpickr library must be loaded globally.
  // If not already included in your layout, add the scripts/styles there.

  const $mealType  = document.getElementById('mealTypeSelect');
  const $dateInput = document.getElementById('effectiveDateInput');

  // helpers
  const fmtYmd = d => {
    const z = n => (n < 10 ? '0' + n : n);
    return d.getFullYear() + '-' + z(d.getMonth() + 1) + '-' + z(d.getDate());
  };
  const addDays = (date, n) => {
    const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    d.setDate(d.getDate() + n);
    return d;
  };

  const today = new Date(); today.setHours(0,0,0,0);

  // init flatpickr
  let fp = null;
  if (window.flatpickr) {
    fp = flatpickr($dateInput, {
      dateFormat: 'Y-m-d',
      // temporary minDate; will be updated after AJAX
      minDate: fmtYmd(today),
      // keep current value if editing
      defaultDate: '<?= $isEdit ? esc($cost['effective_date'] ?? '', 'attr') : '' ?>' || null,
      disableMobile: true
    });
  } else {
    // graceful fallback if flatpickr is not present
    $dateInput.setAttribute('type', 'date');
    $dateInput.setAttribute('min', fmtYmd(today));
  }

  async function refreshMin() {
    const mealTypeId = $mealType.value;
    if (!mealTypeId) return;

    try {
      const url = '<?= site_url('meal-costs/horizon') ?>/' + encodeURIComponent(mealTypeId);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
      const json = await res.json();

      const horizon = parseInt(json.max_horizon_days ?? 0, 10);
      // "Effective Date starts AFTER max_horizon_days":
      // e.g., horizon=30  => next 30 days disabled, earliest is day 31.
      const minDate = addDays(today, (isNaN(horizon) ? 0 : horizon) + 1);

      if (fp) {
        fp.set('minDate', fmtYmd(minDate));
      } else {
        $dateInput.setAttribute('min', fmtYmd(minDate));
      }
    } catch (e) {
      // fallback to today if endpoint fails
      if (fp) fp.set('minDate', fmtYmd(addDays(today, 1)));
      else $dateInput.setAttribute('min', fmtYmd(addDays(today, 1)));
      console.warn('Failed to load horizon:', e);
    }
  }

  // when meal type changes → fetch horizon & update minDate
  $mealType.addEventListener('change', refreshMin);

  // run once on load (for the initially selected meal type)
  refreshMin();
})();
</script>

<?= $this->endSection() ?>
