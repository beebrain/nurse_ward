<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">แดชบอร์ดพฤติกรรมคนไข้</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body bg-light rounded">
        <form id="filterForm" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">เลือกแผนก/วอร์ด</label>
                <select name="ward_id" id="ward_id" class="form-select">
                    <?php foreach ($wards as $ward): ?>
                        <option value="<?= $ward['id'] ?>"><?= esc($ward['code'] . ' - ' . $ward['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">เดือน</label>
                <select name="month" id="month" class="form-select">
                    <?php 
                    $months = [
                        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                    ];
                    foreach ($months as $m => $label): 
                    ?>
                        <option value="<?= $m ?>" <?= $m === $current_month ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">ปี (ค.ศ.)</label>
                <select name="year" id="year" class="form-select">
                    <?php for ($y = $current_year - 2; $y <= $current_year; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $current_year ? 'selected' : '' ?>><?= $y + 543 ?> (<?= $y ?>)</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" id="btnFilter" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> แสดงข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<div id="loading" class="text-center my-5 d-none">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
</div>

<div id="error" class="alert alert-danger d-none"></div>

<div id="dashboardContent" class="d-none">
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">รับใหม่ (Admission)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="sumAdmissions">0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">จำหน่าย (Discharge)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="sumDischarges">0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-minus fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">ย้ายเข้า/ออก (Transfers)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="sumTransfers">0 / 0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-exchange-alt fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">เสียชีวิต (Deaths)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="sumDeaths">0</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-bed fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">การเคลื่อนไหวของผู้ป่วยรายวัน (Admissions vs Discharges)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="movementChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">ยอดผู้ป่วยเฉลี่ยรายวัน</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie" style="height: 320px;">
                        <canvas id="patientCountChart"></canvas>
                    </div>
                </div>
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
        dcData.push(-d.discharges); // negative for visual separation
        
        // Calculate average patient count for the day
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
                    backgroundColor: '#1cc88a',
                    data: admitData,
                },
                {
                    label: 'จำหน่าย (Discharges)',
                    backgroundColor: '#e74a3b',
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
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                pointRadius: 3,
                pointBackgroundColor: '#4e73df',
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
