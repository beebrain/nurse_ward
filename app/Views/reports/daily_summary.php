<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12">
        <h1 class="mb-4 text-center">ตารางรายวัน</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="<?= base_url('reports/daily-summary') ?>" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="ward_id" class="form-label fw-bold">แผนก</label>
                        <select name="ward_id" id="ward_id" class="form-select" required>
                            <option value="">เลือกแผนก...</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value="<?= $ward['id'] ?>" <?= (int) $selected_ward_id === (int) $ward['id'] ? 'selected' : '' ?>>
                                    <?= esc($ward['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="month" class="form-label fw-bold">เดือน</label>
                        <select name="month" id="month" class="form-select" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m === (int) $current_month ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year" class="form-label fw-bold">ปี</label>
                        <select name="year" id="year" class="form-select" required>
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === (int) $current_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">แสดง</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_ward): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <strong><?= esc($selected_ward['name']) ?></strong> - <?= date('F', mktime(0, 0, 0, (int) $current_month, 1)) ?> <?= esc((string) $current_year) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($rows)): ?>
                        <div class="alert alert-info mb-0">ไม่พบข้อมูลรายวันสำหรับช่วงเวลานี้</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>วันที่</th>
                                        <th>รับใหม่</th>
                                        <th>จำหน่าย</th>
                                        <th>ย้ายเข้า</th>
                                        <th>ย้ายออก</th>
                                        <th>เสียชีวิต</th>
                                        <th>วันนอนผู้ป่วย</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= esc($row['record_date']) ?></td>
                                            <td><?= esc((string) $row['admissions']) ?></td>
                                            <td><?= esc((string) $row['discharges']) ?></td>
                                            <td><?= esc((string) $row['transfers_in']) ?></td>
                                            <td><?= esc((string) $row['transfers_out']) ?></td>
                                            <td><?= esc((string) $row['deaths']) ?></td>
                                            <td class="fw-bold"><?= esc((string) $row['patient_days']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>