<?php
// app/Views/partials/title_manual_bar.php

/**
 * Props you can pass:
 * - $date_range    (string)    e.g. '02 Jan 2025' or '02 Jan 2025 to 07 Jan 2025'
 */

 use CodeIgniter\I18n\Time;
 // today in your timezone (change if needed)
 $fmt = static fn($d) => Time::parse($d, 'Asia/Dhaka')->toLocalizedString('dd MMM yyyy');
 $date_range = ($start_date === $end_date)
   ? $fmt($start_date)
   : $fmt($start_date) . ' to ' . $fmt($end_date);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
  <div>
    <h6 class="mb-0 fw-bold">
    Statistics
    <?= $date_range !== '' ? ' (' . esc($date_range) . ')' : '' ?>
  </div>

  <!-- right: message + button -->
  <div class="d-flex align-items-center gap-2">
    <div class="pill-alert rounded-pill px-2 py-1 d-flex align-items-center gap-1">
      <i class="bi bi-journal-text"></i>
      <span class="small">Please complete your registration by today at 5:00 PM for tomorrow’s lunch</span>
    </div>

    <a href="<?= esc(base_url('templates/LMS_User_Manual_Test.pdf'), 'attr') ?>"
      class="btn btn-sm btn-pink d-flex align-items-center gap-1 px-2 py-1 rounded-pill" target="_blank" rel="noopener">
      <i class="bi bi-file-earmark-pdf"></i>
      Download User Manual
    </a>
  </div>
</div>