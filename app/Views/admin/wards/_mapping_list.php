<?php
/** @var array{rows: list<array>, unmapped_api: list<array>, counts: array} $mapping_list */
/** @var string|null $api_snapshot_at */

$list = $mapping_list ?? ['rows' => [], 'unmapped_api' => [], 'counts' => ['rows' => 0, 'issues' => 0, 'unmapped_api' => 0]];
$rows = $list['rows'] ?? [];
$unmapped = $list['unmapped_api'] ?? [];
$counts = $list['counts'] ?? ['issues' => 0, 'unmapped_api' => 0];

$snapshotLabel = '—';
if (! empty($api_snapshot_at)) {
    try {
        $snapshotLabel = (new DateTime((string) $api_snapshot_at))->format('d/m/Y H:i');
    } catch (Exception) {
        $snapshotLabel = (string) $api_snapshot_at;
    }
}
?>
<div class="card shadow-sm ward-map-list-card mb-3">
    <div class="ward-map-list-toolbar">
        <div class="ward-map-search-wrap">
            <span class="material-symbols-outlined ward-map-search-icon" aria-hidden="true">search</span>
            <input type="search" id="ward-map-search" class="form-control"
                   placeholder="ค้นหาแผนกหรือชื่อ API…" autocomplete="off"
                   aria-label="ค้นหาแผนกหรือชื่อ API">
        </div>
        <div class="ward-map-filter-group" role="group" aria-label="กรองสถานะ">
            <input type="radio" class="btn-check" name="ward-map-filter" id="wf-all" value="all" checked>
            <label class="btn btn-sm btn-outline-secondary" for="wf-all">ทั้งหมด</label>
            <input type="radio" class="btn-check" name="ward-map-filter" id="wf-issues" value="issues">
            <label class="btn btn-sm btn-outline-danger" for="wf-issues">
                ต้องแก้ <span class="badge rounded-pill bg-danger"><?= (int) $counts['issues'] ?></span>
            </label>
            <input type="radio" class="btn-check" name="ward-map-filter" id="wf-unmapped" value="unmapped">
            <label class="btn btn-sm btn-outline-warning" for="wf-unmapped">
                API ค้าง <span class="badge rounded-pill bg-warning text-dark"><?= (int) $counts['unmapped_api'] ?></span>
            </label>
            <input type="radio" class="btn-check" name="ward-map-filter" id="wf-ok" value="ok">
            <label class="btn btn-sm btn-outline-success" for="wf-ok">เชื่อมแล้ว</label>
        </div>
        <?php if ($snapshotLabel !== '—'): ?>
            <span class="ward-map-snapshot text-muted small">
                <span class="material-symbols-outlined align-middle" style="font-size:1rem;" aria-hidden="true">schedule</span>
                API <?= esc($snapshotLabel) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="ward-map-list-body" id="ward-map-list-body">
        <?php if ($rows === []): ?>
            <p class="text-muted text-center py-5 mb-0">ไม่มีแผนกในระบบ</p>
        <?php else: ?>
            <p class="ward-map-board-hint small text-muted mb-0 px-3 pt-2">
                กด <strong>จัดการ</strong> ในแต่ละแถวเพื่อเลือกรหัส API และติ๊กชื่อที่ต้องการรวมยอด — บันทึกได้ในหน้านี้เลย
            </p>
            <div class="ward-map-board" role="table" aria-label="รายการจับคู่แผนกกับ API">
                <div class="ward-map-board-head" role="row">
                    <div role="columnheader">แผนกในระบบ</div>
                    <div class="ward-map-board-head-mid" aria-hidden="true"></div>
                    <div role="columnheader">ชื่อ API — รวมได้หลายชื่อ</div>
                    <div role="columnheader">สถานะ</div>
                    <div role="columnheader"><span class="visually-hidden">แก้ไข</span></div>
                </div>

                <div class="ward-map-board-body">
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $db     = $row['db'];
                        $apis   = $row['apis'];
                        $status = (string) ($db['status'] ?? 'missing');
                        $search = mb_strtolower(implode(' ', array_filter([
                            $db['name'] ?? '',
                            $db['code'] ?? '',
                            $db['department'] ?? '',
                            $db['api_code'] ?? '',
                            implode(' ', $db['mapped_names'] ?? []),
                            implode(' ', array_column($apis, 'name')),
                        ])));
                        $isIssue = in_array($status, ['missing', 'duplicate', 'not_in_snapshot'], true);
                        $isOk    = $status === 'ok' && $apis !== [];
                        $badgeClass = match ($status) {
                            'ok', 'not_in_snapshot' => 'ward-map-ok',
                            'duplicate'             => 'ward-map-duplicate',
                            default                 => 'ward-map-missing',
                        };
                        ?>
                        <div class="ward-map-row status-<?= esc($status) ?>"
                             role="row"
                             data-ward-id="<?= (int) $db['id'] ?>"
                             data-ward-name="<?= esc($db['name']) ?>"
                             data-api-code="<?= esc($db['api_code'] ?? '') ?>"
                             data-selected-names="<?= esc(json_encode($db['mapped_names'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
                             data-status="<?= esc($status) ?>"
                             data-issue="<?= $isIssue ? '1' : '0' ?>"
                             data-ok="<?= $isOk ? '1' : '0' ?>"
                             data-search="<?= esc($search) ?>">
                            <div class="ward-map-cell ward-map-cell--db" role="cell">
                                <div class="ward-map-db-name">
                                    <?= esc($db['name']) ?>
                                    <?php if (count($apis) > 1): ?>
                                        <span class="ward-map-multi-badge" title="รวม <?= count($apis) ?> ชื่อ API">
                                            <?= count($apis) ?> ชื่อ
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="ward-map-db-meta">
                                    <?php if (! empty($db['code'])): ?>
                                        <span class="ward-map-tag"><?= esc($db['code']) ?></span>
                                    <?php endif; ?>
                                    <?php if (! empty($db['department'])): ?>
                                        <span class="ward-map-tag ward-map-tag--muted"><?= esc($db['department']) ?></span>
                                    <?php endif; ?>
                                    <?php if (! ($db['is_active'] ?? true)): ?>
                                        <span class="ward-map-tag ward-map-tag--off">ไม่ใช้งาน</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ward-map-cell ward-map-cell--link" aria-hidden="true">
                                <span class="material-symbols-outlined">link</span>
                            </div>

                            <div class="ward-map-cell ward-map-cell--api" role="cell">
                                <?php if ($apis === []): ?>
                                    <span class="ward-map-api-empty">— ยังไม่เชื่อม —</span>
                                <?php else: ?>
                                    <ul class="ward-map-api-list list-unstyled mb-0">
                                        <?php foreach ($apis as $api): ?>
                                            <?php
                                            $chipClass = match ($api['link_status'] ?? 'ok') {
                                                'duplicate' => 'is-dup',
                                                'ghost'     => 'is-ghost',
                                                default     => 'is-ok',
                                            };
                                            $chipTitle = ! empty($api['ghost'])
                                                ? 'ตั้งในระบบแล้ว แต่ไม่พบใน API ล่าสุด'
                                                : (! empty($api['group']) ? 'กลุ่ม: ' . $api['group'] : '');
                                            ?>
                                            <li class="ward-map-api-item <?= esc($chipClass) ?>"
                                                <?php if ($chipTitle !== ''): ?>title="<?= esc($chipTitle) ?>"<?php endif; ?>>
                                                <span class="ward-map-api-item-head">
                                                    <span class="ward-map-api-item-name"><?= esc($api['name']) ?></span>
                                                    <?php if (! empty($api['is_primary'])): ?>
                                                        <span class="ward-map-api-role">หลัก</span>
                                                    <?php elseif (count($apis) > 1): ?>
                                                        <span class="ward-map-api-role ward-map-api-role--merge">รวมยอด</span>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="ward-map-api-item-code"><?= esc($api['code']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>

                            <div class="ward-map-cell ward-map-cell--status" role="cell">
                                <span class="ward-map-status-badge <?= esc($badgeClass) ?>">
                                    <?= esc($db['status_label'] ?? '') ?>
                                </span>
                            </div>

                            <div class="ward-map-cell ward-map-cell--action" role="cell">
                                <button type="button"
                                        class="btn btn-sm btn-primary ward-map-edit-btn ward-map-inline-open"
                                        aria-label="ตั้งค่า mapping สำหรับ <?= esc($db['name']) ?>"
                                        aria-expanded="false">
                                    <span class="material-symbols-outlined" aria-hidden="true">tune</span>
                                    <span class="ward-map-edit-label">จัดการ</span>
                                </button>
                            </div>

                            <div class="ward-map-row-editor d-none" aria-hidden="true">
                                <?php if (($api_options_count ?? 0) > 0): ?>
                                    <form class="ward-map-inline-form">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-lg-3">
                                                <label class="form-label small fw-semibold mb-1">รหัส API (HOSxP)</label>
                                                <select class="form-select form-select-sm ward-map-code-select" aria-label="รหัส API"></select>
                                                <div class="ward-map-code-hint small text-muted mt-1 d-none"></div>
                                            </div>
                                            <div class="col-lg-9">
                                                <label class="form-label small fw-semibold mb-1">ชื่อ API — ติ๊กได้หลายชื่อ</label>
                                                <div class="ward-map-names-panel"></div>
                                            </div>
                                        </div>
                                        <div class="ward-map-inline-actions mt-3">
                                            <span class="ward-map-inline-msg small"></span>
                                            <div class="ward-map-inline-btns">
                                                <button type="submit" class="btn btn-sm btn-primary ward-map-inline-save">บันทึก</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary ward-map-inline-cancel">ยกเลิก</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger ward-map-inline-clear">ล้าง mapping</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <p class="small text-muted mb-0">ยังไม่มีข้อมูล API — รอ cron ดึง HOSxP หรือดูที่บันทึกดิบ HOSxP</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($unmapped !== []): ?>
            <div class="ward-map-unmapped-section" id="ward-map-unmapped-section">
                <h2 class="h6 mb-1 d-flex align-items-center gap-1">
                    <span class="material-symbols-outlined text-warning" style="font-size:1.25rem;" aria-hidden="true">warning</span>
                    API ยังไม่ map (<?= count($unmapped) ?>)
                </h2>
                <p class="small text-muted mb-3">กด map เพื่อเปิดตัวแก้ไขที่แผนกที่แนะนำ</p>
                <div class="ward-map-unmapped-table-wrap">
                    <table class="table table-sm ward-map-unmapped-table mb-0">
                        <thead>
                            <tr>
                                <th>ชื่อ API</th>
                                <th>รหัส</th>
                                <th>แนะนำแผนก</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unmapped as $api): ?>
                                <?php $searchApi = mb_strtolower(implode(' ', [$api['name'], $api['code'], $api['group'] ?? ''])); ?>
                                <tr class="ward-map-unmapped-row" data-search="<?= esc($searchApi) ?>">
                                    <td class="fw-semibold"><?= esc($api['name']) ?></td>
                                    <td><code><?= esc($api['code']) ?></code></td>
                                    <td>
                                        <?php if (! empty($api['suggest_ward_id'])): ?>
                                            <?= esc($api['suggest_ward_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?php if (! empty($api['suggest_ward_id'])): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-primary ward-map-quick-map"
                                                    data-ward-id="<?= (int) $api['suggest_ward_id'] ?>"
                                                    data-api-code="<?= esc($api['code'], 'attr') ?>"
                                                    data-api-name="<?= esc($api['name'], 'attr') ?>">
                                                map
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <p id="ward-map-no-results" class="text-center text-muted py-5 mb-0 d-none">ไม่พบรายการตามตัวกรอง</p>
    </div>
</div>

<script>
window.WARD_MAP_INLINE = {
    namesByCode: <?= json_encode($api_names_by_code ?? [], JSON_UNESCAPED_UNICODE) ?>,
    usedNameToWard: <?= json_encode($used_name_to_ward ?? [], JSON_UNESCAPED_UNICODE) ?>,
    saveUrlBase: <?= json_encode(base_url('admin/wards/api-mapping/')) ?>,
    csrfName: <?= json_encode(csrf_token()) ?>,
    csrfHash: <?= json_encode(csrf_hash()) ?>
};
</script>
<script src="<?= asset_url('js/ward_mapping_inline.js') ?>"></script>
<script>
(function () {
    const search = document.getElementById('ward-map-search');
    const filters = document.querySelectorAll('input[name="ward-map-filter"]');
    const unmappedSection = document.getElementById('ward-map-unmapped-section');
    const noResults = document.getElementById('ward-map-no-results');
    const board = document.querySelector('.ward-map-board');

    function currentFilter() {
        const checked = document.querySelector('input[name="ward-map-filter"]:checked');
        return checked ? checked.value : 'all';
    }

    function apply() {
        const rows = document.querySelectorAll('.ward-map-row');
        const unmappedRows = document.querySelectorAll('.ward-map-unmapped-row');
        const q = (search?.value || '').trim().toLowerCase();
        const mode = currentFilter();
        let visibleRows = 0;

        rows.forEach(function (el) {
            const hay = el.dataset.search || '';
            const matchQ = !q || hay.includes(q);
            let matchF = true;
            if (mode === 'issues') {
                matchF = el.dataset.issue === '1';
            } else if (mode === 'ok') {
                matchF = el.dataset.ok === '1';
            } else if (mode === 'unmapped') {
                matchF = false;
            }
            const show = matchQ && matchF;
            el.classList.toggle('d-none', !show);
            if (show) {
                visibleRows++;
            }
        });

        let showUnmapped = mode === 'all' || mode === 'unmapped';
        if (showUnmapped && unmappedSection) {
            let any = false;
            unmappedRows.forEach(function (el) {
                const hay = el.dataset.search || '';
                const show = !q || hay.includes(q);
                el.classList.toggle('d-none', !show);
                if (show) {
                    any = true;
                }
            });
            unmappedSection.classList.toggle('d-none', !any);
            showUnmapped = any;
        } else if (unmappedSection) {
            unmappedSection.classList.add('d-none');
        }

        board?.classList.toggle('d-none', visibleRows === 0 && mode !== 'unmapped');
        noResults?.classList.toggle('d-none', visibleRows > 0 || showUnmapped);
    }

    window.wardMapFilterRefresh = apply;

    search?.addEventListener('input', apply);
    filters.forEach(function (f) {
        f.addEventListener('change', apply);
    });
    apply();
})();
</script>
