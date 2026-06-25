<?= $this->extend('layout/main') ?>

<?= $this->section('layout_class') ?>layout-fluid<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="<?= asset_url('css/ward-matrix.css') ?>" rel="stylesheet">
<link href="<?= asset_url('css/reports-dashboard.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$thaiMonths = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
];
?>
<div class="dashboard-page">
    <div class="dashboard-page-header mb-3">
        <div class="d-flex align-items-start gap-2">
            <span class="material-symbols-outlined dashboard-page-icon" aria-hidden="true">dashboard</span>
            <div>
                <h1 class="dashboard-page-title mb-0"><?= esc($title) ?></h1>
                <p class="dashboard-page-subtitle mb-0">สรุปอัตราใช้เตียงและ Productivity การพยาบาล · เปรียบเทียบแผนกและแนวโน้ม</p>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs dashboard-view-tabs mb-3" id="dashboardTabs" role="tablist">
        <?php if ($isSuperAdmin): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-monthly-btn" data-bs-toggle="tab" data-bs-target="#tab-monthly" type="button" role="tab" aria-controls="tab-monthly" aria-selected="true">
                    ภาพรวมรายเดือน
                </button>
            </li>
        <?php endif; ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $isSuperAdmin ? '' : 'active' ?>" id="tab-snapshot-btn" data-bs-toggle="tab" data-bs-target="#tab-snapshot" type="button" role="tab" aria-controls="tab-snapshot" aria-selected="<?= $isSuperAdmin ? 'false' : 'true' ?>">
                สถานะปัจจุบัน
            </button>
        </li>
    </ul>

    <div class="tab-content" id="dashboardTabContent">
        <?php if ($isSuperAdmin): ?>
            <div class="tab-pane fade show active" id="tab-monthly" role="tabpanel" aria-labelledby="tab-monthly-btn" tabindex="0">
                <div class="dashboard-panel dashboard-panel--filter mb-3">
                    <form id="dashboard-filter" class="dashboard-filter-bar">
                        <div class="filter-field">
                            <label for="month" class="form-label small fw-bold mb-1">เดือน</label>
                            <select name="month" id="month" class="form-select form-select-sm" required>
                                <?php foreach ($thaiMonths as $m => $name): ?>
                                    <option value="<?= $m ?>" <?= $m === $current_month ? 'selected' : '' ?>><?= esc($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="year" class="form-label small fw-bold mb-1">ปี</label>
                            <select name="year" id="year" class="form-select form-select-sm" required>
                                <?php for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= $y === $current_year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <span class="material-symbols-outlined align-middle me-1" style="font-size:1rem;" aria-hidden="true">refresh</span>
                                แสดงข้อมูล
                            </button>
                        </div>
                    </form>
                </div>

                <div id="dashboard-monthly-alert" class="alert alert-danger d-none py-2 small" role="alert" aria-live="assertive"></div>

                <div id="dashboard-monthly-loading" class="dashboard-loading d-none" aria-live="polite">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"><span class="visually-hidden">กำลังโหลด</span></div>
                    <span class="text-muted small">กำลังโหลดข้อมูล...</span>
                </div>

                <div id="dashboard-monthly-body" class="d-none">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <span class="dashboard-period-badge" id="dashboard-period-badge" aria-live="polite"></span>
                        <button type="button"
                                class="dashboard-info-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#dashboardFormulaModal"
                                aria-label="วิธีคำนวณตัวชี้วัด">
                            <span class="material-symbols-outlined" aria-hidden="true">info</span>
                            <span>วิธีคำนวณ</span>
                        </button>
                    </div>

                    <div class="dashboard-kpi-grid mb-3" id="dashboard-kpis" aria-live="polite"></div>

                    <div class="dashboard-panel mb-3">
                        <h2 class="dashboard-section-title">เปรียบเทียบทุกแผนก</h2>
                        <p class="dashboard-section-desc">เรียงตามชื่อแผนก · เลื่อนแนวนอนถ้าจอแคบ</p>
                        <div class="dashboard-table-shell comparison-table-scroll">
                            <table class="table dashboard-table data-table-full mb-0" id="ward-comparison-table">
                                <thead>
                                    <tr>
                                        <th class="ps-3 col-ward">แผนก</th>
                                        <th class="col-num">เตียง</th>
                                        <th class="col-num">ผู้ป่วย<br><span class="th-hint">(สะสม)</span></th>
                                        <th class="col-num">ใช้เตียง</th>
                                        <th class="col-num pe-3">Productivity</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="dashboard-chart-row mb-3">
                        <div class="dashboard-panel">
                            <h2 class="dashboard-section-title">อัตราใช้เตียง แยกตามแผนก</h2>
                            <div class="dashboard-chart-wrap">
                                <canvas id="occupancyBarChart" aria-label="กราฟแท่งอัตราใช้เตียงแยกตามแผนก"></canvas>
                            </div>
                        </div>
                        <div class="dashboard-panel">
                            <h2 class="dashboard-section-title">Productivity การพยาบาล แยกตามแผนก</h2>
                            <div class="dashboard-chart-wrap">
                                <canvas id="nursingBarChart" aria-label="กราฟแท่ง Productivity การพยาบาลแยกตามแผนก"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-panel dashboard-panel--accordion mb-3">
                        <button class="dashboard-accordion-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dashboardTrendsCollapse" aria-expanded="false" aria-controls="dashboardTrendsCollapse">
                            <span class="material-symbols-outlined" aria-hidden="true">timeline</span>
                            <span>แนวโน้มรายปีและกลุ่มงาน</span>
                            <span class="material-symbols-outlined dashboard-accordion-chevron" aria-hidden="true">expand_more</span>
                        </button>
                        <div class="collapse" id="dashboardTrendsCollapse">
                            <div class="dashboard-accordion-body">
                                <div class="dashboard-chart-row mb-3">
                                    <div class="dashboard-panel dashboard-panel--nested">
                                        <h3 class="dashboard-section-title dashboard-section-title--sm">แนวโน้มอัตราใช้เตียง (รายปี)</h3>
                                        <div class="dashboard-chart-wrap">
                                            <canvas id="occupancyTrendChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="dashboard-panel dashboard-panel--nested">
                                        <h3 class="dashboard-section-title dashboard-section-title--sm">แนวโน้ม Productivity (รายปี)</h3>
                                        <div class="dashboard-chart-wrap">
                                            <canvas id="nursingTrendChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="dashboard-chart-row">
                                    <div class="dashboard-panel dashboard-panel--nested">
                                        <h3 class="dashboard-section-title dashboard-section-title--sm">จำนวนผู้ป่วยสะสม (รายปี)</h3>
                                        <div class="dashboard-chart-wrap">
                                            <canvas id="patientDaysTrendChart"></canvas>
                                        </div>
                                    </div>
                                    <div class="dashboard-panel dashboard-panel--nested">
                                        <h3 class="dashboard-section-title dashboard-section-title--sm">อัตราใช้เตียง แยกกลุ่มงาน</h3>
                                        <div class="dashboard-chart-wrap">
                                            <canvas id="deptChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-light border small mb-3" role="status">
                การเปรียบเทียบทุกแผนกใช้ได้เฉพาะ Super Admin — ด้านล่างเป็นสถานะล่าสุดของแผนกที่มีข้อมูล
            </div>
        <?php endif; ?>

        <div class="tab-pane fade <?= $isSuperAdmin ? '' : 'show active' ?>" id="tab-snapshot" role="tabpanel" aria-labelledby="tab-snapshot-btn" tabindex="0">
            <div class="dashboard-panel">
                <h2 class="dashboard-section-title d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined text-primary" aria-hidden="true">monitor_heart</span>
                    สถานะล่าสุดตามข้อมูลที่บันทึก
                </h2>
                <p class="dashboard-section-desc">อัตราใช้เตียง ณ ข้อมูล census ล่าสุดของแต่ละแผนก (ไม่ใช่สรุปรายเดือน)</p>

                <div class="dashboard-legend mb-2" aria-label="ความหมายสีการใช้เตียง">
                    <span class="dashboard-legend-item"><span class="dashboard-legend-dot dashboard-legend-dot--high" aria-hidden="true"></span> ≥95% สูงมาก</span>
                    <span class="dashboard-legend-item"><span class="dashboard-legend-dot dashboard-legend-dot--warn" aria-hidden="true"></span> 85–94% ใกล้เต็ม</span>
                </div>

                <div class="dashboard-table-shell snapshot-table-wrap">
                    <table class="table dashboard-table data-table-full mb-0">
                        <thead>
                            <tr>
                                <th class="col-ward ps-3">แผนก</th>
                                <th class="col-num">เตียง</th>
                                <th class="col-num">ผู้ป่วย</th>
                                <th class="col-num">ใช้เตียง</th>
                                <th class="col-num">วันที่ข้อมูล</th>
                                <th class="col-num pe-3">เวร</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($snapshot)): ?>
                                <tr><td colspan="6" class="text-muted text-center py-4">ยังไม่มีข้อมูล</td></tr>
                            <?php else: ?>
                                <?php foreach ($snapshot as $row): ?>
                                    <?php
                                    $util = $row['utilization_pct'];
                                    $utilClass = '';
                                    if ($util !== null && $util >= 95) {
                                        $utilClass = 'util-high';
                                    } elseif ($util !== null && $util >= 85) {
                                        $utilClass = 'util-warn';
                                    }
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= esc($row['ward_name']) ?></td>
                                        <td class="col-num"><?= (int) $row['total_beds'] ?></td>
                                        <td class="col-num"><?= (int) $row['occupancy'] ?></td>
                                        <td class="col-num <?= esc($utilClass) ?>">
                                            <?= $util !== null ? esc((string) $util) . '%' : '—' ?>
                                        </td>
                                        <td class="col-num text-nowrap">
                                            <?php if (! empty($row['record_date'])): ?>
                                                <?php
                                                $p = explode('-', (string) $row['record_date']);
                                                echo esc(count($p) === 3 ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $row['record_date']);
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-num pe-3"><?= esc($row['shift_label'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dashboardFormulaModal" tabindex="-1" aria-labelledby="dashboardFormulaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h2 class="modal-title h6 mb-0" id="dashboardFormulaModalLabel">วิธีคำนวณตัวชี้วัด</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body prod-formula-body">
                <div class="prod-formula-block mb-2">
                    <h3>อัตราใช้เตียง (%)</h3>
                    <p class="mb-0 small">จำนวนผู้ป่วยสะสมในเดือน ÷ (จำนวนเตียง × วันในเดือน) × 100</p>
                </div>
                <div class="prod-formula-block mb-0">
                    <h3>Productivity การพยาบาล (%)</h3>
                    <p class="mb-0 small">ชั่วโมงดูแลที่ต้องการ ÷ ชั่วโมงทำงานของ RN/TN/PN × 100 — ยิ่งสูง ภาระงานยิ่งมากเมื่อเทียบกำลังคน</p>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(function() {
    let occupancyBarChart = null;
    let nursingBarChart = null;
    let occupancyTrendChart = null;
    let nursingTrendChart = null;
    let patientDaysTrendChart = null;
    let deptChart = null;

    const thaiMonths = <?= json_encode($thaiMonths, JSON_UNESCAPED_UNICODE) ?>;
    const chartPrimary = 'rgba(0, 93, 172, 0.72)';
    const chartGrid = 'rgba(230, 232, 240, 0.9)';
    const chartText = '#414752';

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 200 },
    };

    function nursingBucket(value) {
        if (value === null || value === undefined || value === '') return 'none';
        const n = Number(value);
        if (n > 150) return 'critical';
        if (n > 100) return 'high';
        if (n >= 60) return 'adequate';
        return 'low';
    }

    function nursingClass(value) {
        const bucket = nursingBucket(value);
        return bucket === 'none' ? '' : `prod-${bucket}`;
    }

    function occupancyClass(value) {
        if (value === null || value === undefined || value === '') return '';
        const n = Number(value);
        if (n >= 95) return 'util-high';
        if (n >= 85) return 'util-warn';
        return '';
    }

    function nursingBarColor(value) {
        const colors = {
            critical: 'rgba(185, 28, 28, 0.85)',
            high: 'rgba(234, 88, 12, 0.85)',
            adequate: 'rgba(21, 128, 61, 0.75)',
            low: 'rgba(71, 85, 105, 0.65)',
            none: 'rgba(100, 116, 139, 0.4)',
        };
        return colors[nursingBucket(value)] || colors.none;
    }

    function fmtPct(v) {
        return v === null || v === undefined ? '—' : Number(v).toFixed(1) + '%';
    }

    function fmtNum(v) {
        return Number(v || 0).toLocaleString();
    }

    function escapeHtml(text) {
        return $('<div>').text(text ?? '').html();
    }

    function baseChartOptions(extra = {}) {
        return {
            ...chartDefaults,
            scales: {
                x: {
                    grid: { color: chartGrid },
                    ticks: { color: chartText, font: { size: 11 } },
                },
                y: {
                    grid: { color: chartGrid },
                    ticks: { color: chartText, font: { size: 11 } },
                },
            },
            plugins: {
                legend: {
                    labels: { color: chartText, boxWidth: 12, font: { size: 11 } },
                },
            },
            ...extra,
        };
    }

    function renderKpis(summary, month, year) {
        const bestOcc = summary.best_occupancy;
        const bestNurse = summary.best_nursing;
        const monthLabel = thaiMonths[Number(month)] || month;

        $('#dashboard-period-badge').text(`${monthLabel} ${year}`);

        $('#dashboard-kpis').html(`
            <div class="dashboard-kpi dashboard-kpi--primary">
                <div class="dashboard-kpi-label">อัตราใช้เตียงเฉลี่ย</div>
                <div class="dashboard-kpi-value ${occupancyClass(summary.avg_occupancy_productivity)}">${fmtPct(summary.avg_occupancy_productivity)}</div>
                <div class="dashboard-kpi-sub">ผู้ป่วยสะสม ${fmtNum(summary.total_patient_days)} คน-วัน</div>
            </div>
            <div class="dashboard-kpi dashboard-kpi--primary">
                <div class="dashboard-kpi-label">Productivity เฉลี่ย</div>
                <div class="dashboard-kpi-value ${nursingClass(summary.avg_nursing_productivity)}">${fmtPct(summary.avg_nursing_productivity)}</div>
                <div class="dashboard-kpi-sub">รวมทุกแผนกในเดือน</div>
            </div>
            <div class="dashboard-kpi">
                <div class="dashboard-kpi-label">ใช้เตียงสูงสุด</div>
                <div class="dashboard-kpi-value dashboard-kpi-value--name">${bestOcc ? escapeHtml(bestOcc.ward_name) : '—'}</div>
                <div class="dashboard-kpi-sub">${bestOcc ? fmtPct(bestOcc.value) : '—'}</div>
            </div>
            <div class="dashboard-kpi">
                <div class="dashboard-kpi-label">Productivity สูงสุด</div>
                <div class="dashboard-kpi-value dashboard-kpi-value--name ${bestNurse ? nursingClass(bestNurse.value) : ''}">${bestNurse ? escapeHtml(bestNurse.ward_name) : '—'}</div>
                <div class="dashboard-kpi-sub">${bestNurse ? fmtPct(bestNurse.value) : '—'}</div>
            </div>
        `);
    }

    function renderTable(wards) {
        const rows = wards.map(w => `
            <tr>
                <td class="ps-3 col-ward fw-semibold">${escapeHtml(w.ward_name)}</td>
                <td class="col-num">${fmtNum(w.beds)}</td>
                <td class="col-num">${fmtNum(w.patient_days)}</td>
                <td class="col-num ${occupancyClass(w.occupancy_productivity)}">${fmtPct(w.occupancy_productivity)}</td>
                <td class="col-num pe-3 ${nursingClass(w.nursing_productivity)}">${fmtPct(w.nursing_productivity)}</td>
            </tr>
        `).join('');
        $('#ward-comparison-table tbody').html(rows || '<tr><td colspan="5" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>');
    }

    function makeBarOptions() {
        return baseChartOptions({
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    suggestedMax: 100,
                    grid: { color: chartGrid },
                    ticks: { color: chartText },
                    title: { display: true, text: '%', color: chartText, font: { size: 11 } },
                },
                y: {
                    grid: { display: false },
                    ticks: { color: chartText, font: { size: 11 } },
                },
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x.toFixed(1) + '%' } },
            },
        });
    }

    function renderBarChart(canvasId, chartRef, labels, values, colorFn) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        if (chartRef) chartRef.destroy();
        const colors = typeof colorFn === 'function'
            ? values.map(colorFn)
            : values.map(() => chartPrimary);
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: '%',
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    borderRadius: 4,
                }],
            },
            options: makeBarOptions(),
        });
    }

    function renderLineChart(canvasId, chartRef, labels, payload, yLabel) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        if (chartRef) chartRef.destroy();
        const datasets = payload.datasets && payload.datasets.length ? payload.datasets : [];
        if (!datasets.length) return null;
        return new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: baseChartOptions({
                scales: {
                    x: { grid: { color: chartGrid }, ticks: { color: chartText, maxRotation: 0 } },
                    y: {
                        beginAtZero: true,
                        grid: { color: chartGrid },
                        ticks: { color: chartText },
                        title: { display: true, text: yLabel || '%', color: chartText, font: { size: 11 } },
                    },
                },
                plugins: {
                    legend: { display: datasets.length > 1, position: 'bottom' },
                },
                elements: {
                    line: { tension: 0.25, borderWidth: 2 },
                    point: { radius: 2, hoverRadius: 4 },
                },
            }),
        });
    }

    function renderPatientDaysTrend(labels, payload) {
        const ctx = document.getElementById('patientDaysTrendChart').getContext('2d');
        if (patientDaysTrendChart) patientDaysTrendChart.destroy();
        const datasets = payload.datasets || [];
        if (!datasets.length) return;
        patientDaysTrendChart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: baseChartOptions({
                scales: {
                    x: { grid: { color: chartGrid }, ticks: { color: chartText } },
                    y: {
                        beginAtZero: true,
                        grid: { color: chartGrid },
                        ticks: { color: chartText },
                        title: { display: true, text: 'จำนวนผู้ป่วย', color: chartText, font: { size: 11 } },
                    },
                },
                plugins: { legend: { display: datasets.length > 1, position: 'bottom' } },
                elements: { line: { tension: 0.25 }, point: { radius: 2 } },
            }),
        });
    }

    function renderDeptChart(dept) {
        const ctx = document.getElementById('deptChart').getContext('2d');
        if (deptChart) deptChart.destroy();
        deptChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dept.labels,
                datasets: [{
                    label: 'อัตราใช้เตียง %',
                    data: dept.productivity,
                    backgroundColor: dept.productivity.map(v => chartPrimary),
                    borderWidth: 0,
                    borderRadius: 4,
                }],
            },
            options: makeBarOptions(),
        });
    }

    $('#dashboard-filter').on('submit', function(e) {
        e.preventDefault();
        const month = $('#month').val();
        const year = $('#year').val();

        $('#dashboard-monthly-alert').addClass('d-none').text('');
        $('#dashboard-monthly-loading').removeClass('d-none');
        $('#dashboard-monthly-body').addClass('d-none');

        $.ajax({
            url: '<?= base_url('reports/dashboardData') ?>',
            method: 'GET',
            dataType: 'json',
            data: { month, year },
            success: function(data) {
                renderKpis(data.summary, month, year);
                renderTable(data.wards);
                occupancyBarChart = renderBarChart(
                    'occupancyBarChart', occupancyBarChart,
                    data.occupancy_comparison.labels,
                    data.occupancy_comparison.values
                );
                nursingBarChart = renderBarChart(
                    'nursingBarChart', nursingBarChart,
                    data.nursing_comparison.labels,
                    data.nursing_comparison.values,
                    nursingBarColor
                );
                occupancyTrendChart = renderLineChart(
                    'occupancyTrendChart', occupancyTrendChart,
                    data.occupancy_trend.labels, data.occupancy_trend, '%'
                );
                nursingTrendChart = renderLineChart(
                    'nursingTrendChart', nursingTrendChart,
                    data.nursing_trend.labels, data.nursing_trend, '%'
                );
                renderPatientDaysTrend(data.trend.labels, data.trend);
                if (data.department_comparison) {
                    renderDeptChart(data.department_comparison);
                }
                $('#dashboard-monthly-body').removeClass('d-none');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.error || 'โหลดข้อมูลไม่สำเร็จ';
                $('#dashboard-monthly-alert').removeClass('d-none').text(msg);
            },
            complete: function() {
                $('#dashboard-monthly-loading').addClass('d-none');
            },
        });
    });

    document.getElementById('dashboardTrendsCollapse')?.addEventListener('shown.bs.collapse', function() {
        [occupancyTrendChart, nursingTrendChart, patientDaysTrendChart, deptChart].forEach(function(chart) {
            chart?.resize();
        });
    });

    $('#dashboard-filter').trigger('submit');
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
