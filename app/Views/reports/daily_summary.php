<?= $this->extend('layout/main') ?>

<?= $this->section('layout_class') ?>layout-fluid<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$months = [
    1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
    5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
    9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
];
$monthsFull = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
];
?>
<?= $this->include('partials/ward_matrix_styles') ?>
<style>
    .daily-none { color: #94a3b8; font-weight: 400; }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.75rem;">table_chart</span>
                <div>
                    <h1 class="h3 mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">การเคลื่อนไหวและจำนวนผู้ป่วย ทุกแผนกรายวัน</div>
                </div>
            </div>
            <a href="<?= base_url('reports/dashboard') ?>" class="btn btn-sm btn-outline-secondary align-self-start">
                <span class="material-symbols-outlined me-1" style="font-size:1rem;">dashboard</span>
                แดชบอร์ด
            </a>
        </div>

        <div class="alert alert-info py-2 small mb-3">
            แสดง<strong>การเคลื่อนไหวผู้ป่วย</strong>และ<strong>จำนวนผู้ป่วยคงเหลือ</strong>รายวัน (เวรดึก → บ่าย → เช้า)
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body py-3">
                <form id="daily-filter" class="row g-2 align-items-end matrix-filter-actions">
                    <div class="col-12 col-md-4">
                        <label for="metric" class="form-label fw-bold small mb-1">ตัวชี้วัด</label>
                        <select name="metric" id="metric" class="form-select form-select-sm">
                            <option value="patient_days">จำนวนผู้ป่วย</option>
                            <option value="admissions">รับใหม่</option>
                            <option value="discharges">จำหน่าย</option>
                            <option value="transfers_in">ย้ายเข้า</option>
                            <option value="transfers_out">ย้ายออก</option>
                            <option value="deaths">เสียชีวิต</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="month" class="form-label fw-bold small mb-1">เดือน</label>
                        <select name="month" id="month" class="form-select form-select-sm">
                            <?php foreach ($months as $monthNo => $monthName): ?>
                                <option value="<?= $monthNo ?>" <?= $monthNo === $current_month ? 'selected' : '' ?>><?= esc($monthName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="year" class="form-label fw-bold small mb-1">ปี</label>
                        <select name="year" id="year" class="form-select form-select-sm">
                            <?php for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $current_year ? 'selected' : '' ?>><?= $y ?></option>
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
            <div class="alert alert-warning py-2 small">ยังไม่มีแผนกที่เปิดใช้งาน</div>
        <?php endif; ?>

        <div id="daily-alert" class="alert alert-danger d-none py-2 small"></div>

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-4">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label" id="kpi-metric-label">รวมทั้งเดือน</div>
                    <div class="matrix-page-kpi-value" id="kpi-total">0</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">จำนวนผู้ป่วย (สะสม)</div>
                    <div class="matrix-page-kpi-value" id="kpi-patient-count">0</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="matrix-page-kpi p-2 h-100">
                    <div class="matrix-page-kpi-label">รับใหม่ / จำหน่าย</div>
                    <div class="matrix-page-kpi-value" id="kpi-adm-dis" style="font-size:1.2rem;">0 / 0</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
                <h6 class="mb-0" id="daily-title">สรุปรายวัน</h6>
                <span class="text-muted small" id="daily-subtitle"></span>
            </div>
            <div class="card-body p-0">
                <div id="daily-loading" class="text-center text-muted py-4 d-none small">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    กำลังโหลด...
                </div>
                <div id="daily-result"></div>
            </div>
        </div>
    </div>
</div>

<?= $this->include('partials/ward_matrix_helpers') ?>
<script>
    $(function() {
        const WM = window.WardMatrix;
        const endpoint = '<?= base_url('reports/daily-summary-data') ?>';
        const monthNames = <?= json_encode($monthsFull, JSON_UNESCAPED_UNICODE) ?>;

        const METRIC_LABELS = {
            patient_days: 'จำนวนผู้ป่วย',
            admissions: 'รับใหม่',
            discharges: 'จำหน่าย',
            transfers_in: 'ย้ายเข้า',
            transfers_out: 'ย้ายออก',
            deaths: 'เสียชีวิต',
        };

        function numberValue(value) {
            return Number(value || 0).toLocaleString();
        }

        function currentMetric() {
            return $('#metric').val() || 'patient_days';
        }

        function wardCell(day, ward, metric) {
            const cell = day.by_ward?.[String(ward.id)];
            if (!cell) {
                return '<td class="ward-cell daily-none">—</td>';
            }
            const value = cell[metric];
            if (value === null || value === undefined) {
                return '<td class="ward-cell daily-none">—</td>';
            }
            return `<td class="ward-cell">${numberValue(value)}</td>`;
        }

        function updateKpis(payload) {
            const metric = currentMetric();
            const summary = payload.summary || {};
            $('#kpi-metric-label').text(`รวม ${METRIC_LABELS[metric] || metric}`);
            $('#kpi-total').text(numberValue(summary[metric]));
            $('#kpi-patient-count').text(numberValue(summary.patient_days));
            $('#kpi-adm-dis').text(`${numberValue(summary.admissions)} / ${numberValue(summary.discharges)}`);
        }

        function renderTable(payload) {
            const metric = currentMetric();
            const wards = payload.wards || [];
            const wardHeaders = wards.map(w => WM.headerTh(w)).join('');
            const rows = (payload.days || []).map(day => `
                <tr>
                    ${WM.dateCell(day)}
                    ${wards.map(w => wardCell(day, w, metric)).join('')}
                </tr>
            `).join('');

            const colSpan = wards.length + 1;
            $('#daily-title').text(`${METRIC_LABELS[metric] || metric} — ทุกแผนก`);
            $('#daily-subtitle').text(`${monthNames[payload.month] || payload.month} ${payload.year} · ${wards.length} แผนก · เลื่อนแนวนอน`);
            $('#daily-result').html(WM.wrapTable(`
                <thead class="table-light"><tr>
                    <th class="ward-matrix-date-col ps-2">วันที่</th>
                    ${wardHeaders}
                </tr></thead>
                <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>`}</tbody>
            `));
        }

        let dailyPayload = null;

        function loadDaily() {
            $('#daily-alert').addClass('d-none').text('');
            $('#daily-loading').removeClass('d-none');
            $('#daily-result').empty();

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: { month: $('#month').val(), year: $('#year').val() },
                dataType: 'json',
            }).done(function(payload) {
                dailyPayload = payload;
                updateKpis(payload);
                renderTable(payload);
            }).fail(function(xhr) {
                $('#daily-alert').removeClass('d-none').text(xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ');
                dailyPayload = null;
                updateKpis({});
            }).always(function() {
                $('#daily-loading').addClass('d-none');
            });
        }

        $('#daily-filter').on('submit', function(event) {
            event.preventDefault();
            loadDaily();
        });

        $('#metric').on('change', function() {
            if (dailyPayload) {
                updateKpis(dailyPayload);
                renderTable(dailyPayload);
            }
        });

        <?php if (! empty($wards)): ?>
        loadDaily();
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
