<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>เพิ่ม Ward ใหม่</h1>
            <a href="<?= base_url('admin/wards') ?>" class="btn btn-outline-secondary">กลับรายการ</a>
        </div>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session('errors') as $err): ?>
                        <li><?= esc($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="<?= base_url('admin/wards/store') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">รหัสย่อ (Code)</label>
                            <input type="text" name="code" id="code"
                                   class="form-control <?= session('errors.code') ? 'is-invalid' : '' ?>"
                                   value="<?= old('code') ?>" placeholder="เช่น ER, MICU, ศช.">
                            <?php if (session('errors.code')): ?>
                                <div class="invalid-feedback"><?= session('errors.code') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">ชื่อ Ward <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                   class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>"
                                   value="<?= old('name') ?>" required>
                            <?php if (session('errors.name')): ?>
                                <div class="invalid-feedback"><?= session('errors.name') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="department_id" class="form-label">กลุ่มงาน (แผนก)</label>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">— ไม่ระบุ —</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"
                                    <?= old('department_id') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= esc($dept['short_name'] ?? $dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="total_beds" class="form-label">จำนวนเตียง <span class="text-danger">*</span></label>
                        <input type="number" name="total_beds" id="total_beds" min="0"
                               class="form-control <?= session('errors.total_beds') ? 'is-invalid' : '' ?>"
                               value="<?= old('total_beds', 0) ?>" required>
                        <?php if (session('errors.total_beds')): ?>
                            <div class="invalid-feedback"><?= session('errors.total_beds') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                               value="1" <?= old('is_active', '1') == '1' ? 'checked' : '' ?>>
                        <label for="is_active" class="form-check-label">เปิดใช้งาน</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
