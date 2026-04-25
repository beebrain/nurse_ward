<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<style>
/* ═══════════════════════════════════════════════════════════════════════
   PROFESSIONAL HEALTHCARE PALETTE
   Calm muted tones, soft tints, off-white surfaces (no pure white)
   ═══════════════════════════════════════════════════════════════════════ */
:root {
    /* App surfaces */
    --bg-page:        #eef2f7;   /* soft cool gray-blue, easy on eyes */
    --bg-card:        #fbfcfe;   /* near-white card surface */
    --bg-input:       #fafbfd;   /* off-white inputs */
    --border-soft:    #d8dee8;
    --text-primary:   #1f2937;
    --text-muted:     #64748b;

    /* Severity (clinical, muted but distinguishable) */
    --c-l5:    #9c2942;   --c-l5-bg:  #fbeef1;
    --c-l4:    #a85c1f;   --c-l4-bg:  #fbf2e8;
    --c-l3:    #1f6f8b;   --c-l3-bg:  #ebf3f7;
    --c-l2:    #3f7a4f;   --c-l2-bg:  #ecf3ee;
    --c-l1:    #5a4eb6;   --c-l1-bg:  #efedf8;
    --c-total: #2c3e50;

    /* Movements (soft accent tones) */
    --c-admit: #2e7d56;   --c-admit-bg:  #e8f1ec;
    --c-dc:    #8c3a5e;   --c-dc-bg:     #f5e8ee;
    --c-tin:   #3d5fa3;   --c-tin-bg:    #ebeff7;
    --c-tout:  #8a6d2c;   --c-tout-bg:   #f6f0e2;
    --c-death: #2c3e50;   --c-death-bg:  #2c3e50;

    /* Nurses */
    --c-rn:    #9c2942;   --c-rn-bg:    #fbeef1;
    --c-other: #4d6485;   --c-other-bg: #eaeef5;
}

/* Page background — stop "bright white" glare */
body { background: var(--bg-page) !important; }

/* ── Cards & section headers ───────────────────────────────────────── */
.card {
    border-radius: 12px;
    border: 1px solid var(--border-soft);
    background: var(--bg-card);
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15,23,42,.06);
}
.card-header {
    padding: 12px 18px !important;
    font-size: .98rem !important;
    font-weight: 700 !important;
    letter-spacing: .2px;
    border-bottom: none !important;
    color: #fff !important;
}
.card-header .material-symbols-outlined,
.card-header i { font-size: 1.1rem; vertical-align: -2px; opacity: .9; }

