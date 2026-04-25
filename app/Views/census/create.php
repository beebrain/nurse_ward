<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="row justify-content-center">
  <div class="col-xxl-10 col-xl-11">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">บันทึกยอดผู้ป่วยรายวัน</h4>
      <a href="<?= base_url('census/history') ?>" class="btn btn-sm btn-outline-secondary">ประวัติการบันทึก</a>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= session()->getFlashdata('message') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form id="censusForm" action="<?= base_url('census/store') ?>" method="post" novalidate>
      <?= csrf_field() ?>

      <!-- ── Header: Ward / Date / Shift ──────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Ward <span class="text-danger">*</span></label>
              <select name="ward_id" id="ward_id" class="form-select" required>
                <option value="">— เลือก Ward —</option>
                <?php
                $currentDept = '';
                foreach ($wards as $w):
                    $dept = $w['department_name'] ?? '';
                    if ($dept !== $currentDept):
                        if ($currentDept !== '') echo '</optgroup>';
                        echo '<optgroup label="' . esc($dept ?: 'ไม่ระบุกลุ่มงาน') . '">';
                        $currentDept = $dept;
                    endif;
                ?>
                  <option value="<?= $w['id'] ?>" <?= old('ward_id') == $w['id'] ? 'selected' : '' ?>>
                    <?= esc($w['code'] ? $w['code'] . ' — ' . $w['name'] : $w['name']) ?>
                  </option>
                <?php endforeach; ?>
                <?php if ($currentDept !== '') echo '</optgroup>'; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">วันที่ <span class="text-danger">*</span></label>
              <input type="date" name="record_date" id="record_date" class="form-control"
                     value="<?= old('record_date', date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Shift <span class="text-danger">*</span></label>
              <select name="shift" id="shift" class="form-select" required>
                <option value="">— เลือก —</option>
                <option value="Night"     <?= old('shift') === 'Night'     ? 'selected' : '' ?>>ดึก (Night)</option>
                <option value="Morning"   <?= old('shift') === 'Morning'   ? 'selected' : '' ?>>เช้า (Morning)</option>
                <option value="Afternoon" <?= old('shift') === 'Afternoon' ? 'selected' : '' ?>>บ่าย (Afternoon)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Section 1: Patient Levels ────────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white">
          <strong>ผู้ป่วยคงอยู่ แยกตาม Level การพยาบาล</strong>
          <small class="ms-2 opacity-75">(รวม ปกติ + พิเศษ)</small>
        </div>
        <div class="card-body">
          <div class="row g-2 align-items-end">
            <?php
            $levels = [
                5 => ['label' => 'Level 5', 'desc' => 'วิกฤต',          'hours' => '12',  'color' => 'danger'],
                4 => ['label' => 'Level 4', 'desc' => 'หนัก',            'hours' => '7.5', 'color' => 'warning'],
                3 => ['label' => 'Level 3', 'desc' => 'ปานกลาง',         'hours' => '5.5', 'color' => 'info'],
                2 => ['label' => 'Level 2', 'desc' => 'น้อย',            'hours' => '3.5', 'color' => 'success'],
                1 => ['label' => 'Level 1', 'desc' => 'ช่วยตัวเองได้',  'hours' => '1.5', 'color' => 'secondary'],
            ];
            foreach ($levels as $lvl => $meta):
            ?>
            <div class="col">
              <div class="text-center mb-1">
                <span class="badge bg-<?= $meta['color'] ?> fs-6 px-2"><?= $meta['label'] ?></span>
                <div class="small text-muted mt-1"><?= $meta['desc'] ?></div>
                <div class="small text-muted"><?= $meta['hours'] ?> ชม./คน</div>
              </div>
              <input type="number" name="patients_level_<?= $lvl ?>"
                     id="patients_level_<?= $lvl ?>"
                     class="form-control text-center patient-level"
                     min="0" value="<?= old("patients_level_$lvl", 0) ?>"
                     data-hours="<?= $meta['hours'] ?>">
            </div>
            <?php endforeach; ?>
            <div class="col">
              <div class="text-center mb-1">
                <span class="badge bg-dark fs-6 px-2">รวม</span>
                <div class="small text-muted mt-1">&nbsp;</div>
                <div class="small text-muted">&nbsp;</div>
              </div>
              <input type="text" id="total_patients_display"
                     class="form-control text-center fw-bold bg-light fs-5" readonly value="0">
              <input type="hidden" name="total_patients" id="total_patients" value="0">
            </div>
          </div>

          <!-- Productivity preview (Afternoon shift only) -->
          <div id="care_hours_preview" class="alert alert-light border mt-3 mb-0 d-none">
            <div class="small text-center text-muted mb-2">
              Preview Productivity (คำนวณเมื่อบันทึกครบ 3 Shift)
            </div>
            <div class="row text-center g-0">
              <div class="col border-end">
                <div class="small text-muted">ชั่วโมงดูแลที่ต้องการ</div>
                <div class="fs-5 fw-bold text-primary" id="preview_care_hrs">0</div>
                <div class="small text-muted">ชม.</div>
              </div>
              <div class="col border-end">
                <div class="small text-muted">ชั่วโมงทำงาน (RN+TN+PN×7)</div>
                <div class="fs-5 fw-bold text-success" id="preview_work_hrs">0</div>
                <div class="small text-muted">ชม.</div>
              </div>
              <div class="col">
                <div class="small text-muted">Productivity (เฉพาะ shift นี้)</div>
                <div class="fs-5 fw-bold" id="preview_productivity">—</div>
                <div class="small text-muted">%</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Section 2: Patient Movements ──────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <strong>การเคลื่อนไหวผู้ป่วย</strong>
        </div>
        <div class="card-body">
          <div class="row g-3 text-center">
            <?php
            $movements = [
                'admissions'    => 'รับใหม่',
                'discharges'    => 'จำหน่าย',
                'transfers_in'  => 'ย้ายเข้า',
                'transfers_out' => 'ย้ายออก',
                'deaths'        => 'เสียชีวิต',
            ];
            foreach ($movements as $field => $label):
            ?>
            <div class="col">
              <label class="form-label small"><?= $label ?></label>
              <input type="number" name="<?= $field ?>" class="form-control text-center"
                     min="0" value="<?= old($field, 0) ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ── Section 3: Nursing Staff ───────────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <strong>บุคลากรพยาบาล</strong>
          <small class="text-danger ms-2">* RN + TN + PN ใช้คำนวณ Productivity</small>
        </div>
        <div class="card-body">
          <div class="row g-3 text-center">
            <?php
            $nurseTypes = [
                'nurses_hw'   => ['label' => 'HW',  'desc' => 'หัวหน้าเวร',       'calc' => false],
                'nurses_rn'   => ['label' => 'RN ★','desc' => 'พยาบาลวิชาชีพ',    'calc' => true],
                'nurses_tn'   => ['label' => 'TN ★','desc' => 'พยาบาลเทคนิค',     'calc' => true],
                'nurses_pn'   => ['label' => 'PN ★','desc' => 'จนท.ช่วยพยาบาล',   'calc' => true],
                'nurses_aide' => ['label' => 'Aide','desc' => 'ผู้ช่วยเหลือคนไข้', 'calc' => false],
                'nurses_ward' => ['label' => 'W',   'desc' => 'พนักงานผู้ป่วย',   'calc' => false],
            ];
            foreach ($nurseTypes as $field => $meta):
            ?>
            <div class="col">
              <label class="form-label">
                <strong class="<?= $meta['calc'] ? 'text-danger' : '' ?>"><?= $meta['label'] ?></strong>
                <div class="small text-muted"><?= $meta['desc'] ?></div>
              </label>
              <input type="number"
                     name="<?= $field ?>"
                     id="<?= $field ?>"
                     class="form-control text-center<?= $meta['calc'] ? ' nurse-calc' : '' ?>"
                     min="0"
                     value="<?= old($field, 0) ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ── Section 4: Quality Indicators ─────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>ตัวชี้วัดคุณภาพ (Quality Indicators)</strong>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleQI">แสดง/ซ่อน</button>
        </div>
        <div class="card-body" id="qiSection" style="display:none">
          <p class="fw-semibold text-danger mb-2 small">Hospital-Acquired Infections (HAI)</p>
          <div class="row g-2 text-center mb-3">
            <?php
            $haiFields = [
                'hai_vap'    => 'VAP',
                'hai_hap'    => 'HAP',
                'hai_uti'    => 'UTI',
                'hai_cauti'  => 'CAUTI',
                'hai_clabsi' => 'CLABSI',
                'hai_ssi'    => 'SSI',
                'hai_bsi'    => 'BSI',
                'hai_mdr'    => 'MDR',
            ];
            foreach ($haiFields as $field => $label):
            ?>
            <div class="col">
              <label class="form-label small fw-semibold"><?= $label ?></label>
              <input type="number" name="qi[<?= $field ?>]"
                     class="form-control form-control-sm text-center"
                     min="0" value="<?= old("qi[$field]", 0) ?>">
            </div>
            <?php endforeach; ?>
          </div>

          <p class="fw-semibold text-primary mb-2 small">ภาวะพิเศษ</p>
          <div class="row g-2 text-center">
            <?php
            $specialFields = [
                'new_sepsis'            => 'New Sepsis',
                'end_of_life'           => 'End of Life',
                'palliative_care'       => 'Palliative Care',
                'critical_care_support' => 'Critical Care',
                'high_flow_oxygen'      => 'High Flow O₂',
            ];
            foreach ($specialFields as $field => $label):
            ?>
            <div class="col">
              <label class="form-label small fw-semibold"><?= $label ?></label>
              <input type="number" name="qi[<?= $field ?>]"
                     class="form-control form-control-sm text-center"
                     min="0" value="<?= old("qi[$field]", 0) ?>">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ── Notes & Submit ─────────────────────────────────────────────── -->
      <div class="mb-3">
        <label class="form-label small">หมายเหตุ</label>
        <textarea name="notes" class="form-control form-control-sm" rows="2"><?= old('notes') ?></textarea>
      </div>

      <div id="autosave_status" class="text-muted small text-end mb-2" style="min-height:1.2em"></div>

      <div class="d-flex gap-2 justify-content-end">
        <button type="button" id="btnAutosave" class="btn btn-outline-secondary">
          บันทึกชั่วคราว
        </button>
        <button type="submit" class="btn btn-primary px-4">บันทึกถาวร</button>
      </div>

    </form>
  </div>
