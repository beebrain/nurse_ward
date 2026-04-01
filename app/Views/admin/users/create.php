<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="d-flex align-items-center gap-2 mb-4">
            <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">
                <span class="material-symbols-outlined" style="font-size:1rem;">arrow_back</span>
            </a>
            <h1 class="mb-0"><?= esc($title) ?></h1>
        </div>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('errors') as $err): ?>
                        <li><?= esc($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= base_url('admin/users/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                        <input type="text"
                               name="username"
                               class="form-control"
                               value="<?= esc(old('username')) ?>"
                               required
                               autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">อีเมล (Email) <span class="text-danger">*</span></label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="<?= esc(old('email')) ?>"
                               required
                               autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">รหัสผ่าน <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password"
                                   name="password"
                                   id="passwordInput"
                                   class="form-control"
                                   minlength="8"
                                   required
                                   autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary" id="togglePwd">
                                <span class="material-symbols-outlined" style="font-size:1.1rem;">visibility</span>
                            </button>
                        </div>
                        <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">บทบาท (Role) <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="nurse" <?= old('role') === 'nurse' ? 'selected' : '' ?>>
                                Nurse — บันทึกยอดรายวัน
                            </option>
                            <option value="manager" <?= old('role') === 'manager' ? 'selected' : '' ?>>
                                Manager — ดูรายงานและแดชบอร์ด
                            </option>
                            <option value="superadmin" <?= old('role') === 'superadmin' ? 'selected' : '' ?>>
                                Super Admin — ควบคุมระบบทั้งหมด
                            </option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <span class="material-symbols-outlined align-middle me-1">person_add</span>
                            เพิ่มผู้ใช้งาน
                        </button>
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    const input = document.getElementById('passwordInput');
    const icon  = this.querySelector('.material-symbols-outlined');
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
});
</script>

<?= $this->endSection() ?>
