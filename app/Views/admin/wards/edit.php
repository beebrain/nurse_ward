<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>แก้ไขแผนก</h1>
            <a href="<?= base_url('admin/wards') ?>" class="btn btn-outline-secondary">กลับรายการ</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="<?= base_url('admin/wards/update/' . $ward['id']) ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">ชื่อแผนก</label>
                        <input type="text" name="name" id="name" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" value="<?= old('name', $ward['name']) ?>" required>
                        <?php if (session('errors.name')): ?>
                            <div class="invalid-feedback"><?= session('errors.name') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="total_beds" class="form-label">จำนวนเตียง</label>
                        <input type="number" name="total_beds" id="total_beds" class="form-control <?= session('errors.total_beds') ? 'is-invalid' : '' ?>" value="<?= old('total_beds', $ward['total_beds']) ?>" required>
                        <?php if (session('errors.total_beds')): ?>
                            <div class="invalid-feedback"><?= session('errors.total_beds') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" <?= old('is_active', $ward['is_active']) ? 'checked' : '' ?>>
                        <label for="is_active" class="form-check-label">ใช้งาน</label>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>