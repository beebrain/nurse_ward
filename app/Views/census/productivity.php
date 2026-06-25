<?= $this->extend('layout/main') ?>

<?= $this->section('layout_class') ?>layout-fluid<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="<?= asset_url('css/ward-matrix.css') ?>" rel="stylesheet">
<link href="<?= asset_url('css/reports-dashboard.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.75rem;">monitoring</span>
                <div>
                    <div class="d-flex align-items-center gap-1">
                        <h1 class="h3 mb-0"><?= esc($title) ?></h1>
                        <button type="button"
                                class="prod-formula-info-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#prodFormulaModal"
                                aria-label="วิธีคิด Productivity"
                                title="วิธีคิด Productivity">
                            <span class="material-symbols-outlined" aria-hidden="true">info</span>
                        </button>
                    </div>
                    <div class="text-muted small"><?= esc($wardSubtitle ?? 'ประสิทธิภาพการพยาบาล (ชั่วโมง)') ?></div>
                </div>
            </div>
            <?php if (! empty($canRecord)): ?>
            <a href="<?= base_url('census/history') ?>" class="btn btn-sm btn-outline-secondary align-self-start">
                <span class="material-symbols-outlined me-1" style="font-size:1rem;">history</span>
                ประวัติย้อนหลัง
            </a>
            <?php elseif (auth()->user()->can('reports.view')): ?>
            <a href="<?= base_url('reports/dashboard') ?>" class="btn btn-sm btn-outline-secondary align-self-start">
                <span class="material-symbols-outlined me-1" style="font-size:1rem;">dashboard</span>
                แดชบอร์ดสรุป
            </a>
            <?php endif; ?>
        </div>

        <?php
        $levelHours = $levelHours ?? [];
        $shiftHours = (int) ($shiftHours ?? 7);
        $levelExplainParts = [];
        foreach ([5, 4, 3, 2, 1] as $lv) {
            if (isset($levelHours[$lv])) {
                $h = rtrim(rtrim((string) $levelHours[$lv], '0'), '.');
                $levelExplainParts[] = '(จำนวน L' . $lv . ' × ' . $h . ' ชม.)';
            }
        }
        $levelExplain = implode(' + ', $levelExplainParts);
        $l3Hours = rtrim(rtrim((string) ($levelHours[3] ?? 5.5), '0'), '.');
        ?>

        <div class="card shadow-sm mb-3">
            <div class="card-body py-3">
                <form id="productivity-filter" class="row g-2 align-items-end matrix-filter-actions">
                    <div class="col-12 col-md-4">
                        <label for="mode" class="form-label fw-bold small mb-1">รูปแบบ</label>
                        <select name="mode" id="mode" class="form-select form-select-sm">
                            <option value="month">รายเดือน (รายวัน)</option>
                            <option value="year">รายปี (รายเดือน)</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3" id="month-filter-wrap">
                        <label for="month" class="form-label fw-bold small mb-1">เดือน</label>
                        <select name="month" id="month" class="form-select form-select-sm">
                            <?php
                            $months = [
                                1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
                                5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
                                9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
                            ];
                            ?>
                            <?php foreach ($months as $monthNo => $monthName): ?>
                                <option value="<?= $monthNo ?>" <?= $monthNo === $currentMonth ? 'selected' : '' ?>><?= esc($monthName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="year" class="form-label fw-bold small mb-1">ปี</label>
                        <select name="year" id="year" class="form-select form-select-sm">
                            <?php for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
                                <option value="<?= $year ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">แสดง</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($wards)): ?>
            <div class="alert alert-warning py-2 small">ยังไม่มี Ward ที่คุณสามารถดูข้อมูลได้</div>
        <?php endif; ?>

        <div id="productivity-alert" class="alert alert-danger d-none py-2 small" role="alert" aria-live="assertive"></div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi matrix-page-kpi--status p-2 h-100" id="kpi-productivity-card">
                    <div class="matrix-page-kpi-label">Productivity รวม</div>
                    <div class="matrix-page-kpi-value" id="kpi-productivity">—</div>
                    <div class="matrix-page-kpi-hint" id="kpi-productivity-hint"></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">จำนวนผู้ป่วย (สะสม)</div>
                    <div class="matrix-page-kpi-value matrix-page-kpi-value--metric" id="kpi-patient-days">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">ชม.ดูแลที่ต้องการ</div>
                    <div class="matrix-page-kpi-value matrix-page-kpi-value--metric" id="kpi-required-hours">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">ชม.ทำงาน</div>
                    <div class="matrix-page-kpi-value matrix-page-kpi-value--metric" id="kpi-working-hours">0</div>
                </div>
            </div>
        </div>

        <div class="status-legend" role="list" aria-label="ความหมายสี Productivity">
            <span class="status-legend-chip status-legend-chip--critical" role="listitem">&gt;150% ภาระสูงมาก</span>
            <span class="status-legend-chip status-legend-chip--high" role="listitem">100–150% ภาระสูง</span>
            <span class="status-legend-chip status-legend-chip--adequate" role="listitem">60–100% สมดุล</span>
            <span class="status-legend-chip status-legend-chip--low" role="listitem">&lt;60% ภาระต่ำ</span>
            <span class="status-legend-chip status-legend-chip--none" role="listitem">— ไม่มีข้อมูล</span>
        </div>

        <ul class="nav nav-tabs prod-view-tabs mb-3" id="prodViewTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="prod-tab-summary" data-bs-toggle="tab" data-bs-target="#prod-panel-summary" type="button" role="tab" aria-controls="prod-panel-summary" aria-selected="true">สรุป</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="prod-tab-ward" data-bs-toggle="tab" data-bs-target="#prod-panel-ward" type="button" role="tab" aria-controls="prod-panel-ward" aria-selected="false">รายแผนก</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="prod-tab-matrix" data-bs-toggle="tab" data-bs-target="#prod-panel-matrix" type="button" role="tab" aria-controls="prod-panel-matrix" aria-selected="false">ตารางเต็ม</button>
            </li>
        </ul>

        <div id="productivity-loading" class="text-center text-muted py-4 d-none small" aria-live="polite">
            <div class="spinner-border spinner-border-sm me-2" role="status"><span class="visually-hidden">กำลังโหลด</span></div>กำลังโหลด...
        </div>

        <div class="tab-content" id="prodViewTabContent">
            <div class="tab-pane fade show active" id="prod-panel-summary" role="tabpanel" aria-labelledby="prod-tab-summary" tabindex="0">
                <div id="productivity-summary" aria-live="polite"></div>
            </div>

            <div class="tab-pane fade" id="prod-panel-ward" role="tabpanel" aria-labelledby="prod-tab-ward" tabindex="0">
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-3">
                        <label for="ward-detail-select" class="form-label fw-bold small mb-1">เลือกแผนก</label>
                        <select id="ward-detail-select" class="form-select form-select-sm"></select>
                    </div>
                </div>
                <div id="productivity-ward-detail" aria-live="polite"></div>
            </div>

            <div class="tab-pane fade" id="prod-panel-matrix" role="tabpanel" aria-labelledby="prod-tab-matrix" tabindex="0">
                <div class="card shadow-sm">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <h2 class="h6 mb-0" id="productivity-title">Productivity</h2>
                        <span class="text-muted small" id="productivity-subtitle"></span>
                    </div>
                    <div class="card-body p-0">
                        <div id="productivity-result" aria-live="polite"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="prodFormulaModal" tabindex="-1" aria-labelledby="prodFormulaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="prodFormulaModalLabel">วิธีคิด Productivity</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body prod-formula-body">
                <div class="prod-formula-main">
                    <strong>Productivity</strong> บอกว่าในวันนั้น งานดูแลผู้ป่วยที่ต้องใช้เทียบกับชั่วโมงทำงานของพยาบาลวิชาชีพมีมากน้อยแค่ไหน
                    — ยิ่งใกล้หรือเกิน 100% แปลว่าภาระงานสูงเมื่อเทียบกับกำลังคน
                </div>

                <div class="prod-formula-grid">
                    <div class="prod-formula-block">
                        <h3>แผนกผู้ป่วยใน (ทั่วไป)</h3>
                        <p class="mb-2">นับ<strong>ชั่วโมงดูแลที่ต้องการต่อวัน</strong>จากจำนวนผู้ป่วยแยกตามระดับความรุนแรง L1–L5 (รวมผู้ป่วยปกติและพิเศษ) ใน<strong>หนึ่งเวรต่อวัน</strong></p>
                        <p class="prod-formula-example mb-2">
                            ชม.ดูแล = <?= esc($levelExplain) ?>
                        </p>
                        <table class="prod-level-table">
                            <thead><tr><th>ระดับ</th><th>ชม.ดูแลต่อคนต่อวัน</th></tr></thead>
                            <tbody>
                            <?php foreach ([5, 4, 3, 2, 1] as $lv): ?>
                                <?php if (isset($levelHours[$lv])): ?>
                                    <tr><td>L<?= $lv ?></td><td><?= esc($levelHours[$lv]) ?> ชม.</td></tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p class="prod-formula-note mb-0">
                            เลือกเวรจากลำดับ <strong>บ่าย → ดึก → เช้า</strong> (ใช้เวรแรกที่มีการบันทึกข้อมูล)
                            · บันทึกครบเวรบ่ายแล้ว ระบบจะคำนวณและเก็บค่ารายวันให้อัตโนมัติ
                        </p>
                    </div>

                    <div class="prod-formula-block">
                        <h3>ชั่วโมงทำงานของพยาบาล</h3>
                        <ol class="prod-formula-steps mb-2">
                            <li>ต่อเวร: (RN + TN + PN) × <?= $shiftHours ?> ชม.</li>
                            <li>ต่อวัน: รวมทุกเวรที่บันทึก (เช้า + บ่าย + ดึก)</li>
                        </ol>
                        <p class="prod-formula-note mb-0">
                            นับเฉพาะ RN, TN, PN · ไม่รวมผู้ช่วยพยาบาล (HW), Aide และ Ward
                        </p>
                    </div>

                    <div class="prod-formula-block">
                        <h3>Productivity รายวัน</h3>
                        <p class="prod-formula-example mb-2">
                            (ชม.ดูแลที่ต้องการ ÷ ชม.ทำงานของพยาบาล) × 100
                        </p>
                        <p class="prod-formula-note mb-0">
                            ถ้าวันนั้นยังไม่บันทึกข้อมูลครบ หรือชม.ใดชม.หนึ่งเป็น 0 จะแสดงเป็น «—»
                        </p>
                    </div>

                    <div class="prod-formula-block">
                        <h3>สรุปรายเดือน / รายปี (ตารางนี้)</h3>
                        <ol class="prod-formula-steps mb-2">
                            <li>รวมชม.ดูแลที่ต้องการทุกวันในช่วงที่เลือก</li>
                            <li>รวมชม.ทำงานของพยาบาลทุกวันในช่วงเดียวกัน</li>
                            <li>นำสองค่านี้มาคิดเป็นเปอร์เซ็นต์แบบเดียวกับรายวัน</li>
                        </ol>
                        <p class="prod-formula-note mb-0">
                            คอลัมน์ «จำนวนผู้ป่วย» ในตาราง = ผู้ป่วยคงเหลือ ณ จบเวร
                            (เลือกจากเวร ดึก → บ่าย → เช้า ตามลำดับที่มีข้อมูล)
                        </p>
                    </div>

                    <div class="prod-formula-block prod-formula-block--lr">
                        <h3>ห้องคลอด (LR) — คนไข้เข้า–ออกเร็ว</h3>
                        <p class="mb-2">ห้องคลอดผู้ป่วยอยู่ไม่นาน จึงนับ<strong>ทุกคนที่ได้รับการดูแลในเวร</strong> ไม่ใช่แค่คนคงเหลือ ณ จบเวร</p>
                        <ol class="prod-formula-steps mb-2">
                            <li><strong>ผู้ป่วยที่ดูแลในเวร</strong> = คงเหลือ + จำหน่าย + ย้ายออก + เสียชีวิต</li>
                            <li><strong>ชม.ดูแลคนคงเหลือ</strong> = <?= esc($levelExplain) ?></li>
                            <li><strong>ชม.ดูแลคนที่ออก</strong> = จำนวนคนออก × ค่าเฉลี่ยชม./คนจากคนคงเหลือ
                                (ถ้าไม่มีคนคงเหลือ ใช้ค่าระดับ L3 = <?= esc($l3Hours) ?> ชม./คน)</li>
                            <li><strong>ชม.ดูแลต่อเวร</strong> = ชม.ดูแลคนคงเหลือ + ชม.ดูแลคนที่ออก</li>
                            <li><strong>ชม.ดูแลต่อวัน</strong> = รวมทุกเวรที่บันทึก</li>
                        </ol>
                        <p class="prod-formula-note mb-0">
                            ถ้าระบบ HosXP ส่งระดับผู้ป่วยราย 30 นาที ระบบจะคิดชม.ดูแลของผู้ป่วยที่ออกแยกตามระดับได้ละเอียดกว่า
                            · ถ้ายังไม่มีข้อมูลจาก HosXP ระบบจะประมาณจากค่าเฉลี่ยของผู้ป่วยคงเหลือในเวรนั้น
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="prodCellModal" tabindex="-1" aria-labelledby="prodCellModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h2 class="modal-title h6 mb-0" id="prodCellModalLabel">รายละเอียด Productivity</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body py-3" id="prodCellModalBody"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary btn-sm" id="prodCellModalWardBtn">ดูรายละเอียดแผนก</button>
            </div>
        </div>
    </div>
