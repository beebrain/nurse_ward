<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4 text-center">สรุปรายเดือน</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form id="report-filter" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="ward_id" class="form-label fw-bold">แผนก</label>
                        <select name="ward_id" id="ward_id" class="form-select" required>
                            <option value="">เลือกแผนก...</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value="<?= $ward['id'] ?>"><?= esc($ward['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="month" class="form-label fw-bold">เดือน</label>
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
                        <button type="submit" class="btn btn-primary w-100">ดูรายงาน</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="report-result" class="d-none">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ข้อมูลสรุป</h5>
                    <button id="export-excel" class="btn btn-sm btn-outline-success">ส่งออก Excel</button>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-muted">จำนวนวันนอนผู้ป่วย</h6>
                                <h3 id="stat-patient-days" class="mb-0 text-primary">0</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-muted">จำนวนเตียง</h6>
                                <h3 id="stat-ward-beds" class="mb-0">0</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-muted">จำนวนวันในเดือน</h6>
                                <h3 id="stat-days-in-month" class="mb-0">0</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-3 bg-light border-primary">
                                <h6 class="text-muted">อัตราผลิต (%)</h6>
                                <h3 id="stat-productivity" class="mb-0 text-primary">0%</h3>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th>รับใหม่</th>
                                    <th>จำหน่าย</th>
                                    <th>ย้ายเข้า</th>
                                    <th>ย้ายออก</th>
                                    <th>เสียชีวิต</th>
                                    <th class="table-primary">จำนวนวันนอนผู้ป่วย</th>
                                </tr>
                            </thead>
                            <tbody id="report-table-body" class="text-center">
                                <!-- Data will be populated by AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#report-filter').on('submit', function(e) {
            e.preventDefault();
            const wardId = $('#ward_id').val();
            const month = $('#month').val();
            const year = $('#year').val();

            if (!wardId) return;

            $.ajax({
                url: '<?= base_url('reports/getData') ?>',
                method: 'GET',
                data: {
                    ward_id: wardId,
                    month: month,
                    year: year
                },
                dataType: 'json',
                success: function(data) {
                    $('#stat-patient-days').text(data.patient_days);
                    $('#stat-ward-beds').text(data.ward_beds);
                    $('#stat-days-in-month').text(data.days_in_month);
                    $('#stat-productivity').text(parseFloat(data.productivity).toFixed(2) + '%');

                    const tbody = $('#report-table-body');
                    tbody.empty();
                    tbody.append(`
                    <tr>
                        <td>${data.admissions}</td>
                        <td>${data.discharges}</td>
                        <td>${data.transfers_in}</td>
                        <td>${data.transfers_out}</td>
                        <td>${data.deaths}</td>
                        <td class="table-primary fw-bold">${data.patient_days}</td>
                    </tr>
                `);

                    $('#report-result').removeClass('d-none');
                },
                error: function(xhr) {
                    alert('ไม่สามารถดึงข้อมูลรายงาน: ' + (xhr.responseJSON?.error || 'ข้อผิดพลาดไม่ทราบสาเหตุ'));
                }
            });
        });

        $('#export-excel').on('click', function() {
            const wardId = $('#ward_id').val();
            const month = $('#month').val();
            const year = $('#year').val();
            window.location.href = `<?= base_url('reports/export') ?>?ward_id=${wardId}&month=${month}&year=${year}`;
        });
    });
</script>
<?= $this->endSection() ?>