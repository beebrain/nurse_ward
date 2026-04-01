<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="row g-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">import_export</span>
            <h1 class="mb-0"><?= esc($title) ?></h1>
        </div>
        <p class="text-muted mb-4">ส่งออกข้อมูลยอดผู้ป่วยเป็นไฟล์ Excel หรือนำเข้าข้อมูลจากไฟล์ Excel</p>

        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="material-symbols-outlined align-middle me-1">check_circle</span>
                <?= esc(session()->getFlashdata('message')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="material-symbols-outlined align-middle me-1">error</span>
                <?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php $importErrors = session()->getFlashdata('import_errors'); ?>
        <?php if (!empty($importErrors)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong><span class="material-symbols-outlined align-middle me-1">warning</span>รายการที่มีปัญหา:</strong>
                <ul class="mb-0 mt-1">
                    <?php foreach ($importErrors as $err): ?>
                        <li><?= esc($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Export Section -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center gap-2" style="background:var(--primary);color:#fff;border-radius:.75rem .75rem 0 0;">
                <span class="material-symbols-outlined">download</span>
                <strong>ส่งออกข้อมูล (Export)</strong>
            </div>
            <div class="card-body">
                <p class="text-muted small">เลือกแผนก เดือน และปีที่ต้องการส่งออก แล้วกดดาวน์โหลด</p>
                <form action="<?= base_url('admin/import-export/export') ?>" method="get">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">แผนก</label>
                        <select name="ward_id" class="form-select" required>
                            <option value="">-- เลือกแผนก --</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value="<?= $ward['id'] ?>"><?= esc($ward['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">เดือน</label>
                            <select name="month" class="form-select" required>
                                <?php
                                $thaiMonths = ['1' => 'มกราคม', '2' => 'กุมภาพันธ์', '3' => 'มีนาคม',
                                    '4' => 'เมษายน', '5' => 'พฤษภาคม', '6' => 'มิถุนายน',
                                    '7' => 'กรกฎาคม', '8' => 'สิงหาคม', '9' => 'กันยายน',
                                    '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'];
                                foreach ($thaiMonths as $num => $name):
                                ?>
                                    <option value="<?= $num ?>" <?= $num == date('n') ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">ปี (พ.ศ.)</label>
                            <select name="year" class="form-select" required>
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y + 543 ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span class="material-symbols-outlined align-middle me-1">download</span>
                        ดาวน์โหลด Excel
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Section -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center gap-2" style="background:#0c7521;color:#fff;border-radius:.75rem .75rem 0 0;">
                <span class="material-symbols-outlined">upload</span>
                <strong>นำเข้าข้อมูล (Import)</strong>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    อัปโหลดไฟล์ Excel ที่มีข้อมูลยอดผู้ป่วย หากมีข้อมูลซ้ำ (แผนก + วันที่ + กะ) จะทำการอัปเดตแทน
                </p>

                <div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3">
                    <span class="material-symbols-outlined">info</span>
                    <span class="small">ดาวน์โหลด
                        <a href="<?= base_url('admin/import-export/template') ?>" class="fw-semibold">
                            แม่แบบไฟล์ (Template)
                        </a>
                        ก่อนกรอกข้อมูล
                    </span>
                </div>

                <form action="<?= base_url('admin/import-export/import') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">เลือกไฟล์ Excel (.xlsx / .xls)</label>
                        <input type="file"
                               name="excel_file"
                               class="form-control"
                               accept=".xlsx,.xls"
                               required
                               id="importFile">
                        <div class="form-text">ขนาดไฟล์ไม่เกิน 10 MB</div>
                    </div>

                    <div id="filePreview" class="mb-3 d-none">
                        <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:var(--surface-low);">
                            <span class="material-symbols-outlined text-success">description</span>
                            <span id="fileName" class="small fw-semibold"></span>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100" style="background:#0c7521;color:#fff;">
                        <span class="material-symbols-outlined align-middle me-1">upload</span>
                        นำเข้าข้อมูล
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Format Guide -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">table_chart</span>
                <strong>รูปแบบไฟล์สำหรับนำเข้า</strong>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>A: ชื่อแผนก</th>
                                <th>B: วันที่</th>
                                <th>C: กะ</th>
                                <th>D: รับใหม่</th>
                                <th>E: จำหน่าย</th>
                                <th>F: รับย้าย</th>
                                <th>G: ส่งย้าย</th>
                                <th>H: เสียชีวิต</th>
                                <th>I: คงเหลือ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-warning">
                                <td><?= !empty($wards) ? esc($wards[0]['name']) : 'หอผู้ป่วย 1' ?></td>
                                <td>2025-03-15</td>
                                <td>Morning</td>
                                <td>3</td>
                                <td>2</td>
                                <td>1</td>
                                <td>0</td>
                                <td>0</td>
                                <td>25</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <ul class="small text-muted mb-0 mt-2">
                    <li>แถวแรก (Row 1) คือหัวตาราง ไม่ต้องกรอกข้อมูล</li>
                    <li>ชื่อแผนกต้องตรงกับที่มีในระบบ: <strong><?= implode(', ', array_column($wards, 'name')) ?></strong></li>
                    <li>กะ (Shift) รับเฉพาะ: <code>Morning</code>, <code>Afternoon</code>, <code>Night</code></li>
                    <li>วันที่ต้องเป็นรูปแบบ <code>YYYY-MM-DD</code> เช่น <code>2025-03-15</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('importFile').addEventListener('change', function() {
    const preview = document.getElementById('filePreview');
    const nameEl  = document.getElementById('fileName');
    if (this.files.length > 0) {
        nameEl.textContent = this.files[0].name;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
});
</script>

<?= $this->endSection() ?>
