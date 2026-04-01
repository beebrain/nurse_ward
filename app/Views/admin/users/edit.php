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

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:var(--surface-low);">
                    <span class="material-symbols-outlined" style="font-size:2.5rem;color:var(--primary);">account_circle</span>
                    <div>
                        <div class="fw-bold fs-5"><?= esc($editUser->username) ?></div>
                        <div class="text-muted small"><?= esc($editUser->email) ?></div>
                    </div>
                </div>

                <form action="<?= base_url('admin/users/update/' . $editUser->id) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">บทบาท (Role)</label>
                        <?php $currentRole = !empty($editUser->getGroups()) ? $editUser->getGroups()[0] : 'nurse'; ?>
                        <select name="role" class="form-select">
                            <option value="nurse"       <?= $currentRole === 'nurse'       ? 'selected' : '' ?>>Nurse — บันทึกยอดรายวัน</option>
                            <option value="manager"     <?= $currentRole === 'manager'     ? 'selected' : '' ?>>Manager — ดูรายงานและแดชบอร์ด</option>
                            <option value="superadmin"  <?= $currentRole === 'superadmin'  ? 'selected' : '' ?>>Super Admin — ควบคุมระบบทั้งหมด</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">สถานะบัญชี</label>
                        <select name="approval_status" class="form-select">
                            <option value="approved"    <?= $editUser->approval_status === 'approved'    ? 'selected' : '' ?>>อนุมัติแล้ว (Approved)</option>
                            <option value="pending"     <?= $editUser->approval_status === 'pending'     ? 'selected' : '' ?>>รอการอนุมัติ (Pending)</option>
                            <option value="deactivated" <?= $editUser->approval_status === 'deactivated' ? 'selected' : '' ?>>ปิดการใช้งาน (Deactivated)</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <span class="material-symbols-outlined align-middle me-1">save</span>
                            บันทึกการเปลี่ยนแปลง
                        </button>
                        <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