</div>

<script>
const LEVEL_HOURS = { 5: 12, 4: 7.5, 3: 5.5, 2: 3.5, 1: 1.5 };
const SHIFT_HOURS = 7;

function getInt(id) { return parseInt(document.getElementById(id)?.value) || 0; }

function updateTotals() {
    let total = 0, care = 0;
    [5,4,3,2,1].forEach(lvl => {
        const n = getInt(`patients_level_${lvl}`);
        total += n;
        care  += n * LEVEL_HOURS[lvl];
    });
    document.getElementById('total_patients_display').value = total;
    document.getElementById('total_patients').value = total;
    document.getElementById('preview_care_hrs').textContent = care.toFixed(1);

    const rn = getInt('nurses_rn'), tn = getInt('nurses_tn'), pn = getInt('nurses_pn');
    const work = (rn + tn + pn) * SHIFT_HOURS;
    document.getElementById('preview_work_hrs').textContent = work.toFixed(1);

    const shift  = document.getElementById('shift').value;
    const preEl  = document.getElementById('care_hours_preview');
    if (shift === 'Afternoon') {
        preEl.classList.remove('d-none');
        const pEl  = document.getElementById('preview_productivity');
        const prod = work > 0 ? (care * 100 / work) : 0;
        pEl.textContent = work > 0 ? prod.toFixed(2) : '—';
        pEl.className   = 'fs-5 fw-bold ' +
            (prod > 100 ? 'text-danger' : prod >= 80 ? 'text-warning' : 'text-success');
    } else {
        preEl.classList.add('d-none');
    }
}

