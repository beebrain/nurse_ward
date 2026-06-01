<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    .hosxp-log-table td { vertical-align: middle; font-size: 0.9rem; }
    .hosxp-payload-pre {
        max-height: 65vh;
        overflow: auto;
        font-size: 0.78rem;
        background: #0f172a;
        color: #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        margin: 0;
    }
    .badge-ok { background: #dcfce7; color: #166534; }
    .badge-fail { background: #fee2e2; color: #991b1b; }
</style>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="material-symbols-outlined" style="color:var(--primary);font-size:1.75rem;">receipt_long</span>
        <div>
            <h1 class="h3 mb-0"><?= esc($title) ?></h1>
            <div class="text-muted small">ประวัติการดึงข้อมูลจาก IPD API (HOSxP) ทุก 30 นาที — เฉพาะ Super Admin</div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <form id="log-filter" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="date_from" class="form-label fw-bold small mb-1">ตั้งแต่วันที่</label>
                <input type="date" id="date_from" name="date_from" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label for="date_to" class="form-label fw-bold small mb-1">ถึงวันที่</label>
                <input type="date" id="date_to" name="date_to" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label for="limit" class="form-label fw-bold small mb-1">จำนวนแถว</label>
                <select id="limit" name="limit" class="form-select form-select-sm">
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">ค้นหา</button>
            </div>
        </form>
    </div>
</div>

<div id="log-loading" class="text-center py-4 d-none">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="text-muted small mt-2 mb-0">กำลังโหลด...</p>
</div>

<div id="log-error" class="alert alert-danger d-none"></div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover hosxp-log-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>เวลาดึงข้อมูล</th>
                        <th>ช่วง record_time</th>
                        <th>สถานะ</th>
                        <th class="text-end">แผนก</th>
                        <th class="text-end">ผู้ป่วยรวม</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="log-tbody">
                    <tr><td colspan="7" class="text-center text-muted py-4">กำลังโหลด...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="payloadModal" tabindex="-1" aria-labelledby="payloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payloadModalLabel">Raw JSON จาก HOSxP API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="payload-meta" class="small text-muted mb-2"></div>
                <pre id="payload-json" class="hosxp-payload-pre"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-copy-json">คัดลอก JSON</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const tbody = document.getElementById('log-tbody');
    const loading = document.getElementById('log-loading');
    const errBox = document.getElementById('log-error');
    const filterForm = document.getElementById('log-filter');
    const payloadModal = document.getElementById('payloadModal');
    const payloadPre = document.getElementById('payload-json');
    const payloadMeta = document.getElementById('payload-meta');
    let lastJsonText = '';

    async function loadLogs() {
        loading.classList.remove('d-none');
        errBox.classList.add('d-none');
        const params = new URLSearchParams(new FormData(filterForm));
        try {
            const resp = await fetch(`<?= base_url('admin/hosxp-logs/data') ?>?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await resp.json();
            if (!json.success) {
                throw new Error(json.message || 'โหลดไม่สำเร็จ');
            }
            renderRows(json.rows || []);
        } catch (e) {
            errBox.textContent = e.message || 'เกิดข้อผิดพลาด';
            errBox.classList.remove('d-none');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">โหลดไม่สำเร็จ</td></tr>';
        } finally {
            loading.classList.add('d-none');
        }
    }

    function renderRows(rows) {
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">ไม่มีบันทึก (รอ cron ดึง API ครั้งถัดไป)</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r => {
            const ok = Number(r.success) === 1;
            const badge = ok
                ? '<span class="badge badge-ok">สำเร็จ</span>'
                : '<span class="badge badge-fail">ล้มเหลว</span>';
            const err = r.error_message
                ? `<div class="small text-danger">${escapeHtml(r.error_message)}</div>`
                : '';
            return `<tr>
                <td>${r.id}</td>
                <td>${escapeHtml(r.fetched_at_fmt)}</td>
                <td>${escapeHtml(r.record_time_fmt)}</td>
                <td>${badge}${err}</td>
                <td class="text-end">${r.wards_saved ?? 0}</td>
                <td class="text-end">${r.patient_total ?? 0}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-view-payload" data-id="${r.id}">ดู JSON</button>
                </td>
            </tr>`;
        }).join('');
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    tbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('.btn-view-payload');
        if (!btn) return;
        const id = btn.dataset.id;
        btn.disabled = true;
        try {
            const resp = await fetch(`<?= base_url('admin/hosxp-logs/detail') ?>/${id}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await resp.json();
            if (!json.success) throw new Error(json.message || 'ไม่พบข้อมูล');
            const log = json.log;
            payloadMeta.innerHTML = `ดึงเมื่อ: <strong>${escapeHtml(log.fetched_at)}</strong> |
                ช่วง: <strong>${escapeHtml(log.record_time)}</strong> |
                แผนก: ${log.wards_saved} | ผู้ป่วยรวม: ${log.patient_total}`;
            lastJsonText = JSON.stringify(json.payload, null, 2);
            payloadPre.textContent = lastJsonText;
            bootstrap.Modal.getOrCreateInstance(payloadModal).show();
        } catch (err) {
            alert(err.message || 'โหลด JSON ไม่สำเร็จ');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('btn-copy-json')?.addEventListener('click', () => {
        navigator.clipboard.writeText(lastJsonText).then(() => {
            const btn = document.getElementById('btn-copy-json');
            const orig = btn.textContent;
            btn.textContent = 'คัดลอกแล้ว';
            setTimeout(() => { btn.textContent = orig; }, 1500);
        });
    });

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        loadLogs();
    });

    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('date_to').value = today;
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    document.getElementById('date_from').value = weekAgo.toISOString().slice(0, 10);

    loadLogs();
})();
</script>
<?= $this->endSection() ?>
