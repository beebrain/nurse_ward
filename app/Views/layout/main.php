<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'ระบบสถิติผู้ป่วยหอผู้ป่วย') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <?= $this->renderSection('styles') ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<?php
$currentUrl = current_url();
$navItems = [];
if (auth()->loggedIn()) {
    $navItems[] = ['label' => 'บันทึกยอดรายวัน', 'icon' => 'clinical_notes', 'url' => base_url('census')];
    if (auth()->user()->can('census.record')) {
        $navItems[] = ['label' => 'พฤติกรรมคนไข้', 'icon' => 'insights', 'url' => base_url('census/behavior-dashboard'), 'hint' => 'Dashboard แสดงพฤติกรรมคนไข้รายวอร์ด'];
        $navItems[] = ['label' => 'ประวัติย้อนหลัง', 'icon' => 'history', 'url' => base_url('census/history')];
        $navItems[] = ['label' => 'Productivity', 'icon' => 'monitoring', 'url' => base_url('census/productivity'), 'hint' => 'ประสิทธิภาพการพยาบาล ทุกแผนก'];
    }
    if (auth()->user()->can('reports.view')) {
        $navItems[] = ['label' => 'สรุปรายวัน', 'icon' => 'table_chart', 'url' => base_url('reports/daily-summary'), 'hint' => 'การเคลื่อนไหวผู้ป่วย ทุกแผนก'];
        $navItems[] = ['label' => 'แดชบอร์ด', 'icon' => 'dashboard', 'url' => base_url('reports/dashboard'), 'hint' => 'เปรียบเทียบและแนวโน้ม'];
        $navItems[] = ['label' => 'ผู้รับผิดชอบแผนก', 'icon' => 'assignment_ind', 'url' => base_url('reports/user-wards')];
    }
    if (auth()->user()->can('wards.manage')) {
        $navItems[] = ['label' => 'จัดการแผนก', 'icon' => 'domain', 'url' => base_url('admin/wards')];
    }
    if (auth()->user()->inGroup('superadmin')) {
        $navItems[] = ['label' => 'บันทึกดิบ HOSxP', 'icon' => 'receipt_long', 'url' => base_url('admin/hosxp-logs'), 'hint' => 'Raw log จาก API ที่ดึงเข้าระบบ'];
        $navItems[] = ['label' => 'จัดการผู้ใช้', 'icon' => 'group', 'url' => base_url('admin/users')];
        $navItems[] = ['label' => 'นำเข้า/ส่งออก', 'icon' => 'import_export', 'url' => base_url('admin/import-export')];
        $navItems[] = ['label' => 'สำรองข้อมูล', 'icon' => 'database', 'url' => base_url('admin/backup')];
    }
    $navItems[] = ['label' => 'เปลี่ยนรหัสผ่าน', 'icon' => 'key', 'url' => base_url('account/change-password')];
}

$bottomNavItems = array_slice($navItems, 0, 4);
$drawerHasActive = false;
foreach (array_slice($navItems, 4) as $item) {
    if (str_starts_with($currentUrl, $item['url'])) {
        $drawerHasActive = true;
        break;
    }
}
?>

