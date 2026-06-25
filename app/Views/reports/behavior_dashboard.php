<?= $this->extend('layout/main') ?>

<?= $this->section('layout_class') ?>layout-fluid<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<link href="<?= asset_url('css/reports-dashboard.css') ?>" rel="stylesheet">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-4">
    <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.75rem;">insights</span>
    <div>
        <h1 class="h3 mb-0">แดชบอร์ดพฤติกรรมคนไข้</h1>
        <div class="text-muted small">การเคลื่อนไหวผู้ป่วยรายวัน แยกตาม Ward</div>
    </div>
</div>

<div class="dashboard-card mb-4">
    <form id="filterForm" class="dashboard-filter-bar">
        <div class="filter-field">
            <label for="ward_id" class="form-label fw-bold">แผนก / Ward</label>
            <select name="ward_id" id="ward_id" class="form-select">
                <?php foreach ($wards as $ward): ?>
                    <option value="<?= $ward['id'] ?>"><?= esc($ward['code'] . ' - ' . $ward['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field">
            <label for="month" class="form-label fw-bold">เดือน</label>
            <select name="month" id="month" class="form-select">
                <?php
                $months = [
                    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
                ];
                foreach ($months as $m => $label):
                ?>
                    <option value="<?= $m ?>" <?= $m === $current_month ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-field">
            <label for="year" class="form-label fw-bold">ปี (ค.ศ.)</label>
            <select name="year" id="year" class="form-select">
                <?php for ($y = $current_year - 2; $y <= $current_year; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $current_year ? 'selected' : '' ?>><?= $y + 543 ?> (<?= $y ?>)</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button type="button" id="btnFilter" class="btn btn-primary">
                <span class="material-symbols-outlined align-middle me-1">search</span>
                แสดงข้อมูล
            </button>
        </div>
    </form>
</div>

<div id="loading" class="text-center my-5 d-none" aria-live="polite">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">กำลังโหลด...</span>
    </div>
    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
</div>

<div id="error" class="alert alert-danger d-none" role="alert" aria-live="assertive"></div>

<div id="dashboardContent" class="d-none" aria-live="polite">
    <div class="dashboard-kpi-grid mb-4">
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-label">รับใหม่ (Admission)</div>
            <div class="dashboard-kpi-value" id="sumAdmissions">0</div>
        </div>
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-label">จำหน่าย (Discharge)</div>
            <div class="dashboard-kpi-value" id="sumDischarges">0</div>
        </div>
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-label">ย้ายเข้า / ย้ายออก</div>
            <div class="dashboard-kpi-value" style="font-size:1.35rem;" id="sumTransfers">0 / 0</div>
        </div>
        <div class="dashboard-kpi">
            <div class="dashboard-kpi-label">เสียชีวิต (Deaths)</div>
            <div class="dashboard-kpi-value" id="sumDeaths">0</div>
        </div>
    </div>

    <div class="dashboard-chart-row">
        <div class="dashboard-card mb-4">
            <div class="dashboard-section-title">การเคลื่อนไหวของผู้ป่วยรายวัน</div>
            <div class="behavior-chart-wrap">
                <canvas id="movementChart"></canvas>
            </div>
        </div>
        <div class="dashboard-card mb-4">
            <div class="dashboard-section-title">ยอดผู้ป่วยเฉลี่ยรายวัน</div>
            <div class="behavior-chart-wrap">
                <canvas id="patientCountChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let movementChart = null;
let patientCountChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadDashboard();
    document.getElementById('btnFilter').addEventListener('click', loadDashboard);
});

async function loadDashboard() {
    const wardId = document.getElementById('ward_id').value;
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;

    if (!wardId) {
        showError('กรุณาเลือกแผนก');
        return;
    }

    document.getElementById('loading').classList.remove('d-none');
    document.getElementById('error').classList.add('d-none');
    document.getElementById('dashboardContent').classList.add('d-none');

    try {
        const resp = await fetch(`<?= base_url('census/behavior-dashboard-data') ?>?ward_id=${wardId}&month=${month}&year=${year}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await resp.json();

        if (json.error) {
            showError(json.error);
            return;
        }

        renderDashboard(json);
    } catch (e) {
        showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
    } finally {
        document.getElementById('loading').classList.add('d-none');
    }
}

function showError(msg) {
    const el = document.getElementById('error');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function renderDashboard(data) {
    document.getElementById('dashboardContent').classList.remove('d-none');

    let tAdmissions = 0, tDischarges = 0, tTransfersIn = 0, tTransfersOut = 0, tDeaths = 0;
    const labels = [];
    const admitData = [];
    const dcData = [];
    const countData = [];

    data.daily_summary.forEach(d => {
        tAdmissions += d.admissions;
        tDischarges += d.discharges;
        tTransfersIn += d.transfers_in;
        tTransfersOut += d.transfers_out;
        tDeaths += d.deaths;

        const day = d.date.split('-')[2];
        labels.push(day);
        admitData.push(d.admissions);
        dcData.push(-d.discharges);

        const avgCount = d.patient_count.length ?
            (d.patient_count.reduce((a,b)=>a+b,0) / d.patient_count.length).toFixed(1) : 0;
        countData.push(avgCount);
    });

    document.getElementById('sumAdmissions').textContent = tAdmissions;
    document.getElementById('sumDischarges').textContent = tDischarges;
    document.getElementById('sumTransfers').textContent = `${tTransfersIn} / ${tTransfersOut}`;
    document.getElementById('sumDeaths').textContent = tDeaths;

    renderMovementChart(labels, admitData, dcData);
    renderPatientCountChart(labels, countData);
}

function renderMovementChart(labels, admitData, dcData) {
    const ctx = document.getElementById('movementChart');
    if (movementChart) movementChart.destroy();

    movementChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'รับใหม่ (Admissions)',
                    backgroundColor: 'rgba(12, 117, 33, 0.75)',
                    data: admitData,
                },
                {
                    label: 'จำหน่าย (Discharges)',
                    backgroundColor: 'rgba(147, 0, 10, 0.65)',
                    data: dcData,
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + Math.abs(context.raw);
                        }
                    }
                }
            }
        }
    });
}

function renderPatientCountChart(labels, countData) {
    const ctx = document.getElementById('patientCountChart');
    if (patientCountChart) patientCountChart.destroy();

    patientCountChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'ยอดผู้ป่วยเฉลี่ย',
                data: countData,
                borderColor: '#005dac',
                backgroundColor: 'rgba(0, 93, 172, 0.08)',
                pointRadius: 3,
                pointBackgroundColor: '#005dac',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
</script>

<?= $this->endSection() ?>
