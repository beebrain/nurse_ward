<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบสถิติผู้ป่วยหอผู้ป่วย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="<?= asset_url('css/app.css') ?>" rel="stylesheet">
    <link href="<?= asset_url('css/login.css') ?>" rel="stylesheet">
</head>

<?php
$loginErrors = [];
if (session('error') !== null) {
    $loginErrors[] = (string) session('error');
}
$validationErrors = session('errors');
if (is_array($validationErrors)) {
    foreach ($validationErrors as $field => $error) {
        $msg = is_array($error) ? implode(', ', $error) : (string) $error;
        $loginErrors[] = $msg;
    }
} elseif ($validationErrors !== null) {
    $loginErrors[] = (string) $validationErrors;
}
$flashErrors = session()->getFlashdata('errors');
if (is_array($flashErrors)) {
    foreach ($flashErrors as $err) {
        $loginErrors[] = (string) $err;
    }
} elseif ($flashErrors) {
    $loginErrors[] = (string) $flashErrors;
}
$loginMessage = session('message');
?>

<body class="login-page-body">
    <a class="skip-link" href="#login-main">ข้ามไปฟอร์มเข้าสู่ระบบ</a>
    <div class="login-orb-top" aria-hidden="true"></div>
    <div class="login-orb-bottom" aria-hidden="true"></div>
    <header class="login-topbar">
        <a class="login-brand" href="<?= base_url() ?>">
            <span class="login-brand-mark"><span class="material-symbols-outlined" aria-hidden="true">shield_with_heart</span></span>
            <span>
                <span class="login-brand-title d-block">ระบบสถิติผู้ป่วยหอผู้ป่วย</span>
                <span class="login-brand-subtitle d-block">Clinical Sanctuary Portal</span>
            </span>
        </a>
    </header>
    <main class="login-page" id="login-main" tabindex="-1">
        <div class="login-wrap">
            <div class="login-card">
                <div class="mb-4">
                    <h1 class="login-title">ยินดีต้อนรับกลับ</h1>
                    <p class="login-subtitle">เข้าสู่ระบบเพื่อจัดการข้อมูลผู้ป่วย รายงาน และการดำเนินงานของหอผู้ป่วย</p>
                </div>

                <form action="<?= site_url('login') ?>" method="post" aria-label="เข้าสู่ระบบ" id="loginForm" novalidate>
                    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                    <div id="loginAlerts" aria-live="polite" aria-atomic="true">
                        <?php if ($loginErrors !== []): ?>
                            <div class="alert login-alert alert-danger" role="alert">
                                <strong>ไม่สามารถเข้าสู่ระบบได้</strong>
                                <ul class="mb-0 mt-2 ps-3">
                                    <?php foreach ($loginErrors as $err): ?>
                                        <li><?= esc($err) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($loginMessage !== null): ?>
                            <div class="alert login-alert alert-success" role="status">
                                <?= esc($loginMessage) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <label class="field-label" for="floatingUsernameInput">ชื่อผู้ใช้</label>
                    <div class="field-shell">
                        <span class="material-symbols-outlined field-icon" aria-hidden="true">badge</span>
                        <input type="text" class="login-input" id="floatingUsernameInput" name="username"
                            inputmode="text" autocomplete="username" placeholder="กรอกชื่อผู้ใช้"
                            value="<?= esc(old('username') ?? '') ?>" required
                            <?= $loginErrors !== [] ? 'aria-invalid="true" aria-describedby="loginAlerts"' : '' ?>>
                    </div>

                    <label class="field-label" for="floatingPasswordInput">รหัสผ่าน</label>
                    <div class="field-shell field-shell--password">
                        <span class="material-symbols-outlined field-icon" aria-hidden="true">lock</span>
                        <input type="password" class="login-input" id="floatingPasswordInput" name="password"
                            autocomplete="current-password" placeholder="กรอกรหัสผ่าน" required
                            <?= $loginErrors !== [] ? 'aria-invalid="true" aria-describedby="loginAlerts"' : '' ?>>
                        <button type="button" class="login-toggle-pwd" id="toggleLoginPwd"
                            aria-label="แสดงรหัสผ่าน" aria-pressed="false" aria-controls="floatingPasswordInput">
                            <span class="material-symbols-outlined" id="toggleLoginPwdIcon" aria-hidden="true">visibility</span>
                        </button>
                    </div>

                    <button type="submit" class="login-submit mt-2" id="loginSubmitBtn">เข้าสู่แดชบอร์ด</button>
                </form>

                <p class="text-center text-muted small mb-0 mt-3">
                    บัญชีผู้ใช้จัดทำโดยผู้ดูแลระบบเท่านั้น
                </p>
            </div>
        </div>
    </main>
    <script>
      (function () {
        const pwd = document.getElementById('floatingPasswordInput');
        const toggle = document.getElementById('toggleLoginPwd');
        const icon = document.getElementById('toggleLoginPwdIcon');
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('loginSubmitBtn');

        if (toggle && pwd) {
          toggle.addEventListener('click', function () {
            const show = pwd.type === 'password';
            pwd.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
            toggle.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
            icon.textContent = show ? 'visibility_off' : 'visibility';
          });
        }

        if (form && submitBtn) {
          form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.textContent = 'กำลังเข้าสู่ระบบ…';
          });
        }

        <?php if ($loginErrors !== []): ?>
        const userField = document.getElementById('floatingUsernameInput');
        if (userField) {
          userField.focus();
        }
        <?php endif; ?>
      })();
    </script>
</body>

</html>