<body>
    <a class="skip-link" href="#main-content">ข้ามไปเนื้อหาหลัก</a>
    <div class="bg-orb-top" aria-hidden="true"></div>
    <div class="bg-orb-bottom" aria-hidden="true"></div>
    <header class="top-shell">
        <div class="top-shell-inner">
            <div class="d-flex align-items-center gap-2 gap-md-3 flex-grow-1 min-w-0">
                <?php if (auth()->loggedIn()): ?>
                    <button type="button" class="mobile-menu-header-btn d-lg-none"
                      data-bs-toggle="offcanvas" data-bs-target="#mobileNavDrawer"
                      aria-controls="mobileNavDrawer" aria-label="เปิดเมนูทั้งหมด">
                        <span class="material-symbols-outlined" aria-hidden="true">menu</span>
                    </button>
                <?php endif; ?>
                <a class="brand-link" href="<?= base_url() ?>">
                    <span class="brand-mark"><span class="material-symbols-outlined" aria-hidden="true">shield_with_heart</span></span>
                    <span class="min-w-0">
                        <span class="brand-title d-block text-truncate">ระบบสถิติผู้ป่วยหอผู้ป่วย</span>
                        <span class="brand-subtitle d-block">Clinical Sanctuary Portal</span>
                    </span>
                </a>
                <?php if (auth()->loggedIn()): ?>
                    <div class="top-menu-wrap">
                        <nav class="top-menu" aria-label="เมนูหลัก">
                            <?php foreach ($navItems as $item): ?>
                                <?php $isActive = str_starts_with($currentUrl, $item['url']); ?>
                                <a href="<?= $item['url'] ?>"
                                  class="<?= $isActive ? 'active-link' : '' ?>"
                                  <?= $isActive ? 'aria-current="page"' : '' ?>><?= esc($item['label']) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
            <div class="top-actions">
                <?php if (auth()->loggedIn()): ?>
                    <span class="user-chip" title="<?= esc(auth()->user()->username) ?>">
                        <span class="material-symbols-outlined" aria-hidden="true">account_circle</span>
                        <span><?= esc(auth()->user()->username) ?></span>
                    </span>
                    <a class="ghost-chip" href="<?= base_url('logout') ?>" aria-label="ออกจากระบบ">
                        <span class="material-symbols-outlined" aria-hidden="true">logout</span>
                        <span class="d-none d-md-inline">ออกจากระบบ</span>
                    </a>
                <?php else: ?>
                    <a class="ghost-chip" href="<?= base_url('login') ?>">
                        <span class="material-symbols-outlined" aria-hidden="true">login</span>
                        เข้าสู่ระบบ
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-shell">
        <?php if (auth()->loggedIn()): ?>
            <aside class="side-shell" aria-label="เมนูด้านข้าง">
                <div class="side-card">
                    <div class="side-title">The Sanctuary</div>
                    <div class="side-subtitle">Clinical Portal</div>
                    <nav>
                        <?php foreach ($navItems as $item): ?>
                            <?php $isActive = str_starts_with($currentUrl, $item['url']); ?>
                            <a href="<?= $item['url'] ?>"
                              class="side-nav-link <?= $isActive ? 'active-link' : '' ?>"
                              <?= $isActive ? 'aria-current="page"' : '' ?>
                              <?= ! empty($item['hint']) ? 'title="' . esc($item['hint']) . '"' : '' ?>>
                                <span class="material-symbols-outlined" aria-hidden="true"><?= $item['icon'] ?></span>
                                <span><?= esc($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </aside>
        <?php endif; ?>

        <main class="page-content" id="main-content" tabindex="-1">
            <?php if (session()->getFlashdata('message')): ?>
                <div class="alert alert-success mb-4" role="status">
                    <?= session()->getFlashdata('message') ?>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <?php if (auth()->loggedIn()): ?>
        <?= view('partials/mobile_nav_drawer', ['navItems' => $navItems, 'currentUrl' => $currentUrl]) ?>

        <nav class="bottom-nav d-lg-none" aria-label="เมนูล่าง">
            <?php foreach ($bottomNavItems as $item): ?>
                <?php $isActive = str_starts_with($currentUrl, $item['url']); ?>
                <a href="<?= $item['url'] ?>"
                  class="<?= $isActive ? 'active-link' : '' ?>"
                  <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <span class="material-symbols-outlined" aria-hidden="true"><?= $item['icon'] ?></span>
                    <span><?= esc($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <button type="button"
              class="bottom-nav-menu-btn <?= $drawerHasActive ? 'active-link' : '' ?>"
              data-bs-toggle="offcanvas"
              data-bs-target="#mobileNavDrawer"
              aria-controls="mobileNavDrawer"
              aria-label="เมนูทั้งหมด">
                <span class="material-symbols-outlined" aria-hidden="true">apps</span>
                <span>เมนู</span>
            </button>
        </nav>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>

</html>
