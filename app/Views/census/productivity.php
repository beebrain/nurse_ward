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
        white-space: nowrap;
    }

    .productivity-table-scroll {
        max-height: calc(100vh - 22rem);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }

    .productivity-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
        box-shadow: 0 1px 0 #dee2e6;
    }

    .productivity-table thead th.ward-col {
        min-width: 88px;
        text-align: end;
        font-size: 0.82rem;
    }

    .productivity-table tbody td.ward-cell {
        text-align: end;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .prod-good { color: #0c7521; }
    .prod-warn { color: #b45309; }
    .prod-low  { color: #93000a; }
    .prod-none { color: #94a3b8; font-weight: 400; }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">monitoring</span>
                <div>
                    <h1 class="mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">ประสิทธิภาพการพยาบาล (ชั่วโมง) ทุกแผนก — ต่างจากสรุปรายวันและแดชบอร์ด</div>
                </div>
            </div>
            <a href="<?= base_url('census/history') ?>" class="btn btn-outline-secondary align-self-start align-self-xl-end">
                <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">history</span>
                ดู Raw ประวัติย้อนหลัง
            </a>
        </div>

        <div class="alert alert-info">
            <span class="material-symbols-outlined align-middle me-1">info</span>
            Productivity = Required Care Hours ÷ Working Hours × 100 (รวม 3 เวรต่อวัน) · Patient Days ใช้ค่าเดียวต่อวัน (เวรดึก → บ่าย → เช้า)
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="productivity-filter" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="mode" class="form-label fw-bold">รูปแบบรายงาน</label>
                        <select name="mode" id="mode" class="form-select">
                            <option value="month">รายเดือน: ทุกแผนกรายวัน</option>
                            <option value="year">รายปี: ทุกแผนกรายเดือน</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="month-filter-wrap">
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
                    <div class="productivity-kpi-label">Productivity รวม</div>
                    <div class="productivity-kpi-value" id="kpi-productivity">-</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="productivity-kpi p-3 h-100">
                    <div class="productivity-kpi-label">Patient Days รวม</div>
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
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
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
            return value === null || value === undefined ? '-' : `${numberValue(value, 1)}%`;
        }

        function prodClass(value) {
            if (value === null || value === undefined) return 'prod-none';
            const n = Number(value);
            if (n >= 80) return 'prod-good';
            if (n >= 60) return 'prod-warn';
            return 'prod-low';
        }

        function wardCell(day, ward) {
            const cell = day.by_ward?.[String(ward.id)];
            if (!cell || cell.productivity === null || cell.productivity === undefined) {
                return `<td class="ward-cell prod-none" title="ไม่มีข้อมูล">—</td>`;
            }
            const title = `Patient Days: ${cell.patient_days ?? 0} · Req: ${cell.required_care_hours ?? 0}h · Work: ${cell.working_hours ?? 0}h`;
            return `<td class="ward-cell ${prodClass(cell.productivity)}" title="${escapeHtml(title)}">${productivityValue(cell.productivity)}</td>`;
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
            const wards = payload.wards || [];
            const wardHeaders = wards.map(w => `
                <th class="ward-col" title="${escapeHtml(w.label)}">${escapeHtml(w.name)}</th>
            `).join('');

            const rows = (payload.days || []).map(day => `
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">${escapeHtml(day.day_label)}</div>
                        <div class="text-muted small">${escapeHtml(day.weekday_label)}</div>
                    </td>
                    ${wards.map(w => wardCell(day, w)).join('')}
                    <td class="pe-3"></td>
                </tr>
            `).join('');

            const colSpan = wards.length + 2;

            $('#productivity-title').text('รายเดือน: Productivity ทุกแผนก (รายวัน)');
            $('#productivity-subtitle').text(`${payload.month}/${payload.year} · ${wards.length} แผนก`);
            $('#productivity-result').html(`
                <div class="productivity-table-scroll table-responsive">
                    <table class="table productivity-table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="min-width:100px;">วันที่</th>
                                ${wardHeaders}
                                <th class="pe-3"></th>
                            </tr>
                        </thead>
                        <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
                    </table>
                </div>
            `);
        }

        function renderYear(payload) {
            const wards = payload.wards || [];
            const wardHeaders = wards.map(w => `
                <th class="ward-col" title="${escapeHtml(w.label)}">${escapeHtml(w.name)}</th>
            `).join('');

            const rows = (payload.months || []).map(month => `
                <tr>
                    <td class="ps-3 fw-semibold">${escapeHtml(month.month_label)}</td>
                    ${wards.map(w => {
                        const cell = month.by_ward?.[String(w.id)];
                        if (!cell || cell.productivity === null || cell.productivity === undefined) {
                            return '<td class="ward-cell prod-none">—</td>';
                        }
                        return `<td class="ward-cell ${prodClass(cell.productivity)}" title="วันที่มีข้อมูล: ${cell.recorded_days ?? 0}">${productivityValue(cell.productivity)}</td>`;
                    }).join('')}
                    <td class="pe-3"></td>
                </tr>
            `).join('');

            const colSpan = wards.length + 2;

            $('#productivity-title').text('รายปี: Productivity ทุกแผนก (รายเดือน)');
            $('#productivity-subtitle').text(`ปี ${payload.year} · ${wards.length} แผนก`);
            $('#productivity-result').html(`
                <div class="productivity-table-scroll table-responsive">
                    <table class="table productivity-table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">เดือน</th>
                                ${wardHeaders}
                                <th class="pe-3"></th>
                            </tr>
                        </thead>
                        <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
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
        <?php if (! empty($wards)): ?>
        loadProductivity();
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
