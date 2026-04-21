<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .dashboard-hero {
        margin-bottom: 1rem;
    }

    .dashboard-hero h1 {
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .dashboard-shell {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .dashboard-filter-card,
    .dashboard-chart-card,
    .dashboard-mini-card,
    .dashboard-snapshot-card {
        background: var(--surface-card);
        border-radius: 1.75rem;
        box-shadow: var(--shadow-soft);
    }

    .dashboard-filter-card,
    .dashboard-chart-card,
    .dashboard-snapshot-card {
        padding: 1.5rem;
    }

    .dashboard-mini-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }

    .dashboard-mini-card {
        padding: 1.25rem;
    }

    .dashboard-mini-label {
        color: var(--text-muted);
        font-size: 0.82rem;
        font-weight: 700;
        margin-bottom: 0.55rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .dashboard-mini-value {
        font-family: 'Manrope', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1;
    }

    .dashboard-spotlight {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-strong) 100%);
        color: #fff;
    }

    .dashboard-spotlight .dashboard-mini-label {
        color: rgba(255, 255, 255, 0.78);
    }

    .dashboard-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
    }

    .dashboard-chart-wrap {
        position: relative;
        min-height: 280px;
    }

    @media (min-width: 768px) {
        .dashboard-mini-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .dashboard-chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
    }

    .snapshot-table-wrap {
        overflow-x: auto;
        border-radius: 1rem;
    }

    .snapshot-table-wrap table {
        margin-bottom: 0;
        font-size: 0.92rem;
    }

    .util-high {
        color: var(--danger-text);
        font-weight: 800;
    }

    .util-warn {
        color: #b45309;
        font-weight: 700;
    }
</style>

<div class="dashboard-hero mb-3">
    <h1 class="mb-0">แดชบอร์ดผู้บริหาร</h1>
</div>

<div class="dashboard-snapshot-card mb-4">
    <div class="dashboard-section-title d-flex align-items-center gap-2">
        <span class="material-symbols-outlined text-primary">monitoring</span>
        สถานะปัจจุบัน (ข้อมูลล่าสุดตามวันที่บันทึก)
    </div>
    <div class="snapshot-table-wrap">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>แผนก</th>
                    <th class="text-center">เตียงทั้งหมด</th>
                    <th class="text-center">คงพยาบาล</th>
                    <th class="text-center">ใช้เตียง %</th>
                    <th class="text-nowrap">วันที่ข้อมูล</th>
                    <th class="text-center">เวรอ้างอิง</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snapshot)): ?>
                    <tr>
                        <td colspan="6" class="text-muted text-center py-4">ยังไม่มีแผนกที่เปิดใช้งาน</td>
                    </tr>
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
                                    $d = $row['record_date'];
                                    $p = explode('-', (string) $d);
                                    echo esc(count($p) === 3 ? ($p[2] . '/' . $p[1] . '/' . $p[0]) : $d);
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

<div class="dashboard-mini-grid mb-4">
    <div class="dashboard-mini-card">
        <div class="dashboard-mini-label">แผนกที่เปิด</div>
        <div class="dashboard-mini-value"><?= count($wards) ?></div>
    </div>
    <div class="dashboard-mini-card">
        <div class="dashboard-mini-label">เดือนเปรียบเทียบ (ค่าเริ่มต้น)</div>
        <div class="dashboard-mini-value"><?= (int) $current_month ?></div>
    </div>
    <div class="dashboard-mini-card dashboard-spotlight">
        <div class="dashboard-mini-label">ปีอ้างอิง</div>
        <div class="dashboard-mini-value"><?= (int) $current_year ?></div>
    </div>
</div>