document.querySelectorAll('.patient-level, .nurse-calc').forEach(el => {
    el.addEventListener('input', updateTotals);
});
document.getElementById('shift').addEventListener('change', updateTotals);
updateTotals();

document.getElementById('toggleQI').addEventListener('click', () => {
    const s = document.getElementById('qiSection');
    s.style.display = s.style.display === 'none' ? '' : 'none';
});

// ── Auto-save ──────────────────────────────────────────────────────────────
let saveTimer = null;
const statusEl = document.getElementById('autosave_status');

async function doAutosave() {
    const wardId = document.getElementById('ward_id').value;
    const date   = document.getElementById('record_date').value;
    const shift  = document.getElementById('shift').value;
    if (!wardId || !date || !shift) {
        statusEl.textContent = 'กรุณาเลือก Ward / วันที่ / Shift ก่อน';
        return;
    }
    statusEl.textContent = 'กำลังบันทึก…';
    try {
        const resp = await fetch('<?= base_url('census/autosave') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(document.getElementById('censusForm')),
        });
        const json = await resp.json();
        statusEl.textContent = json.success
            ? `บันทึกอัตโนมัติ ${new Date().toLocaleTimeString('th-TH')}`
            : `ผิดพลาด: ${json.message ?? 'Unknown'}`;
    } catch(e) {
        statusEl.textContent = 'Auto-save ล้มเหลว';
    }
}

document.getElementById('censusForm').addEventListener('input', () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(doAutosave, 1800);
});
document.getElementById('btnAutosave').addEventListener('click', doAutosave);
</script>

<?= $this->endSection() ?>
