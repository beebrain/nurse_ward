<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .productivity-kpi {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        background: #fbfcfe;
    }

    .productivity-kpi-label {
        color: #64748b;
        font-size: .84rem;
        font-weight: 700;
    }

    .productivity-kpi-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1e3a8a;
    }

    .productivity-table th,
    .productivity-table td {
        vertical-align: middle;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">monitoring</span>
                <div>
                    <h1 class="mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">สรุป Productivity โดยใช้ข้อมูล 3 กะอย่างถูกต้อง ไม่รวม Patient Days ซ้ำ</div>
                </div>
            </div>
            <a href="<?= base_url('census/history') ?>" class="btn btn-outline-secondary align-self-start align-self-xl-end">
                <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">history</span>
                ดู Raw ประวัติย้อนหลัง
            </a>
        </div>

        <div class="alert alert-info">
            <span class="material-symbols-outlined align-middle me-1">info</span>
            Patient Days ใช้ค่าเดียวต่อวันตามลำดับ กะดึก → กะบ่าย → กะเช้า ส่วนรับใหม่/จำหน่าย/ย้าย/เสียชีวิต และชั่วโมงทำงาน รวมจากทั้ง 3 กะ
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="productivity-filter" class="row g-3 align-items-end">
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
                        <label for="mode" class="form-label fw-bold">รูปแบบรายงาน</label>
                        <select name="mode" id="mode" class="form-select">
                            <option value="month">รายเดือน: สรุปรายวัน</option>
                            <option value="year">รายปี: สรุปรายเดือน</option>
                        </select>
                    </div>

                    <div class="col-md-2" id="month-filter-wrap">
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

                    <div class="col-md-2">
                        <label for="year" class="form-label fw-bold">ปี</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
                                <option value="<?= $year ?>" <?= $year === $currentYear ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="material-symbols-outlined me-1" style="font-size:1rem;">calculate</span>
                            คำนวณ
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

        <div id="productivity-alert" class="alert alert-danger d-none"></div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="productivity-kpi p-3 h-100">
                    <div class="productivity-kpi-label">Productivity</div>
                    <div class="productivity-kpi-value" id="kpi-productivity">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="productivity-kpi p-3 h-100">
                    <div class="productivity-kpi-label">Patient Days</div>
                    <div class="productivity-kpi-value" id="kpi-patient-days">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="productivity-kpi p-3 h-100">
                    <div class="productivity-kpi-label">Required Care Hours</div>
                    <div class="productivity-kpi-value" id="kpi-required-hours">0</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="productivity-kpi p-3 h-100">
                    <div class="productivity-kpi-label">Working Hours</div>
                    <div class="productivity-kpi-value" id="kpi-working-hours">0</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="productivity-title">Productivity Summary</h5>
                <span class="text-muted small" id="productivity-subtitle"></span>
            </div>
            <div class="card-body p-0">
                <div id="productivity-loading" class="text-center text-muted py-5 d-none">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    กำลังโหลดข้อมูล...
                </div>
                <div id="productivity-result"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        const endpoint = '<?= base_url('census/productivity-data') ?>';
        const shiftLabels = { Night: 'กะดึก', Morning: 'กะเช้า', Afternoon: 'กะบ่าย' };

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function numberValue(value, digits = 0) {
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: digits,
                maximumFractionDigits: digits
            });
        }

        function productivityValue(value) {
            return value === null || value === undefined ? '-' : `${numberValue(value, 2)}%`;
        }

        function updateModeVisibility() {
            $('#month-filter-wrap').toggle($('#mode').val() === 'month');
        }

        function updateKpis(summary) {
            $('#kpi-productivity').text(productivityValue(summary?.productivity));
            $('#kpi-patient-days').text(numberValue(summary?.patient_days));
            $('#kpi-required-hours').text(numberValue(summary?.required_care_hours, 1));
            $('#kpi-working-hours').text(numberValue(summary?.working_hours, 1));
        }

        function renderMonth(payload) {
            const rows = payload.days.map(day => `
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">${escapeHtml(day.day_label)}</div>
                        <div class="text-muted small">${escapeHtml(day.weekday_label)}</div>
                    </td>
                    <td class="text-end">${numberValue(day.recorded_shifts)}</td>
                    <td class="text-end">${numberValue(day.patient_days)}</td>
                    <td>${day.patient_day_shift ? escapeHtml(shiftLabels[day.patient_day_shift] || day.patient_day_shift) : '-'}</td>
                    <td>${day.care_shift ? escapeHtml(shiftLabels[day.care_shift] || day.care_shift) : '-'}</td>
                    <td class="text-end">${numberValue(day.required_care_hours, 1)}</td>
                    <td class="text-end">${numberValue(day.working_hours, 1)}</td>
                    <td class="text-end fw-semibold">${productivityValue(day.productivity)}</td>
                    <td class="text-end">${numberValue(day.admissions)}</td>
                    <td class="text-end">${numberValue(day.discharges)}</td>
                    <td class="text-end pe-3">${numberValue(day.deaths)}</td>
                </tr>
            `).join('');

            $('#productivity-title').text('รายเดือน: Productivity รายวัน');
            $('#productivity-subtitle').text(`${payload.month}/${payload.year}`);
            $('#productivity-result').html(`
                <div class="table-responsive">
                    <table class="table productivity-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">วันที่</th>
                                <th class="text-end">กะที่บันทึก</th>
                                <th class="text-end">Patient Days</th>
                                <th>ใช้ยอดจาก</th>
                                <th>Care จาก</th>
                                <th class="text-end">Required Hours</th>
                                <th class="text-end">Working Hours</th>
                                <th class="text-end">Productivity</th>
                                <th class="text-end">รับใหม่</th>
                                <th class="text-end">จำหน่าย</th>
                                <th class="text-end pe-3">เสียชีวิต</th>
                            </tr>
                        </thead>
                        <tbody>${rows || '<tr><td colspan="11" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>'}</tbody>
                    </table>
                </div>
            `);
        }

        function renderYear(payload) {
            const rows = payload.months.map(month => `
                <tr>
                    <td class="ps-3 fw-semibold">${escapeHtml(month.month_label)}</td>
                    <td class="text-end">${numberValue(month.recorded_days)}</td>
                    <td class="text-end">${numberValue(month.recorded_shifts)}</td>
                    <td class="text-end">${numberValue(month.patient_days)}</td>
                    <td class="text-end">${numberValue(month.required_care_hours, 1)}</td>
                    <td class="text-end">${numberValue(month.working_hours, 1)}</td>
                    <td class="text-end fw-semibold">${productivityValue(month.productivity)}</td>
                    <td class="text-end">${numberValue(month.admissions)}</td>
                    <td class="text-end">${numberValue(month.discharges)}</td>
                    <td class="text-end">${numberValue(month.transfers_in)}</td>
                    <td class="text-end">${numberValue(month.transfers_out)}</td>
                    <td class="text-end pe-3">${numberValue(month.deaths)}</td>
                </tr>
            `).join('');

            $('#productivity-title').text('รายปี: Productivity รายเดือน');
            $('#productivity-subtitle').text(`ปี ${payload.year}`);
            $('#productivity-result').html(`
                <div class="table-responsive">
                    <table class="table productivity-table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">เดือน</th>
                                <th class="text-end">วันที่มีข้อมูล</th>
                                <th class="text-end">จำนวนกะ</th>
                                <th class="text-end">Patient Days</th>
                                <th class="text-end">Required Hours</th>
                                <th class="text-end">Working Hours</th>
                                <th class="text-end">Productivity</th>
                                <th class="text-end">รับใหม่</th>
                                <th class="text-end">จำหน่าย</th>
                                <th class="text-end">ย้ายเข้า</th>
                                <th class="text-end">ย้ายออก</th>
                                <th class="text-end pe-3">เสียชีวิต</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `);
        }

        function loadProductivity() {
            const formData = $('#productivity-filter').serializeArray();

            $('#productivity-alert').addClass('d-none').text('');
            $('#productivity-loading').removeClass('d-none');
            $('#productivity-result').empty();

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: formData,
                dataType: 'json'
            }).done(function(payload) {
                updateKpis(payload.summary || {});
                if (payload.mode === 'year') {
                    renderYear(payload);
                } else {
                    renderMonth(payload);
                }
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ';
                $('#productivity-alert').removeClass('d-none').text(message);
                updateKpis({});
            }).always(function() {
                $('#productivity-loading').addClass('d-none');
            });
        }

        $('#mode').on('change', updateModeVisibility);
        $('#productivity-filter').on('submit', function(event) {
            event.preventDefault();
            loadProductivity();
        });

        updateModeVisibility();
        if ($('#ward_id').val() || $('#ward_id:disabled').val()) {
            loadProductivity();
        }
    });
</script>
<?= $this->endSection() ?>
