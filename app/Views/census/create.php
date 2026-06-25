<?= $this->extend('layout/main') ?>

<?= $this->section('layout_class') ?>layout-form<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="<?= asset_url('css/census-layout.css') ?>" rel="stylesheet">
<link href="<?= asset_url('css/census-create.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-fluid daily-census-template census-page-full">

  <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-end gap-4 mb-4">
    <div>
      <h1 class="daily-page-title mb-1">บันทึกยอดผู้ป่วยรายวัน</h1>
      <p class="daily-page-subtitle mb-0">Record and track patient volume, nursing level, movement, and staffing by shift.</p>
    </div>
    <a href="<?= base_url('census/history') ?>" class="btn btn-outline-secondary align-self-start align-self-xl-end">
      <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">history</span>
      ประวัติการบันทึก
    </a>
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
    <div class="daily-toolbar daily-toolbar-sticky d-flex flex-wrap align-items-center gap-2 mb-4">
      <div class="toolbar-field flex-grow-1">
        <label for="ward_id">Ward <span class="text-danger">*</span></label>
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
            <option value="<?= $w['id'] ?>" <?= (int)old('ward_id', $defaultWardId ?? 0) === (int)$w['id'] ? 'selected' : '' ?>>
              <?= esc($w['code'] ? $w['code'] . ' — ' . $w['name'] : $w['name']) ?>
            </option>
          <?php endforeach; ?>
          <?php if ($currentDept !== '') echo '</optgroup>'; ?>
        </select>
      </div>
      <div class="toolbar-divider"></div>
      <div class="toolbar-field">
        <label for="record_date">วันที่ <span class="text-danger">*</span></label>
        <input type="date" name="record_date" id="record_date" class="form-control"
          value="<?= old('record_date', date('Y-m-d')) ?>" required>
      </div>
      <div class="toolbar-divider"></div>
      <div class="toolbar-field">
        <label for="shift">เวร <span class="text-danger">*</span></label>
        <select name="shift" id="shift" class="form-select" required>
          <option value="">— เลือก —</option>
          <option value="Night" <?= old('shift') === 'Night'     ? 'selected' : '' ?>>ดึก (Night)</option>
          <option value="Morning" <?= old('shift') === 'Morning'   ? 'selected' : '' ?>>เช้า (Morning)</option>
          <option value="Afternoon" <?= old('shift') === 'Afternoon' ? 'selected' : '' ?>>บ่าย (Afternoon)</option>
        </select>
      </div>
    </div>

    <div id="carry_forward_note" class="carry-forward-note mb-3 d-none">
      <span class="material-symbols-outlined" style="font-size:1rem;">update</span>
      <span id="carry_forward_note_text">ยอดยกมาจากเวรก่อนหน้า 0 คน</span>
    </div>

    <!-- ── Handover Guidelines Widget ────────────────────────────────── -->
    <div id="handover_guidelines_card" class="card shadow-sm mb-4 d-none">
      <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e3a8a, #0d9488) !important; color: #fff !important; padding: 14px 18px !important;">
        <span class="d-flex align-items-center gap-2">
          <span class="material-symbols-outlined" style="font-size:1.3rem;">clinical_notes</span>
          <span>
            <span class="fw-bold d-block">แนวทางการส่งเวรรายชั่วโมง (Handover Guidelines)</span>
            <span class="badge bg-warning text-dark mt-1" style="font-size:0.7rem;font-weight:700;">อยู่ในช่วงทดสอบการเชื่อมต่อ</span>
          </span>
        </span>
        <button type="button" id="btn_apply_hourly" class="btn btn-sm btn-light fw-bold text-primary d-flex align-items-center gap-1 py-1 px-2 border-0">
          <span class="material-symbols-outlined" style="font-size:1rem;">install_desktop</span>
          นำเข้าข้อมูลไปยังฟอร์มบันทึก
        </button>
      </div>
      <div class="card-body">
        <div class="alert alert-warning py-2 px-3 mb-3 small d-flex align-items-start gap-2" role="status">
          <span class="material-symbols-outlined flex-shrink-0" style="font-size:1.15rem;">info</span>
          <span><strong>อยู่ในช่วงทดสอบการเชื่อมต่อ</strong> กับ HOSxP — ข้อมูลจาก API ใช้เป็นข้อมูลอ้างอิงเท่านั้น กรุณาตรวจสอบก่อนนำเข้าฟอร์มบันทึก</span>
        </div>
        <div class="row align-items-stretch">
          <!-- Stats Summary -->
          <div class="col-lg-5 col-12 mb-3 mb-lg-0">
            <h6 class="fw-bold text-muted mb-2 small text-uppercase" style="letter-spacing: .05em;">สรุปข้อมูลการเคลื่อนไหวระหว่างเวร จาก HOSxP</h6>
            <div class="hourly-handover-grid">
              <div class="hourly-stat-card" style="background: var(--c-admit-bg); border-color: var(--c-admit);">
                <div class="hourly-stat-label text-success">รับใหม่ (Admit)</div>
                <div class="hourly-stat-value text-success" id="hourly_admit_val">0</div>
              </div>
              <div class="hourly-stat-card" style="background: var(--c-dc-bg); border-color: var(--c-dc);">
                <div class="hourly-stat-label text-danger">จำหน่าย (Discharge)</div>
                <div class="hourly-stat-value text-danger" id="hourly_dc_val">0</div>
              </div>
              <div class="hourly-stat-card" style="background: var(--c-tin-bg); border-color: var(--c-tin);">
                <div class="hourly-stat-label text-primary">ย้ายเข้า (Transfer In)</div>
                <div class="hourly-stat-value text-primary" id="hourly_tin_val">0</div>
              </div>
              <div class="hourly-stat-card" style="background: var(--c-tout-bg); border-color: var(--c-tout);">
                <div class="hourly-stat-label" style="color: var(--c-tout);">ย้ายออก (Transfer Out)</div>
                <div class="hourly-stat-value" style="color: var(--c-tout);" id="hourly_tout_val">0</div>
              </div>
              <div class="hourly-stat-card" style="background: #f1f5f9; border-color: #64748b;">
                <div class="hourly-stat-label text-secondary">เสียชีวิต (Death)</div>
                <div class="hourly-stat-value text-secondary" id="hourly_death_val">0</div>
              </div>
              <div class="hourly-stat-card" style="background: #fbeef1; border-color: #ba1a1a;">
                <div class="hourly-stat-label" style="color: #ba1a1a;">ผู้ป่วยคงพยาบาล</div>
                <div class="hourly-stat-value" style="color: #ba1a1a;" id="hourly_patient_val">0</div>
              </div>
            </div>
          </div>
          <!-- Chart / Timeline -->
          <div class="col-lg-7 col-12 d-flex flex-column">
            <h6 class="fw-bold text-muted mb-2 small text-uppercase" style="letter-spacing: .05em;">ไทม์ไลน์ยอดผู้ป่วยรายชั่วโมง (Hourly Patient Census Timeline)</h6>
            <div class="flex-grow-1 p-2 rounded" style="background: #fafbfd; border: 1px solid var(--border-soft); min-height: 120px;">
              <div id="hourly_timeline_chart_container" class="position-relative h-100 w-100 d-flex align-items-end justify-content-between px-3 pt-4" style="height: 120px !important;">
                <!-- Timeline bars will be dynamically rendered here -->
              </div>
              <div id="hourly_timeline_labels" class="d-flex justify-content-between px-3 mt-1 text-muted" style="font-size: .65rem; font-weight: bold;">
                <!-- Labels will be dynamically rendered here -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Section 1: Patient Movements ──────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">swap_horiz</span>
          การเคลื่อนไหวผู้ป่วย
        </span>
      </div>
      <div class="card-body">
        <div class="movement-balance-panel mb-3">
          <div class="row g-2 align-items-center">
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ยอดยกมา</div>
              <div class="movement-balance-value" id="carried_forward_display">0</div>
            </div>
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ยอดคาดการณ์</div>
              <div class="movement-balance-value" id="movement_expected_display">0</div>
            </div>
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ยอดคงอยู่จริง</div>
              <div class="movement-balance-value" id="movement_actual_display">0</div>
            </div>
            <div class="col-md-3 col-6">
              <div class="movement-balance-label">ส่วนต่าง</div>
              <div class="movement-balance-value" id="movement_variance_display">0</div>
            </div>
          </div>
          <div id="movement_balance_status" class="small text-muted mt-2">
            เลือก Ward / วันที่ / เวร เพื่อดึงยอดยกมาจากเวรก่อนหน้า
          </div>
        </div>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-2">

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">รับใหม่ <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Admission">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_admissions" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="admissions" id="admissions" class="stat-input movement-input" min="0" value="<?= old('admissions', 0) ?>" data-field="admissions">
              <div id="reason_container_admissions" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[admissions]" id="reason_admissions" class="form-control form-control-sm discrepancy-reason" aria-label="เหตุผลความแตกต่าง รับใหม่" placeholder="เหตุผล">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">จำหน่าย <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Discharge">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_discharges" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="discharges" id="discharges" class="stat-input movement-input" min="0" value="<?= old('discharges', 0) ?>" data-field="discharges">
              <div id="reason_container_discharges" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[discharges]" id="reason_discharges" class="form-control form-control-sm discrepancy-reason" aria-label="เหตุผลความแตกต่าง จำหน่าย" placeholder="เหตุผล">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">ย้ายเข้า <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Transfer In">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_transfers_in" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="transfers_in" id="transfers_in" class="stat-input movement-input" min="0" value="<?= old('transfers_in', 0) ?>" data-field="transfers_in">
              <div id="reason_container_transfers_in" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[transfers_in]" id="reason_transfers_in" class="form-control form-control-sm discrepancy-reason" aria-label="เหตุผลความแตกต่าง ย้ายเข้า" placeholder="เหตุผล">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">ย้ายออก <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Transfer Out">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_transfers_out" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="transfers_out" id="transfers_out" class="stat-input movement-input" min="0" value="<?= old('transfers_out', 0) ?>" data-field="transfers_out">
              <div id="reason_container_transfers_out" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[transfers_out]" id="reason_transfers_out" class="form-control form-control-sm discrepancy-reason" aria-label="เหตุผลความแตกต่าง ย้ายออก" placeholder="เหตุผล">
              </div>
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other" style="position: relative;">
              <div class="stat-label">เสียชีวิต <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="Death">info</span><span class="badge bg-secondary ms-1 api-guideline" id="gl_deaths" style="display:none; font-size:0.6rem;" data-bs-toggle="tooltip" title="ข้อมูลอ้างอิงจาก HOSxP">-</span></div>
              <input type="number" name="deaths" id="deaths" class="stat-input movement-input" min="0" value="<?= old('deaths', 0) ?>" data-field="deaths">
              <div id="reason_container_deaths" style="display:none; margin-top: 5px;">
                  <input type="text" name="api_discrepancy_reasons[deaths]" id="reason_deaths" class="form-control form-control-sm discrepancy-reason" aria-label="เหตุผลความแตกต่าง เสียชีวิต" placeholder="เหตุผล">
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── Section 2: Patient Levels ────────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">clinical_notes</span>
          ผู้ป่วยคงอยู่ แยกตาม Level การพยาบาล
          <small class="ms-2 fw-normal">(สามัญ + พิเศษ)</small>
        </span>
        <span class="total-pill">TOTAL: <span id="total_patients_badge">0</span></span>
      </div>
      <div class="card-body">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-2">

          <?php
          $levelMeta = [
            5 => ['class' => 'lv5', 'help' => 'วิกฤต · 12 ชม./คน'],
            4 => ['class' => 'lv4', 'help' => 'หนัก · 7.5 ชม./คน'],
            3 => ['class' => 'lv3', 'help' => 'ปานกลาง · 5.5 ชม./คน'],
            2 => ['class' => 'lv2', 'help' => 'น้อย · 3.5 ชม./คน'],
            1 => ['class' => 'lv1', 'help' => 'ช่วยตัวเองได้ · 1.5 ชม./คน'],
          ];
          foreach ($levelMeta as $level => $meta):
            $generalField = "patients_general_level_{$level}";
            $specialField = "patients_special_level_{$level}";
            $totalField   = "patients_level_{$level}";
            $generalValue = old($generalField, old($totalField, 0));
            $specialValue = old($specialField, 0);
          ?>
            <div class="col">
              <div class="stat-card <?= $meta['class'] ?>">
                <div class="stat-label">Level <?= $level ?> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="<?= esc($meta['help']) ?>">info</span></div>
                <div class="row g-2 align-items-end mt-1">
                  <div class="col-6">
                    <label class="mini-field-label" for="<?= $generalField ?>">สามัญ</label>
                    <input type="number" name="<?= $generalField ?>" id="<?= $generalField ?>"
                      class="stat-input patient-level-type"
                      min="0" value="<?= $generalValue ?>">
                  </div>
                  <div class="col-6">
                    <label class="mini-field-label" for="<?= $specialField ?>">พิเศษ</label>
                    <input type="number" name="<?= $specialField ?>" id="<?= $specialField ?>"
                      class="stat-input patient-level-type"
                      min="0" value="<?= $specialValue ?>">
                  </div>
                </div>
                <div class="level-total-line">รวม <strong id="<?= $totalField ?>_total">0</strong></div>
                <input type="hidden" name="<?= $totalField ?>" id="<?= $totalField ?>"
                  class="patient-level" value="<?= old($totalField, 0) ?>">
              </div>
            </div>
          <?php endforeach; ?>

          <div class="col">
            <div class="stat-card lv-total">
              <div class="stat-label">รวมทั้งหมด</div>
              <input type="text" id="total_patients_display"
                class="stat-input" readonly value="0">
            </div>
            <input type="hidden" name="total_patients" id="total_patients" value="0">
          </div>

        </div>

        <!-- Productivity preview (ทุกเวร) -->
        <div id="care_hours_preview" class="prod-preview">
          <div class="prod-section-title">Productivity</div>

          <div id="preview_shift_scope" class="prod-scope-note mb-2">
            เวรนี้: <span id="preview_shift_label">—</span>
          </div>
          <div class="row text-center g-0">
            <div class="col-4 border-end prod-divider">
              <div class="prod-stat-label">ชม.ดูแล (เวรนี้)</div>
              <div class="prod-stat-value" style="color: #7dd3fc;" id="preview_shift_care_hrs">0</div>
            </div>
            <div class="col-4 border-end prod-divider">
              <div class="prod-stat-label">ชม.ทำงาน (เวรนี้)</div>
              <div class="prod-stat-value" style="color: #86efac;" id="preview_shift_work_hrs">0</div>
            </div>
            <div class="col-4">
              <div class="prod-stat-label">% (เวรนี้)</div>
              <div class="prod-stat-value" id="preview_shift_productivity">—</div>
            </div>
          </div>

          <hr>

          <div id="preview_daily_scope" class="prod-scope-note mb-2">
            รายวัน · ชม.ดูแลจาก: <span id="preview_daily_care_from">—</span>
            · ชม.ทำงานจาก: <span id="preview_daily_work_from">—</span>
          </div>
          <div class="row text-center g-0">
            <div class="col-4 border-end prod-divider">
              <div class="prod-stat-label">ชม.ดูแล (รายวัน)</div>
              <div class="prod-stat-value" style="color: #7dd3fc;" id="preview_daily_care_hrs">0</div>
            </div>
            <div class="col-4 border-end prod-divider">
              <div class="prod-stat-label">ชม.ทำงาน (รายวัน)</div>
              <div class="prod-stat-value" style="color: #86efac;" id="preview_daily_work_hrs">0</div>
            </div>
            <div class="col-4">
              <div class="prod-stat-label">% (รายวัน)</div>
              <div class="prod-stat-value" id="preview_daily_productivity">—</div>
            </div>
          </div>
          <div id="preview_level_source" class="small text-center opacity-75 mt-2 d-none"></div>
        </div>

      </div>
    </div>

    <!-- ── Section 3: Nursing Staff ───────────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">medical_services</span>
          บุคลากรพยาบาล
          <small class="ms-2 fw-normal">
            <span class="material-symbols-outlined align-middle" style="font-size:1rem;">calculate</span>
            RN / TN / PN ใช้คำนวณ Productivity
          </small>
        </span>
      </div>
      <div class="card-body">
        <div class="row row-cols-3 row-cols-md-6 g-2">

          <div class="col">
            <div class="stat-card nc-other">
              <div class="stat-label">HW <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="หัวหน้าเวร">info</span></div>
              <input type="number" name="nurses_hw" id="nurses_hw"
                class="stat-input" min="0" value="<?= old('nurses_hw', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-calc">
              <div class="stat-label">RN <span><span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ใช้คำนวณ Productivity">calculate</span> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="พยาบาลวิชาชีพ">info</span></span></div>
              <input type="number" name="nurses_rn" id="nurses_rn"
                class="stat-input nurse-calc" min="0" value="<?= old('nurses_rn', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-calc">
              <div class="stat-label">TN <span><span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ใช้คำนวณ Productivity">calculate</span> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="พยาบาลเทคนิค">info</span></span></div>
              <input type="number" name="nurses_tn" id="nurses_tn"
                class="stat-input nurse-calc" min="0" value="<?= old('nurses_tn', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-calc">
              <div class="stat-label">PN <span><span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ใช้คำนวณ Productivity">calculate</span> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="เจ้าหน้าที่ช่วยพยาบาล">info</span></span></div>
              <input type="number" name="nurses_pn" id="nurses_pn"
                class="stat-input nurse-calc" min="0" value="<?= old('nurses_pn', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other">
              <div class="stat-label">Aide <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="ผู้ช่วยเหลือคนไข้">info</span></div>
              <input type="number" name="nurses_aide" id="nurses_aide"
                class="stat-input" min="0" value="<?= old('nurses_aide', 0) ?>">
            </div>
          </div>

          <div class="col">
            <div class="stat-card nc-other">
              <div class="stat-label">W <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="พนักงานผู้ป่วย">info</span></div>
              <input type="number" name="nurses_ward" id="nurses_ward"
                class="stat-input" min="0" value="<?= old('nurses_ward', 0) ?>">
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- ── Section 4: Patients using ward equipment ───────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">medical_information</span>
          ผู้ป่วยที่ใช้อุปกรณ์
        </span>
        <small class="text-muted d-block mt-1">บันทึกเป็นจำนวนผู้ป่วย (คน) ไม่ใช่จำนวนเครื่อง</small>
      </div>
      <div class="card-body">
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-2">
          <?php
          $equipmentFields = [
            'equipment_ventilator' => ['label' => 'เครื่องช่วยหายใจ', 'help' => 'จำนวนผู้ป่วยที่ใช้เครื่องช่วยหายใจในเวรนี้'],
            'equipment_hfnc'       => ['label' => 'High Flow O₂', 'help' => 'จำนวนผู้ป่วยที่ใช้ High Flow O₂ ในเวรนี้ (บันทึกที่เดียว)'],
          ];
          foreach ($equipmentFields as $field => $meta):
          ?>
            <div class="col">
              <div class="stat-card nc-other">
                <div class="stat-label"><?= esc($meta['label']) ?> <span class="material-symbols-outlined field-help" data-bs-toggle="tooltip" title="<?= esc($meta['help']) ?>">info</span></div>
                <input type="number" name="<?= $field ?>" id="<?= $field ?>"
                  class="stat-input"
                  min="0" value="<?= old($field, 0) ?>">
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Section 5: Quality Indicators ─────────────────────────────── -->
    <div class="card shadow-sm mb-3">
      <div class="card-header">
        <span>
          <span class="material-symbols-outlined text-primary me-1">monitor_heart</span>
          ตัวชี้วัดคุณภาพ (Quality Indicators)
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleQI">แสดง/ซ่อน</button>
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

    <div id="save_confirm_status" class="save-confirm-status text-muted small text-end mb-2"></div>

    <div class="d-flex gap-2 justify-content-end form-actions-bar">
      <button type="submit" id="btnConfirmSave" class="btn btn-primary px-4">
        <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">task_alt</span>
        บันทึกเพื่อยืนยันข้อมูล
      </button>
    </div>

  </form>
