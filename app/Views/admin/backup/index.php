<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<?php
function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>

<div class="row g-4">

    <!-- Header -->
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">database</span>
            <h1 class="mb-0"><?= esc($title) ?></h1>
        </div>
        <p class="text-muted mb-0">สำรองฐานข้อมูลทั้งหมดออกมาเป็นไฟล์ SQL สามารถดาวน์โหลดหรือเก็บไว้บนเซิร์ฟเวอร์ได้</p>
    </div>

    <!-- Alerts -->
    <div class="col-12">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <span class="material-symbols-outlined align-middle me-1">check_circle</span>
                <?= esc(session()->getFlashdata('message')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <span class="material-symbols-outlined align-middle me-1">error</span>
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Action Cards -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center gap-2" style="background:var(--primary);color:#fff;border-radius:.75rem .75rem 0 0;">
                <span class="material-symbols-outlined">bolt</span>
                <strong>ดาวน์โหลดทันที</strong>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <p class="text-muted small mb-0">สำรองข้อมูลและดาวน์โหลดไฟล์ SQL ทันทีโดยไม่บันทึกไว้บนเซิร์ฟเวอร์</p>
                <a href="<?= base_url('admin/backup/download-now') ?>"
                   class="btn btn-primary mt-auto"
                   id="btnDownloadNow">
                    <span class="material-symbols-outlined align-middle me-1">download</span>
                    สำรองและดาวน์โหลด
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center gap-2" style="background:#0c7521;color:#fff;border-radius:.75rem .75rem 0 0;">
                <span class="material-symbols-outlined">save</span>
                <strong>บันทึกไว้บนเซิร์ฟเวอร์</strong>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <p class="text-muted small mb-0">สำรองข้อมูลและบันทึกไฟล์ไว้ใน <code>writable/backups/</code> เพื่อดาวน์โหลดภายหลัง</p>
                <form action="<?= base_url('admin/backup/create') ?>" method="post" class="mt-auto">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <input type="text"
                               name="label"
                               class="form-control"
                               placeholder="ชื่อเพิ่มเติม (ไม่บังคับ)"
                               maxlength="30"
                               pattern="[a-zA-Z0-9_\-]*"
                               title="ตัวอักษร a-z, 0-9, _ หรือ - เท่านั้น">
                        <button type="submit" class="btn" style="background:#0c7521;color:#fff;"
                                onclick="return confirm('ยืนยันการสำรองข้อมูล?')">
                            <span class="material-symbols-outlined align-middle" style="font-size:1.1rem;">backup</span>
                        </button>
                    </div>
                    <div class="form-text">เช่น <code>before_update</code> → ชื่อไฟล์จะเป็น backup_2025-03-15_before_update.sql</div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center gap-2" style="background:var(--surface-high);border-radius:.75rem .75rem 0 0;">
                <span class="material-symbols-outlined">info</span>
                <strong>ข้อมูลการสำรอง</strong>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-7 text-muted small">จำนวนไฟล์ backup</dt>
                    <dd class="col-5 fw-bold"><?= count($files) ?> ไฟล์</dd>

                    <dt class="col-7 text-muted small">พื้นที่ใช้งานรวม</dt>
                    <dd class="col-5 fw-bold"><?= formatBytes($dirSize) ?></dd>

                    <dt class="col-7 text-muted small">ที่เก็บไฟล์</dt>
                    <dd class="col-5 fw-bold small text-break"><code>writable/backups/</code></dd>

                    <?php if (!empty($files)): ?>
                        <dt class="col-7 text-muted small">Backup ล่าสุด</dt>
                        <dd class="col-5 fw-bold small"><?= date('d/m/Y H:i', $files[0]['modified']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- File List -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined">folder_open</span>
                    <strong>ไฟล์ Backup ที่บันทึกไว้ (<?= count($files) ?> ไฟล์)</strong>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($files)): ?>
                    <div class="text-center py-5 text-muted">
                        <span class="material-symbols-outlined d-block mb-2" style="font-size:3rem;opacity:.3;">folder_off</span>
                        ยังไม่มีไฟล์ backup ที่บันทึกไว้
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>ชื่อไฟล์</th>
                                    <th>ขนาด</th>
                                    <th>วันที่สร้าง</th>
                                    <th class="text-end pe-3">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $i => $file): ?>
                                    <tr>
                                        <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="material-symbols-outlined text-warning">description</span>
                                                <span class="fw-semibold small font-monospace"><?= esc($file['name']) ?></span>
                                                <?php if ($i === 0): ?>
                                                    <span class="badge bg-primary ms-1">ล่าสุด</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-muted small"><?= formatBytes($file['size']) ?></td>
                                        <td class="text-muted small"><?= date('d/m/Y H:i:s', $file['modified']) ?></td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="<?= base_url('admin/backup/download?file=' . urlencode($file['name'])) ?>"
                                                   class="btn btn-sm btn-outline-primary" title="ดาวน์โหลด">
                                                    <span class="material-symbols-outlined" style="font-size:.9rem;">download</span>
                                                </a>
                                                <form action="<?= base_url('admin/backup/delete') ?>" method="post" class="d-inline"
                                                      onsubmit="return confirm('ลบไฟล์ \'<?= esc($file['name']) ?>\' ?\nการกระทำนี้ไม่สามารถย้อนกลับได้');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="file" value="<?= esc($file['name']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบ">
                                                        <span class="material-symbols-outlined" style="font-size:.9rem;">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
// Show spinner while generating backup
document.getElementById('btnDownloadNow').addEventListener('click', function () {
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังสำรองข้อมูล...';
    this.classList.add('disabled');
    // Re-enable after 15s in case of slow connections
    setTimeout(() => {
        this.innerHTML = '<span class="material-symbols-outlined align-middle me-1">download</span> สำรองและดาวน์โหลด';
        this.classList.remove('disabled');
    }, 15000);
});
</script>

<?= $this->endSection() ?>
