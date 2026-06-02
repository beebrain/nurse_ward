<?php
/**
 * @var list<array{label: string, icon: string, url: string, hint?: string}> $navItems
 * @var string $currentUrl
 */
?>
<div class="offcanvas offcanvas-end mobile-nav-drawer" tabindex="-1" id="mobileNavDrawer"
  aria-labelledby="mobileNavDrawerLabel">
  <div class="offcanvas-header">
    <h2 class="offcanvas-title h5 mb-0" id="mobileNavDrawerLabel">เมนูทั้งหมด</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="ปิดเมนู"></button>
  </div>
  <div class="offcanvas-body">
    <p class="drawer-nav-section mb-0">นำทาง</p>
    <?php foreach ($navItems as $item): ?>
      <?php $isActive = str_starts_with($currentUrl, $item['url']); ?>
      <a href="<?= $item['url'] ?>"
        class="drawer-nav-link <?= $isActive ? 'active-link' : '' ?>"
        <?= $isActive ? 'aria-current="page"' : '' ?>
        <?= ! empty($item['hint']) ? 'title="' . esc($item['hint']) . '"' : '' ?>>
        <span class="material-symbols-outlined" aria-hidden="true"><?= $item['icon'] ?></span>
        <span><?= esc($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
