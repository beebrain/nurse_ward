<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
];
?>
<style>
    .daily-kpi {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        background: #fbfcfe;
    }

    .daily-kpi-label {
        color: #64748b;
        font-size: .84rem;
        font-weight: 700;
    }

    .daily-kpi-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1e3a8a;
    }

    .daily-table th,
    .daily-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .daily-table-scroll {
        max-height: calc(100vh - 22rem);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }

    .daily-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
        box-shadow: 0 1px 0 #dee2e6;
    }

    .daily-table thead th.ward-col {
        min-width: 72px;
        text-align: end;
        font-size: 0.82rem;
    }

    .daily-table tbody td.ward-cell {
        text-align: end;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .daily-none { color: #94a3b8; font-weight: 400; }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">table_chart</span>
                <div>
                    <h1 class="mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">รับใหม่ · จำหน่าย · ย้าย · เสียชีวิต · วันนอน — ทุกแผนกรายวัน</div>
                </div>
            </div>
            <a href="<?= base_url('reports/dashboard') ?>" class="btn btn-outline-secondary align-self-start align-self-xl-end">
                <span class="material-symbols-outlined me-1" style="font-size:1.1rem;">dashboard</span>
                แดชบอร์ด (เปรียบเทียบรายเดือน)
            </a>
        </div>

        <div class="alert alert-info">
            <span class="material-symbols-outlined align-middle me-1">info</span>
            หน้านี้แสดง<strong>การเคลื่อนไหวผู้ป่วย</strong>รายวัน — ต่างจาก Productivity (ชั่วโมงการพยาบาล) และแดชบอร์ด (สรุป/เปรียบเทียบรายเดือน)
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="daily-filter" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="metric" class="form-label fw-bold">ตัวชี้วัด</label>
                        <select name="metric" id="metric" class="form-select">
                            <option value="patient_days">วันนอนผู้ป่วย</option>
                            <option value="admissions">รับใหม่</option>
                            <option value="discharges">จำหน่าย</option>
                            <option value="transfers_in">ย้ายเข้า</option>
                            <option value="transfers_out">ย้ายออก</option>
                            <option value="deaths">เสียชีวิต</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="month" class="form-label fw-bold">เดือน</label>
                        <select name="month" id="month" class="form-select">
                            <?php foreach ($months as $monthNo => $monthName): ?>
                                <option value="<?= $monthNo ?>" <?= $monthNo === $current_month ? 'selected' : '' ?>><?= esc($monthName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year" class="form-label fw-bold">ปี</label>
                        <select name="year" id="year" class="form-select">
                            <?php for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $current_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <span class="material-symbols-outlined me-1" style="font-size:1rem;">search</span>
                            แสดง
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($wards)): ?>
            <div class="alert alert-warning">ยังไม่มีแผนกที่เปิดใช้งาน</div>
        <?php endif; ?>

        <div id="daily-alert" class="alert alert-danger d-none"></div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="daily-kpi p-3 h-100">
                    <div class="daily-kpi-label" id="kpi-metric-label">รวมทั้งเดือน</div>
                    <div class="daily-kpi-value" id="kpi-total">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="daily-kpi p-3 h-100">
                    <div class="daily-kpi-label">วันนอนผู้ป่วย (รวม)</div>
                    <div class="daily-kpi-value" id="kpi-patient-days">0</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="daily-kpi p-3 h-100">
                    <div class="daily-kpi-label">รับใหม่ / จำหน่าย (รวม)</div>
                    <div class="daily-kpi-value" id="kpi-adm-dis" style="font-size:1.35rem;">0 / 0</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0" id="daily-title">สรุปรายวัน</h5>
                <span class="text-muted small" id="daily-subtitle"></span>
            </div>
            <div class="card-body p-0">
                <div id="daily-loading" class="text-center text-muted py-5 d-none">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    กำลังโหลดข้อมูล...
                </div>
                <div id="daily-result"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        const endpoint = '<?= base_url('reports/daily-summary-data') ?>';

        const METRIC_LABELS = {
            patient_days: 'วันนอนผู้ป่วย',
            admissions: 'รับใหม่',
            discharges: 'จำหน่าย',
            transfers_in: 'ย้ายเข้า',
            transfers_out: 'ย้ายออก',
            deaths: 'เสียชีวิต',
        };

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

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
            $('#kpi-patient-days').text(numberValue(summary.patient_days));
            $('#kpi-adm-dis').text(`${numberValue(summary.admissions)} / ${numberValue(summary.discharges)}`);
        }

        function renderTable(payload) {
            const metric = currentMetric();
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
                    ${wards.map(w => wardCell(day, w, metric)).join('')}
                    <td class="pe-3"></td>
                </tr>
            `).join('');

            const colSpan = wards.length + 2;
            const monthNames = <?= json_encode($months, JSON_UNESCAPED_UNICODE) ?>;

            $('#daily-title').text(`${METRIC_LABELS[metric] || metric} — ทุกแผนก (รายวัน)`);
            $('#daily-subtitle').text(`${monthNames[payload.month] || payload.month} ${payload.year} · ${wards.length} แผนก`);
            $('#daily-result').html(`
                <div class="daily-table-scroll table-responsive">
                    <table class="table daily-table table-hover table-sm mb-0">
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

        function loadDaily() {
            const data = {
                month: $('#month').val(),
                year: $('#year').val(),
            };

            $('#daily-alert').addClass('d-none').text('');
            $('#daily-loading').removeClass('d-none');
            $('#daily-result').empty();

            $.ajax({
                url: endpoint,
                method: 'GET',
                data: data,
                dataType: 'json',
            }).done(function(payload) {
                updateKpis(payload);
                renderTable(payload);
            }).fail(function(xhr) {
                const message = xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ';
                $('#daily-alert').removeClass('d-none').text(message);
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
            if ($('#daily-result').children().length) {
                loadDaily();
            }
        });

        <?php if (! empty($wards)): ?>
        loadDaily();
        <?php endif; ?>
    });
</script>
<?= $this->endSection() ?>
