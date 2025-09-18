<?php
$perm  = $perm  ?? null;
$uri = '';

// Hide item if a permission is required and the user doesn't have it
if ($perm !== null && ! (has_role('SUPER ADMIN') || can($perm))) {
    return; // hide if not permitted
}

?>

<li class="nav-item">
  <a 
    class="nav-link 
      <?= (uri_string() == $path) ? 'active' : '' ?>
      <?= ($label == 'Dashboard') ? 'bg-primary text-white' : '' ?>
    " href="<?= site_url($path) ?>"
  >
    <i class="<?= $icon ?> me-2"></i> <?= esc($label) ?>
  </a>
</li>