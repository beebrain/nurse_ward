<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบสถิติผู้ป่วยหอผู้ป่วย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        :root {
            --surface: #f9f9ff;
            --surface-lowest: #ffffff;
            --surface-high: #e6e8f0;
            --text-main: #181c21;
            --text-muted: #414752;
            --primary: #005dac;
            --primary-strong: #1976d2;
            --outline-soft: rgba(193, 198, 212, 0.25);
            --danger-soft: #ffdad6;
            --danger-text: #93000a;
            --shadow-soft: 0 12px 32px rgba(0, 95, 175, 0.06);
        }

        body {
            min-height: 100vh;
            background: var(--surface);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .login-orb-top,
        .login-orb-bottom {
            position: fixed;
            border-radius: 999px;
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
        }

        .login-orb-top {
            top: -8rem;
            right: -6rem;
            width: 32rem;
            height: 32rem;
            background: rgba(0, 93, 172, 0.08);
        }

        .login-orb-bottom {
            left: -8rem;
            bottom: -8rem;
            width: 28rem;
            height: 28rem;
            background: rgba(152, 249, 148, 0.12);
        }

        .login-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            padding: 1rem 1.5rem;
            background: rgba(249, 249, 255, 0.7);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-soft);
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            color: var(--text-main);
            text-decoration: none;
        }

        .login-brand-mark {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%);
            box-shadow: 0 14px 24px rgba(0, 95, 172, 0.18);
        }

        .login-brand-title {
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
        }

        .login-brand-subtitle {
            color: var(--text-muted);
            font-size: 0.76rem;
            font-weight: 600;
        }

        .login-page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6.5rem 1.25rem 2rem;
        }

        .login-wrap {
            width: 100%;
            max-width: 28rem;
        }

        .login-card {
            background: var(--surface-lowest);
            border-radius: 1.75rem;
            box-shadow: var(--shadow-soft);
            padding: 2.2rem;
            border: 1px solid var(--outline-soft);
        }

        .login-title {
            font-family: 'Manrope', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 0.4rem;
        }

        .login-subtitle {
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }

        .field-label {
            color: var(--text-muted);
            display: block;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 0.55rem;
            text-transform: uppercase;
        }

        .field-shell {
            position: relative;
            margin-bottom: 1rem;
        }

        .field-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #717783;
        }

        .login-input {
            width: 100%;
            border: 0;
            border-radius: 1rem;
            background: var(--surface-high);
            min-height: 56px;
            padding: 1rem 1rem 1rem 3.3rem;
            color: var(--text-main);
        }

        .login-input:focus {
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(0, 93, 172, 0.12);
            background: #fff;
        }

        .login-submit {
            width: 100%;
            border: 0;
            border-radius: 999px;
            min-height: 56px;
            color: #fff;
            font-family: 'Manrope', sans-serif;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%);
            box-shadow: 0 16px 28px rgba(0, 95, 172, 0.2);
        }

        .login-submit:hover {
            opacity: 0.96;
        }

        .login-alert {
            border: 0;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }

        .login-alert.alert-danger {
            background: var(--danger-soft);
            color: var(--danger-text);
        }

        .debug-info {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 1rem;
            padding: 0.85rem 1rem;
            background: #f1f3f8;
            border-radius: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-orb-top"></div>
    <div class="login-orb-bottom"></div>
    <header class="login-topbar">
        <a class="login-brand" href="<?= base_url() ?>">
            <span class="login-brand-mark"><span class="material-symbols-outlined">shield_with_heart</span></span>
            <span>
                <span class="login-brand-title d-block">ระบบสถิติผู้ป่วยหอผู้ป่วย</span>
                <span class="login-brand-subtitle d-block">Clinical Sanctuary Portal</span>
            </span>
        </a>
    </header>
    <main class="login-page">
        <div class="login-wrap">
            <div class="login-card">
                <div class="mb-4">
                    <h1 class="login-title">ยินดีต้อนรับกลับ</h1>
                    <p class="login-subtitle">เข้าสู่ระบบเพื่อจัดการข้อมูลผู้ป่วย รายงาน และการดำเนินงานของหอผู้ป่วย</p>
                </div>

                <form action="<?= site_url('login') ?>" method="post">
                    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                    <label class="field-label" for="floatingUsernameInput">ชื่อผู้ใช้</label>
                    <div class="field-shell">
                        <span class="material-symbols-outlined field-icon">badge</span>
                        <input type="text" class="login-input" id="floatingUsernameInput" name="username" inputmode="text" autocomplete="username" placeholder="กรอกชื่อผู้ใช้" required>
                    </div>

                    <label class="field-label" for="floatingPasswordInput">รหัสผ่าน</label>
                    <div class="field-shell">
                        <span class="material-symbols-outlined field-icon">lock</span>
                        <input type="password" class="login-input" id="floatingPasswordInput" name="password" inputmode="text" autocomplete="current-password" placeholder="กรอกรหัสผ่าน" required>
                    </div>

                    <?php
                    if (session('error') !== null) {
                        echo '<div class="alert login-alert alert-danger" role="alert">';
                        echo '<strong>ข้อผิดพลาด:</strong> ' . session('error');
                        echo '</div>';
                    }

                    if (session('errors') !== null) {
                        echo '<div class="alert login-alert alert-danger" role="alert">';
                        echo '<strong>ข้อผิดพลาดการตรวจสอบ:</strong><br>';
                        $errors = session('errors');
                        if (is_array($errors)) {
                            foreach ($errors as $field => $error) {
                                echo htmlspecialchars($field) . ': ' . htmlspecialchars(is_array($error) ? implode(', ', $error) : $error) . '<br>';
                            }
                        } else {
                            echo htmlspecialchars($errors);
                        }
                        echo '</div>';
                    }

                    $flashErrors = session()->getFlashdata('errors');
                    if ($flashErrors) {
                        echo '<div class="alert login-alert alert-danger" role="alert">';
                        echo '<strong>ข้อผิดพลาดอื่นๆ:</strong><br>';
                        if (is_array($flashErrors)) {
                            foreach ($flashErrors as $err) {
                                echo htmlspecialchars($err) . '<br>';
                            }
                        } else {
                            echo htmlspecialchars($flashErrors);
                        }
                        echo '</div>';
                    }
                    ?>

                    <?php if (session('message') !== null) : ?>
                        <div class="alert login-alert alert-success" role="alert">
                            <?= session('message') ?>
                        </div>
                    <?php endif ?>

                    <button type="submit" class="login-submit">เข้าสู่แดชบอร์ด</button>
                </form>

                <hr class="my-4">

                <p class="text-center text-muted mb-0">
                    ยังไม่มีบัญชี? <a href="<?= site_url('register') ?>">ลงทะเบียน</a>
                </p>

                <div class="debug-info">
                    <small>ข้อมูลดีบัก:</small><br>
                    <small>รหัสเซสชัน: <?= substr(session_id(), 0, 8) ?>...</small><br>
                    <small>CSRF: <?= substr(csrf_hash(), 0, 8) ?>...</small>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>