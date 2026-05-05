<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .history-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: .75rem;
    }

    .history-detail-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: .75rem;
        background: #f8fafc;
    }

    .history-detail-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
    }

    .history-detail-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #1e293b;
    }

    .history-table th,
    .history-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .history-table tr[data-shift-key] {
        cursor: pointer;
    }

    .history-table tr[data-shift-key]:hover td {
        filter: brightness(.95);
    }

    .history-row-night td   { background: #eef2ff; }
    .history-row-morning td { background: #ecfdf5; }
    .history-row-afternoon td { background: #fff7ed; }
    .history-row-empty td   { background: #f8fafc; color: #94a3b8; }

    .history-date-group-start td {
        border-top: 2px solid #dee2e6 !important;
    }

    #shiftDetailModal,
    #dailyProdModal {
        z-index: 2000;
    }

    .modal-backdrop {
        z-index: 1990;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">history</span>
                <div>
                    <h1 class="mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">ดูรายการที่เคยบันทึกย้อนหลังแบบอ่านอย่างเดียว</div>
                </div>
            </div>
            <a href="<?= base_url('census/new') ?>" class="btn btn-outline-secondary align-self-start align-self-xl-end">
                <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">edit_note</span>
                กลับไปหน้าบันทึก
            </a>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="history-filter" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="ward_id" class="form-label fw-bold">Ward</label>
                        <select name="ward_id" id="ward_id" class="form-select" required <?= $isNurse && count($wards) <= 1 ? 'disabled' : '' ?>>
                            <?php foreach ($wards as $ward): ?>
                                <option value="<?= esc((string)$ward['id']) ?>" <?= (int)$defaultWardId === (int)$ward['id'] ? 'selected' : '' ?>>
                                    <?= esc(trim(($ward['code'] ?? '') . ' ' . $ward['name'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isNurse && count($wards) <= 1): ?>
                            <input type="hidden" name="ward_id" value="<?= esc((string)$defaultWardId) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <label for="month" class="form-label fw-bold">เดือน</label>
                        <select name="month" id="month" class="form-select">
                            <?php
                            $months = [
                                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
                            ];
                            ?>
                            <?php foreach ($months as $monthNo => $monthName): ?>
                                <option value="<?= $monthNo ?>" <?= $monthNo === $currentMonth ? 'selected' : '' ?>><?= esc($monthName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="year" class="form-label fw-bold">ปี</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
                                <option value="<?= $year ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="material-symbols-outlined me-1" style="font-size:1rem;">search</span>
                            ดูข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($wards)): ?>
            <div class="alert alert-warning">
                <span class="material-symbols-outlined align-middle me-1">warning</span>
                ยังไม่มี Ward ที่คุณสามารถดูข้อมูลได้
            </div>
        <?php endif; ?>

        <div id="history-alert" class="alert alert-danger d-none"></div>

<div class="row g-3 mb-4" id="history-summary">
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">จำนวนรายการบันทึก</div>
                        <div class="display-6 fw-bold mb-0" id="sum-recorded-shifts">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">รับใหม่</div>
                        <div class="display-6 fw-bold text-success mb-0" id="sum-admissions">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">จำหน่าย</div>
                        <div class="display-6 fw-bold text-primary mb-0" id="sum-discharges">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">เสียชีวิต</div>
                        <div class="display-6 fw-bold text-danger mb-0" id="sum-deaths">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="history-title">รายการย้อนหลัง</h5>
                <span class="text-muted small" id="history-subtitle"></span>
            </div>
            <div class="card-body p-0">
                <div id="history-loading" class="text-center text-muted py-5 d-none">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    กำลังโหลดข้อมูล...
                </div>
                <div id="history-result"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shiftDetailModal" tabindex="-1" aria-labelledby="shiftDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="shiftDetailModalLabel">รายละเอียดเวร</h5>
                    <div class="text-muted small" id="shiftDetailSubtitle"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="shiftDetailBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dailyProdModal" tabindex="-1" aria-labelledby="dailyProdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dailyProdModalLabel">Productivity รายวัน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="dailyProdBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        const endpoint = '<?= base_url('census/history-data') ?>';
        const shiftDetails = {};

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function numberValue(value) {
            return Number(value || 0).toLocaleString();
        }

        function decimalValue(value) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function detailCard(label, value, suffix = '') {
            return `
                <div class="history-detail-card">
                    <div class="history-detail-label">${escapeHtml(label)}</div>
                    <div class="history-detail-value">${escapeHtml(value)}${suffix}</div>
                </div>
            `;
        }

        function detailTable(title, rows) {
            return `
                <h6 class="fw-bold mt-4 mb-2">${escapeHtml(title)}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>รายการ</th>
                                <th class="text-end">จำนวน</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map(row => `
                                <tr>
                                    <td>${escapeHtml(row[0])}</td>
                                    <td class="text-end fw-semibold">${numberValue(row[1])}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function updateSummary(summary) {
            $('#sum-recorded-shifts').text(numberValue(summary?.recorded_shifts));
            $('#sum-admissions').text(numberValue(summary?.admissions));
            $('#sum-discharges').text(numberValue(summary?.discharges));
            $('#sum-deaths').text(numberValue(summary?.deaths));
        }

        const LEVEL_H  = { 5: 4.0, 4: 3.5, 3: 3.0, 2: 2.5, 1: 2.0 };
        const SHIFT_H  = 7;
        const dayCalcs = {};

        function calcShiftProd(shift) {
            if (!shift) return null;
            const req  = [5,4,3,2,1].reduce((s, lv) => s + LEVEL_H[lv] * Number(shift[`patients_level_${lv}`]||0), 0);
            const work = (Number(shift.nurses_rn||0) + Number(shift.nurses_tn||0) + Number(shift.nurses_pn||0)) * SHIFT_H;
            return (work > 0 && req > 0) ? req * 100 / work : null;
        }

        function prodBadge(pv, size = '') {
            if (pv === null) return '<span class="text-muted">-</span>';
            const cls = pv >= 80 ? 'text-success' : pv >= 60 ? 'text-warning' : 'text-danger';
            return `<span class="${cls} fw-bold ${size}">${decimalValue(pv)}%</span>`;
        }

        function renderDayGroup(day, isFirstDay) {
            const SHIFTS        = ['Night', 'Morning', 'Afternoon'];
            const shiftRowClass = { Night: 'history-row-night', Morning: 'history-row-morning', Afternoon: 'history-row-afternoon' };
            const badgeClass    = { Night: 'bg-primary', Morning: 'bg-success', Afternoon: 'bg-warning text-dark' };

            // Daily Productivity: sum required hours across all 3 shifts / total working hours
            // required per shift = level hours × patient counts for THAT shift
            // working per shift  = (RN+TN+PN) × 7h
            let maxPatients = -1, maxShift = null;
            SHIFTS.forEach(sk => {
                const s = day.shifts[sk];
                if (s && Number(s.total_patients||0) > maxPatients) {
                    maxPatients = Number(s.total_patients||0);
                    maxShift = s;
                }
            });
            const dailyRequired = SHIFTS.reduce((sum, sk) => {
                const s = day.shifts[sk];
                if (!s) return sum;
                return sum + [5,4,3,2,1].reduce((r, lv) => r + LEVEL_H[lv] * Number(s[`patients_level_${lv}`]||0), 0);
            }, 0);
            const totalProf = SHIFTS.reduce((sum, sk) => {
                const s = day.shifts[sk];
                return s ? sum + Number(s.nurses_rn||0) + Number(s.nurses_tn||0) + Number(s.nurses_pn||0) : sum;
            }, 0);
            const dailyWorking = totalProf * SHIFT_H;
            const dailyProd = (dailyWorking > 0 && dailyRequired > 0) ? dailyRequired * 100 / dailyWorking : null;

            // Build daily calculation detail object (shared across all shifts of this day)
            const maxShiftName = maxShift
                ? SHIFTS.find(sk => day.shifts[sk] === maxShift)
                : null;
            const LABEL = { Night:'ดึก', Morning:'เช้า', Afternoon:'บ่าย' };
            const dailyCalc = {
                date_label: day.day_label + ' ' + day.weekday_label,
                shifts_required: SHIFTS.map(sk => {
                    const s = day.shifts[sk];
                    if (!s) return null;
                    const levels = [5,4,3,2,1].map(lv => ({
                        level: lv, h_per: LEVEL_H[lv],
                        pts: Number(s[`patients_level_${lv}`]||0),
                        total: LEVEL_H[lv] * Number(s[`patients_level_${lv}`]||0),
                    }));
                    return { label: LABEL[sk], patients: Number(s.total_patients||0),
                             required: levels.reduce((r,v)=>r+v.total,0), levels };
                }).filter(Boolean),
                required:     dailyRequired,
                shifts_staff: SHIFTS.map(sk => {
                    const s = day.shifts[sk];
                    if (!s) return null;
                    const rn=Number(s.nurses_rn||0), tn=Number(s.nurses_tn||0), pn=Number(s.nurses_pn||0);
                    return { label:LABEL[sk], rn, tn, pn, people:rn+tn+pn, hours:(rn+tn+pn)*SHIFT_H };
                }).filter(Boolean),
                total_working: dailyWorking,
                daily_prod:    dailyProd,
            };
            dayCalcs[day.date] = dailyCalc;

            // Compute and cache per-shift productivity into shift objects
            SHIFTS.forEach(sk => {
                const s = day.shifts[sk];
                if (s) {
                    s._prod_shift = calcShiftProd(s);
                    s._daily_prod = dailyProd;
                    s._daily_calc = dailyCalc;
                    // Per-shift calc detail
                    const rn = Number(s.nurses_rn||0), tn = Number(s.nurses_tn||0), pn = Number(s.nurses_pn||0);
                    const shiftWorking = (rn+tn+pn)*SHIFT_H;
                    const shiftReq = [5,4,3,2,1].reduce((sum,lv)=>sum+LEVEL_H[lv]*Number(s[`patients_level_${lv}`]||0),0);
                    s._shift_calc = {
                        levels: [5,4,3,2,1].map(lv => ({
                            level: lv, h_per: LEVEL_H[lv],
                            pts: Number(s[`patients_level_${lv}`]||0),
                            total: LEVEL_H[lv]*Number(s[`patients_level_${lv}`]||0),
                        })),
                        required: shiftReq, rn, tn, pn,
                        working: shiftWorking, prod: s._prod_shift,
                    };
                }
            });

            const prodCell = `<td rowspan="3" class="text-center align-middle border-start prod-day-cell"
                data-day-key="${day.date}" style="min-width:90px;cursor:pointer;">
                ${prodBadge(dailyProd, 'fs-6')}
                <div class="text-muted" style="font-size:.7rem;margin-top:.25rem;">คลิกดูรายละเอียด</div>
            </td>`;

            return SHIFTS.map((shiftKey, i) => {
                const shift   = day.shifts[shiftKey];
                const isFirst = (i === 0);
                const borderClass = isFirst && isFirstDay ? 'history-date-group-start' : '';

                const dateCell = isFirst
                    ? `<td class="ps-3 fw-semibold" rowspan="3" style="vertical-align:middle;min-width:90px;">
                            <div>${escapeHtml(day.day_label)}</div>
                            <div class="text-muted fw-normal small">${escapeHtml(day.weekday_label)}</div>
                       </td>`
                    : '';

                if (!shift) {
                    return `<tr class="${borderClass} history-row-empty">
                        ${dateCell}
                        <td><span class="badge bg-secondary">${escapeHtml({Night:'เวรดึก',Morning:'เวรเช้า',Afternoon:'เวรบ่าย'}[shiftKey])}</span></td>
                        <td colspan="8" class="text-muted small fst-italic">ยังไม่บันทึก</td>
                        ${isFirst ? prodCell : ''}
                    </tr>`;
                }

                const detailKey = `shift-${shift.id}`;
                shiftDetails[detailKey] = shift;
                const staff = Number(shift.nurses_rn||0) + Number(shift.nurses_tn||0) + Number(shift.nurses_pn||0);

                // Per-shift productivity badge shown inline
                const shiftProdHtml = shift._prod_shift !== null
                    ? `<div class="mt-1">${prodBadge(shift._prod_shift)}</div>`
                    : '';

                return `<tr class="${borderClass} ${shiftRowClass[shiftKey]}" data-shift-key="${detailKey}">
                    ${dateCell}
                    <td><span class="badge ${badgeClass[shiftKey]}">${escapeHtml(shift.shift_label)}</span></td>
                    <td class="text-end fw-bold">${numberValue(shift.total_patients)}</td>
                    <td class="text-end text-success">${numberValue(shift.admissions)}</td>
                    <td class="text-end">${numberValue(shift.discharges)}</td>
                    <td class="text-end">${numberValue(shift.transfers_in)}</td>
                    <td class="text-end">${numberValue(shift.transfers_out)}</td>
                    <td class="text-end text-danger">${numberValue(shift.deaths)}</td>
                    <td class="text-end small">
                        <div>${numberValue(staff)} คน</div>
                        ${shiftProdHtml}
                    </td>
                    <td class="small text-muted pe-3">${escapeHtml(shift.recorder_username || '-')}</td>
                    ${isFirst ? prodCell : ''}
                </tr>`;
            }).join('');
        }

        function buildPopoverTitle(shift) {
            const badge = `<span class="badge bg-secondary ms-1">รวม ${numberValue(shift.total_patients)}</span>`;
            return `${escapeHtml(shift.shift_label)} ${badge}`;
        }

        function buildPopoverContent(shift) {
            const staff = Number(shift.nurses_rn||0) + Number(shift.nurses_tn||0) + Number(shift.nurses_pn||0);
            const row = (label, value, cls = '') =>
                `<div class="d-flex justify-content-between gap-3 py-1 border-bottom">
                    <small class="text-muted">${escapeHtml(label)}</small>
                    <strong class="${cls}">${numberValue(value)}</strong>
                </div>`;
            const dischargeOut = Number(shift.discharges||0) + Number(shift.transfers_out||0);
            const shiftProdHtml = shift._prod_shift !== null
                ? `<strong>${decimalValue(shift._prod_shift)}%</strong>`
                : '<span class="text-muted">-</span>';
            return `
                ${row('รับใหม่', shift.admissions, 'text-success')}
                ${row('จำหน่าย/ย้ายออก', dischargeOut)}
                ${row('ย้ายเข้า', shift.transfers_in)}
                ${row('เสียชีวิต', shift.deaths, 'text-danger')}
                <div class="d-flex justify-content-between gap-3 py-1 border-bottom">
                    <small class="text-muted">RN+TN+PN</small>
                    <strong>${staff} คน</strong>
                </div>
                <div class="d-flex justify-content-between gap-3 py-1 border-bottom">
                    <small class="text-muted">Productivity (เวรนี้)</small>
                    ${shiftProdHtml}
                </div>
                <div class="text-center text-muted small mt-2">คลิกเพื่อดูรายละเอียด</div>
                <div class="text-center text-muted small">ผู้บันทึก: ${escapeHtml(shift.recorder_username || '-')}</div>
            `;
        }

        function initPopovers() {
            $('#history-result tr[data-shift-key]').each(function() {
                const existing = bootstrap.Popover.getInstance(this);
                if (existing) existing.dispose();
                const key = $(this).data('shift-key');
                const shift = shiftDetails[key];
                if (!shift) return;
                new bootstrap.Popover(this, {
                    html: true,
                    trigger: 'hover focus',
                    placement: 'top',
                    title: buildPopoverTitle(shift),
                    content: buildPopoverContent(shift),
                    sanitize: false,
                });
            });
        }

        function calcBreakdownHtml(shift) {
            const dc = shift._daily_calc;
            const sc = shift._shift_calc;
            if (!dc || !sc) return '';

            const levelRows = sc.levels.map(r =>
                `<tr>
                    <td class="text-center"><span class="badge bg-secondary">L${r.level}</span></td>
                    <td class="text-end">${r.pts}</td>
                    <td class="text-end text-muted">${r.h_per.toFixed(1)}</td>
                    <td class="text-end fw-semibold">${decimalValue(r.total)}</td>
                </tr>`
            ).join('');

            const dailyLevelRows = dc.levels.map(r =>
                `<tr${dc.max_shift_key === shift.shift ? ' class="table-primary"' : ''}>
                    <td class="text-center"><span class="badge bg-secondary">L${r.level}</span></td>
                    <td class="text-end">${r.pts}</td>
                    <td class="text-end text-muted">${r.h_per.toFixed(1)}</td>
                    <td class="text-end fw-semibold">${decimalValue(r.total)}</td>
                </tr>`
            ).join('');

            const staffRows = dc.shifts_staff.map(r =>
                `<tr${r.label === shift.shift_label ? ' class="table-warning"' : ''}>
                    <td>เวร${r.label}</td>
                    <td class="text-end">${r.rn}</td>
                    <td class="text-end">${r.tn}</td>
                    <td class="text-end">${r.pn}</td>
                    <td class="text-end fw-semibold">${r.people}</td>
                    <td class="text-end fw-semibold">${r.hours.toFixed(1)}</td>
                </tr>`
            ).join('');

            const shiftProdVal = sc.prod !== null ? `${decimalValue(sc.prod)}%` : '-';
            const dailyProdVal = dc.daily_prod !== null ? `${decimalValue(dc.daily_prod)}%` : '-';

            return `
            <hr>
            <h6 class="fw-bold mb-3">รายละเอียดการคำนวณ Productivity</h6>
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="fw-bold mb-2 small text-uppercase text-muted">
                                Productivity เวรนี้ (${escapeHtml(shift.shift_label)})
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-light"><tr>
                                        <th>Level</th><th class="text-end">คนไข้</th>
                                        <th class="text-end">ชม./คน</th><th class="text-end">รวมชม.</th>
                                    </tr></thead>
                                    <tbody>${levelRows}</tbody>
                                    <tfoot class="table-light"><tr>
                                        <td colspan="3" class="fw-bold">Required Care Hours</td>
                                        <td class="text-end fw-bold">${decimalValue(sc.required)}</td>
                                    </tr></tfoot>
                                </table>
                            </div>
                            <div class="small text-muted mb-1">
                                Working Hours = (${sc.rn} RN + ${sc.tn} TN + ${sc.pn} PN) × 7 ชม.
                                = <strong>${decimalValue(sc.working)} ชม.</strong>
                            </div>
                            <div class="p-2 rounded text-center" style="background:#e8f5e9;">
                                <span class="text-muted small">Productivity เวรนี้ = </span>
                                <strong>${decimalValue(sc.required)} × 100 / ${decimalValue(sc.working)}</strong>
                                <span class="fs-5 fw-bold ms-2 ${sc.prod===null?'text-muted':sc.prod>=80?'text-success':sc.prod>=60?'text-warning':'text-danger'}">
                                    = ${shiftProdVal}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="fw-bold mb-1 small text-uppercase text-muted">
                                Productivity รายวัน (รวม Required Hours ทั้ง 3 เวร)
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-2">
                                    <thead class="table-light"><tr>
                                        <th>เวร</th><th class="text-end">คนไข้</th>
                                        <th class="text-end">Required ชม.</th>
                                    </tr></thead>
                                    <tbody>${dc.shifts_required.map(r=>`<tr${r.label===shift.shift_label?' class="table-warning"':''}><td>เวร${escapeHtml(r.label)}</td><td class="text-end">${r.patients}</td><td class="text-end fw-semibold">${decimalValue(r.required)}</td></tr>`).join('')}</tbody>
                                    <tfoot class="table-light"><tr>
                                        <td colspan="2" class="fw-bold">รวม Required Care Hours</td>
                                        <td class="text-end fw-bold">${decimalValue(dc.required)}</td>
                                    </tr></tfoot>
                                </table>
                            </div>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light"><tr>
                                        <th>เวร</th><th class="text-end">RN</th><th class="text-end">TN</th>
                                        <th class="text-end">PN</th><th class="text-end">รวมคน</th><th class="text-end">ชม.</th>
                                    </tr></thead>
                                    <tbody>${staffRows}</tbody>
                                    <tfoot class="table-light"><tr>
                                        <td class="fw-bold">รวม Working Hours</td>
                                        <td colspan="4"></td>
                                        <td class="text-end fw-bold">${decimalValue(dc.total_working)}</td>
                                    </tr></tfoot>
                                </table>
                            </div>
                            <div class="p-2 rounded text-center" style="background:#e8f5e9;">
                                <span class="text-muted small">Productivity รายวัน = </span>
                                <strong>${decimalValue(dc.required)} × 100 / ${decimalValue(dc.total_working)}</strong>
                                <span class="fs-5 fw-bold ms-2 ${dc.daily_prod===null?'text-muted':dc.daily_prod>=80?'text-success':dc.daily_prod>=60?'text-warning':'text-danger'}">
                                    = ${dailyProdVal}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function showShiftDetail(shift) {
            const updatedAt = shift.updated_at || '-';

            $('#shiftDetailModalLabel').text(`รายละเอียด ${shift.shift_label}`);
            $('#shiftDetailSubtitle').text(`วันที่ ${shift.record_date_label || shift.record_date || '-'} · ผู้บันทึก ${shift.recorder_username || '-'}`);
            $('#shiftDetailBody').html(`
                <div class="history-detail-grid mb-3">
                    ${detailCard('ผู้ป่วยรวม', numberValue(shift.total_patients))}
                    ${detailCard('ยอดยกมา', numberValue(shift.carried_forward_patients))}
                    ${detailCard('คาดการณ์จาก movement', numberValue(shift.movement_expected_patients))}
                    ${detailCard('Variance', numberValue(shift.movement_variance))}
                    ${detailCard('แก้ไขล่าสุด', updatedAt)}
                </div>

                ${detailTable('ผู้ป่วยตามระดับความรุนแรง', [
                    ['Level 5', shift.patients_level_5],
                    ['Level 4', shift.patients_level_4],
                    ['Level 3', shift.patients_level_3],
                    ['Level 2', shift.patients_level_2],
                    ['Level 1', shift.patients_level_1],
                ])}

                <div class="row g-3">
                    <div class="col-lg-6">
                        ${detailTable('ผู้ป่วยสามัญ', [
                            ['Level 5', shift.patients_general_level_5],
                            ['Level 4', shift.patients_general_level_4],
                            ['Level 3', shift.patients_general_level_3],
                            ['Level 2', shift.patients_general_level_2],
                            ['Level 1', shift.patients_general_level_1],
                        ])}
                    </div>
                    <div class="col-lg-6">
                        ${detailTable('ผู้ป่วยพิเศษ', [
                            ['Level 5', shift.patients_special_level_5],
                            ['Level 4', shift.patients_special_level_4],
                            ['Level 3', shift.patients_special_level_3],
                            ['Level 2', shift.patients_special_level_2],
                            ['Level 1', shift.patients_special_level_1],
                        ])}
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-4">
                        ${detailTable('Movement', [
                            ['รับใหม่', shift.admissions],
                            ['จำหน่าย', shift.discharges],
                            ['ย้ายเข้า', shift.transfers_in],
                            ['ย้ายออก', shift.transfers_out],
                            ['เสียชีวิต', shift.deaths],
                        ])}
                    </div>
                    <div class="col-lg-4">
                        ${detailTable('บุคลากร', [
                            ['HW', shift.nurses_hw],
                            ['RN', shift.nurses_rn],
                            ['TN', shift.nurses_tn],
                            ['PN', shift.nurses_pn],
                            ['Aide', shift.nurses_aide],
                            ['Ward', shift.nurses_ward],
                        ])}
                    </div>
                    <div class="col-lg-4">
                        ${detailTable('อุปกรณ์', [
                            ['เครื่องช่วยหายใจ', shift.equipment_ventilator],
                            ['High Flow', shift.equipment_hfnc],
                        ])}
                    </div>
                </div>
            `);

            const modalEl = document.getElementById('shiftDetailModal');
            document.body.appendChild(modalEl);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        function renderMonth(payload) {
            const rows = payload.days.map((day, di) => renderDayGroup(day, di === 0)).join('');

            $('#history-title').text('รายเดือน: แสดงรายวันแยก 3 เวร');
            $('#history-subtitle').text(`${payload.month}/${payload.year}`);
            $('#history-result').html(`
                <div class="table-responsive">
                    <table class="table history-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:90px;">วันที่</th>
                                <th style="width:90px;">เวร</th>
                                <th class="text-end">คงเหลือ</th>
                                <th class="text-end">รับใหม่</th>
                                <th class="text-end">จำหน่าย</th>
                                <th class="text-end">ย้ายเข้า</th>
                                <th class="text-end">ย้ายออก</th>
                                <th class="text-end">เสียชีวิต</th>
                                <th class="text-end">RN+TN+PN</th>
                                <th class="pe-3">บันทึกโดย</th>
                                <th class="text-center border-start" id="prod-col-header" style="min-width:110px;cursor:pointer;">
                                    Productivity<br>
                                    <small class="fw-normal text-muted">(รายวัน)</small>
                                    <span class="badge bg-primary ms-1" style="font-size:.65rem;vertical-align:middle;">?</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `);
        }

        function initProdHeaderPopover() {
            const el = document.getElementById('prod-col-header');
            if (!el) return;
            const existing = bootstrap.Popover.getInstance(el);
            if (existing) existing.dispose();

            const content = `
                <div style="min-width:340px;font-size:.82rem;">
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light"><tr>
                            <th>Level</th><th class="text-center">คนไข้ประเภท</th><th class="text-end">ชม./คน/เวร</th>
                        </tr></thead>
                        <tbody>
                            <tr><td>5</td><td>หนักมาก</td><td class="text-end fw-bold">4.0</td></tr>
                            <tr><td>4</td><td>หนัก</td><td class="text-end fw-bold">3.5</td></tr>
                            <tr><td>3</td><td>ปานกลาง</td><td class="text-end fw-bold">3.0</td></tr>
                            <tr><td>2</td><td>เบา</td><td class="text-end fw-bold">2.5</td></tr>
                            <tr><td>1</td><td>เบามาก</td><td class="text-end fw-bold">2.0</td></tr>
                        </tbody>
                    </table>

                    <div class="fw-bold text-primary mb-1">Productivity เวรนี้ (ตัวเลขใต้ RN+TN+PN)</div>
                    <div class="p-2 rounded mb-3" style="background:#f0f9ff;border:1px solid #bae6fd;">
                        Required = Σ (Level Hours × คนไข้ Level นั้น)<br>
                        Working = (RN+TN+PN) × 7 ชม.<br>
                        <strong>Productivity = Required × 100 / Working</strong>
                    </div>

                    <div class="fw-bold text-success mb-1">Productivity รายวัน (Merged Column)</div>
                    <div class="p-2 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        Required_รวม = Σ Required ทั้ง 3 เวร<br>
                        Working_รวม = Σ (RN+TN+PN)×7 ทั้ง 3 เวร<br>
                        <strong>Productivity = Required_รวม × 100 / Working_รวม</strong>
                    </div>
                    <div class="mt-2 text-muted" style="font-size:.75rem;">
                        🟢 ≥80%&nbsp;&nbsp;🟡 60–79%&nbsp;&nbsp;🔴 &lt;60%
                    </div>
                </div>`;

            new bootstrap.Popover(el, {
                html: true,
                trigger: 'click',
                placement: 'left',
                title: '<strong>วิธีการคำนวณ Productivity</strong>',
                content: content,
                sanitize: false,
            });
        }

        function loadHistory() {
            const formData = $('#history-filter').serializeArray();

            $('#history-alert').addClass('d-none').text('');
            $('#history-loading').removeClass('d-none');
            $('#history-result').empty();

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: formData,
                dataType: 'json'
            }).done(function(payload) {
                updateSummary(payload.summary || {});
                renderMonth(payload);
                initPopovers();
                initProdHeaderPopover();
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ';
                $('#history-alert').removeClass('d-none').text(message);
                updateSummary({});
            }).always(function() {
                $('#history-loading').addClass('d-none');
            });
        }

        function showDailyProdModal(dayKey) {
            const dc = dayCalcs[dayKey];
            if (!dc) return;

            const dailyProdVal = dc.daily_prod !== null ? `${decimalValue(dc.daily_prod)}%` : '-';
            const prodCls = dc.daily_prod === null ? 'text-muted'
                          : dc.daily_prod >= 80    ? 'text-success'
                          : dc.daily_prod >= 60    ? 'text-warning' : 'text-danger';

            const reqRows = dc.shifts_required.map(r => {
                const levelRows = r.levels.filter(lv => lv.pts > 0).map(lv =>
                    `<tr class="text-muted" style="font-size:.8rem;">
                        <td class="ps-3"><span class="badge bg-secondary">L${lv.level}</span></td>
                        <td class="text-end">${lv.pts}</td>
                        <td class="text-end">${lv.h_per.toFixed(1)}</td>
                        <td class="text-end">${decimalValue(lv.total)}</td>
                    </tr>`
                ).join('');
                return `
                    <tr class="fw-semibold table-light">
                        <td>เวร${escapeHtml(r.label)}</td>
                        <td class="text-end">${r.patients}</td>
                        <td class="text-end">—</td>
                        <td class="text-end">${decimalValue(r.required)}</td>
                    </tr>
                    ${levelRows}`;
            }).join('');

            const staffRows = dc.shifts_staff.map(r =>
                `<tr>
                    <td>เวร${escapeHtml(r.label)}</td>
                    <td class="text-end">${r.rn}</td>
                    <td class="text-end">${r.tn}</td>
                    <td class="text-end">${r.pn}</td>
                    <td class="text-end fw-semibold">${r.people}</td>
                    <td class="text-end fw-semibold">${r.hours.toFixed(1)}</td>
                </tr>`
            ).join('');

            const body = `
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body p-3">
                                <div class="fw-bold mb-2 small text-uppercase text-muted">Required Care Hours (รวม 3 เวร)</div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light"><tr>
                                            <th>เวร / Level</th>
                                            <th class="text-end">คนไข้</th>
                                            <th class="text-end">ชม./คน</th>
                                            <th class="text-end">รวมชม.</th>
                                        </tr></thead>
                                        <tbody>${reqRows}</tbody>
                                        <tfoot class="table-primary"><tr>
                                            <td colspan="3" class="fw-bold">รวม Required Care Hours</td>
                                            <td class="text-end fw-bold">${decimalValue(dc.required)}</td>
                                        </tr></tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body p-3">
                                <div class="fw-bold mb-2 small text-uppercase text-muted">Working Hours (รวม 3 เวร)</div>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light"><tr>
                                            <th>เวร</th>
                                            <th class="text-end">RN</th>
                                            <th class="text-end">TN</th>
                                            <th class="text-end">PN</th>
                                            <th class="text-end">รวมคน</th>
                                            <th class="text-end">ชม.</th>
                                        </tr></thead>
                                        <tbody>${staffRows}</tbody>
                                        <tfoot class="table-primary"><tr>
                                            <td class="fw-bold">รวม Working Hours</td>
                                            <td colspan="4"></td>
                                            <td class="text-end fw-bold">${decimalValue(dc.total_working)}</td>
                                        </tr></tfoot>
                                    </table>
                                </div>
                                <div class="p-3 rounded text-center" style="background:#e8f5e9;border:1px solid #a5d6a7;">
                                    <div class="text-muted small mb-1">Productivity รายวัน</div>
                                    <div class="small mb-2">
                                        <strong>${decimalValue(dc.required)}</strong>
                                        <span class="text-muted"> × 100 / </span>
                                        <strong>${decimalValue(dc.total_working)}</strong>
                                    </div>
                                    <div class="fs-3 fw-bold ${prodCls}">${dailyProdVal}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            $('#dailyProdModalLabel').text(`Productivity รายวัน — ${dc.date_label}`);
            $('#dailyProdBody').html(body);
            const dailyModalEl = document.getElementById('dailyProdModal');
            document.body.appendChild(dailyModalEl);
            bootstrap.Modal.getOrCreateInstance(dailyModalEl).show();
        }

        $('#history-filter').on('submit', function(event) {
            event.preventDefault();
            loadHistory();
        });
        $('#history-result').on('click', 'td.prod-day-cell', function(e) {
            e.stopImmediatePropagation();
            showDailyProdModal($(this).data('day-key'));
        });
        $('#history-result').on('click', 'tr[data-shift-key]', function() {
            const shift = shiftDetails[$(this).data('shift-key')];
            if (shift) showShiftDetail(shift);
        });

        if ($('#ward_id').val() || $('#ward_id:disabled').val()) {
            loadHistory();
        }
    });
</script>
<?= $this->endSection() ?>
