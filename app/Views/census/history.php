<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .history-shift-card {
        border-radius: 10px;
        padding: .75rem;
        min-width: 180px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: transform .12s ease, box-shadow .12s ease;
    }

    .history-shift-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .12);
    }

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

    .history-shift-night {
        background: #eef2ff;
        border-color: #6366f1;
        color: #312e81;
    }

    .history-shift-morning {
        background: #ecfdf5;
        border-color: #10b981;
        color: #065f46;
    }

    .history-shift-afternoon {
        background: #fff7ed;
        border-color: #f97316;
        color: #9a3412;
    }

    .history-empty-shift {
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: .75rem;
        min-width: 180px;
        color: #94a3b8;
        background: #f8fafc;
    }

    .history-metric {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        font-size: .82rem;
        line-height: 1.35;
    }

    .history-metric strong {
        font-size: 1rem;
    }

    .history-table th,
    .history-table td {
        vertical-align: middle;
    }

    #shiftDetailModal {
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
                    <h5 class="modal-title" id="shiftDetailModalLabel">รายละเอียดกะ</h5>
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

<script>
    $(function() {
        const endpoint = '<?= base_url('census/history-data') ?>';
        const shiftDetails = {};
        const shiftClasses = {
            Night: 'history-shift-night',
            Morning: 'history-shift-morning',
            Afternoon: 'history-shift-afternoon'
        };

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

        function renderShiftCard(shift) {
            if (!shift) {
                return '<div class="history-empty-shift text-center">ยังไม่บันทึก</div>';
            }

            const productivity = shift.productivity === null ? '-' : `${numberValue(shift.productivity)}%`;
            const staff = Number(shift.nurses_rn || 0) + Number(shift.nurses_tn || 0) + Number(shift.nurses_pn || 0);
            const detailKey = `shift-${shift.id}`;
            shiftDetails[detailKey] = shift;

            return `
                <div class="history-shift-card ${shiftClasses[shift.shift] || ''}" data-shift-key="${detailKey}" role="button" tabindex="0" title="คลิกเพื่อดูรายละเอียด">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>${escapeHtml(shift.shift_label)}</strong>
                        <span class="badge bg-light text-dark">รวม ${numberValue(shift.total_patients)}</span>
                    </div>
                    <div class="history-metric"><span>รับใหม่</span><strong>${numberValue(shift.admissions)}</strong></div>
                    <div class="history-metric"><span>จำหน่าย/ย้ายออก</span><strong>${numberValue(Number(shift.discharges || 0) + Number(shift.transfers_out || 0))}</strong></div>
                    <div class="history-metric"><span>ย้ายเข้า</span><strong>${numberValue(shift.transfers_in)}</strong></div>
                    <div class="history-metric"><span>เสียชีวิต</span><strong>${numberValue(shift.deaths)}</strong></div>
                    <hr class="my-2">
                    <div class="small">RN+TN+PN: ${numberValue(staff)} คน</div>
                    <div class="small">Productivity: ${productivity}</div>
                    <div class="small fw-semibold mt-1">คลิกเพื่อดูรายละเอียด</div>
                    <div class="small text-muted mt-1">ผู้บันทึก: ${escapeHtml(shift.recorder_username)}</div>
                </div>
            `;
        }

        function showShiftDetail(shift) {
            const productivity = shift.productivity === null ? '-' : `${decimalValue(shift.productivity)}%`;
            const updatedAt = shift.updated_at || '-';

            $('#shiftDetailModalLabel').text(`รายละเอียด ${shift.shift_label}`);
            $('#shiftDetailSubtitle').text(`วันที่ ${shift.record_date_label || shift.record_date || '-'} · ผู้บันทึก ${shift.recorder_username || '-'}`);
            $('#shiftDetailBody').html(`
                <div class="history-detail-grid mb-3">
                    ${detailCard('ผู้ป่วยรวม', numberValue(shift.total_patients))}
                    ${detailCard('ยอดยกมา', numberValue(shift.carried_forward_patients))}
                    ${detailCard('คาดการณ์จาก movement', numberValue(shift.movement_expected_patients))}
                    ${detailCard('Variance', numberValue(shift.movement_variance))}
                    ${detailCard('Working Hours', decimalValue(shift.working_hours))}
                    ${detailCard('Required Hours', decimalValue(shift.required_care_hours))}
                    ${detailCard('Productivity', productivity)}
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
            const rows = payload.days.map(day => `
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">${escapeHtml(day.day_label)}</div>
                        <div class="text-muted small">${escapeHtml(day.weekday_label)}</div>
                    </td>
                    <td>${renderShiftCard(day.shifts.Night)}</td>
                    <td>${renderShiftCard(day.shifts.Morning)}</td>
                    <td class="pe-3">${renderShiftCard(day.shifts.Afternoon)}</td>
                </tr>
            `).join('');

            $('#history-title').text('รายเดือน: แสดงรายวันแยก 3 กะ');
            $('#history-subtitle').text(`${payload.month}/${payload.year}`);
            $('#history-result').html(`
                <div class="table-responsive">
                    <table class="table history-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:140px;">วันที่</th>
                                <th>กะดึก</th>
                                <th>กะเช้า</th>
                                <th class="pe-3">กะบ่าย</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `);
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
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ';
                $('#history-alert').removeClass('d-none').text(message);
                updateSummary({});
            }).always(function() {
                $('#history-loading').addClass('d-none');
            });
        }

        $('#history-filter').on('submit', function(event) {
            event.preventDefault();
            loadHistory();
        });
        $('#history-result').on('click keydown', '.history-shift-card', function(event) {
            if (event.type === 'keydown' && !['Enter', ' '].includes(event.key)) {
                return;
            }
            event.preventDefault();
            const shift = shiftDetails[$(this).data('shift-key')];
            if (shift) {
                showShiftDetail(shift);
            }
        });

        if ($('#ward_id').val() || $('#ward_id:disabled').val()) {
            loadHistory();
        }
    });
</script>
<?= $this->endSection() ?>