</div>

<?= $this->include('partials/ward_matrix_helpers') ?>
<script>
    $(function() {
        const WM = window.WardMatrix;
        const endpoint = '<?= base_url('census/productivity-data') ?>';
        const defaultWardId = <?= json_encode(isset($defaultWardId) ? (int) $defaultWardId : null) ?>;
        let lastPayload = null;
        let highlightPeriod = null;
        let cellModalWardId = null;
        let cellModalPeriod = null;

        const SUMMARY_GROUPS = [
            { key: 'critical', label: 'ภาระสูงมาก', hint: 'เกิน 150%', icon: 'error' },
            { key: 'high', label: 'ภาระสูง', hint: '100–150%', icon: 'warning' },
            { key: 'adequate', label: 'สมดุล', hint: '60–100%', icon: 'check_circle' },
            { key: 'low', label: 'ภาระต่ำ', hint: 'น้อยกว่า 60%', icon: 'info' },
            { key: 'none', label: 'ไม่มีข้อมูล', hint: 'ยังไม่บันทึกครบ', icon: 'remove' },
        ];

        function numberValue(value, digits = 0) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: digits,
                maximumFractionDigits: digits
            });
        }

        function productivityValue(value) {
            return value === null || value === undefined ? '—' : `${numberValue(value, 1)}%`;
        }

        function productivityMatrixValue(value) {
            if (value === null || value === undefined) {
                return '—';
            }
            const n = Number(value);
            const digits = n >= 100 ? 0 : 1;
            return `${numberValue(n, digits)}%`;
        }

        function matrixPctHtml(value) {
            const text = productivityMatrixValue(value);
            return `<span class="matrix-pct">${text}</span>`;
        }

        function prodBucket(value) {
            if (value === null || value === undefined) {
                return 'none';
            }
            const n = Number(value);
            if (n > 150) return 'critical';
            if (n > 100) return 'high';
            if (n >= 60) return 'adequate';
            return 'low';
        }

        function prodClass(value) {
            return `prod-${prodBucket(value)}`;
        }

        function prodHeatClass(value) {
            return `prod-heat-${prodBucket(value)}`;
        }

        function prodStatusLabel(value) {
            const labels = {
                critical: 'ภาระสูงมาก — ควรตรวจสอบกำลังคน',
                high: 'ภาระสูง',
                adequate: 'สมดุล / ใกล้เกณฑ์',
                low: 'ภาระต่ำ — ชม.พยาบาลมากเมื่อเทียบภาระ',
                none: 'ไม่มีข้อมูล',
            };
            return labels[prodBucket(value)];
        }

        function aggregateWardMonth(ward, days) {
            let recorded = 0;
            let required = 0;
            let working = 0;
            let patients = 0;
            const series = [];

            (days || []).forEach(function(day) {
                const cell = day.by_ward?.[String(ward.id)];
                if (!cell || cell.productivity === null || cell.productivity === undefined) {
                    series.push({
                        label: day.day_label,
                        sub: day.weekday_label,
                        productivity: null,
                    });
                    return;
                }
                recorded++;
                required += Number(cell.required_care_hours || 0);
                working += Number(cell.working_hours || 0);
                patients += Number(cell.patient_days || 0);
                series.push({
                    label: day.day_label,
                    sub: day.weekday_label,
                    date: day.date,
                    productivity: cell.productivity,
                    patient_days: cell.patient_days,
                    required_care_hours: cell.required_care_hours,
                    working_hours: cell.working_hours,
                });
            });

            return {
                ward,
                recorded,
                totalSlots: (days || []).length,
                required,
                working,
                patients,
                productivity: working > 0 && required > 0 ? (required * 100) / working : null,
                series,
            };
        }

        function aggregateWardYear(ward, months) {
            let recorded = 0;
            let required = 0;
            let working = 0;
            const series = [];

            (months || []).forEach(function(month) {
                const cell = month.by_ward?.[String(ward.id)];
                if (!cell || cell.productivity === null || cell.productivity === undefined) {
                    series.push({ label: month.month_label, productivity: null });
                    return;
                }
                recorded += Number(cell.recorded_days || 0);
                required += Number(cell.required_care_hours || 0);
                working += Number(cell.working_hours || 0);
                series.push({
                    label: month.month_label,
                    productivity: cell.productivity,
                    recorded_days: cell.recorded_days,
                    required_care_hours: cell.required_care_hours,
                    working_hours: cell.working_hours,
                });
            });

            return {
                ward,
                recorded,
                totalSlots: 12,
                required,
                working,
                patients: null,
                productivity: working > 0 && required > 0 ? (required * 100) / working : null,
                series,
            };
        }

        function buildWardSummaries(payload) {
            const wards = payload.wards || [];
            if (payload.mode === 'year') {
                return wards.map(function(ward) {
                    return aggregateWardYear(ward, payload.months || []);
                });
            }
            return wards.map(function(ward) {
                return aggregateWardMonth(ward, payload.days || []);
            });
        }

        function groupSummaries(summaries) {
            const groups = { critical: [], high: [], adequate: [], low: [], none: [] };
            summaries.forEach(function(item) {
                groups[prodBucket(item.productivity)].push(item);
            });
            groups.critical.sort((a, b) => Number(b.productivity || 0) - Number(a.productivity || 0));
            groups.high.sort((a, b) => Number(b.productivity || 0) - Number(a.productivity || 0));
            groups.adequate.sort((a, b) => Number(b.productivity || 0) - Number(a.productivity || 0));
            groups.low.sort((a, b) => Number(a.productivity || 0) - Number(b.productivity || 0));
            groups.none.sort((a, b) => String(a.ward.label || a.ward.name || '').localeCompare(String(b.ward.label || b.ward.name || ''), 'th'));
            return groups;
        }

        function periodLabel(payload) {
            if (payload.mode === 'year') {
                return `ปี ${payload.year}`;
            }
            return `${payload.month}/${payload.year}`;
        }

        function recordedLabel(item, payload) {
            if (payload.mode === 'year') {
                return `วันที่มีข้อมูล ${numberValue(item.recorded)} วัน`;
            }
            return `บันทึกแล้ว ${numberValue(item.recorded)} / ${numberValue(item.totalSlots)} วัน`;
        }

        function matrixCellButton(ward, opts) {
            const status = prodStatusLabel(opts.productivity);
            const wardLabel = ward.label || ward.name || '';
            const aria = `ดูรายละเอียด ${wardLabel} ${opts.periodLabel} Productivity ${productivityValue(opts.productivity)} (${status})`;

            return `<button type="button"
                class="prod-matrix-cell-btn ${prodClass(opts.productivity)}"
                data-ward-id="${ward.id}"
                data-period-label="${WM.escapeHtml(opts.periodLabel)}"
                data-period-sub="${WM.escapeHtml(opts.periodSub || '')}"
                data-productivity="${opts.productivity}"
                data-patient-days="${opts.patientDays ?? ''}"
                data-required-hours="${opts.requiredHours ?? ''}"
                data-working-hours="${opts.workingHours ?? ''}"
                data-recorded-days="${opts.recordedDays ?? ''}"
                aria-label="${WM.escapeHtml(aria)}">${matrixPctHtml(opts.productivity)}</button>`;
        }

        function showCellDetailModal($btn) {
            const wardId = Number($btn.data('ward-id'));
            const ward = (lastPayload?.wards || []).find(function(row) {
                return Number(row.id) === wardId;
            });
            const productivity = Number($btn.data('productivity'));
            const periodLabel = String($btn.data('period-label') || '');
            const periodSub = String($btn.data('period-sub') || '');
            const patientDays = $btn.data('patient-days');
            const requiredHours = $btn.data('required-hours');
            const workingHours = $btn.data('working-hours');
            const recordedDays = $btn.data('recorded-days');
            const periodKind = lastPayload?.mode === 'year' ? 'เดือน' : 'วันที่';

            cellModalWardId = wardId;
            cellModalPeriod = periodLabel;

            const rows = [
                ['แผนก', ward?.label || ward?.name || '—'],
                [periodKind, periodSub ? `${periodLabel} (${periodSub})` : periodLabel],
                ['Productivity', `<span class="${prodClass(productivity)} fw-bold">${productivityValue(productivity)}</span> · ${WM.escapeHtml(prodStatusLabel(productivity))}`],
            ];

            if (patientDays !== '' && patientDays !== undefined && patientDays !== null) {
                rows.push(['จำนวนผู้ป่วย', numberValue(patientDays)]);
            }
            if (requiredHours !== '' && requiredHours !== undefined && requiredHours !== null) {
                rows.push(['ชม.ดูแลที่ต้องการ', numberValue(requiredHours, 1)]);
            }
            if (workingHours !== '' && workingHours !== undefined && workingHours !== null) {
                rows.push(['ชม.ทำงานพยาบาล', numberValue(workingHours, 1)]);
            }
            if (recordedDays !== '' && recordedDays !== undefined && recordedDays !== null) {
                rows.push(['วันที่มีข้อมูล', `${numberValue(recordedDays)} วัน`]);
            }

            const body = rows.map(function(row) {
                return `<div class="prod-cell-detail-row">
                    <div class="prod-cell-detail-label">${WM.escapeHtml(row[0])}</div>
                    <div class="prod-cell-detail-value">${row[1]}</div>
                </div>`;
            }).join('');

            $('#prodCellModalLabel').text(`${ward?.label || ward?.name || 'แผนก'} · ${periodLabel}`);
            $('#prodCellModalBody').html(body);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('prodCellModal')).show();
        }

        function openWardDetail(wardId, periodLabel = null) {
            highlightPeriod = periodLabel;
            $('#ward-detail-select').val(String(wardId));
            bootstrap.Tab.getOrCreateInstance(document.getElementById('prod-tab-ward')).show();
            renderWardDetail();
        }

        function renderSummary(payload) {
            const summaries = buildWardSummaries(payload);
            const groups = groupSummaries(summaries);
            const recordedWards = summaries.filter(function(item) {
                return item.productivity !== null && item.productivity !== undefined;
            }).length;

            let html = `<p class="prod-summary-intro">${WM.escapeHtml(periodLabel(payload))} · ${payload.wards.length} แผนก · มีข้อมูล ${recordedWards} แผนก — เรียงตามความเร่งด่วน คลิก «ดูรายละเอียด» เพื่อดูแนวโน้มรายแผนก</p>`;
            html += '<div class="prod-summary-grid">';

            SUMMARY_GROUPS.forEach(function(group) {
                const items = groups[group.key] || [];
                html += `<section class="prod-summary-group prod-summary-group--${group.key}" aria-label="${WM.escapeHtml(group.label)}">
                    <div class="prod-summary-group-header">
                        <span class="material-symbols-outlined" aria-hidden="true">${group.icon}</span>
                        <span>${WM.escapeHtml(group.label)}</span>
                        <span class="ms-auto badge rounded-pill text-bg-light">${items.length}</span>
                    </div>`;

                if (!items.length) {
                    html += `<div class="prod-summary-empty">ไม่มีแผนกในช่วง ${WM.escapeHtml(group.hint)}</div>`;
                } else {
                    html += '<ul class="prod-summary-list">';
                    items.forEach(function(item) {
                        const pct = productivityValue(item.productivity);
                        const status = prodStatusLabel(item.productivity);
                        html += `<li class="prod-summary-item">
                            <div class="prod-summary-item-name">${WM.escapeHtml(item.ward.label || item.ward.name)}</div>
                            <div class="prod-summary-item-meta">${WM.escapeHtml(recordedLabel(item, payload))}</div>
                            <div class="prod-summary-item-value ${prodClass(item.productivity)}" aria-label="Productivity ${pct} (${status})">${pct}</div>
                            <div class="prod-summary-item-action">
                                <button type="button" class="btn btn-link btn-sm p-0 prod-open-ward" data-ward-id="${item.ward.id}">ดูรายละเอียด</button>
                            </div>
                        </li>`;
                    });
                    html += '</ul>';
                }
                html += '</section>';
            });

            html += '</div>';
            $('#productivity-summary').html(html);
        }

        function sparklineHeight(value) {
            if (value === null || value === undefined) {
                return 4;
            }
            return Math.max(4, Math.min(100, (Number(value) / 200) * 100));
        }

        function renderSparkline(series) {
            const bars = (series || []).map(function(point, index) {
                const bucket = prodBucket(point.productivity);
                const height = sparklineHeight(point.productivity);
                const title = point.productivity === null || point.productivity === undefined
                    ? `${point.label}: ไม่มีข้อมูล`
                    : `${point.label}: ${productivityValue(point.productivity)} (${prodStatusLabel(point.productivity)})`;
                return `<div class="prod-spark-bar prod-heat-${bucket}" style="height:${height}%" title="${WM.escapeHtml(title)}" role="img" aria-label="${WM.escapeHtml(title)}" tabindex="0"></div>`;
            }).join('');

            return `<div class="prod-sparkline-wrap">
                <div class="prod-sparkline-label">แนวโน้ม Productivity</div>
                <div class="prod-sparkline">${bars || '<span class="text-muted small">ไม่มีข้อมูล</span>'}</div>
            </div>`;
        }

        function renderWardDetailTable(item, payload) {
            const rows = (item.series || []).filter(function(point) {
                return point.productivity !== null && point.productivity !== undefined;
            });

            if (!rows.length) {
                return '<div class="alert alert-light border small mb-0">ยังไม่มีข้อมูล Productivity ในช่วงที่เลือก</div>';
            }

            const dateHeader = payload.mode === 'year' ? 'เดือน' : 'วันที่';
            const body = rows.map(function(point) {
                const pct = productivityValue(point.productivity);
                const status = prodStatusLabel(point.productivity);
                const patients = point.patient_days !== undefined ? numberValue(point.patient_days) : '—';
                const meta = payload.mode === 'year'
                    ? `วันที่มีข้อมูล ${numberValue(point.recorded_days || 0)}`
                    : WM.escapeHtml(point.sub || '');

                const focusClass = highlightPeriod && point.label === highlightPeriod ? 'prod-ward-row--focus' : '';

                return `<tr class="${focusClass}">
                    <td>
                        <div class="fw-semibold">${WM.escapeHtml(point.label)}</div>
                        ${payload.mode === 'month' ? `<div class="text-muted small">${meta}</div>` : ''}
                    </td>
                    <td class="col-num">${patients}</td>
                    <td class="col-num">${numberValue(point.required_care_hours, 1)}</td>
                    <td class="col-num">${numberValue(point.working_hours, 1)}</td>
                    <td class="col-num ${prodClass(point.productivity)}" aria-label="Productivity ${pct} (${status})">${pct}</td>
                </tr>`;
            }).join('');

            return `<div class="prod-ward-table-scroll">
                <table class="table prod-ward-table mb-0">
                    <thead><tr>
                        <th>${dateHeader}</th>
                        <th class="col-num">ผู้ป่วย</th>
                        <th class="col-num">ชม.ดูแล</th>
                        <th class="col-num">ชม.ทำงาน</th>
                        <th class="col-num">Productivity</th>
                    </tr></thead>
                    <tbody>${body}</tbody>
                </table>
            </div>`;
        }

        function renderWardDetail() {
            if (!lastPayload) {
                return;
            }

            const wardId = Number($('#ward-detail-select').val());
            const summaries = buildWardSummaries(lastPayload);
            const item = summaries.find(function(row) {
                return Number(row.ward.id) === wardId;
            });

            if (!item) {
                $('#productivity-ward-detail').html('<div class="alert alert-warning small mb-0">ไม่พบข้อมูลแผนก</div>');
                return;
            }

            const pct = productivityValue(item.productivity);
            const html = `
                <div class="prod-ward-kpi-grid">
                    <div class="prod-ward-kpi">
                        <div class="prod-ward-kpi-label">Productivity ช่วงที่เลือก</div>
                        <div class="prod-ward-kpi-value ${prodClass(item.productivity)}">${pct}</div>
                    </div>
                    <div class="prod-ward-kpi">
                        <div class="prod-ward-kpi-label">${lastPayload.mode === 'year' ? 'วันที่มีข้อมูล' : 'วันที่บันทึก'}</div>
                        <div class="prod-ward-kpi-value">${WM.escapeHtml(recordedLabel(item, lastPayload))}</div>
                    </div>
                    <div class="prod-ward-kpi">
                        <div class="prod-ward-kpi-label">ชม.ดูแลรวม</div>
                        <div class="prod-ward-kpi-value">${numberValue(item.required, 1)}</div>
                    </div>
                    <div class="prod-ward-kpi">
                        <div class="prod-ward-kpi-label">ชม.ทำงานรวม</div>
                        <div class="prod-ward-kpi-value">${numberValue(item.working, 1)}</div>
                    </div>
                </div>
                ${renderSparkline(item.series)}
                ${renderWardDetailTable(item, lastPayload)}
            `;
            $('#productivity-ward-detail').html(html);
        }

        function populateWardSelect(payload) {
            const $select = $('#ward-detail-select');
            const current = $select.val();
            $select.empty();
            (payload.wards || []).forEach(function(ward) {
                $select.append(`<option value="${ward.id}">${WM.escapeHtml(ward.label || ward.name)}</option>`);
            });

            const preferred = current || (defaultWardId ? String(defaultWardId) : null);
            if (preferred && $select.find(`option[value="${preferred}"]`).length) {
                $select.val(preferred);
            }
        }

        function wardCell(day, ward) {
            const cell = day.by_ward?.[String(ward.id)];
            if (!cell || cell.productivity === null || cell.productivity === undefined) {
                return '<td class="ward-cell prod-heat-none" aria-label="ไม่มีข้อมูล">—</td>';
            }
            return `<td class="ward-cell ${prodHeatClass(cell.productivity)}">${matrixCellButton(ward, {
                periodLabel: day.day_label,
                periodSub: day.weekday_label || '',
                productivity: cell.productivity,
                patientDays: cell.patient_days,
                requiredHours: cell.required_care_hours,
                workingHours: cell.working_hours,
            })}</td>`;
        }

        function renderMatrixMonth(payload) {
            const wards = payload.wards || [];
            const wardHeaders = wards.map(w => WM.headerTh(w)).join('');
            const rows = (payload.days || []).map(day => `
                <tr>
                    ${WM.dateCell(day)}
                    ${wards.map(w => wardCell(day, w)).join('')}
                </tr>
            `).join('');

            const colSpan = wards.length + 1;
            $('#productivity-title').text('Productivity ทุกแผนก (รายวัน)');
            $('#productivity-subtitle').text(`${payload.month}/${payload.year} · ${wards.length} แผนก · เลื่อนแนวนอน · คลิกเซลล์ดูรายละเอียด`);
            $('#productivity-result').html(WM.wrapTable(`
                <thead class="table-light"><tr>
                    <th class="ward-matrix-date-col ps-2">วันที่</th>
                    ${wardHeaders}
                </tr></thead>
                <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
            `));
        }

        function renderMatrixYear(payload) {
            const wards = payload.wards || [];
            const wardHeaders = wards.map(w => WM.headerTh(w)).join('');
            const rows = (payload.months || []).map(month => `
                <tr>
                    <td class="ward-matrix-date-col ps-2"><span class="date-main">${WM.escapeHtml(month.month_label)}</span></td>
                    ${wards.map(w => {
                        const cell = month.by_ward?.[String(w.id)];
                        if (!cell || cell.productivity === null || cell.productivity === undefined) {
                            return '<td class="ward-cell prod-heat-none" aria-label="ไม่มีข้อมูล">—</td>';
                        }
                        return `<td class="ward-cell ${prodHeatClass(cell.productivity)}">${matrixCellButton(w, {
                            periodLabel: month.month_label,
                            productivity: cell.productivity,
                            requiredHours: cell.required_care_hours,
                            workingHours: cell.working_hours,
                            recordedDays: cell.recorded_days,
                        })}</td>`;
                    }).join('')}
                </tr>
            `).join('');

            const colSpan = wards.length + 1;
            $('#productivity-title').text('Productivity ทุกแผนก (รายเดือน)');
            $('#productivity-subtitle').text(`ปี ${payload.year} · ${wards.length} แผนก · เลื่อนแนวนอน · คลิกเซลล์ดูรายละเอียด`);
            $('#productivity-result').html(WM.wrapTable(`
                <thead class="table-light"><tr>
                    <th class="ward-matrix-date-col ps-2">เดือน</th>
                    ${wardHeaders}
                </tr></thead>
                <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
            `));
        }

        function renderAll(payload) {
            lastPayload = payload;
            populateWardSelect(payload);
            renderSummary(payload);
            renderWardDetail();
            if (payload.mode === 'year') {
                renderMatrixYear(payload);
            } else {
                renderMatrixMonth(payload);
            }
        }

        function updateModeVisibility() {
            $('#month-filter-wrap').toggle($('#mode').val() === 'month');
        }

        function updateKpis(summary) {
            const productivity = summary?.productivity;
            const bucket = prodBucket(productivity);
            const $card = $('#kpi-productivity-card');

            $card.removeClass('prod-status-critical prod-status-high prod-status-adequate prod-status-low prod-status-none');
            if (bucket !== 'none') {
                $card.addClass('prod-status-' + bucket);
            }

            $('#kpi-productivity')
                .text(productivityValue(productivity))
                .removeClass('prod-critical prod-high prod-adequate prod-low prod-none')
                .addClass(prodClass(productivity));

            $('#kpi-productivity-hint').text(
                productivity !== null && productivity !== undefined
                    ? prodStatusLabel(productivity)
                    : 'ยังไม่มีข้อมูลในช่วงที่เลือก'
            );

            $('#kpi-patient-days').text(numberValue(summary?.patient_days));
            $('#kpi-required-hours').text(numberValue(summary?.required_care_hours, 1));
            $('#kpi-working-hours').text(numberValue(summary?.working_hours, 1));
        }

        function loadProductivity() {
            $('#productivity-alert').addClass('d-none').text('');
            $('#productivity-loading').removeClass('d-none');
            $('#productivity-summary').empty();
            $('#productivity-ward-detail').empty();
            $('#productivity-result').empty();

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: $('#productivity-filter').serializeArray(),
                dataType: 'json'
            }).done(function(payload) {
                updateKpis(payload.summary || {});
                renderAll(payload);
            }).fail(function(xhr) {
                $('#productivity-alert').removeClass('d-none').text(xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ');
                updateKpis({});
                lastPayload = null;
            }).always(function() {
                $('#productivity-loading').addClass('d-none');
            });
        }

        $(document).on('click', '.prod-open-ward', function() {
            openWardDetail(Number($(this).data('ward-id')));
        });

        $(document).on('click', '.prod-matrix-cell-btn', function() {
            showCellDetailModal($(this));
        });

        $('#prodCellModalWardBtn').on('click', function() {
            if (!cellModalWardId) {
                return;
            }
            bootstrap.Modal.getInstance(document.getElementById('prodCellModal'))?.hide();
            openWardDetail(cellModalWardId, cellModalPeriod);
        });

        $('#ward-detail-select').on('change', function() {
            highlightPeriod = null;
            renderWardDetail();
        });

        $('button[data-bs-target="#prod-panel-ward"]').on('shown.bs.tab', function() {
            renderWardDetail();
        });

        $('#mode').on('change', updateModeVisibility);
        $('#productivity-filter').on('submit', function(event) {
            event.preventDefault();
            loadProductivity();
        });

        updateModeVisibility();
        <?php if (! empty($wards)): ?>
        loadProductivity();
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
