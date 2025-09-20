<tbody>
<?php
  // compute once per page
  $today = new DateTime('today'); // 00:00 today
  $now   = new DateTime();        // current time
?>

<?php foreach($rows as $index => $r): ?>
  <?php
    // meal date
    $mealDate = new DateTime($r['subs_date']);

    // cutoff settings (with safe defaults)
    $leadDays = isset($r['lead_days']) ? (int)$r['lead_days'] : 0;
    $cutoff   = $r['cutoff_time'] ?? '23:59:59';
    [$hh,$mm,$ss] = array_map('intval', array_pad(explode(':', $cutoff), 3, 0));

    // deadline = (mealDate - lead_days) @ cut_off_time
    $deadline = (clone $mealDate)->modify("-{$leadDays} days")->setTime($hh,$mm,$ss);

    // allow unsubscribe only if ACTIVE + strictly future meal date + before deadline
    $canUnsubscribe = ($r['status'] === 'ACTIVE') && ($mealDate > $today) && ($now < $deadline);
  ?>
  <tr id="row-<?= $r['id'] ?>">
    <td>
      <?php if ($canUnsubscribe): ?>
        <input type="checkbox" name="subscription_ids[]" value="<?= $r['id'] ?>" class="row-checkbox">
      <?php else: ?>
        <input type="checkbox" disabled>
      <?php endif ?>
    </td>

    <td><?= esc($index + 1) ?></td>

    <!-- Emp. ID & Emp. Name -->
    <?php if(isset($employee_id) && $employee_id == true): ?>
      <td><?= esc($r['employee_id']) ?></td>
      <td><?= esc($r['name']) ?></td>
    <?php endif ?>

    
    <!-- Guest Type -->
    <?php if(isset($guest_type) && $guest_type == true): ?>
      <td><?= esc($r['emp_type_name']) ?></td>
    <?php endif ?>

    <!-- Subscription Type -->
    <?php if(isset($subs_type) && $subs_type == true): ?>
      <td><?= esc($r['employment_type_name']) ?></td>
    <?php endif ?>

    <!-- Intern Emp. ID -->
    <?php if(isset($list) && $list == 'intern'): ?>
      <td><?= esc($r['user_reference_id']) ?></td>
    <?php endif ?>
    
    <!-- Subs/Unsubs Date -->
    <?php if(isset($list) && $list != 'intern'): ?>
    <td><?= esc(date('d M Y', strtotime(($r['status']=='CANCELLED') ? $r['updated_at'] : $r['created_at']))) ?></td>
    <?php endif ?>

    <!-- Name & Phone -->
    <?php if(isset($list) && (($list == 'intern') || ($list != 'ramadan'))): ?>
      <td><?= esc($r['ref_name']) ?></td>
      <td><?= esc($r['ref_phone']) ?></td>
    <?php endif ?>

    <!-- Meal Date -->
    <td
      class="meal-date-cell"
      data-meal-date="<?= esc(date('Y-m-d', strtotime($r['subs_date'])), 'attr') ?>"
      data-order="<?= esc(date('Y-m-d', strtotime($r['subs_date'])), 'attr') ?>"
    >
      <?= esc(date('d M Y', strtotime($r['subs_date']))) ?>
    </td>

    <!-- Meal Type Name -->
    <td><?= esc($r['meal_type_name']) ?></td>

    <!-- cafeteria-name -->
    <td
      class="cafeteria-cell"
      data-cafeteria-name="<?= esc($r['caffname'], 'attr') ?>"
    >
      <?= esc($r['caffname']) ?>
    </td>

    <!-- otp -->
    <?php if(isset($list) && (($list == 'intern') || ($list == 'guest'))): ?>
      <td><?= esc($r['otp']) ?></td>
    <?php endif ?>

    <!-- Status -->
    <td><?= esc($r['status']) ?></td>

    <!-- Action -->
    <td>
      <?php if ($canUnsubscribe): ?>
        <?php if ($showUnsubs): ?>
          <button
            type="submit"
            class="btn btn-sm btn-danger"
            formaction="<?= site_url("admin/".$unsubs."/unsubscribe/{$r['id']}") ?>"
            formmethod="post"
            data-bs-toggle="tooltip"
            title="Unsubscribe"
            id="unsubscribe_btn"
          >
            <i class="bi bi-x-circle"></i>
          </button>
        <?php endif; ?>
      <?php endif; ?>
    </td>
  </tr>
<?php endforeach ?>
</tbody>
