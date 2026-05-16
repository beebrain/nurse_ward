<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?= $this->include('partials/ward_matrix_styles') ?>
<style>
    .prod-good { color: #0c7521; }
    .prod-warn { color: #b45309; }
    .prod-low  { color: #93000a; }
    .prod-none { color: #94a3b8; font-weight: 400; }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.75rem;">monitoring</span>
                <div>
                    <h1 class="h3 mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">ประสิทธิภาพการพยาบาล (ชั่วโมง) ทุกแผนก</div>
                </div>
            </div>
            <a href="<?= base_url('census/history') ?>" class="btn btn-sm btn-outline-secondary align-self-start">
                <span class="material-symbols-outlined me-1" style="font-size:1rem;">history</span>
                ประวัติย้อนหลัง
            </a>
        </div>

        <div class="alert alert-info py-2 small mb-3">
            Productivity = ชั่วโมงดูแลที่ต้องการ ÷ ชั่วโมงทำงาน × 100 · จำนวนผู้ป่วยใช้ค่าเดียวต่อวัน (เวรดึก → บ่าย → เช้า)
        </div>

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

        <div id="productivity-alert" class="alert alert-danger d-none py-2 small"></div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">Productivity รวม</div>
                    <div class="matrix-page-kpi-value" id="kpi-productivity">-</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">จำนวนผู้ป่วย (สะสม)</div>
                    <div class="matrix-page-kpi-value" id="kpi-patient-days">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">ชม.ดูแลที่ต้องการ</div>
                    <div class="matrix-page-kpi-value" id="kpi-required-hours">0</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">ชม.ทำงาน</div>
                    <div class="matrix-page-kpi-value" id="kpi-working-hours">0</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
                <h6 class="mb-0" id="productivity-title">Productivity</h6>
                <span class="text-muted small" id="productivity-subtitle"></span>
            </div>
            <div class="card-body p-0">
                <div id="productivity-loading" class="text-center text-muted py-4 d-none small">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>กำลังโหลด...
                </div>
                <div id="productivity-result"></div>
            </div>
        </div>
    </div>
</div>

<?= $this->include('partials/ward_matrix_helpers') ?>
<script>
    $(function() {
        const WM = window.WardMatrix;
        const endpoint = '<?= base_url('census/productivity-data') ?>';

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
                return '<td class="ward-cell prod-none" title="ไม่มีข้อมูล">—</td>';
            }
            const title = `จำนวนผู้ป่วย: ${cell.patient_days ?? 0} · ชม.ดูแล: ${cell.required_care_hours ?? 0} · ชม.ทำงาน: ${cell.working_hours ?? 0}`;
            return `<td class="ward-cell ${prodClass(cell.productivity)}" title="${WM.escapeHtml(title)}">${productivityValue(cell.productivity)}</td>`;
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
            const wardHeaders = wards.map(w => WM.headerTh(w)).join('');
            const rows = (payload.days || []).map(day => `
                <tr>
                    ${WM.dateCell(day)}
                    ${wards.map(w => wardCell(day, w)).join('')}
                </tr>
            `).join('');

            const colSpan = wards.length + 1;
            $('#productivity-title').text('Productivity ทุกแผนก (รายวัน)');
            $('#productivity-subtitle').text(`${payload.month}/${payload.year} · ${wards.length} แผนก · เลื่อนแนวนอน`);
            $('#productivity-result').html(WM.wrapTable(`
                <thead class="table-light"><tr>
                    <th class="ward-matrix-date-col ps-2">วันที่</th>
                    ${wardHeaders}
                </tr></thead>
                <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
            `));
        }

        function renderYear(payload) {
            const wards = payload.wards || [];
            const wardHeaders = wards.map(w => WM.headerTh(w)).join('');
            const rows = (payload.months || []).map(month => `
                <tr>
                    <td class="ward-matrix-date-col ps-2"><span class="date-main">${WM.escapeHtml(month.month_label)}</span></td>
                    ${wards.map(w => {
                        const cell = month.by_ward?.[String(w.id)];
                        if (!cell || cell.productivity === null || cell.productivity === undefined) {
                            return '<td class="ward-cell prod-none">—</td>';
                        }
                        return `<td class="ward-cell ${prodClass(cell.productivity)}" title="วันที่มีข้อมูล: ${cell.recorded_days ?? 0}">${productivityValue(cell.productivity)}</td>`;
                    }).join('')}
                </tr>
            `).join('');

            const colSpan = wards.length + 1;
            $('#productivity-title').text('Productivity ทุกแผนก (รายเดือน)');
            $('#productivity-subtitle').text(`ปี ${payload.year} · ${wards.length} แผนก · เลื่อนแนวนอน`);
            $('#productivity-result').html(WM.wrapTable(`
                <thead class="table-light"><tr>
                    <th class="ward-matrix-date-col ps-2">เดือน</th>
                    ${wardHeaders}
                </tr></thead>
                <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
            `));
        }

        function loadProductivity() {
            $('#productivity-alert').addClass('d-none').text('');
            $('#productivity-loading').removeClass('d-none');
            $('#productivity-result').empty();

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: $('#productivity-filter').serializeArray(),
                dataType: 'json'
            }).done(function(payload) {
                updateKpis(payload.summary || {});
                if (payload.mode === 'year') {
                    renderYear(payload);
                } else {
                    renderMonth(payload);
                }
            }).fail(function(xhr) {
                $('#productivity-alert').removeClass('d-none').text(xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ');
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