<div class="dashboard-shell">
    <div>
        <div class="dashboard-filter-card mb-4">
            <div class="dashboard-section-title">ตัวกรองกราฟแนวโน้ม</div>
            <form id="dashboard-filter" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="ward_id" class="form-label fw-bold">แผนก (เส้นแนวโน้ม)</label>
                    <select name="ward_id" id="ward_id" class="form-select" required>
                        <option value="all" selected>ทุกแผนก</option>
                        <?php foreach ($wards as $ward): ?>
                            <option value="<?= $ward['id'] ?>"><?= esc($ward['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label fw-bold">เดือน (เปรียบเทียบแผนก)</label>
                    <select name="month" id="month" class="form-select" required>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == $current_month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label fw-bold">ปี</label>
                    <select name="year" id="year" class="form-select" required>
                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                            <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">โหลดกราฟ</button>
                </div>
            </form>
        </div>

        <div id="dashboard-result" class="d-none">
            <div class="dashboard-chart-row mb-4">
                <div class="dashboard-chart-card">
                    <div class="dashboard-section-title d-flex flex-wrap align-items-baseline justify-content-between gap-2">
                        <span>แนวโน้มวันนอนผู้ป่วย</span>
                        <span class="small fw-semibold text-muted"><span id="trend-ward-label">-</span></span>
                    </div>
                    <div class="dashboard-chart-wrap">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                <div class="dashboard-chart-card">
                    <div class="dashboard-section-title">แนวโน้มอัตราผลิต %</div>
                    <div class="dashboard-chart-wrap">
                        <canvas id="productivityTrendChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="dashboard-chart-card">
                <div class="dashboard-section-title">อัตราผลิตแยกตามแผนก</div>
                <div class="dashboard-chart-wrap" style="min-height: 320px;">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function() {
        let trendChart = null;
        let productivityTrendChart = null;
        let comparisonChart = null;

        const chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
        };

        function normalizeTrendDatasets(trendPayload) {
            if (trendPayload.datasets && trendPayload.datasets.length) {
                return trendPayload.datasets;
            }
            if (trendPayload.patient_days) {
                return [{
                    label: 'วันนอนผู้ป่วย (รวมในเดือน)',
                    data: trendPayload.patient_days,
                    fill: true,
                    tension: 0.25,
                    backgroundColor: 'rgba(0, 93, 172, 0.12)',
                    borderColor: 'rgba(0, 93, 172, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }];
            }
            return [];
        }

        function normalizeProductivityDatasets(prodPayload) {
            if (!prodPayload) {
                return [];
            }
            if (prodPayload.datasets && prodPayload.datasets.length) {
                return prodPayload.datasets;
            }
            if (prodPayload.values) {
                return [{
                    label: 'อัตราผลิต %',
                    data: prodPayload.values,
                    fill: true,
                    tension: 0.25,
                    backgroundColor: 'rgba(152, 249, 148, 0.2)',
                    borderColor: 'rgba(12, 117, 33, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                }];
            }
            return [];
        }

        function renderTrendChart(labels, trendPayload) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if (trendChart) {
                trendChart.destroy();
            }

            const datasets = normalizeTrendDatasets(trendPayload);
            const showLegend = datasets.length > 1;

            if (!datasets.length) {
                if (trendChart) {
                    trendChart.destroy();
                    trendChart = null;
                }
                return;
            }

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets,
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'วันนอน' }
                        }
                    },
                    plugins: {
                        legend: { display: showLegend, position: 'bottom' }
                    }
                }
            });
        }

        function renderProductivityTrendChart(labels, prodPayload) {
            const ctx = document.getElementById('productivityTrendChart').getContext('2d');
            if (productivityTrendChart) {
                productivityTrendChart.destroy();
            }

            const datasets = normalizeProductivityDatasets(prodPayload);
            const showLegend = datasets.length > 1;

            if (!datasets.length) {
                if (productivityTrendChart) {
                    productivityTrendChart.destroy();
                    productivityTrendChart = null;
                }
                return;
            }

            productivityTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets,
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            title: { display: true, text: '%' }
                        }
                    },
                    plugins: {
                        legend: { display: showLegend, position: 'bottom' }
                    }
                }
            });
        }

        function renderComparisonChart(labels, values) {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            if (comparisonChart) {
                comparisonChart.destroy();
            }

            comparisonChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'อัตราผลิต %',
                        data: values,
                        backgroundColor: 'rgba(25, 135, 84, 0.65)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    ...chartDefaults,
                    scales: {
                        x: {
                            beginAtZero: true,
                            suggestedMax: 100,
                            title: { display: true, text: '%' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        $('#dashboard-filter').on('submit', function(e) {
            e.preventDefault();
            const wardId = $('#ward_id').val();
            const month = $('#month').val();
            const year = $('#year').val();

            if (wardId === undefined || wardId === null || wardId === '') {
                return;
            }

            $.ajax({
                url: '<?= base_url('reports/dashboardData') ?>',
                method: 'GET',
                dataType: 'json',
                data: {
                    ward_id: wardId,
                    month: month,
                    year: year
                },
                success: function(data) {
                    $('#trend-ward-label').text(data.selected_ward + ' · ปี ' + data.year);
                    renderTrendChart(data.trend.labels, data.trend);
                    if (data.productivity_trend) {
                        renderProductivityTrendChart(
                            data.productivity_trend.labels,
                            data.productivity_trend
                        );
                    }
                    renderComparisonChart(data.comparison.labels, data.comparison.productivity);
                    $('#dashboard-result').removeClass('d-none');
                },
                error: function(xhr) {
                    alert('ไม่สามารถโหลดข้อมูลแดชบอร์ด: ' + (xhr.responseJSON?.error || 'ข้อผิดพลาดไม่ทราบสาเหตุ'));
                }
            });
        });

        $('#dashboard-filter').trigger('submit');
    });
</script>
<?= $this->endSection() ?>
