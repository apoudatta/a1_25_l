<div class="col-md-6 g-0">
  <div class="card shadow border-0 shortcut-card">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="mb-0 fw-semibold">Menu Shortcuts</h6>
      </div>
      <div class="row row-cols-2 row-cols-md-3 g-3">
        <?php foreach ($shortcutMenus as $it): ?>
          <div class="col">
            <a class="shortcut" href="<?= esc($it['url']) ?>">
              <i class="bi <?= esc($it['icon']) ?>"></i>
              <div class="title"><?= esc($it['text']) ?></div>
            </a>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </div>
</div>