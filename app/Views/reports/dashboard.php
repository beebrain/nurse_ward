<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$thaiMonths = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
];
?>
<style>
    .dashboard-hero h1 {
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .dashboard-card {
        background: var(--surface-card);
        border-radius: 1.75rem;
        box-shadow: var(--shadow-soft);
        padding: 1.5rem;
    }

    .dashboard-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
    }

    .dashboard-kpi-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .dashboard-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .dashboard-kpi-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .dashboard-kpi {
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fbfcfe;
    }

    .dashboard-kpi-label {
        color: var(--text-muted);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.35rem;
    }

    .dashboard-kpi-value {
        font-family: 'Manrope', sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .dashboard-kpi-sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }

    .dashboard-chart-wrap {
        position: relative;
        min-height: 280px;
    }

    .dashboard-chart-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 992px) {
        .dashboard-chart-row {
            grid-template-columns: 1fr 1fr;
        }
    }

    .comparison-table-scroll {
        max-height: calc(100vh - 20rem);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 1rem;
    }

    .comparison-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
        box-shadow: 0 1px 0 #dee2e6;
        white-space: nowrap;
    }

    .comparison-table th,
    .comparison-table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .prod-good { color: #0c7521; font-weight: 700; }
    .prod-warn { color: #b45309; font-weight: 700; }
    .prod-low  { color: #93000a; font-weight: 700; }

    .snapshot-table-wrap {
        overflow-x: auto;
        border-radius: 1rem;
    }

    .util-high { color: var(--danger-text); font-weight: 800; }
    .util-warn { color: #b45309; font-weight: 700; }

    .formula-note {
        font-size: 0.82rem;
        color: var(--text-muted);
        border-left: 3px solid var(--primary);
        padding-left: 0.75rem;
        margin-bottom: 1rem;
    }
</style>

<div class="dashboard-hero mb-3">
    <h1 class="mb-1"><?= esc($title) ?></h1>
    <p class="text-muted small mb-0">เปรียบเทียบประสิทธิภาพทุกแผนก — เฉพาะ Super Admin</p>
</div>

<ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
    <?php if ($isSuperAdmin): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-monthly-btn" data-bs-toggle="tab" data-bs-target="#tab-monthly" type="button" role="tab">
                ภาพรวมรายเดือน
            </button>
        </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $isSuperAdmin ? '' : 'active' ?>" id="tab-snapshot-btn" data-bs-toggle="tab" data-bs-target="#tab-snapshot" type="button" role="tab">
            สถานะปัจจุบัน
        </button>
    </li>
</ul>

<div class="tab-content" id="dashboardTabContent">
    <?php if ($isSuperAdmin): ?>
        <div class="tab-pane fade show active" id="tab-monthly" role="tabpanel">
            <div class="dashboard-card mb-4">
                <form id="dashboard-filter" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="month" class="form-label fw-bold">เดือน</label>
                        <select name="month" id="month" class="form-select" required>
                            <?php foreach ($thaiMonths as $m => $name): ?>
                                <option value="<?= $m ?>" <?= $m === $current_month ? 'selected' : '' ?>><?= esc($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="year" class="form-label fw-bold">ปี</label>
                        <select name="year" id="year" class="form-select" required>
                            <?php for ($y = $current_year; $y >= $current_year - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $current_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">โหลดข้อมูล</button>
                    </div>
                </form>
            </div>

            <div id="dashboard-monthly-alert" class="alert alert-danger d-none"></div>

            <div id="dashboard-monthly-body" class="d-none">
                <div class="formula-note">
                    <strong>อัตราใช้เตียง</strong> = วันนอนรวม ÷ (เตียง × วันในเดือน) × 100 &nbsp;|&nbsp;
                    <strong>ประสิทธิภาพการพยาบาล</strong> = Required Care Hours ÷ Working Hours × 100
                </div>

                <div class="dashboard-kpi-grid mb-4" id="dashboard-kpis"></div>

                <div class="dashboard-card mb-4">
                    <div class="dashboard-section-title">เปรียบเทียบทุกแผนก</div>
                    <div class="comparison-table-scroll">
                        <table class="table table-hover comparison-table mb-0" id="ward-comparison-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">แผนก</th>
                                    <th class="text-end">เตียง</th>
                                    <th class="text-end">Patient Days</th>
                                    <th class="text-end">อัตราใช้เตียง %</th>
                                    <th class="text-end pe-3">ประสิทธิภาพการพยาบาล %</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="dashboard-chart-row mb-4">
                    <div class="dashboard-card">
                        <div class="dashboard-section-title">อัตราใช้เตียง แยกตามแผนก</div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="occupancyBarChart"></canvas>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="dashboard-section-title">ประสิทธิภาพการพยาบาล แยกตามแผนก</div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="nursingBarChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="dashboard-chart-row mb-4">
                    <div class="dashboard-card">
                        <div class="dashboard-section-title">แนวโน้มอัตราใช้เตียง (รายปี ทุกแผนก)</div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="occupancyTrendChart"></canvas>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="dashboard-section-title">แนวโน้มประสิทธิภาพการพยาบาล (รายปี ทุกแผนก)</div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="nursingTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="dashboard-chart-row mb-4">
                    <div class="dashboard-card">
                        <div class="dashboard-section-title">แนวโน้มวันนอนผู้ป่วย (รายปี ทุกแผนก)</div>
                        <div class="dashboard-chart-wrap">
                            <canvas id="patientDaysTrendChart"></canvas>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="dashboard-section-title">อัตราใช้เตียง แยกตามกลุ่มงาน</div>
                        <div class="dashboard-chart-wrap" style="min-height:300px;">
                            <canvas id="deptChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="dashboard-monthly-loading" class="text-center text-muted py-5 d-none">
                <div class="spinner-border spinner-border-sm me-2"></div>กำลังโหลด...
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-4">
            การเปรียบเทียบประสิทธิภาพทุกแผนก ใช้ได้เฉพาะ <strong>Super Admin</strong> — ดูได้เฉพาะแท็บสถานะปัจจุบัน
        </div>
    <?php endif; ?>

    <div class="tab-pane fade <?= $isSuperAdmin ? '' : 'show active' ?>" id="tab-snapshot" role="tabpanel">
        <div class="dashboard-card">
            <div class="dashboard-section-title d-flex align-items-center gap-2">
                <span class="material-symbols-outlined text-primary">monitoring</span>
                สถานะล่าสุดตามข้อมูลที่บันทึก
            </div>
            <p class="text-muted small">Occupancy ณ ข้อมูล census ล่าสุดของแต่ละแผนก (ไม่ใช่สรุปรายเดือน)</p>
            <div class="snapshot-table-wrap">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>แผนก</th>
                            <th class="text-center">เตียง</th>
                            <th class="text-center">คงพยาบาล</th>
                            <th class="text-center">ใช้เตียง %</th>
                            <th class="text-nowrap">วันที่ข้อมูล</th>
                            <th class="text-center">เวรอ้างอิง</th>
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
                                    <td class="fw-semibold"><?= esc($row['ward_name']) ?></td>
                                    <td class="text-center"><?= (int) $row['total_beds'] ?></td>
                                    <td class="text-center"><?= (int) $row['occupancy'] ?></td>
                                    <td class="text-center <?= $utilClass ?>">
                                        <?= $util !== null ? esc((string) $util) . '%' : '—' ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <?php if (! empty($row['record_date'])): ?>
                                            <?php
                                            $p = explode('-', (string) $row['record_date']);
                                            echo esc(count($p) === 3 ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $row['record_date']);
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= esc($row['shift_label'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    const chartDefaults = { responsive: true, maintainAspectRatio: false };

    function prodClass(v) {
        if (v === null || v === undefined || v === '') return '';
        const n = Number(v);
        if (n >= 80) return 'prod-good';
        if (n >= 60) return 'prod-warn';
        return 'prod-low';
    }

    function fmtPct(v) {
        return v === null || v === undefined ? '—' : Number(v).toFixed(1) + '%';
    }

    function fmtNum(v) {
        return Number(v || 0).toLocaleString();
    }

    function renderKpis(summary) {
        const bestOcc = summary.best_occupancy;
        const bestNurse = summary.best_nursing;
        $('#dashboard-kpis').html(`
            <div class="dashboard-kpi">
                <div class="dashboard-kpi-label">อัตราใช้เตียงเฉลี่ย</div>
                <div class="dashboard-kpi-value">${fmtPct(summary.avg_occupancy_productivity)}</div>
                <div class="dashboard-kpi-sub">Patient Days รวม ${fmtNum(summary.total_patient_days)}</div>
            </div>
            <div class="dashboard-kpi">
                <div class="dashboard-kpi-label">ประสิทธิภาพการพยาบาลเฉลี่ย</div>
                <div class="dashboard-kpi-value">${fmtPct(summary.avg_nursing_productivity)}</div>
                <div class="dashboard-kpi-sub">รวมทุกแผนกในเดือน</div>
            </div>
            <div class="dashboard-kpi">
                <div class="dashboard-kpi-label">แผนกใช้เตียงสูงสุด</div>
                <div class="dashboard-kpi-value" style="font-size:1.25rem;">${bestOcc ? escapeHtml(bestOcc.ward_name) : '—'}</div>
                <div class="dashboard-kpi-sub">${bestOcc ? fmtPct(bestOcc.value) : ''}</div>
            </div>
            <div class="dashboard-kpi">
                <div class="dashboard-kpi-label">แผนกพยาบาลสูงสุด</div>
                <div class="dashboard-kpi-value" style="font-size:1.25rem;">${bestNurse ? escapeHtml(bestNurse.ward_name) : '—'}</div>
                <div class="dashboard-kpi-sub">${bestNurse ? fmtPct(bestNurse.value) : ''}</div>
            </div>
        `);
    }

    function escapeHtml(text) {
        return $('<div>').text(text ?? '').html();
    }

    function renderTable(wards) {
        const rows = wards.map(w => `
            <tr>
                <td class="ps-3 fw-semibold">${escapeHtml(w.ward_name)}</td>
                <td class="text-end">${fmtNum(w.beds)}</td>
                <td class="text-end">${fmtNum(w.patient_days)}</td>
                <td class="text-end ${prodClass(w.occupancy_productivity)}">${fmtPct(w.occupancy_productivity)}</td>
                <td class="text-end pe-3 ${prodClass(w.nursing_productivity)}">${fmtPct(w.nursing_productivity)}</td>
            </tr>
        `).join('');
        $('#ward-comparison-table tbody').html(rows || '<tr><td colspan="5" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>');
    }

    function makeBarOptions() {
        return {
            indexAxis: 'y',
            ...chartDefaults,
            scales: {
                x: { beginAtZero: true, suggestedMax: 100, title: { display: true, text: '%' } },
                y: { ticks: { font: { size: 11 } } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x.toFixed(1) + '%' } }
            }
        };
    }

    function barColors(values) {
        return values.map(v =>
            v >= 80 ? 'rgba(25, 135, 84, 0.75)' :
            v >= 60 ? 'rgba(253, 126, 20, 0.75)' :
                      'rgba(220, 53, 69, 0.65)'
        );
    }

    function renderBarChart(canvasId, chartRef, labels, values, color) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        if (chartRef) chartRef.destroy();
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: '%',
                    data: values,
                    backgroundColor: color || barColors(values),
                    borderWidth: 0
                }]
            },
            options: makeBarOptions()
        });
    }

    function renderLineChart(canvasId, chartRef, labels, payload) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        if (chartRef) chartRef.destroy();
        const datasets = payload.datasets && payload.datasets.length ? payload.datasets : [];
        if (!datasets.length) return null;
        return new Chart(ctx, {
            type: 'line',
            data: { labels, datasets },
            options: {
                ...chartDefaults,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: '%' } }
                },
                plugins: { legend: { display: datasets.length > 1, position: 'bottom' } }
            }
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
            options: {
                ...chartDefaults,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'วันนอน' } } },
                plugins: { legend: { display: datasets.length > 1, position: 'bottom' } }
            }
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
                    backgroundColor: barColors(dept.productivity),
                    borderWidth: 0
                }]
            },
            options: makeBarOptions()
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
                renderKpis(data.summary);
                renderTable(data.wards);
                occupancyBarChart = renderBarChart(
                    'occupancyBarChart', occupancyBarChart,
                    data.occupancy_comparison.labels,
                    data.occupancy_comparison.values
                );
                nursingBarChart = renderBarChart(
                    'nursingBarChart', nursingBarChart,
                    data.nursing_comparison.labels,
                    data.nursing_comparison.values
                );
                occupancyTrendChart = renderLineChart(
                    'occupancyTrendChart', occupancyTrendChart,
                    data.occupancy_trend.labels, data.occupancy_trend
                );
                nursingTrendChart = renderLineChart(
                    'nursingTrendChart', nursingTrendChart,
                    data.nursing_trend.labels, data.nursing_trend
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
            }
        });
    });

    $('#dashboard-filter').trigger('submit');
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>
