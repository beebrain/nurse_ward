<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ระบบสถิติผู้ป่วยหอผู้ป่วย' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --surface: #f9f9ff;
            --surface-low: #f2f3fc;
            --surface-card: #ffffff;
            --surface-high: #e6e8f0;
            --surface-highest: #e0e2ea;
            --text-main: #181c21;
            --text-muted: #414752;
            --primary: #005dac;
            --primary-strong: #1976d2;
            --secondary-soft: #98f994;
            --secondary-text: #0c7521;
            --danger-soft: #ffdad6;
            --danger-text: #93000a;
            --outline-soft: rgba(193, 198, 212, 0.22);
            --shadow-soft: 0 12px 32px rgba(0, 95, 175, 0.06);
        }

        body {
            background: var(--surface);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .brand-text,
        .nav-link,
        .btn,
        .headline-font {
            font-family: 'Manrope', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .bg-orb-top,
        .bg-orb-bottom {
            position: fixed;
            z-index: 0;
            border-radius: 999px;
            filter: blur(120px);
            pointer-events: none;
        }

        .bg-orb-top {
            width: 34rem;
            height: 34rem;
            top: -10rem;
            right: -8rem;
            background: rgba(0, 93, 172, 0.08);
        }

        .bg-orb-bottom {
            width: 28rem;
            height: 28rem;
            bottom: -10rem;
            left: -8rem;
            background: rgba(152, 249, 148, 0.12);
        }

        .top-shell {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: rgba(249, 249, 255, 0.74);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-soft);
            border-bottom: 1px solid rgba(193, 198, 212, 0.12);
        }

        .top-shell-inner {
            max-width: 1440px;
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .brand-mark {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 24px rgba(0, 95, 175, 0.18);
        }

        .brand-link {
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .brand-title {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 600;
        }

        .top-menu {
            display: none;
            align-items: center;
            gap: 1.25rem;
        }

        .top-menu a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .top-menu a:hover,
        .top-menu a.active-link {
            color: var(--primary);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .ghost-chip,
        .user-chip {
            border: 0;
            background: var(--surface-low);
            border-radius: 999px;
            padding: 0.7rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .ghost-chip:hover {
            background: var(--surface-high);
            color: var(--text-main);
        }

        .page-shell {
            position: relative;
            z-index: 1;
            max-width: 1440px;
            margin: 0 auto;
            display: flex;
            min-height: calc(100vh - 84px);
        }

        .side-shell {
            width: 280px;
            padding: 1.5rem 1rem 6rem;
            display: none;
        }

        .side-card {
            position: sticky;
            top: 108px;
            background: rgba(255, 255, 255, 0.76);
            backdrop-filter: blur(18px);
            border-radius: 1.75rem;
            box-shadow: var(--shadow-soft);
            padding: 1.25rem;
        }

        .side-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.2rem;
        }

        .side-subtitle {
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .side-nav-link {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: var(--text-muted);
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .side-nav-link:hover,
        .side-nav-link.active-link {
            background: rgba(0, 93, 172, 0.08);
            color: var(--primary);
        }

        .page-content {
            flex: 1;
            padding: 1.5rem 1rem 6rem;
        }

        .content-card,
        .card,
        .alert,
        .table-responsive,
        .modal-content {
            border: 0;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-soft);
        }

        .card,
        .content-card,
        .table-responsive {
            background: var(--surface-card);
        }

        .card-header {
            background: var(--surface-low) !important;
            border-bottom: 0 !important;
            border-top-left-radius: 1.5rem !important;
            border-top-right-radius: 1.5rem !important;
            padding: 1.1rem 1.4rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-control,
        .form-select,
        textarea {
            border: 0;
            background: var(--surface-high);
            border-radius: 1rem;
            min-height: 50px;
            padding: 0.9rem 1rem;
            color: var(--text-main);
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            background: #fff;
            box-shadow: 0 0 0 0.25rem rgba(0, 93, 172, 0.12);
        }

        .btn-primary,
        .btn-success {
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%);
            box-shadow: 0 16px 28px rgba(0, 95, 175, 0.18);
            padding-left: 1.2rem;
            padding-right: 1.2rem;
            font-weight: 800;
        }

        .btn-outline-secondary,
        .btn-outline-primary,
        .btn-outline-danger,
        .btn-outline-success {
            border-radius: 999px;
            border-color: rgba(0, 93, 172, 0.12);
        }

        .alert-success {
            background: rgba(152, 249, 148, 0.28);
            color: var(--secondary-text);
        }

        .alert-danger {
            background: rgba(255, 218, 214, 0.9);
            color: var(--danger-text);
        }

        .table {
            margin-bottom: 0;
        }

        .table> :not(caption)>*>* {
            padding: 1rem 1.1rem;
            border-bottom-color: rgba(193, 198, 212, 0.24);
        }

        .table-light,
        .bg-light {
            background: var(--surface-low) !important;
        }

        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1030;
            display: flex;
            justify-content: space-around;
            padding: 0.8rem 0.75rem 1.2rem;
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(18px);
            box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.04);
        }

        .bottom-nav a {
            text-decoration: none;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.18rem;
            font-size: 0.68rem;
            font-weight: 800;
            padding: 0.55rem 0.9rem;
            border-radius: 1rem;
        }

        .bottom-nav a.active-link {
            background: rgba(0, 93, 172, 0.1);
            color: var(--primary);
        }

        @media (min-width: 992px) {
            .top-menu {
                display: flex;
            }

            .side-shell {
                display: block;
            }

            .page-content {
                padding: 2.25rem 1.5rem 3rem 0;
            }

            .bottom-nav {
                display: none;
            }
        }
    </style>
</head>

<?php
$currentUrl = current_url();
$navItems = [];
if (auth()->loggedIn()) {
    $navItems[] = ['label' => 'บันทึกยอดรายวัน', 'icon' => 'clinical_notes', 'url' => base_url('census')];
    if (auth()->user()->can('reports.view')) {
        $navItems[] = ['label' => 'สรุปรายเดือน', 'icon' => 'summarize', 'url' => base_url('reports/monthly')];
        $navItems[] = ['label' => 'ตารางรายวัน', 'icon' => 'table_chart', 'url' => base_url('reports/daily-summary')];
        $navItems[] = ['label' => 'แดชบอร์ด', 'icon' => 'dashboard', 'url' => base_url('reports/dashboard')];
    }
    if (auth()->user()->can('wards.manage')) {
        $navItems[] = ['label' => 'จัดการแผนก', 'icon' => 'domain', 'url' => base_url('admin/wards')];
    }
    if (auth()->user()->inGroup('superadmin')) {
        $navItems[] = ['label' => 'จัดการผู้ใช้', 'icon' => 'group', 'url' => base_url('admin/users')];
    }
}
?>

<body>
    <div class="bg-orb-top"></div>
    <div class="bg-orb-bottom"></div>
    <header class="top-shell">
        <div class="top-shell-inner">
            <div class="d-flex align-items-center gap-4">
                <a class="brand-link" href="<?= base_url() ?>">
                    <span class="brand-mark"><span class="material-symbols-outlined">shield_with_heart</span></span>
                    <span>
                        <span class="brand-title d-block">ระบบสถิติผู้ป่วยหอผู้ป่วย</span>
                        <span class="brand-subtitle d-block">Clinical Sanctuary Portal</span>
                    </span>
                </a>
                <?php if (auth()->loggedIn()): ?>
                    <nav class="top-menu">
                        <?php foreach ($navItems as $item): ?>
                            <a href="<?= $item['url'] ?>" class="<?= str_starts_with($currentUrl, $item['url']) ? 'active-link' : '' ?>"><?= $item['label'] ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </div>
            <div class="top-actions">
                <?php if (auth()->loggedIn()): ?>
                    <span class="user-chip"><span class="material-symbols-outlined">account_circle</span><?= auth()->user()->username ?></span>
                    <a class="ghost-chip" href="<?= base_url('logout') ?>"><span class="material-symbols-outlined">logout</span>ออกจากระบบ</a>
                <?php else: ?>
                    <a class="ghost-chip" href="<?= base_url('login') ?>"><span class="material-symbols-outlined">login</span>เข้าสู่ระบบ</a>
                    <a class="ghost-chip" href="<?= base_url('register') ?>"><span class="material-symbols-outlined">person_add</span>ลงทะเบียน</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="page-shell">
        <?php if (auth()->loggedIn()): ?>
            <aside class="side-shell">
                <div class="side-card">
                    <div class="side-title">The Sanctuary</div>
                    <div class="side-subtitle">Clinical Portal</div>
                    <?php foreach ($navItems as $item): ?>
                        <a href="<?= $item['url'] ?>" class="side-nav-link <?= str_starts_with($currentUrl, $item['url']) ? 'active-link' : '' ?>">
                            <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
                            <span><?= $item['label'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        <?php endif; ?>

        <main class="page-content">
            <?php if (session()->getFlashdata('message')): ?>
                <div class="alert alert-success mb-4">
                    <?= session()->getFlashdata('message') ?>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger mb-4">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <?php if (auth()->loggedIn()): ?>
        <nav class="bottom-nav">
            <?php foreach (array_slice($navItems, 0, 4) as $item): ?>
                <a href="<?= $item['url'] ?>" class="<?= str_starts_with($currentUrl, $item['url']) ? 'active-link' : '' ?>">
                    <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>