<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .census-hero {
        margin-bottom: 2rem;
    }

    .census-hero h1 {
        font-size: clamp(2rem, 3vw, 3.2rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: 0.35rem;
    }

    .census-hero p {
        color: var(--text-muted);
        max-width: 760px;
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

    .census-shell {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
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
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 0.35rem;
    }

    .section-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
        margin-bottom: 1rem;
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

    .history-card {
        background: var(--surface-card);
        border-radius: 1.5rem;
        box-shadow: var(--shadow-soft);
        padding: 1.5rem;
        height: 100%;
    }

    .history-item {
        background: var(--surface-low);
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 0.85rem;
    }

    .history-chip {
        background: rgba(152, 249, 148, 0.35);
        color: var(--secondary-text);
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        font-size: 0.72rem;
        font-weight: 800;
        padding: 0.25rem 0.6rem;
    }

    @media (min-width: 1200px) {
        .census-shell {
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
        }
    }
</style>

<div class="census-hero d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
    <div>
        <h1>บันทึกข้อมูลผู้ป่วยรายวัน</h1>
        <p>กรอกข้อมูลผู้ป่วยแต่ละเวรให้ครบถ้วนเพื่ออัปเดตสรุปผลและแดชบอร์ดของผู้บริหารแบบต่อเนื่อง</p>
    </div>
    <span id="autosave-status" class="status-pill"><span class="material-symbols-outlined">cloud_done</span>พร้อม</span>
</div>

<div class="census-shell">
    <div class="census-panel">
        <div class="census-soft-panel mb-4">
            <div class="section-title">บริบทการรายงาน</div>
            <div class="section-subtitle">เลือกแผนก วันที่ และเวร ก่อนบันทึกข้อมูลรายวัน</div>
            <form action="<?= base_url('census/store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label for="record_date" class="form-label fw-bold">วันที่</label>
                        <input type="date" name="record_date" id="record_date" class="form-control <?= session('errors.record_date') ? 'is-invalid' : '' ?>" value="<?= old('record_date', date('Y-m-d')) ?>" required>
                        <?php if (session('errors.record_date')): ?>
                            <div class="invalid-feedback"><?= session('errors.record_date') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
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
                <div class="section-subtitle">บันทึกการเคลื่อนไหวของผู้ป่วยในเวรนี้ให้ครบทุกช่อง</div>
                <div class="row g-4">
                    <div class="col-sm-6 col-xl-4">
                        <div class="metric-card">
                            <label for="admissions">รับใหม่</label>
                            <input type="number" name="admissions" id="admissions" class="form-control census-input" value="<?= old('admissions', 0) ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="metric-card">
                            <label for="discharges">จำหน่าย</label>
                            <input type="number" name="discharges" id="discharges" class="form-control census-input" value="<?= old('discharges', 0) ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="metric-card">
                            <label for="deaths">เสียชีวิต</label>
                            <input type="number" name="deaths" id="deaths" class="form-control census-input" value="<?= old('deaths', 0) ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="metric-card">
                            <label for="transfers_in">ย้ายเข้า</label>
                            <input type="number" name="transfers_in" id="transfers_in" class="form-control census-input" value="<?= old('transfers_in', 0) ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="metric-card">
                            <label for="transfers_out">ย้ายออก</label>
                            <input type="number" name="transfers_out" id="transfers_out" class="form-control census-input" value="<?= old('transfers_out', 0) ?>" min="0" required>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-4">
                        <div class="metric-card">
                            <label for="total_remaining" class="text-primary">คงพยาบาล</label>
                            <input type="number" name="total_remaining" id="total_remaining" class="form-control census-input" value="<?= old('total_remaining', 0) ?>" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-5">
                    <button type="submit" class="btn btn-primary btn-lg">บันทึกข้อมูลรายงาน</button>
                </div>
            </form>
        </div>
    </div>

    <aside>
        <div class="history-card mb-4">
            <div class="section-title d-flex align-items-center gap-2"><span class="material-symbols-outlined text-primary">history</span>แนวทางการกรอกข้อมูล</div>
            <div class="section-subtitle">ช่วยให้การบันทึกข้อมูลสอดคล้องกันทุกเวร</div>
            <div class="history-item">
                <span class="history-chip">ขั้นตอน 1</span>
                <div class="fw-bold mt-2">เลือกแผนกและเวรให้ถูกต้อง</div>
                <div class="text-muted small mt-1">ระบบ autosave จะเริ่มทำงานเมื่อเลือกแผนก วันที่ และเวรครบแล้ว</div>
            </div>
            <div class="history-item">
                <span class="history-chip">ขั้นตอน 2</span>
                <div class="fw-bold mt-2">กรอกข้อมูลผู้ป่วยตามเหตุการณ์จริง</div>
                <div class="text-muted small mt-1">ค่าที่เป็นตัวเลขติดลบจะถูกป้องกันอัตโนมัติ และตรวจสอบอีกครั้งก่อนบันทึก</div>
            </div>
            <div class="history-item mb-0">
                <span class="history-chip">ขั้นตอน 3</span>
                <div class="fw-bold mt-2">กดบันทึกหรือรอ autosave</div>
                <div class="text-muted small mt-1">ข้อมูลที่บันทึกแล้วจะถูกนำไปใช้ในสรุปรายวัน รายเดือน และแดชบอร์ด</div>
            </div>
        </div>

        <div class="history-card">
            <div class="section-title d-flex align-items-center gap-2"><span class="material-symbols-outlined text-primary">tips_and_updates</span>คำแนะนำเพิ่มเติม</div>
            <p class="text-muted mb-0">หากมีการย้ายเข้า-ย้ายออกจำนวนมากในเวรเดียว ควรตรวจสอบยอดคงพยาบาลให้ตรงกับสถานะล่าสุดของหอผู้ป่วยก่อนกดยืนยัน</p>
        </div>
    </aside>
</div>

<script src="<?= base_url('js/census_entry.js') ?>"></script>
<?= $this->endSection() ?>