</div>

<script>
  let carriedForwardPatients = 0;
  let hasCarryForwardSource = false;

  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  function getInt(id) {
    return parseInt(document.getElementById(id)?.value) || 0;
  }

  function hasEnteredPatientLevels() {
    return [5, 4, 3, 2, 1].some(lvl => {
      return getInt(`patients_general_level_${lvl}`) > 0 || getInt(`patients_special_level_${lvl}`) > 0;
    });
  }

  function applyPreviousShiftSnapshot(snapshot, force = false) {
    if (!snapshot || (!force && hasEnteredPatientLevels())) {
      return;
    }

    [5, 4, 3, 2, 1].forEach(lvl => {
      const general = document.getElementById(`patients_general_level_${lvl}`);
      const special = document.getElementById(`patients_special_level_${lvl}`);
      if (general) {
        general.value = parseInt(snapshot[`patients_general_level_${lvl}`], 10) || 0;
      }
      if (special) {
        special.value = parseInt(snapshot[`patients_special_level_${lvl}`], 10) || 0;
      }
    });

    ['equipment_ventilator', 'equipment_hfnc'].forEach(id => {
      const input = document.getElementById(id);
      if (input) {
        input.value = parseInt(snapshot[id], 10) || 0;
      }
    });

    updateTotals();
  }

  function resetMovementInputs() {
    ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(id => {
      const input = document.getElementById(id);
      if (input) {
        input.value = 0;
      }
    });
  }

  function applyCurrentRecord(record) {
    if (!record) {
      return;
    }

    Object.entries(record).forEach(([field, value]) => {
      if (field === 'qi') {
        return;
      }
      const input = document.querySelector(`[name="${field}"]`);
      if (input) {
        input.value = value ?? '';
      }
    });

    if (record.api_discrepancy_reasons) {
        Object.entries(record.api_discrepancy_reasons).forEach(([field, value]) => {
            const input = document.getElementById('reason_' + field);
            if (input) {
                input.value = value ?? '';
            }
        });
    }

    if (record.qi) {
      Object.entries(record.qi).forEach(([field, value]) => {
        const input = document.querySelector(`[name="qi[${field}]"]`);
        if (input) {
          input.value = value ?? 0;
        }
      });
    }

    updateTotals();
  }

  function updateMovementBalance() {
    const expected = Math.max(0, carriedForwardPatients + getInt('admissions') + getInt('transfers_in') - getInt('discharges') - getInt('transfers_out') - getInt('deaths'));
    const actual = getInt('total_patients');
    const variance = actual - expected;

    document.getElementById('carried_forward_display').textContent = carriedForwardPatients;
    document.getElementById('movement_expected_display').textContent = expected;
    document.getElementById('movement_actual_display').textContent = actual;
    document.getElementById('movement_variance_display').textContent = variance;

    const status = document.getElementById('movement_balance_status');
    status.className = 'small mt-2 ' + (variance === 0 ? 'text-success' : 'text-warning');
    if (!hasCarryForwardSource) {
      status.className = 'small text-muted mt-2';
      status.textContent = 'ยังไม่พบเวรก่อนหน้า ระบบใช้ยอดยกมา 0 และถือเป็นข้อมูลตั้งต้น';
    } else if (variance === 0) {
      status.textContent = 'ยอดคงอยู่สัมพันธ์กับยอดยกมาและการเคลื่อนไหวผู้ป่วย';
    } else {
      status.textContent = `ยอดคงอยู่ต่างจากยอดคาดการณ์ ${variance > 0 ? '+' : ''}${variance} คน กรุณาตรวจสอบ movement หรือยอด Level`;
    }
  }

  function updateCarryForwardNote(context = null) {
    const note = document.getElementById('carry_forward_note');
    const text = document.getElementById('carry_forward_note_text');
    if (!note || !text) {
      return;
    }

    if (!context || !context.success) {
      note.classList.add('d-none');
      return;
    }

    note.classList.remove('d-none');
    if (context.has_previous) {
      text.textContent = `ยกยอดมาจากเวร ${context.previous_shift} วันที่ ${context.previous_date} จำนวน ${carriedForwardPatients} คน`;
    } else {
      text.textContent = 'ยังไม่มีเวรก่อนหน้า เวรนี้เริ่มต้นด้วยยอดยกมา 0 คน';
    }
  }

  async function loadMovementContext(forcePopulate = false) {
    const wardId = document.getElementById('ward_id').value;
    const date = document.getElementById('record_date').value;
    const shift = document.getElementById('shift').value;
    const status = document.getElementById('movement_balance_status');

    if (!wardId || !date || !shift) {
      carriedForwardPatients = 0;
      hasCarryForwardSource = false;
      updateCarryForwardNote(null);
      status.className = 'small text-muted mt-2';
      status.textContent = 'เลือก Ward / วันที่ / เวร เพื่อดึงยอดยกมาจากเวรก่อนหน้า';
      updateMovementBalance();
      return;
    }

    status.className = 'small text-muted mt-2';
    status.textContent = 'กำลังดึงยอดยกมา...';
    try {
      const params = new URLSearchParams({
        ward_id: wardId,
        record_date: date,
        shift
      });
      const resp = await fetch(`<?= base_url('census/movement-context') ?>?${params.toString()}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
      });
      const json = await resp.json();
      carriedForwardPatients = json.success ? parseInt(json.carried_forward_patients, 10) || 0 : 0;
      hasCarryForwardSource = Boolean(json.success && json.has_previous);
      updateCarryForwardNote(json);

      if (json.success && json.has_current) {
        applyCurrentRecord(json.current_record);
      }

      if (json.success && !json.has_current && json.has_previous) {
        applyPreviousShiftSnapshot(json.previous_snapshot, forcePopulate);
        if (forcePopulate) {
          resetMovementInputs();
        }
      }
    } catch (e) {
      carriedForwardPatients = 0;
      hasCarryForwardSource = false;
      updateCarryForwardNote(null);
      status.className = 'small text-danger mt-2';
      status.textContent = 'ดึงยอดยกมาไม่สำเร็จ';
      return;
    }
    updateMovementBalance();
  }

  function applyProductivityValue(el, value) {
    if (!el) return;
    if (value === null || value === undefined) {
      el.textContent = '—';
      el.className = 'prod-stat-value text-muted';
      return;
    }
    const pv = Number(value);
    el.textContent = pv.toFixed(2);
    el.className = 'prod-stat-value ' +
      (pv > 100 ? 'text-danger' : pv >= 80 ? 'text-warning' : 'text-success');
  }

  let previewTimer = null;

  async function refreshProductivityPreview() {
    const wardId = document.getElementById('ward_id')?.value;
    const date = document.getElementById('record_date')?.value;
    const shift = document.getElementById('shift')?.value;
    if (!wardId || !date || !shift) {
      return;
    }

    try {
      const resp = await fetch('<?= base_url('census/productivity-preview') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(document.getElementById('censusForm')),
      });
      const json = await resp.json();
      if (!json.success) {
        return;
      }

      const shiftBlock = json.shift || {};
      const dailyBlock = json.daily || {};

      document.getElementById('preview_shift_label').textContent = shiftBlock.label || '—';
      document.getElementById('preview_shift_care_hrs').textContent =
        Number(shiftBlock.required_care_hours || 0).toFixed(1);
      document.getElementById('preview_shift_work_hrs').textContent =
        Number(shiftBlock.working_hours || 0).toFixed(1);
      applyProductivityValue(
        document.getElementById('preview_shift_productivity'),
        shiftBlock.productivity
      );

      const careFrom = (dailyBlock.care_from_shifts || []).join(', ') || '—';
      const workFrom = (dailyBlock.working_from_shifts || []).join(', ') || '—';
      document.getElementById('preview_daily_care_from').textContent = careFrom;
      document.getElementById('preview_daily_work_from').textContent = workFrom;
      document.getElementById('preview_daily_care_hrs').textContent =
        Number(dailyBlock.required_care_hours || 0).toFixed(1);
      document.getElementById('preview_daily_work_hrs').textContent =
        Number(dailyBlock.working_hours || 0).toFixed(1);
      applyProductivityValue(
        document.getElementById('preview_daily_productivity'),
        dailyBlock.productivity
      );

      const levelEl = document.getElementById('preview_level_source');
      if (json.level_source === 'hosxp_diff') {
        levelEl.textContent = 'ระดับผู้ป่วยที่ออก: จาก HosXP (diff ราย 30 นาที)';
        levelEl.classList.remove('d-none');
      } else if (json.productivity_mode === 'turnover') {
        levelEl.textContent = 'ห้องคลอด: รวมชม.ดูแลทุกเวร · ระดับที่ออกประมาณจากค่าเฉลี่ย';
        levelEl.classList.remove('d-none');
      } else {
        levelEl.classList.add('d-none');
      }
    } catch (e) {
      // keep last values on transient errors
    }
  }

  function scheduleProductivityPreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(refreshProductivityPreview, 350);
  }

  function updateTotals() {
    let total = 0;
    [5, 4, 3, 2, 1].forEach(lvl => {
      const n = getInt(`patients_general_level_${lvl}`) + getInt(`patients_special_level_${lvl}`);
      const levelInput = document.getElementById(`patients_level_${lvl}`);
      const levelTotal = document.getElementById(`patients_level_${lvl}_total`);
      if (levelInput) {
        levelInput.value = n;
      }
      if (levelTotal) {
        levelTotal.textContent = n;
      }
      total += n;
    });
    document.getElementById('total_patients_display').value = total;
    document.getElementById('total_patients').value = total;
    document.getElementById('total_patients_badge').textContent = total;
    updateMovementBalance();
    scheduleProductivityPreview();
  }

  // ── Handover Guidelines Logic ──────────────────────────────────
  function drawTimelineChart(timeline) {
    const chartContainer = document.getElementById('hourly_timeline_chart_container');
    const labelsContainer = document.getElementById('hourly_timeline_labels');
    if (!chartContainer || !labelsContainer) return;

    chartContainer.innerHTML = '';
    labelsContainer.innerHTML = '';

    const counts = timeline.map(r => parseInt(r.patient_count) || 0);
    const maxVal = Math.max(...counts, 10);

    timeline.forEach(record => {
      const timeStr = record.record_time || '';
      const hourPart = timeStr.substring(11, 16) || '--:--';
      const patientCount = parseInt(record.patient_count) || 0;

      const heightPct = maxVal > 0 ? (patientCount / maxVal) * 80 : 0;

      const barWrapper = document.createElement('div');
      barWrapper.className = 'd-flex flex-column align-items-center flex-grow-1 h-100 justify-content-end';
      barWrapper.style.minWidth = '0';
      barWrapper.style.padding = '0 2px';

      const valueLabel = document.createElement('span');
      valueLabel.className = 'fw-bold mb-1';
      valueLabel.style.fontSize = '0.7rem';
      valueLabel.style.color = '#1e3a8a';
      valueLabel.textContent = patientCount;

      const bar = document.createElement('div');
      bar.className = 'rounded-top shadow-sm';
      bar.style.width = '70%';
      bar.style.maxWidth = '30px';
      bar.style.height = `${heightPct}%`;
      bar.style.background = 'linear-gradient(to top, #0d9488, #3b82f6)';
      bar.style.transition = 'height 0.4s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s';
      bar.style.cursor = 'pointer';
      
      const details = `รับใหม่: +${record.admissions_today}, จำหน่าย: -${record.discharges_today}, ย้ายเข้า: +${record.moves_in_today}, ย้ายออก: -${record.moves_out_today}, เสียชีวิต: -${record.deaths_today}`;
      bar.title = `เวลา ${hourPart}น.\nผู้ป่วยคงพยาบาล: ${patientCount} คน\n(${details})`;

      bar.addEventListener('mouseenter', () => {
        bar.style.background = 'linear-gradient(to top, #14b8a6, #60a5fa)';
      });
      bar.addEventListener('mouseleave', () => {
        bar.style.background = 'linear-gradient(to top, #0d9488, #3b82f6)';
      });

      barWrapper.appendChild(valueLabel);
      barWrapper.appendChild(bar);
      chartContainer.appendChild(barWrapper);

      const label = document.createElement('span');
      label.className = 'text-center flex-grow-1';
      label.style.minWidth = '0';
      label.style.fontSize = '0.65rem';
      label.textContent = hourPart;
      labelsContainer.appendChild(label);
    });
  }

  function checkDiscrepancy(fieldId) {
    const input = document.getElementById(fieldId);
    const badge = document.getElementById('gl_' + fieldId);
    const container = document.getElementById('reason_container_' + fieldId);
    const reasonInput = document.getElementById('reason_' + fieldId);
    
    if (!input || !badge || !container) return;
    
    if (badge.style.display !== 'none' && badge.dataset.apiValue !== undefined) {
        if (input.value !== '' && parseInt(input.value) !== parseInt(badge.dataset.apiValue)) {
            container.style.display = 'block';
            reasonInput.setAttribute('required', 'required');
        } else {
            container.style.display = 'none';
            reasonInput.removeAttribute('required');
        }
    } else {
        container.style.display = 'none';
        reasonInput.removeAttribute('required');
    }
  }

  function checkAllDiscrepancies() {
      ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(checkDiscrepancy);
  }

  async function loadHourlyGuidelines() {
    const wardId = document.getElementById('ward_id').value;
    const date = document.getElementById('record_date').value;
    const shift = document.getElementById('shift').value;
    const card = document.getElementById('handover_guidelines_card');

    if (!wardId || !date || !shift) {
      if (card) card.classList.add('d-none');
      return;
    }

    try {
      const params = new URLSearchParams({
        ward_id: wardId,
        record_date: date,
        shift
      });
      const resp = await fetch(`<?= base_url('census/hourly-guidelines') ?>?${params.toString()}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
      });
      const json = await resp.json();
      if (json.success && json.timeline && json.timeline.length > 0) {
        if (card) card.classList.remove('d-none');
        
        document.getElementById('hourly_admit_val').textContent = json.totals.admissions;
        document.getElementById('hourly_dc_val').textContent = json.totals.discharges;
        document.getElementById('hourly_tin_val').textContent = json.totals.transfers_in;
        document.getElementById('hourly_tout_val').textContent = json.totals.transfers_out;
        document.getElementById('hourly_death_val').textContent = json.totals.deaths;
        document.getElementById('hourly_patient_val').textContent = json.totals.patient_count;

        ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(f => {
            const badge = document.getElementById('gl_' + f);
            if (badge) {
                badge.textContent = json.totals[f] !== undefined ? json.totals[f] : '-';
                badge.style.display = 'inline-block';
                badge.dataset.apiValue = json.totals[f];
            }
        });
        checkAllDiscrepancies();

        document.getElementById('btn_apply_hourly').dataset.totals = JSON.stringify(json.totals);

        drawTimelineChart(json.timeline);
      } else {
        if (card) card.classList.add('d-none');
        ['admissions', 'discharges', 'transfers_in', 'transfers_out', 'deaths'].forEach(f => {
            const badge = document.getElementById('gl_' + f);
            if (badge) badge.style.display = 'none';
        });
        checkAllDiscrepancies();
      }
    } catch (e) {
      console.error('Failed to load hourly guidelines:', e);
      if (card) card.classList.add('d-none');
    }
  }

  document.getElementById('btn_apply_hourly')?.addEventListener('click', function() {
    const totalsData = this.dataset.totals;
    if (!totalsData) return;

    try {
      const totals = JSON.parse(totalsData);
      
      const inputs = {
        'admissions': totals.admissions,
        'discharges': totals.discharges,
        'transfers_in': totals.transfers_in,
        'transfers_out': totals.transfers_out,
        'deaths': totals.deaths
      };

      Object.entries(inputs).forEach(([id, val]) => {
        const input = document.getElementById(id);
        if (input) {
          input.value = val;
          const parent = input.closest('.stat-card');
          if (parent) {
            parent.style.transition = 'none';
            parent.style.transform = 'scale(1.05)';
            parent.style.boxShadow = '0 0 10px rgba(59, 130, 246, 0.5)';
            parent.style.borderColor = 'rgba(59, 130, 246, 0.8)';
            setTimeout(() => {
              parent.style.transition = 'all 0.3s ease';
              parent.style.transform = '';
              parent.style.boxShadow = '';
              parent.style.borderColor = '';
            }, 800);
          }
        }
      });

      updateMovementBalance();
      
      const form = document.getElementById('censusForm');
      if (form) {
        form.dispatchEvent(new Event('input'));
      }
    } catch (e) {
      console.error('Error applying hourly totals:', e);
    }
  });

  document.querySelectorAll('.patient-level-type, .nurse-calc').forEach(el => {
    el.addEventListener('input', updateTotals);
  });
  document.getElementById('shift').addEventListener('change', updateTotals);
  document.querySelectorAll('.movement-input').forEach(el => {
    el.addEventListener('input', () => {
        updateMovementBalance();
        checkDiscrepancy(el.dataset.field);
    });
  });
  ['ward_id', 'record_date', 'shift'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', () => {
      loadMovementContext(true);
      loadHourlyGuidelines();
    });
  });
  loadMovementContext(false);
  loadHourlyGuidelines();
  updateTotals();

  document.getElementById('toggleQI').addEventListener('click', () => {
    const s = document.getElementById('qiSection');
    s.style.display = s.style.display === 'none' ? '' : 'none';
  });

  document.getElementById('censusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    confirmSave();
  });

  const saveStatusEl = document.getElementById('save_confirm_status');
  const btnConfirmSave = document.getElementById('btnConfirmSave');

  async function confirmSave() {
    const wardId = document.getElementById('ward_id')?.value;
    const date = document.getElementById('record_date')?.value;
    const shift = document.getElementById('shift')?.value;
    if (!wardId || !date || !shift) {
      saveStatusEl.textContent = 'กรุณาเลือก Ward / วันที่ / เวร ก่อน';
      saveStatusEl.className = 'save-confirm-status text-end mb-2 is-error';
      return;
    }

    const variance = parseInt(document.getElementById('movement_variance_display')?.textContent || '0', 10) || 0;
    if (hasCarryForwardSource && variance !== 0 &&
      !confirm('ยอดคงอยู่ไม่ตรงกับยอดคาดการณ์จากยอดยกมาและ movement ต้องการบันทึกต่อหรือไม่?')) {
      return;
    }

    saveStatusEl.textContent = 'กำลังบันทึก…';
    saveStatusEl.className = 'save-confirm-status text-end mb-2 text-muted';
    btnConfirmSave.disabled = true;

    try {
      const resp = await fetch('<?= base_url('census/confirm') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(document.getElementById('censusForm')),
      });
      const json = await resp.json();
      if (json.success) {
        saveStatusEl.textContent = `${json.message || 'ยืนยันข้อมูลเรียบร้อยแล้ว'} · ${new Date().toLocaleTimeString('th-TH')}`;
        saveStatusEl.className = 'save-confirm-status text-end mb-2 is-success';
        await loadMovementContext(false);
        scheduleProductivityPreview();
      } else {
        saveStatusEl.textContent = `ผิดพลาด: ${json.message ?? 'ไม่สามารถบันทึกได้'}`;
        saveStatusEl.className = 'save-confirm-status text-end mb-2 is-error';
      }
    } catch (err) {
      saveStatusEl.textContent = 'บันทึกไม่สำเร็จ กรุณาลองใหม่';
      saveStatusEl.className = 'save-confirm-status text-end mb-2 is-error';
    } finally {
      btnConfirmSave.disabled = false;
    }
  }
</script>

<?= $this->endSection() ?>