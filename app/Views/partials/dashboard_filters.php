<?php
/**
 * Flexible dashboard filter bar.
 * Renders selects only if the corresponding arrays are passed in:
 * - $employeeTypes (array of strings)
 * - $mealTypes     (array of ['id','name'])
 * - $cafeterias    (array of ['id','name'])
 *
 * Also expects current values:
 * - $employee_type, $meal_type_id, $cafeteria_id
 * - $start_date, $end_date  (YYYY-MM-DD)
 */
$today = date('Y-m-d');
$start = $start_date ?? $today;
$end   = $end_date   ?? $today;
?>

<form class="d-flex justify-content-end mb-3"
      method="get"
      action="<?= esc(current_url()) ?>">
  <div class="d-flex flex-wrap align-items-center gap-2">

    <?php if (!empty($employeeTypes)): ?>
      <select class="form-select form-select-sm w-auto"
              name="employee_type">
        <option value="">Employee Type</option>
        <?php foreach ($employeeTypes as $et): ?>
          <option value="<?= esc($et, 'attr') ?>"
                  <?= isset($employee_type) && strcasecmp($employee_type, $et) === 0 ? 'selected' : '' ?>>
            <?= esc($et) ?>
          </option>
        <?php endforeach ?>
      </select>
    <?php endif; ?>

    <?php if (!empty($mealTypes)): ?>
      <select class="form-select form-select-sm w-auto"
              name="meal_type_id">
        <option value="">Meal Type</option>
        <?php foreach ($mealTypes as $mt): ?>
          <option value="<?= esc($mt['id'], 'attr') ?>"
                  <?= isset($meal_type_id) && (string)$meal_type_id === (string)$mt['id'] ? 'selected' : '' ?>>
            <?= esc($mt['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
    <?php endif; ?>

    <?php if (!empty($cafeterias)): ?>
      <select class="form-select form-select-sm w-auto"
              name="cafeteria_id">
        <option value="">Cafeteria</option>
        <?php foreach ($cafeterias as $c): ?>
          <option value="<?= esc($c['id'], 'attr') ?>"
                  <?= isset($cafeteria_id) && (string)$cafeteria_id === (string)$c['id'] ? 'selected' : '' ?>>
            <?= esc($c['name']) ?>
          </option>
        <?php endforeach ?>
      </select>
    <?php endif; ?>

    <input type="date"
           class="form-control form-control-sm w-auto"
           name="start_date"
           value="<?= esc($start, 'attr') ?>"
           max="<?= esc(date('Y-m-d'), 'attr') ?>">

    <input type="date"
           class="form-control form-control-sm w-auto"
           name="end_date"
           value="<?= esc($end, 'attr') ?>"
           max="<?= esc(date('Y-m-d'), 'attr') ?>">

    <button type="submit" class="btn btn-sm btn-outline-secondary">Apply</button>
  </div>
</form>
