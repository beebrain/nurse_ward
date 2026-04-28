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
                        <select name="role" id="roleSelect" class="form-select">
                            <option value="nurse"       <?= $currentRole === 'nurse'       ? 'selected' : '' ?>>ผู้กรอกข้อมูลประจำ Ward</option>
                            <option value="manager"     <?= $currentRole === 'manager'     ? 'selected' : '' ?>>Manager — ดูรายงานและแดชบอร์ด</option>
                            <option value="superadmin"  <?= $currentRole === 'superadmin'  ? 'selected' : '' ?>>Super Admin — ควบคุมระบบทั้งหมด</option>
                        </select>
                    </div>

                    <div class="mb-3" id="wardSelectWrap">
                        <label class="form-label fw-semibold">Ward ที่รับผิดชอบ</label>
                        <select name="ward_id" id="wardSelect" class="form-select">
                            <option value="">— เลือก Ward —</option>
                            <?php
                            $currentDept = '';
                            foreach ($wards as $ward):
                                $dept = $ward['department_name'] ?? 'ไม่ระบุกลุ่มงาน';
                                if ($dept !== $currentDept):
                                    if ($currentDept !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . esc($dept) . '">';
                                    $currentDept = $dept;
                                endif;
                            ?>
                                <option value="<?= $ward['id'] ?>" <?= (int)old('ward_id', $assignedWardId ?? 0) === (int)$ward['id'] ? 'selected' : '' ?>>
                                    <?= esc(($ward['code'] ? $ward['code'] . ' — ' : '') . $ward['name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($currentDept !== '') echo '</optgroup>'; ?>
                        </select>
                        <div class="form-text">ผู้กรอกข้อมูล 1 คนจะเห็นและบันทึกได้เฉพาะ Ward นี้เท่านั้น</div>
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

<script>
const roleSelect = document.getElementById('roleSelect');
const wardSelectWrap = document.getElementById('wardSelectWrap');
const wardSelect = document.getElementById('wardSelect');
function syncWardSelect() {
    const isNurse = roleSelect.value === 'nurse';
    wardSelectWrap.style.display = isNurse ? '' : 'none';
    wardSelect.required = isNurse;
    if (!isNurse) {
        wardSelect.value = '';
    }
}
roleSelect.addEventListener('change', syncWardSelect);
syncWardSelect();
</script>

<?= $this->endSection() ?>