.sh-blue   { background: linear-gradient(135deg,#1e3a8a,#3b5fb8) !important; }
.sh-teal   { background: linear-gradient(135deg,#0f5e58,#2a8a82) !important; }
.sh-purple { background: linear-gradient(135deg,#3f1d8a,#6b46b8) !important; }
.sh-red    { background: linear-gradient(135deg,#7a1f33,#9c2942) !important; }
.sh-gray   { background: linear-gradient(135deg,#1f2937,#475569) !important; }

/* ── Generic stat cards (used by levels / movements / nurses) ─────── */
.stat-card {
    border-radius: 10px;
    padding: 10px 8px;
    text-align: center;
    border: 1px solid;
    transition: box-shadow .15s, transform .1s;
    height: 100%;
}
.stat-card:hover { box-shadow: 0 3px 8px rgba(15,23,42,.12); }

.stat-label  { font-size: .8rem; font-weight: 700; letter-spacing: .2px; }
.stat-sub    { font-size: .68rem; color: var(--text-muted); line-height: 1.25; min-height: 1.6em; }

.stat-input {
    font-size: 1.55rem;
    font-weight: 700;
    border: 1.5px solid;
    border-radius: 8px;
    text-align: center;
    width: 100%;
    padding: 6px 4px;
    background: var(--bg-input);
    transition: border-color .15s, box-shadow .15s;
    margin-top: 6px;
    min-height: 48px;
    -moz-appearance: textfield;
}
.stat-input::-webkit-inner-spin-button,
.stat-input::-webkit-outer-spin-button { opacity: .35; }
.stat-input:focus { outline: none; box-shadow: 0 0 0 3px rgba(31,41,55,.12); }

/* ── Level Cards (severity 5→1) ───────────────────────────────────── */
.lv5 { background: var(--c-l5-bg); border-color: var(--c-l5); }
.lv5 .stat-label, .lv5 .stat-input { color: var(--c-l5); border-color: var(--c-l5); }
.lv5 .stat-input:focus { box-shadow: 0 0 0 3px rgba(156,41,66,.18); }

.lv4 { background: var(--c-l4-bg); border-color: var(--c-l4); }
.lv4 .stat-label, .lv4 .stat-input { color: var(--c-l4); border-color: var(--c-l4); }
.lv4 .stat-input:focus { box-shadow: 0 0 0 3px rgba(168,92,31,.18); }

.lv3 { background: var(--c-l3-bg); border-color: var(--c-l3); }
.lv3 .stat-label, .lv3 .stat-input { color: var(--c-l3); border-color: var(--c-l3); }
.lv3 .stat-input:focus { box-shadow: 0 0 0 3px rgba(31,111,139,.18); }

.lv2 { background: var(--c-l2-bg); border-color: var(--c-l2); }
.lv2 .stat-label, .lv2 .stat-input { color: var(--c-l2); border-color: var(--c-l2); }
.lv2 .stat-input:focus { box-shadow: 0 0 0 3px rgba(63,122,79,.18); }

.lv1 { background: var(--c-l1-bg); border-color: var(--c-l1); }
.lv1 .stat-label, .lv1 .stat-input { color: var(--c-l1); border-color: var(--c-l1); }
.lv1 .stat-input:focus { box-shadow: 0 0 0 3px rgba(90,78,182,.18); }

.lv-total { background: var(--c-total); border-color: var(--c-total); color: #fff; }
.lv-total .stat-label { color: #fff; }
.lv-total .stat-input {
    background: rgba(255,255,255,.08); color: #fff;
    border-color: rgba(255,255,255,.25);
}

/* Larger numbers for patient levels (key data) */
.lv5 .stat-input, .lv4 .stat-input, .lv3 .stat-input,
.lv2 .stat-input, .lv1 .stat-input, .lv-total .stat-input { font-size: 1.85rem; min-height: 56px; }

/* ── Movement Cards ───────────────────────────────────────────────── */
.mc-admit { background: var(--c-admit-bg); border-color: var(--c-admit); }
.mc-admit .stat-label, .mc-admit .stat-input { color: var(--c-admit); border-color: var(--c-admit); }
.mc-admit .stat-input:focus { box-shadow: 0 0 0 3px rgba(46,125,86,.18); }

.mc-dc    { background: var(--c-dc-bg);    border-color: var(--c-dc); }
.mc-dc .stat-label, .mc-dc .stat-input    { color: var(--c-dc);    border-color: var(--c-dc); }
.mc-dc .stat-input:focus    { box-shadow: 0 0 0 3px rgba(140,58,94,.18); }

.mc-in    { background: var(--c-tin-bg);   border-color: var(--c-tin); }
.mc-in .stat-label, .mc-in .stat-input    { color: var(--c-tin);   border-color: var(--c-tin); }
.mc-in .stat-input:focus    { box-shadow: 0 0 0 3px rgba(61,95,163,.18); }

.mc-out   { background: var(--c-tout-bg);  border-color: var(--c-tout); }
.mc-out .stat-label, .mc-out .stat-input  { color: var(--c-tout);  border-color: var(--c-tout); }
.mc-out .stat-input:focus   { box-shadow: 0 0 0 3px rgba(138,109,44,.18); }

.mc-death { background: var(--c-death-bg); border-color: var(--c-death); color: #fff; }
.mc-death .stat-label { color: #fff; }
.mc-death .stat-input {
    background: rgba(255,255,255,.1); color: #fff;
    border-color: rgba(255,255,255,.3);
}

/* ── Nurse Cards ──────────────────────────────────────────────────── */
.nc-calc  { background: var(--c-rn-bg);    border-color: var(--c-rn); }
.nc-calc .stat-label, .nc-calc .stat-input  { color: var(--c-rn);    border-color: var(--c-rn); }
.nc-calc .stat-input:focus  { box-shadow: 0 0 0 3px rgba(156,41,66,.18); }

.nc-other { background: var(--c-other-bg); border-color: var(--c-other); }
.nc-other .stat-label, .nc-other .stat-input { color: var(--c-other); border-color: var(--c-other); }
.nc-other .stat-input:focus { box-shadow: 0 0 0 3px rgba(77,100,133,.18); }

/* ── Productivity preview ─────────────────────────────────────────── */
.prod-preview {
    background: linear-gradient(135deg,#1f2937 0%,#334155 100%);
    border-radius: 10px;
    color: #fff;
    padding: 14px;
    margin-top: 14px;
}
.prod-stat-label { font-size: .72rem; opacity: .75; letter-spacing: .4px; text-transform: uppercase; }
.prod-stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1.2; }
.prod-divider    { border-color: rgba(255,255,255,.18) !important; }

/* ── QI inputs ────────────────────────────────────────────────────── */
.qi-card {
    border: 1px solid var(--border-soft);
    background: var(--bg-input);
    border-radius: 8px;
    padding: 8px 6px;
    text-align: center;
    height: 100%;
}
.qi-card.qi-hai     { border-color: #c08191; background: #fbeef1; }
.qi-card.qi-special { border-color: #8da4c4; background: #ebeff7; }
.qi-card-label { font-size: .72rem; font-weight: 700; }
.qi-hai .qi-card-label     { color: var(--c-l5); }
.qi-special .qi-card-label { color: var(--c-tin); }

.qi-input {
    font-size: 1.2rem;
    font-weight: 700;
    border: 1.5px solid;
    border-radius: 6px;
    text-align: center;
    width: 100%;
    padding: 4px;
    background: var(--bg-input);
    margin-top: 4px;
    min-height: 40px;
    -moz-appearance: textfield;
}
.qi-input::-webkit-inner-spin-button,
.qi-input::-webkit-outer-spin-button { opacity: .35; }
.qi-input:focus { outline: none; }
.qi-hai .qi-input     { color: var(--c-l5); border-color: var(--c-l5); }
.qi-hai .qi-input:focus     { box-shadow: 0 0 0 3px rgba(156,41,66,.18); }
.qi-special .qi-input { color: var(--c-tin); border-color: var(--c-tin); }
.qi-special .qi-input:focus { box-shadow: 0 0 0 3px rgba(61,95,163,.18); }

/* ── Form controls (Ward/Date/Shift) ──────────────────────────────── */
.form-control, .form-select {
    background: var(--bg-input) !important;
    border-color: var(--border-soft) !important;
    color: var(--text-primary) !important;
}
.form-control:focus, .form-select:focus {
    border-color: #3b5fb8 !important;
    box-shadow: 0 0 0 3px rgba(59,95,184,.15) !important;
}
.form-label { color: var(--text-primary); }

/* ── Touch device tweaks ──────────────────────────────────────────── */
@media (hover: none) and (pointer: coarse) {
    .stat-input { font-size: 1.65rem !important; min-height: 54px; }
    .qi-input   { min-height: 46px; font-size: 1.3rem; }
}

/* ── Small-screen layout ──────────────────────────────────────────── */
@media (max-width: 575.98px) {
    .stat-input  { font-size: 1.4rem; min-height: 44px; }
    .stat-sub    { display: none; }            /* hide subtitle on phones */
    .card-header { padding: 10px 14px !important; font-size: .9rem !important; }
    .card-body   { padding: .85rem !important; }
}
</style>

<div class="container-fluid px-md-3 px-2" style="max-width: 1400px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0" style="color: var(--text-primary);">บันทึกยอดผู้ป่วยรายวัน</h4>
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
          <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-2">

            <div class="col">
              <div class="stat-card lv5">
                <div class="stat-label">Level 5</div>
                <div class="stat-sub">วิกฤต · 12 ชม./คน</div>
                <input type="number" name="patients_level_5" id="patients_level_5"
                       class="stat-input patient-level"
                       min="0" value="<?= old('patients_level_5', 0) ?>" data-hours="12">
              </div>
            </div>

            <div class="col">
              <div class="stat-card lv4">
                <div class="stat-label">Level 4</div>
                <div class="stat-sub">หนัก · 7.5 ชม./คน</div>
                <input type="number" name="patients_level_4" id="patients_level_4"
                       class="stat-input patient-level"
                       min="0" value="<?= old('patients_level_4', 0) ?>" data-hours="7.5">
              </div>
            </div>

            <div class="col">
              <div class="stat-card lv3">
                <div class="stat-label">Level 3</div>
                <div class="stat-sub">ปานกลาง · 5.5 ชม./คน</div>
                <input type="number" name="patients_level_3" id="patients_level_3"
                       class="stat-input patient-level"
                       min="0" value="<?= old('patients_level_3', 0) ?>" data-hours="5.5">
              </div>
            </div>

            <div class="col">
              <div class="stat-card lv2">
                <div class="stat-label">Level 2</div>
                <div class="stat-sub">น้อย · 3.5 ชม./คน</div>
                <input type="number" name="patients_level_2" id="patients_level_2"
                       class="stat-input patient-level"
                       min="0" value="<?= old('patients_level_2', 0) ?>" data-hours="3.5">
              </div>
            </div>

            <div class="col">
              <div class="stat-card lv1">
                <div class="stat-label">Level 1</div>
                <div class="stat-sub">ช่วยตัวเองได้ · 1.5 ชม./คน</div>
                <input type="number" name="patients_level_1" id="patients_level_1"
                       class="stat-input patient-level"
                       min="0" value="<?= old('patients_level_1', 0) ?>" data-hours="1.5">
              </div>
            </div>

            <div class="col">
              <div class="stat-card lv-total">
                <div class="stat-label">รวมทั้งหมด</div>
                <div class="stat-sub">&nbsp;</div>
                <input type="text" id="total_patients_display"
                       class="stat-input" readonly value="0">
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
                <div class="prod-stat-value" style="color: #7dd3fc;" id="preview_care_hrs">0</div>
                <div class="prod-stat-label">ชม.</div>
              </div>
              <div class="col border-end prod-divider">
                <div class="prod-stat-label">ชั่วโมงทำงาน (RN+TN+PN×7)</div>
                <div class="prod-stat-value" style="color: #86efac;" id="preview_work_hrs">0</div>
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
          <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-2">

            <div class="col">
              <div class="stat-card mc-admit">
                <div class="stat-label">รับใหม่</div>
                <div class="stat-sub">Admission</div>
                <input type="number" name="admissions" class="stat-input"
                       min="0" value="<?= old('admissions', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card mc-dc">
                <div class="stat-label">จำหน่าย</div>
                <div class="stat-sub">Discharge</div>
                <input type="number" name="discharges" class="stat-input"
                       min="0" value="<?= old('discharges', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card mc-in">
                <div class="stat-label">ย้ายเข้า</div>
                <div class="stat-sub">Transfer In</div>
                <input type="number" name="transfers_in" class="stat-input"
                       min="0" value="<?= old('transfers_in', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card mc-out">
                <div class="stat-label">ย้ายออก</div>
                <div class="stat-sub">Transfer Out</div>
                <input type="number" name="transfers_out" class="stat-input"
                       min="0" value="<?= old('transfers_out', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card mc-death">
                <div class="stat-label">เสียชีวิต</div>
                <div class="stat-sub">Death</div>
                <input type="number" name="deaths" class="stat-input"
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
          <div class="row row-cols-3 row-cols-md-6 g-2">

            <div class="col">
              <div class="stat-card nc-other">
                <div class="stat-label">HW</div>
                <div class="stat-sub">หัวหน้าเวร</div>
                <input type="number" name="nurses_hw" id="nurses_hw"
                       class="stat-input" min="0" value="<?= old('nurses_hw', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card nc-calc">
                <div class="stat-label">RN ★</div>
                <div class="stat-sub">พยาบาลวิชาชีพ</div>
                <input type="number" name="nurses_rn" id="nurses_rn"
                       class="stat-input nurse-calc" min="0" value="<?= old('nurses_rn', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card nc-calc">
                <div class="stat-label">TN ★</div>
                <div class="stat-sub">พยาบาลเทคนิค</div>
                <input type="number" name="nurses_tn" id="nurses_tn"
                       class="stat-input nurse-calc" min="0" value="<?= old('nurses_tn', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card nc-calc">
                <div class="stat-label">PN ★</div>
                <div class="stat-sub">จนท.ช่วยพยาบาล</div>
                <input type="number" name="nurses_pn" id="nurses_pn"
                       class="stat-input nurse-calc" min="0" value="<?= old('nurses_pn', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card nc-other">
                <div class="stat-label">Aide</div>
                <div class="stat-sub">ผู้ช่วยเหลือคนไข้</div>
                <input type="number" name="nurses_aide" id="nurses_aide"
                       class="stat-input" min="0" value="<?= old('nurses_aide', 0) ?>">
              </div>
            </div>

            <div class="col">
              <div class="stat-card nc-other">
                <div class="stat-label">W</div>
                <div class="stat-sub">พนักงานผู้ป่วย</div>
                <input type="number" name="nurses_ward" id="nurses_ward"
                       class="stat-input" min="0" value="<?= old('nurses_ward', 0) ?>">
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

          <p class="fw-semibold mb-2 small" style="color: var(--c-l5);">
            <i class="bi bi-virus me-1"></i> Hospital-Acquired Infections (HAI)
          </p>
          <div class="row row-cols-4 row-cols-md-8 g-2 mb-3">
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
              <div class="qi-card qi-hai">
                <div class="qi-card-label"><?= $label ?></div>
                <input type="number" name="qi[<?= $field ?>]"
                       class="qi-input"
                       min="0" value="<?= old("qi[$field]", 0) ?>">
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <p class="fw-semibold mb-2 small" style="color: var(--c-tin);">
            <i class="bi bi-clipboard-pulse me-1"></i> ภาวะพิเศษ
          </p>
          <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-2">
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
              <div class="qi-card qi-special">
                <div class="qi-card-label"><?= $label ?></div>
                <input type="number" name="qi[<?= $field ?>]"
                       class="qi-input"
                       min="0" value="<?= old("qi[$field]", 0) ?>">
              </div>
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
