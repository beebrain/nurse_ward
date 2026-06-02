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
    .hosxp-detail-table { font-size: 0.82rem; }
    .hosxp-detail-table th { white-space: nowrap; background: var(--surface-low, #f2f3fc); }
    .hosxp-detail-table td { vertical-align: middle; }
    #payloadModal { z-index: 2000; }
    .modal-backdrop { z-index: 1990; }
    .map-ok { background: #dcfce7; color: #166534; }
    .map-warn { background: #fef9c3; color: #854d0e; }
    .map-bad { background: #fee2e2; color: #991b1b; }
    .map-ambig { background: #e0e7ff; color: #3730a3; }
    .hosxp-map-summary .badge { font-weight: 600; }
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
                <h5 class="modal-title" id="payloadModalLabel">ข้อมูลจาก HOSxP API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <div id="payload-meta" class="small text-muted mb-3"></div>
                <ul class="nav nav-tabs nav-tabs-sm mb-3" id="hosxpDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-merged-btn" data-bs-toggle="tab" data-bs-target="#tab-merged" type="button" role="tab">ตารางรวม (แผนก)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-patients-btn" data-bs-toggle="tab" data-bs-target="#tab-patients" type="button" role="tab">ผู้ป่วยปัจจุบัน</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-adm-btn" data-bs-toggle="tab" data-bs-target="#tab-adm" type="button" role="tab">รับใหม่</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-dc-btn" data-bs-toggle="tab" data-bs-target="#tab-dc" type="button" role="tab">จำหน่าย</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-move-btn" data-bs-toggle="tab" data-bs-target="#tab-move" type="button" role="tab">ย้ายเตียง</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-mapping-btn" data-bs-toggle="tab" data-bs-target="#tab-mapping" type="button" role="tab">ตรวจสอบ Map</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-json-btn" data-bs-toggle="tab" data-bs-target="#tab-json" type="button" role="tab">JSON</button>
                    </li>
                </ul>
                <div class="tab-content" id="hosxpDetailTabContent">
                    <div class="tab-pane fade show active" id="tab-merged" role="tabpanel"></div>
                    <div class="tab-pane fade" id="tab-patients" role="tabpanel"></div>
                    <div class="tab-pane fade" id="tab-adm" role="tabpanel"></div>
                    <div class="tab-pane fade" id="tab-dc" role="tabpanel"></div>
                    <div class="tab-pane fade" id="tab-move" role="tabpanel"></div>
                    <div class="tab-pane fade" id="tab-mapping" role="tabpanel"></div>
                    <div class="tab-pane fade" id="tab-json" role="tabpanel">
                        <pre id="payload-json" class="hosxp-payload-pre"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btn-copy-json">คัดลอก JSON</button>
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
    let payloadModalInstance = null;

    if (payloadModal) {
        document.body.appendChild(payloadModal);
    }

    function getPayloadModal() {
        if (!payloadModalInstance && payloadModal) {
            payloadModalInstance = bootstrap.Modal.getOrCreateInstance(payloadModal, {
                backdrop: true,
                keyboard: true,
                focus: true,
            });
            payloadModal.addEventListener('hidden.bs.modal', () => {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            });
        }
        return payloadModalInstance;
    }

    function resetDetailTabs() {
        document.querySelectorAll('#hosxpDetailTabs .nav-link').forEach(el => {
            el.classList.remove('active');
            el.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('#hosxpDetailTabContent .tab-pane').forEach(el => {
            el.classList.remove('show', 'active');
        });
        const firstBtn = document.getElementById('tab-merged-btn');
        const firstPane = document.getElementById('tab-merged');
        if (firstBtn) {
            firstBtn.classList.add('active');
            firstBtn.setAttribute('aria-selected', 'true');
        }
        if (firstPane) {
            firstPane.classList.add('show', 'active');
        }
    }

    function closePayloadModal() {
        const inst = getPayloadModal();
        if (inst) inst.hide();
    }

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
                    <button type="button" class="btn btn-sm btn-outline-primary btn-view-payload" data-id="${r.id}">ดูตาราง</button>
                </td>
            </tr>`;
        }).join('');
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function tableWrap(html) {
        return `<div class="table-responsive">${html}</div>`;
    }

    function renderMergedTable(rows) {
        if (!rows.length) {
            return '<p class="text-muted small mb-0">ไม่มีแถวข้อมูลในรอบนี้</p>';
        }
        const head = `<thead><tr>
            <th>รหัส ward</th><th>ward_name</th><th>กลุ่ม</th>
            <th class="text-end">ผู้ป่วย</th><th class="text-end">รับใหม่</th><th class="text-end">จำหน่าย</th>
            <th class="text-end">เสียชีวิต</th><th class="text-end">ย้ายเข้า</th><th class="text-end">ย้ายออก</th>
        </tr></thead>`;
        const body = rows.map(r => `<tr>
            <td>${escapeHtml(r.ward)}</td>
            <td>${escapeHtml(r.ward_name)}</td>
            <td class="text-muted">${escapeHtml(r.ward_name_ward)}</td>
            <td class="text-end fw-semibold">${r.patient_count ?? 0}</td>
            <td class="text-end">${r.admissions_today ?? 0}</td>
            <td class="text-end">${r.discharges_today ?? 0}</td>
            <td class="text-end">${r.deaths_today ?? 0}</td>
            <td class="text-end">${r.moves_in_today ?? 0}</td>
            <td class="text-end">${r.moves_out_today ?? 0}</td>
        </tr>`).join('');
        return tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">${head}<tbody>${body}</tbody></table>`);
    }

    function renderSimpleEndpoint(rows, valueKey, valueLabel) {
        if (!rows.length) return '<p class="text-muted small mb-0">ไม่มีข้อมูล</p>';
        const head = `<thead><tr><th>รหัส</th><th>ward_name</th><th>กลุ่ม</th><th class="text-end">${escapeHtml(valueLabel)}</th></tr></thead>`;
        const body = rows.map(r => `<tr>
            <td>${escapeHtml(r.ward)}</td><td>${escapeHtml(r.ward_name)}</td>
            <td class="text-muted">${escapeHtml(r.ward_name_ward)}</td>
            <td class="text-end fw-semibold">${r[valueKey] ?? r.value ?? 0}</td>
        </tr>`).join('');
        return tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">${head}<tbody>${body}</tbody></table>`);
    }

    function renderDischargeTable(rows) {
        if (!rows.length) return '<p class="text-muted small mb-0">ไม่มีข้อมูล</p>';
        const head = `<thead><tr><th>รหัส</th><th>ward_name</th><th class="text-end">จำหน่าย</th><th class="text-end">เสียชีวิต</th></tr></thead>`;
        const body = rows.map(r => `<tr>
            <td>${escapeHtml(r.ward)}</td><td>${escapeHtml(r.ward_name)}</td>
            <td class="text-end">${r.discharges ?? 0}</td><td class="text-end">${r.deaths ?? 0}</td>
        </tr>`).join('');
        return tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">${head}<tbody>${body}</tbody></table>`);
    }

    function renderMoveTable(rows) {
        if (!rows.length) return '<p class="text-muted small mb-0">ไม่มีข้อมูล</p>';
        const head = `<thead><tr><th>รหัส</th><th>ward_name</th><th class="text-end">ย้ายเข้า</th><th class="text-end">ย้ายออก</th></tr></thead>`;
        const body = rows.map(r => `<tr>
            <td>${escapeHtml(r.ward)}</td><td>${escapeHtml(r.ward_name)}</td>
            <td class="text-end">${r.moves_in ?? 0}</td><td class="text-end">${r.moves_out ?? 0}</td>
        </tr>`).join('');
        return tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">${head}<tbody>${body}</tbody></table>`);
    }

    function mapStatusBadge(label, statusKey) {
        const cls = {
            matched: 'map-ok',
            name_mismatch: 'map-warn',
            ambiguous: 'map-ambig',
            unmapped: 'map-bad',
        }[statusKey] || 'bg-secondary';
        return `<span class="badge ${cls}">${escapeHtml(label)}</span>`;
    }

    function renderMappingPanel(mapping) {
        if (!mapping || !mapping.summary) {
            return '<p class="text-muted small mb-0">ไม่มีข้อมูล mapping</p>';
        }
        const s = mapping.summary;
        const summaryHtml = `
            <div class="hosxp-map-summary d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-light text-dark border">API ${s.api_total} แผนก</span>
                <span class="badge map-ok">จับคู่แล้ว ${s.matched}</span>
                <span class="badge map-bad">API ยังไม่ map ${s.unmapped_api}</span>
                <span class="badge map-ambig">รหัสซ้ำ ${s.ambiguous_api}</span>
                <span class="badge map-warn">ชื่อไม่ตรง ${s.name_mismatch_api}</span>
                <span class="badge bg-light text-dark border">แผนกในระบบ ${s.db_active}</span>
                <span class="badge map-warn">ยังไม่ตั้งค่า API ${s.db_missing_config}</span>
                <span class="badge bg-secondary">ไม่พบใน API รอบนี้ ${s.db_not_in_api}</span>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <a href="${wardsListUrl}" class="btn btn-sm btn-outline-primary">จัดการ Ward ทั้งหมด</a>
            </div>
            <p class="small text-muted mb-2">ใช้ logic เดียวกับ cron ดึงข้อมูล: จับคู่ด้วย <code>api_ward_name</code> ก่อน แล้วรหัสเมื่อมีแผนกเดียว (เช่น ward 08 ต้องระบุชื่อย่อย)</p>`;

        const apiRows = mapping.api_rows || [];
        let apiTable = '<p class="text-muted small">ไม่มีแถวจาก API</p>';
        if (apiRows.length) {
            const head = `<thead><tr>
                <th>สถานะ</th><th>รหัส API</th><th>ward_name</th><th>กลุ่ม (ward_name_ward)</th>
                <th>แผนกในระบบ</th><th>api ที่ตั้งไว้</th><th class="text-end">ผู้ป่วย</th><th>หมายเหตุ</th><th></th>
            </tr></thead>`;
            const body = apiRows.map(r => {
                const dbLabel = r.ward_id
                    ? `[${r.ward_id}] ${escapeHtml(r.ward_name_db)}`
                    : '<span class="text-danger">—</span>';
                const configured = r.ward_id
                    ? `${escapeHtml(r.api_ward_code_db)}/${escapeHtml(r.api_ward_name_db)}`
                    : '—';
                const rowClass = r.status === 'unmapped' || r.status === 'ambiguous' ? 'table-warning' : '';
                const action = r.ward_id
                    ? `<a href="${wardEditBase}${r.ward_id}" class="btn btn-sm btn-outline-primary">แก้ไข</a>`
                    : `<a href="${wardsListUrl}" class="btn btn-sm btn-outline-secondary">ตั้งค่า</a>`;
                return `<tr class="${rowClass}">
                    <td>${mapStatusBadge(r.status_label || r.status, r.status)}</td>
                    <td>${escapeHtml(r.ward)}</td>
                    <td>${escapeHtml(r.ward_name)}</td>
                    <td class="text-muted">${escapeHtml(r.ward_name_ward || '—')}</td>
                    <td>${dbLabel}</td>
                    <td class="small">${configured}</td>
                    <td class="text-end">${r.patient_count ?? 0}</td>
                    <td class="small text-muted">${escapeHtml(r.note)}</td>
                    <td class="text-nowrap">${action}</td>
                </tr>`;
            }).join('');
            apiTable = tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">${head}<tbody>${body}</tbody></table>`);
        }

        const dbIssues = (mapping.db_issues || []).filter(i => i.issue === 'missing_config');
        const dbNotInApi = (mapping.db_issues || []).filter(i => i.issue === 'not_in_api');
        let dbSection = '';
        if (dbIssues.length) {
            const rows = dbIssues.map(w => `<tr>
                <td>[${w.ward_id}] ${escapeHtml(w.name)}</td>
                <td>${escapeHtml(w.code)}</td>
                <td class="text-danger">${escapeHtml(w.api_ward_code) || '—'} / ${escapeHtml(w.api_ward_name) || '—'}</td>
                <td><a href="${wardEditBase}${w.ward_id}" class="btn btn-sm btn-outline-primary">แก้ไข</a></td>
            </tr>`).join('');
            dbSection += `<h6 class="mt-3 mb-2">แผนกในระบบที่ยังไม่ตั้งค่า mapping</h6>
                ${tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">
                <thead><tr><th>แผนก</th><th>รหัส</th><th>api_ward_code / api_ward_name</th><th></th></tr></thead>
                <tbody>${rows}</tbody></table>`)}`;
        }
        if (dbNotInApi.length) {
            const rows = dbNotInApi.map(w => `<tr>
                <td>[${w.ward_id}] ${escapeHtml(w.name)}</td>
                <td>${escapeHtml(w.api_ward_code)} / ${escapeHtml(w.api_ward_name)}</td>
                <td><a href="${wardEditBase}${w.ward_id}" class="btn btn-sm btn-outline-secondary">แก้ไข</a></td>
            </tr>`).join('');
            dbSection += `<h6 class="mt-3 mb-2">แผนกที่ตั้งค่าแล้วแต่ไม่ปรากฏใน API รอบนี้</h6>
                <p class="small text-muted mb-1">อาจไม่มีผู้ป่วยหรือไม่มีการเคลื่อนไหว — ไม่จำเป็นว่า map ผิด</p>
                ${tableWrap(`<table class="table table-sm table-bordered hosxp-detail-table mb-0">
                <thead><tr><th>แผนก</th><th>API ที่ตั้ง</th><th></th></tr></thead>
                <tbody>${rows}</tbody></table>`)}`;
        }

        return summaryHtml + '<h6 class="mb-2">Ward จาก HOSxP → แผนกในระบบ</h6>' + apiTable + dbSection;
    }

    function renderDetailTables(tables, mapping) {
        const ep = tables.endpoints || {};
        document.getElementById('tab-merged').innerHTML = renderMergedTable(tables.merged || []);
        document.getElementById('tab-patients').innerHTML = renderSimpleEndpoint(ep['current-patients'] || [], 'value', 'จำนวนผู้ป่วย');
        document.getElementById('tab-adm').innerHTML = renderSimpleEndpoint(ep['admissions-today'] || [], 'value', 'รับใหม่วันนี้');
        document.getElementById('tab-dc').innerHTML = renderDischargeTable(ep['discharges-today'] || []);
        document.getElementById('tab-move').innerHTML = renderMoveTable(ep['bed-moves-today'] || []);
        document.getElementById('tab-mapping').innerHTML = renderMappingPanel(mapping);
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
            renderDetailTables(json.tables || { merged: [], endpoints: {} }, json.mapping);
            lastJsonText = JSON.stringify(json.payload, null, 2);
            payloadPre.textContent = lastJsonText;
            document.getElementById('btn-copy-json').classList.remove('d-none');
            resetDetailTabs();
            document.body.appendChild(payloadModal);
            getPayloadModal().show();
        } catch (err) {
            alert(err.message || 'โหลด JSON ไม่สำเร็จ');
        } finally {
            btn.disabled = false;
        }
    });

    payloadModal?.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closePayloadModal();
        });
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

    const wardEditBase = <?= json_encode(rtrim(base_url('admin/wards/edit'), '/') . '/') ?>;
    const wardsListUrl = <?= json_encode(base_url('admin/wards')) ?>;

    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('date_to').value = today;
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    document.getElementById('date_from').value = weekAgo.toISOString().slice(0, 10);

    loadLogs();
})();
</script>
<?= $this->endSection() ?>
