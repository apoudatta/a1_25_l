<?php
// Grab all URI segments
$segments = service('uri')->getSegments();

// The very first segment is your role (admin, vendor, employee, etc.)
$role = array_shift($segments) ?? '';

// Filter out numeric IDs so you don’t show “4” or “123”
$crumbs = array_filter($segments, fn($seg) => ! is_numeric($seg));

// We’ll rebuild the path as we go
$base = $role;
$total = count($crumbs);
?>
<div class="pb-2 ps-3">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <!-- Home icon links back to /{role} -->
      <li class="breadcrumb-item">
        <a href="<?= site_url($role.'/dashboard') ?>" class="text-black">
          <i class="bi bi-house-door-fill"></i>
        </a>
      </li>

      <?php $i = 0;
      foreach ($crumbs as $seg):
        $i++;
        // Append this segment to our growing path
        $base .= '/' . $seg;
        // Make it human-readable: e.g. "approval-flows" → "Approval Flows"
        $label = ucwords(str_replace('-', ' ', $seg));
      ?>
        <?php if ($i === $total): ?>
          <li class="breadcrumb-item active" aria-current="page">
            <?= esc($label) ?>
          </li>
        <?php else: ?>
          <li class="breadcrumb-item">
            <a href="<?= site_url($base) ?>" class="text-black">
              <?= esc($label) ?>
            </a>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ol>
  </nav>
</div>
