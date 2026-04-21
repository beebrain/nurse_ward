<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .census-hero {
        margin-bottom: 1.25rem;
    }

    .census-hero h1 {
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: 0;
    }

    .status-pill {
        background: var(--surface-low);
        border-radius: 999px;
        color: var(--text-muted);
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.88rem;
        font-weight: 700;
        padding: 0.75rem 1rem;
    }

    .census-panel {
        background: var(--surface-card);
        border-radius: 1.75rem;
        box-shadow: var(--shadow-soft);
        padding: 1.5rem;
    }

    .census-soft-panel {
        background: var(--surface-low);
        border-radius: 1.5rem;
        padding: 1.25rem;
    }

    .section-title {
        font-size: 1.05rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
    }

    .metric-card {
        background: var(--surface-card);
        border-radius: 1.25rem;
        padding: 1rem;
        height: 100%;
    }

    .metric-card label {
        color: var(--text-muted);
        display: block;
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.55rem;
    }

    .census-history-mobile-card {
        background: var(--surface-card);
        border-radius: 1.25rem;
        box-shadow: var(--shadow-soft);
        padding: 1rem 1.1rem;
        margin-bottom: 0.85rem;
        border: 1px solid rgba(193, 198, 212, 0.2);
    }

    .census-history-mobile-card:last-child {
        margin-bottom: 0;
    }

    #census-history-table {
        font-size: 0.9rem;
    }

    @media (max-width: 575.98px) {
        .census-hero {
            flex-wrap: wrap;
        }

        .census-panel {
            padding: 1.15rem;
        }
    }
</style>

<?php
$historyDateFrom = date('Y-m-d', strtotime('-30 days'));
$historyDateTo   = date('Y-m-d');
?>
<div class="census-hero d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <h1 class="mb-0">บันทึกข้อมูลผู้ป่วยรายวัน</h1>
    <span id="autosave-status" class="status-pill"><span class="material-symbols-outlined">cloud_done</span>พร้อม</span>
</div>

