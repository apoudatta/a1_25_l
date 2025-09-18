<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0"><?= $heading ?></h4>
  <?php if (
    !empty($add_btn)
    && is_array($add_btn)
    && isset($add_btn[0], $add_btn[1])
  ): ?>
    <a href="<?= site_url($add_btn[1]) ?>" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-lg"></i>
      <?= esc($add_btn[0]) ?>
    </a>
  <?php endif; ?>

</div>