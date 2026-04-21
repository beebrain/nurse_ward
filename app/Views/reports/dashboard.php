<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .dashboard-hero {
        margin-bottom: 2rem;
    }

    .dashboard-hero h1 {
        font-size: clamp(2rem, 3vw, 3.2rem);
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: 0.4rem;
    }

    .dashboard-shell {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .dashboard-filter-card,
    .dashboard-chart-card,
    .dashboard-mini-card {
        background: var(--surface-card);
        border-radius: 1.75rem;
        box-shadow: var(--shadow-soft);
    }

    .dashboard-filter-card,
    .dashboard-chart-card {
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
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 0.75rem;
    }

    #trendChart,
    #comparisonChart {
        min-height: 320px;
    }

    @media (min-width: 768px) {
        .dashboard-mini-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

</style>

<div class="dashboard-hero mb-3">
    <h1 class="mb-0">แดชบอร์ดผู้บริหาร</h1>
</div>

<div class="dashboard-mini-grid mb-4">
    <div class="dashboard-mini-card">
        <div class="dashboard-mini-label">แผนก</div>
        <div class="dashboard-mini-value"><?= count($wards) ?></div>
    </div>
    <div class="dashboard-mini-card">
        <div class="dashboard-mini-label">เดือน</div>
        <div class="dashboard-mini-value"><?= $current_month ?></div>
    </div>
    <div class="dashboard-mini-card dashboard-spotlight">
        <div class="dashboard-mini-label">ปี</div>
        <div class="dashboard-mini-value"><?= $current_year ?></div>
    </div>
</div>

<div class="dashboard-shell">
    <div>
        <div class="dashboard-filter-card mb-4">
            <div class="dashboard-section-title">ตัวกรอง</div>
            <form id="dashboard-filter" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="ward_id" class="form-label fw-bold">แผนก (เทรนด์)</label>
                    <select name="ward_id" id="ward_id" class="form-select" required>
                        <option value="">เลือกแผนก...</option>
                        <?php foreach ($wards as $ward): ?>
                            <option value="<?= $ward['id'] ?>"><?= esc($ward['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label fw-bold">เดือนที่เปรียบเทียบ</label>
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
                    <button type="submit" class="btn btn-primary w-100">โหลดแดชบอร์ด</button>
                </div>
            </form>
        </div>

        <div id="dashboard-result" class="d-none">
            <div class="dashboard-chart-card mb-4">
                <div class="dashboard-section-title d-flex flex-wrap align-items-baseline justify-content-between gap-2">
                    <span>เทรนด์ผู้ป่วยรายเดือน</span>
                    <span class="small fw-semibold text-muted"><span id="trend-ward-label">-</span></span>
                </div>
                <canvas id="trendChart" height="110"></canvas>
            </div>
            <div class="dashboard-chart-card">
                <div class="dashboard-section-title">อัตราผลิตตามแผนก</div>
                <canvas id="comparisonChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function() {
        let trendChart = null;
        let comparisonChart = null;

        function renderTrendChart(labels, values) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if (trendChart) {
                trendChart.destroy();
            }

            trendChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'วันนอนผู้ป่วย',
                        data: values,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
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
                        backgroundColor: 'rgba(25, 135, 84, 0.7)',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        $('#dashboard-filter').on('submit', function(e) {
            e.preventDefault();
            const wardId = $('#ward_id').val();
            const month = $('#month').val();
            const year = $('#year').val();

            if (!wardId) {
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
                    $('#trend-ward-label').text(data.selected_ward + ' - ' + data.year);
                    renderTrendChart(data.trend.labels, data.trend.patient_days);
                    renderComparisonChart(data.comparison.labels, data.comparison.productivity);
                    $('#dashboard-result').removeClass('d-none');
                },
                error: function(xhr) {
                    alert('ไม่สามารถโหลดข้อมูลแดชบอร์ด: ' + (xhr.responseJSON?.error || 'ข้อผิดพลาดไม่ทราบสาเหตุ'));
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>