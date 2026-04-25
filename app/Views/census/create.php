<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<style>
/* ── Level Cards ────────────────────────────────────────────────────── */
.level-card {
    border-radius: 12px;
    padding: 12px 8px 10px;
    text-align: center;
    border: 2px solid transparent;
    transition: transform .1s, box-shadow .1s;
}
.level-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }

.level-card.lv5 { background: #fff0f0; border-color: #dc3545; }
.level-card.lv4 { background: #fff8e1; border-color: #fd7e14; }
.level-card.lv3 { background: #e8f4fd; border-color: #0dcaf0; }
.level-card.lv2 { background: #eafaf1; border-color: #198754; }
.level-card.lv1 { background: #f4f0ff; border-color: #6f42c1; }
.level-card.lv-total { background: #1a1a2e; border-color: #1a1a2e; color: #fff; }

.level-badge { font-size: 1rem; font-weight: 700; letter-spacing: .5px; }
.level-badge.lv5 { color: #dc3545; }
.level-badge.lv4 { color: #fd7e14; }
.level-badge.lv3 { color: #0989b7; }
.level-badge.lv2 { color: #198754; }
.level-badge.lv1 { color: #6f42c1; }
.level-badge.lv-total { color: #fff; }

.level-input {
    font-size: 1.8rem;
    font-weight: 700;
    border: 2px solid;
    border-radius: 10px;
    text-align: center;
    width: 100%;
    padding: 6px 4px;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    -moz-appearance: textfield;
}
.level-input::-webkit-inner-spin-button,
.level-input::-webkit-outer-spin-button { opacity: .4; }
.level-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(0,0,0,.12); }

.level-input.lv5 { border-color: #dc3545; color: #dc3545; }
.level-input.lv5:focus { box-shadow: 0 0 0 3px rgba(220,53,69,.25); }
.level-input.lv4 { border-color: #fd7e14; color: #fd7e14; }
.level-input.lv4:focus { box-shadow: 0 0 0 3px rgba(253,126,20,.25); }
.level-input.lv3 { border-color: #0dcaf0; color: #0989b7; }
.level-input.lv3:focus { box-shadow: 0 0 0 3px rgba(13,202,240,.25); }
.level-input.lv2 { border-color: #198754; color: #198754; }
.level-input.lv2:focus { box-shadow: 0 0 0 3px rgba(25,135,84,.25); }
.level-input.lv1 { border-color: #6f42c1; color: #6f42c1; }
.level-input.lv1:focus { box-shadow: 0 0 0 3px rgba(111,66,193,.25); }
.level-input.lv-total { border-color: #fff3; color: #fff; background: transparent; }

/* ── Movement inputs ────────────────────────────────────────────────── */
.move-card {
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
    border: 2px solid;
}
.move-card.mc-admit   { background: #eaf7f0; border-color: #20c997; }
.move-card.mc-dc      { background: #fff0f5; border-color: #e83e8c; }
.move-card.mc-in      { background: #e8f0ff; border-color: #6610f2; }
.move-card.mc-out     { background: #fff5e0; border-color: #ffc107; }
.move-card.mc-death   { background: #1a1a2e; border-color: #1a1a2e; }

.move-label { font-size: .8rem; font-weight: 600; margin-bottom: 4px; }
.mc-admit .move-label  { color: #0f9b6a; }
.mc-dc    .move-label  { color: #c0285a; }
.mc-in    .move-label  { color: #5a03c7; }
.mc-out   .move-label  { color: #a17800; }
.mc-death .move-label  { color: #adb5bd; }

.move-input {
    font-size: 1.5rem;
    font-weight: 700;
    border: none;
    background: transparent;
    text-align: center;
    width: 100%;
    padding: 2px;
    -moz-appearance: textfield;
}
.move-input::-webkit-inner-spin-button,
.move-input::-webkit-outer-spin-button { opacity: .4; }
.move-input:focus { outline: none; }
.mc-admit .move-input  { color: #0f9b6a; }
.mc-dc    .move-input  { color: #c0285a; }
.mc-in    .move-input  { color: #5a03c7; }
.mc-out   .move-input  { color: #a17800; }
.mc-death .move-input  { color: #fff; }

/* ── Nurse inputs ───────────────────────────────────────────────────── */
.nurse-card {
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
    border: 2px solid;
}
.nurse-card.nc-calc   { background: #fff5f5; border-color: #dc3545; }
.nurse-card.nc-other  { background: #eef1ff; border-color: #4a6cf7; }

.nurse-label { font-size: .85rem; font-weight: 700; }
.nc-calc  .nurse-label { color: #dc3545; }
.nc-other .nurse-label { color: #4a6cf7; }
.nurse-desc { font-size: .72rem; color: #6c757d; }

.nurse-input {
    font-size: 1.5rem;
    font-weight: 700;
    border: none;
    background: transparent;
    text-align: center;
    width: 100%;
    padding: 2px;
    -moz-appearance: textfield;
}
.nurse-input::-webkit-inner-spin-button,
.nurse-input::-webkit-outer-spin-button { opacity: .4; }
.nurse-input:focus { outline: none; }
.nc-calc  .nurse-input { color: #dc3545; }
.nc-other .nurse-input { color: #4a6cf7; }

/* ── Section headers ────────────────────────────────────────────────── */
.card-header {
    padding: 13px 18px !important;
    font-size: 1rem !important;
    font-weight: 700 !important;
    letter-spacing: .2px;
    border-bottom: none !important;
    box-shadow: 0 3px 6px rgba(0,0,0,.18);
}
.sh-blue   { background: linear-gradient(90deg,#0a58ca,#2176ff) !important; color: #fff !important; }
.sh-teal   { background: linear-gradient(90deg,#0a7a52,#1cc98a) !important; color: #fff !important; }
.sh-purple { background: linear-gradient(90deg,#520dc2,#8540f5) !important; color: #fff !important; }
.sh-red    { background: linear-gradient(90deg,#9b1616,#e02020) !important; color: #fff !important; }
.sh-gray   { background: linear-gradient(90deg,#2b3035,#555e68) !important; color: #fff !important; }

/* ── Productivity preview ───────────────────────────────────────────── */
.prod-preview {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border-radius: 10px;
    color: #fff;
    padding: 14px;
    margin-top: 12px;
}
.prod-stat-label { font-size: .72rem; opacity: .7; letter-spacing: .5px; text-transform: uppercase; }
.prod-stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1.2; }
.prod-divider { border-color: rgba(255,255,255,.2) !important; }

/* ── QI inputs ──────────────────────────────────────────────────────── */
.qi-input {
    font-size: 1.2rem;
    font-weight: 600;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    text-align: center;
    width: 100%;
    padding: 4px;
    background: #fff;
    transition: border-color .15s;
    -moz-appearance: textfield;
}
.qi-input:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
.qi-input::-webkit-inner-spin-button,
.qi-input::-webkit-outer-spin-button { opacity: .4; }
.qi-hai:focus     { border-color: #dc3545; box-shadow: 0 0 0 3px rgba(220,53,69,.15); }
.qi-special:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }

/* ── Header card ────────────────────────────────────────────────────── */
.card { border-radius: 12px; border: 2px solid #e9ecef; overflow: hidden; }
.card-header.sh-blue, .card-header.sh-teal,
.card-header.sh-purple, .card-header.sh-red,
.card-header.sh-gray { border: none !important; }
</style>

<div class="row justify-content-center">
  <div class="col-xxl-10 col-xl-11">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">บันทึกยอดผู้ป่วยรายวัน</h4>
      <a href="<?= base_url('census/history') ?>" class="btn btn-sm btn-outline-secondary">ประวัติการบันทึก</a>
    </div>

    <?php if ($isNurse && empty($wards)): ?>
      <div class="alert alert-danger">
        <span class="material-symbols-outlined align-middle me-1">warning</span>
        บัญชีของคุณยังไม่ได้รับการกำหนด Ward กรุณาติดต่อผู้ดูแลระบบ
      </div>
    <?php elseif ($isNurse): ?>
      <div class="alert alert-info d-flex align-items-start gap-2 py-2">
        <span class="material-symbols-outlined mt-1" style="font-size:1.1rem;">lock</span>
        <div class="small">
          คุณสามารถบันทึกได้เฉพาะ Ward ที่รับผิดชอบ (<strong><?= count($wards) ?> Ward</strong>)
          และบันทึกย้อนหลังได้ไม่เกิน <strong>12 ชั่วโมง</strong> หลังจากกะเริ่ม
          <span class="text-muted">(ดึก 00:00 / เช้า 08:00 / บ่าย 16:00)</span>
        </div>
      </div>
    <?php endif; ?>

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
        <div class="card-header sh-blue">
          <i class="bi bi-hospital me-1"></i> Ward / วันที่ / Shift
        </div>
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
        <div class="card-header sh-blue">
          <i class="bi bi-people-fill me-1"></i> ผู้ป่วยคงอยู่ แยกตาม Level การพยาบาล
          <small class="ms-2 opacity-75 fw-normal">(รวม ปกติ + พิเศษ)</small>
        </div>
        <div class="card-body">
          <div class="row g-2">

            <div class="col">
              <div class="level-card lv5">
                <div class="level-badge lv5">Level 5</div>
                <div class="small text-muted mt-1">วิกฤต</div>
                <div class="small text-muted">12 ชม./คน</div>
                <input type="number" name="patients_level_5" id="patients_level_5"
                       class="level-input lv5 patient-level mt-1"
                       min="0" value="<?= old('patients_level_5', 0) ?>" data-hours="12">
              </div>
            </div>

            <div class="col">
              <div class="level-card lv4">
                <div class="level-badge lv4">Level 4</div>
                <div class="small text-muted mt-1">หนัก</div>
                <div class="small text-muted">7.5 ชม./คน</div>
                <input type="number" name="patients_level_4" id="patients_level_4"
                       class="level-input lv4 patient-level mt-1"
                       min="0" value="<?= old('patients_level_4', 0) ?>" data-hours="7.5">
              </div>
            </div>

            <div class="col">
              <div class="level-card lv3">
                <div class="level-badge lv3">Level 3</div>
                <div class="small text-muted mt-1">ปานกลาง</div>
                <div class="small text-muted">5.5 ชม./คน</div>
                <input type="number" name="patients_level_3" id="patients_level_3"
                       class="level-input lv3 patient-level mt-1"
                       min="0" value="<?= old('patients_level_3', 0) ?>" data-hours="5.5">
              </div>
            </div>

            <div class="col">
              <div class="level-card lv2">
                <div class="level-badge lv2">Level 2</div>
                <div class="small text-muted mt-1">น้อย</div>
                <div class="small text-muted">3.5 ชม./คน</div>
                <input type="number" name="patients_level_2" id="patients_level_2"
                       class="level-input lv2 patient-level mt-1"
                       min="0" value="<?= old('patients_level_2', 0) ?>" data-hours="3.5">
              </div>
            </div>

            <div class="col">
              <div class="level-card lv1">
                <div class="level-badge lv1">Level 1</div>
                <div class="small text-muted mt-1">ช่วยตัวเองได้</div>
                <div class="small text-muted">1.5 ชม./คน</div>
                <input type="number" name="patients_level_1" id="patients_level_1"
                       class="level-input lv1 patient-level mt-1"
                       min="0" value="<?= old('patients_level_1', 0) ?>" data-hours="1.5">
              </div>
            </div>

            <div class="col">
              <div class="level-card lv-total">
                <div class="level-badge lv-total">รวม</div>
                <div class="small opacity-50 mt-1">&nbsp;</div>
                <div class="small opacity-50">&nbsp;</div>
                <input type="text" id="total_patients_display"
                       class="level-input lv-total mt-1" readonly value="0">
              </div>
              <input type="hidden" name="total_patients" id="total_patients" value="0">
            </div>

          </div>

          <!-- Productivity preview (Afternoon shift only) -->
          <div id="care_hours_preview" class="prod-preview d-none">
            <div class="small text-center opacity-75 mb-2">
              Preview Productivity (คำนวณเมื่อบันทึกครบ 3 Shift)
            </div>
            <div class="row text-center g-0">
              <div class="col border-end prod-divider">
                <div class="prod-stat-label">ชั่วโมงดูแลที่ต้องการ</div>
                <div class="prod-stat-value text-info" id="preview_care_hrs">0</div>
                <div class="prod-stat-label">ชม.</div>
              </div>
              <div class="col border-end prod-divider">
                <div class="prod-stat-label">ชั่วโมงทำงาน (RN+TN+PN×7)</div>
                <div class="prod-stat-value text-success" id="preview_work_hrs">0</div>
                <div class="prod-stat-label">ชม.</div>
              </div>
              <div class="col">
                <div class="prod-stat-label">Productivity (shift นี้)</div>
                <div class="prod-stat-value" id="preview_productivity">—</div>
                <div class="prod-stat-label">%</div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Section 2: Patient Movements ──────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header sh-teal">
          <i class="bi bi-arrow-left-right me-1"></i> การเคลื่อนไหวผู้ป่วย
        </div>
        <div class="card-body">
          <div class="row g-2">

            <div class="col">
              <div class="move-card mc-admit">
                <div class="move-label">รับใหม่</div>
                <input type="number" name="admissions" class="move-input"
                       min="0" value="<?= old('admissions', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="move-card mc-dc">
                <div class="move-label">จำหน่าย</div>
                <input type="number" name="discharges" class="move-input"
                       min="0" value="<?= old('discharges', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="move-card mc-in">
                <div class="move-label">ย้ายเข้า</div>
                <input type="number" name="transfers_in" class="move-input"
                       min="0" value="<?= old('transfers_in', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="move-card mc-out">
                <div class="move-label">ย้ายออก</div>
                <input type="number" name="transfers_out" class="move-input"
                       min="0" value="<?= old('transfers_out', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="move-card mc-death">
                <div class="move-label">เสียชีวิต</div>
                <input type="number" name="deaths" class="move-input"
                       min="0" value="<?= old('deaths', 0) ?>">
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Section 3: Nursing Staff ───────────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header sh-red">
          <i class="bi bi-person-badge-fill me-1"></i> บุคลากรพยาบาล
          <small class="ms-2 opacity-75 fw-normal">RN ★ TN ★ PN ★ ใช้คำนวณ Productivity</small>
        </div>
        <div class="card-body">
          <div class="row g-2">

            <div class="col">
              <div class="nurse-card nc-other">
                <div class="nurse-label">HW</div>
                <div class="nurse-desc">หัวหน้าเวร</div>
                <input type="number" name="nurses_hw" id="nurses_hw"
                       class="nurse-input" min="0" value="<?= old('nurses_hw', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="nurse-card nc-calc">
                <div class="nurse-label">RN ★</div>
                <div class="nurse-desc">พยาบาลวิชาชีพ</div>
                <input type="number" name="nurses_rn" id="nurses_rn"
                       class="nurse-input nurse-calc" min="0" value="<?= old('nurses_rn', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="nurse-card nc-calc">
                <div class="nurse-label">TN ★</div>
                <div class="nurse-desc">พยาบาลเทคนิค</div>
                <input type="number" name="nurses_tn" id="nurses_tn"
                       class="nurse-input nurse-calc" min="0" value="<?= old('nurses_tn', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="nurse-card nc-calc">
                <div class="nurse-label">PN ★</div>
                <div class="nurse-desc">จนท.ช่วยพยาบาล</div>
                <input type="number" name="nurses_pn" id="nurses_pn"
                       class="nurse-input nurse-calc" min="0" value="<?= old('nurses_pn', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="nurse-card nc-other">
                <div class="nurse-label">Aide</div>
                <div class="nurse-desc">ผู้ช่วยเหลือคนไข้</div>
                <input type="number" name="nurses_aide" id="nurses_aide"
                       class="nurse-input" min="0" value="<?= old('nurses_aide', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="nurse-card nc-other">
                <div class="nurse-label">W</div>
                <div class="nurse-desc">พนักงานผู้ป่วย</div>
                <input type="number" name="nurses_ward" id="nurses_ward"
                       class="nurse-input" min="0" value="<?= old('nurses_ward', 0) ?>">
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Section 4: Quality Indicators ─────────────────────────────── -->
      <div class="card shadow-sm mb-3">
        <div class="card-header sh-gray d-flex justify-content-between align-items-center">
          <span><i class="bi bi-clipboard2-pulse me-1"></i> ตัวชี้วัดคุณภาพ (Quality Indicators)</span>
          <button type="button" class="btn btn-sm btn-light btn-sm" id="toggleQI">แสดง/ซ่อน</button>
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
              <label class="form-label small fw-semibold text-danger"><?= $label ?></label>
              <input type="number" name="qi[<?= $field ?>]"
                     class="qi-input qi-hai"
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
              <label class="form-label small fw-semibold text-primary"><?= $label ?></label>
              <input type="number" name="qi[<?= $field ?>]"
                     class="qi-input qi-special"
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

      <div id="window_warning" class="alert alert-danger py-2 small d-none mb-2"></div>
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
        pEl.className   = 'prod-stat-value ' +
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

// ── Shift window check (nurse only) ───────────────────────────────────────
const IS_NURSE = <?= json_encode($isNurse) ?>;
const SHIFT_START_HOUR = { Night: 0, Morning: 8, Afternoon: 16 };

function getShiftDeadline(dateStr, shift) {
    if (!dateStr || !shift) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    const startHour = SHIFT_START_HOUR[shift] ?? 0;
    const start = new Date(y, m - 1, d, startHour, 0, 0);
    return new Date(start.getTime() + 12 * 3600 * 1000);
}

function checkWindowWarning() {
    if (!IS_NURSE) return;
    const dateStr = document.getElementById('record_date').value;
    const shift   = document.getElementById('shift').value;
    const warn    = document.getElementById('window_warning');
    if (!warn) return;
    const deadline = getShiftDeadline(dateStr, shift);
    if (!deadline) { warn.classList.add('d-none'); return; }
    const now = new Date();
    if (now > deadline) {
        const fmt = deadline.toLocaleString('th-TH', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' });
        warn.textContent = `⚠ เกินระยะเวลาบันทึก (หมดเขต ${fmt}) กะนี้ไม่สามารถบันทึกได้`;
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
}

['shift', 'record_date'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', checkWindowWarning);
});
checkWindowWarning();

document.getElementById('censusForm').addEventListener('submit', function(e) {
    if (!IS_NURSE) return;
    const dateStr = document.getElementById('record_date').value;
    const shift   = document.getElementById('shift').value;
    const deadline = getShiftDeadline(dateStr, shift);
    if (deadline && new Date() > deadline) {
        e.preventDefault();
        alert('ไม่สามารถบันทึกได้ เนื่องจากเกินระยะเวลา 12 ชั่วโมงหลังกะเริ่ม');
    }
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
