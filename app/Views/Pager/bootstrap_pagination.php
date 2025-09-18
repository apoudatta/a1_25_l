<?php if ($pager->getPageCount() <= 1) return; ?>

<nav aria-label="Page navigation example">
  <ul class="pagination justify-content-end">
    <?php if ($pager->hasPreviousPage()): ?>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getFirst() ?>">
            <i class="bi bi-chevron-bar-left"></i>
        </a>
      </li>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getPreviousPage() ?>">
            <i class="bi bi-chevron-left"></i>
        </a>
      </li>
    <?php else: ?>
      <li class="page-item disabled"><span class="page-link">
        <i class="bi bi-chevron-bar-left"></i>
      </span></li>
      <li class="page-item disabled"><span class="page-link">
        <i class="bi bi-chevron-left"></i>
      </span></li>
    <?php endif ?>

    <?php foreach ($pager->links() as $link): ?>
      <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
        <a class="page-link" href="<?= $link['uri'] ?>">
          <?= $link['title'] ?>
        </a>
      </li>
    <?php endforeach ?>

    <?php if ($pager->hasNextPage()): ?>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getNextPage() ?>">
            <i class="bi bi-chevron-right"></i>
        </a>
      </li>
      <li class="page-item">
        <a class="page-link" href="<?= $pager->getLast() ?>">
            <i class="bi bi-chevron-bar-right"></i>
        </a>
      </li>
    <?php else: ?>
      <li class="page-item disabled"><span class="page-link">
        <i class="bi bi-chevron-right"></i>
      </span></li>
      <li class="page-item disabled"><span class="page-link">
        <i class="bi bi-chevron-bar-right"></i>
      </span></li>
    <?php endif ?>
  </ul>
</nav>