<div class="census-panel mb-4">
    <div class="census-soft-panel mb-0">
        <div class="section-title">บริบทการรายงาน</div>
        <form action="<?= base_url('census/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="row g-3 g-md-4 mb-4">
                <div class="col-12 col-lg-4">
                    <label for="ward_id" class="form-label fw-bold">แผนก</label>
                    <select name="ward_id" id="ward_id" class="form-select <?= session('errors.ward_id') ? 'is-invalid' : '' ?>" required>
                        <option value="">เลือกแผนก...</option>
                        <?php foreach ($wards as $ward): ?>
                            <option value="<?= $ward['id'] ?>" <?= old('ward_id') == $ward['id'] ? 'selected' : '' ?>><?= esc($ward['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (session('errors.ward_id')): ?>
                        <div class="invalid-feedback"><?= session('errors.ward_id') ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="record_date" class="form-label fw-bold">วันที่</label>
                    <input type="date" name="record_date" id="record_date" class="form-control <?= session('errors.record_date') ? 'is-invalid' : '' ?>" value="<?= old('record_date', date('Y-m-d')) ?>" required>
                    <?php if (session('errors.record_date')): ?>
                        <div class="invalid-feedback"><?= session('errors.record_date') ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <label for="shift" class="form-label fw-bold">เวร</label>
                    <select name="shift" id="shift" class="form-select <?= session('errors.shift') ? 'is-invalid' : '' ?>" required>
                        <option value="Morning" <?= old('shift') == 'Morning' ? 'selected' : '' ?>>เช้า</option>
                        <option value="Afternoon" <?= old('shift') == 'Afternoon' ? 'selected' : '' ?>>บ่าย</option>
                        <option value="Night" <?= old('shift') == 'Night' ? 'selected' : '' ?>>ดึก</option>
                    </select>
                    <?php if (session('errors.shift')): ?>
                        <div class="invalid-feedback"><?= session('errors.shift') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-title">ตัวชี้วัดผู้ป่วย</div>
            <div class="row g-3 g-md-4">
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="metric-card">
                        <label for="admissions">รับใหม่</label>
                        <input type="number" name="admissions" id="admissions" class="form-control census-input" value="<?= old('admissions', 0) ?>" min="0" required>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="metric-card">
                        <label for="discharges">จำหน่าย</label>
                        <input type="number" name="discharges" id="discharges" class="form-control census-input" value="<?= old('discharges', 0) ?>" min="0" required>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="metric-card">
                        <label for="deaths">เสียชีวิต</label>
                        <input type="number" name="deaths" id="deaths" class="form-control census-input" value="<?= old('deaths', 0) ?>" min="0" required>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="metric-card">
                        <label for="transfers_in">ย้ายเข้า</label>
                        <input type="number" name="transfers_in" id="transfers_in" class="form-control census-input" value="<?= old('transfers_in', 0) ?>" min="0" required>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="metric-card">
                        <label for="transfers_out">ย้ายออก</label>
                        <input type="number" name="transfers_out" id="transfers_out" class="form-control census-input" value="<?= old('transfers_out', 0) ?>" min="0" required>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="metric-card">
                        <label for="total_remaining" class="text-primary">คงพยาบาล</label>
                        <input type="number" name="total_remaining" id="total_remaining" class="form-control census-input" value="<?= old('total_remaining', 0) ?>" min="0" required>
                    </div>
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">บันทึกข้อมูลรายงาน</button>
            </div>
        </form>
    </div>
</div>

<div class="census-panel mt-4 mb-5 pb-3" id="census-history-root" data-history-url="<?= esc(base_url('census/history'), 'attr') ?>">
    <div class="section-title d-flex align-items-center gap-2 flex-wrap mb-3">
        <span class="material-symbols-outlined text-primary">manage_search</span>
        ประวัติการบันทึก
    </div>

    <form id="census-history-filters" class="row g-3 align-items-end mb-3">
        <div class="col-12 col-md-4 col-lg-3">
            <label for="history_ward_id" class="form-label fw-bold mb-1">แผนก</label>
            <select id="history_ward_id" class="form-select">
                <option value="">ทุกแผนก</option>
                <?php foreach ($wards as $ward): ?>
                    <option value="<?= (int) $ward['id'] ?>"><?= esc($ward['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <label for="history_date_from" class="form-label fw-bold mb-1">ตั้งแต่วันที่</label>
            <input type="date" id="history_date_from" class="form-control" value="<?= esc($historyDateFrom) ?>">
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <label for="history_date_to" class="form-label fw-bold mb-1">ถึงวันที่</label>
            <input type="date" id="history_date_to" class="form-control" value="<?= esc($historyDateTo) ?>">
        </div>
        <div class="col-12 col-lg-3 d-grid">
            <button type="submit" class="btn btn-outline-primary btn-lg">โหลดข้อมูล</button>
        </div>
    </form>

    <div id="census-history-error" class="alert alert-danger d-none mb-3" role="alert"></div>
    <div id="census-history-loading" class="text-muted small mb-2 d-none">กำลังโหลดประวัติ...</div>
    <div id="census-history-empty" class="alert alert-light border-0 d-none mb-0">ไม่พบข้อมูลในช่วงที่เลือก</div>

    <div class="d-none d-lg-block rounded-4 overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="census-history-table">
                <thead class="table-light">
                    <tr>
                        <th>วันที่</th>
                        <th>เวร</th>
                        <th>แผนก</th>
                        <th class="text-center">รับใหม่</th>
                        <th class="text-center">จำหน่าย</th>
                        <th class="text-center">เสียชีวิต</th>
                        <th class="text-center">ย้ายเข้า</th>
                        <th class="text-center">ย้ายออก</th>
                        <th class="text-center">คงพยาบาล</th>
                        <th>ผู้บันทึกล่าสุด</th>
                        <th>อัปเดตล่าสุด</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="d-lg-none" id="census-history-cards" aria-live="polite"></div>
</div>

<script src="<?= base_url('app-asset/js/census_entry.js') ?>"></script>
<?= $this->endSection() ?>
