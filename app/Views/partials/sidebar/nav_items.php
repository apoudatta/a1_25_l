<?php 
$perm          = $perm          ?? null;
if ($perm && ! (has_role('SUPER ADMIN') || can($perm))) {
  return; // hide if not permitted
}

$setting_paths = [
  'admin/meal-cards',
  'admin/approval-flows',
  'admin/cafeterias',
  'admin/meal-costs',
  'admin/contributions',
  'admin/public-holidays',
  'admin/occasions',
  'admin/cutoff-times',
  'admin/ramadan-periods',
  'admin/meal-types',
  'admin/employment-types',
];

$uri = uri_string();

$isSettingsActive = false;
foreach ($setting_paths as $p) {
    if (str_starts_with($uri, $p)) {
        $isSettingsActive = true;
        break;
    }
}
?>

<?php if (isset($subMenu)):
  // normalize URI (handle index.php/)
  $uri = trim(uri_string(), '/');
  $uri = preg_replace('#^index\.php/#', '', $uri);

  if ($path === 'settings') {
      $expanded      = $isSettingsActive;
  } else {
      // collect prefixes to match: parent path + all submenu paths (+ path1)
      $prefixes = [ trim((string)($path ?? ''), '/') ];
      foreach ((array)$subMenu as $sm) {
          $p  = trim((string)($sm['path']  ?? ''), '/');
          $p1 = trim((string)($sm['path1'] ?? ''), '/');
          if ($p  !== '') $prefixes[] = $p;
          if ($p1 !== '') $prefixes[] = $p1;
      }

      // expand if URI starts with any collected prefix
      $expanded = false;
      foreach (array_unique($prefixes) as $pref) {
          if ($pref !== '' && str_starts_with($uri, $pref)) {
              $expanded = true;
              break;
          }
      }
  }

  $aria_expanded = $expanded ? 'true' : 'false';
  $active        = $expanded ? 'active' : 'collapsed';
  $show          = $expanded ? 'show' : '';
?>

  <li class="nav-item">
    <a class="nav-link d-flex justify-content-between align-items-center
      <?= $active ?>"
      data-bs-toggle="collapse"
      href="#<?= $collapseName ?>" 
      role="button" 
      aria-expanded="<?= $aria_expanded ?>" 
      aria-controls="<?= $collapseName ?>"
    >
      <span>
        <i class="<?= $icon ?> me-2"></i>
        <?= esc($label) ?> 
      </span>
      <i class="bi bi-chevron-down"></i>
    </a>

    <ul 
      class="collapse list-unstyled ps-3 <?= $show ?>"
      id="<?= $collapseName ?>" 
      data-bs-parent="#sidebarMenu"
    >
      <?php foreach ($subMenu as $subM): ?>
        <?= view('partials/sidebar/nav_item', $subM) ?>

      <?php endforeach ?>
    </ul>
  </li>

<?php else: ?>

  
  <!-- Sub menu not found -->
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

<?php endif ?>


