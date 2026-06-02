<?php
/** @var array{db_nodes: list<array>, api_nodes: list<array>, links: list<array>} $mapping_graph */
/** @var string|null $api_snapshot_at */

$graph = $mapping_graph ?? ['db_nodes' => [], 'api_nodes' => [], 'links' => []];
$dbNodes  = $graph['db_nodes'] ?? [];
$apiNodes = $graph['api_nodes'] ?? [];
$links    = $graph['links'] ?? [];
$hasApi   = $apiNodes !== [];

$snapshotLabel = '—';
if (! empty($api_snapshot_at)) {
    try {
        $snapshotLabel = (new DateTime((string) $api_snapshot_at))->format('d/m/Y H:i');
    } catch (Exception) {
        $snapshotLabel = (string) $api_snapshot_at;
    }
}
?>
<div class="card shadow-sm ward-map-graph-card mb-3">
    <div class="alert alert-light border small py-2 mx-3 mt-3 mb-0">
        แนะนำใช้แท็บ <strong>รายการจับคู่</strong> สำหรับดูและตั้งค่า mapping ในชีวิตจริง — แผนภาพนี้เหมาะเมื่อต้องการดูภาพรวมทีละแผนก
    </div>
    <div class="ward-map-graph-toolbar">
        <span class="small fw-semibold text-muted">แผนภาพการเชื่อมโยง</span>
        <?php if ($hasApi && $dbNodes !== []): ?>
            <select id="ward-map-graph-filter" class="form-select form-select-sm" style="max-width: 220px;">
                <option value="">ทุกแผนก (เส้นอาจทับกัน)</option>
                <?php foreach ($dbNodes as $node): ?>
                    <option value="<?= (int) $node['id'] ?>"><?= esc($node['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <div class="ward-map-graph-legend">
            <span class="ward-map-legend-item"><span class="ward-map-legend-line"></span> map แล้ว</span>
            <span class="ward-map-legend-item"><span class="ward-map-legend-line is-dup"></span> ชื่อ API ซ้ำ</span>
            <span class="ward-map-legend-item"><span class="ward-map-legend-line is-miss"></span> ยังไม่ map</span>
        </div>
        <?php if ($hasApi): ?>
            <span class="small text-muted ms-auto">ข้อมูล API ล่าสุด: <?= esc($snapshotLabel) ?></span>
        <?php endif; ?>
    </div>

    <?php if (! $hasApi): ?>
        <div class="ward-map-empty-api">
            <p class="mb-2">ยังไม่มี snapshot จาก HOSxP API — ไม่สามารถวาดเส้นเชื่อมได้</p>
            <p class="small mb-0">
                รอ cron ดึงข้อมูล หรือดูที่
                <a href="<?= base_url('admin/hosxp-logs') ?>">บันทึกดิบ HOSxP</a>
                แล้วกลับมาหน้านี้
            </p>
        </div>
    <?php else: ?>
        <div class="ward-map-canvas-wrap" id="ward-map-scroll">
            <div class="ward-map-canvas" id="ward-map-canvas">
                <svg class="ward-map-lines" id="ward-map-lines" aria-hidden="true"></svg>

                <div class="ward-map-col ward-map-col--db">
                    <div class="ward-map-col-head">ฐานข้อมูล (Ward ในระบบ)</div>
                    <?php foreach ($dbNodes as $node): ?>
                        <?php
                        $status = (string) ($node['status'] ?? 'missing');
                        $names  = $node['mapped_names'] ?? [];
                        ?>
                        <div class="ward-map-node ward-map-node--db status-<?= esc($status) ?>"
                             id="db-node-<?= (int) $node['id'] ?>"
                             data-db-id="<?= (int) $node['id'] ?>">
                            <div class="ward-map-node-title"><?= esc($node['name']) ?></div>
                            <div class="ward-map-node-meta">
                                <?php if (! empty($node['code'])): ?>
                                    <code><?= esc($node['code']) ?></code>
                                <?php endif; ?>
                                <?php if (! empty($node['department'])): ?>
                                    · <?= esc($node['department']) ?>
                                <?php endif; ?>
                                <?php if (! ($node['is_active'] ?? true)): ?>
                                    · <span class="text-secondary">ไม่ใช้งาน</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($names !== []): ?>
                                <div class="ward-map-node-meta">
                                    API: <?= esc(implode(', ', $names)) ?>
                                </div>
                            <?php endif; ?>
                            <span class="ward-map-node-badge ward-map-<?= $status === 'ok' || $status === 'not_in_snapshot' ? 'ok' : ($status === 'duplicate' ? 'duplicate' : 'missing') ?>">
                                <?= esc($node['status_label'] ?? '') ?>
                            </span>
                            <div class="ward-map-node-actions">
                                <a href="<?= base_url('admin/wards/edit/' . $node['id']) ?>">แก้ไข mapping</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="ward-map-spacer" aria-hidden="true"></div>

                <div class="ward-map-col ward-map-col--api">
                    <div class="ward-map-col-head">HOSxP API</div>
                    <?php foreach ($apiNodes as $node): ?>
                        <?php $mapped = ! empty($node['mapped']); ?>
                        <div class="ward-map-node ward-map-node--api <?= $mapped ? 'is-mapped' : 'is-unmapped' ?>"
                             id="<?= esc($node['key']) ?>"
                             data-api-key="<?= esc($node['key']) ?>">
                            <div class="ward-map-node-title"><?= esc($node['name']) ?></div>
                            <div class="ward-map-node-meta">
                                รหัส <code><?= esc($node['code']) ?></code>
                                <?php if (! empty($node['group'])): ?>
                                    · <?= esc($node['group']) ?>
                                <?php endif; ?>
                            </div>
                            <?php if (! $mapped): ?>
                                <span class="ward-map-node-badge ward-map-missing">ยังไม่ map</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($hasApi): ?>
<script>
(function () {
    const allLinks = <?= json_encode($links, JSON_UNESCAPED_UNICODE) ?>;
    const scrollEl = document.getElementById('ward-map-scroll');
    const canvas = document.getElementById('ward-map-canvas');
    const svg = document.getElementById('ward-map-lines');
    const graphFilter = document.getElementById('ward-map-graph-filter');
    if (!scrollEl || !canvas || !svg) {
        return;
    }

    let raf = 0;
    let links = allLinks.slice();

    function applyGraphFilter() {
        const wardId = graphFilter?.value || '';
        if (!wardId) {
            links = allLinks.slice();
            canvas.querySelectorAll('.ward-map-node').forEach(function (n) {
                n.classList.remove('d-none', 'is-dim');
            });
        } else {
            links = allLinks.filter(function (l) { return String(l.db_id) === wardId; });
            canvas.querySelectorAll('.ward-map-node--db').forEach(function (n) {
                const show = n.dataset.dbId === wardId;
                n.classList.toggle('d-none', !show);
                n.classList.remove('is-dim');
            });
            const apiKeys = {};
            links.forEach(function (l) { apiKeys[l.api_key] = true; });
            canvas.querySelectorAll('.ward-map-node--api').forEach(function (n) {
                const show = !!apiKeys[n.dataset.apiKey];
                n.classList.toggle('d-none', !show);
                n.classList.remove('is-dim');
            });
        }
        scheduleDraw();
    }

    graphFilter?.addEventListener('change', applyGraphFilter);

    function anchor(el, side) {
        const c = canvas.getBoundingClientRect();
        const r = el.getBoundingClientRect();
        const y = r.top + r.height / 2 - c.top;
        const x = side === 'left'
            ? r.right - c.left
            : r.left - c.left;
        return { x, y };
    }

    function draw() {
        const w = canvas.offsetWidth;
        const h = canvas.scrollHeight;
        svg.setAttribute('width', String(w));
        svg.setAttribute('height', String(h));
        svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
        svg.innerHTML = '';

        links.forEach(function (link) {
            const fromEl = document.getElementById('db-node-' + link.db_id);
            const toEl = document.getElementById(link.api_key);
            if (!fromEl || !toEl) {
                return;
            }
            const a = anchor(fromEl, 'left');
            const b = anchor(toEl, 'right');
            const dx = Math.max(40, (b.x - a.x) * 0.45);
            const d = 'M' + a.x + ',' + a.y +
                ' C' + (a.x + dx) + ',' + a.y +
                ' ' + (b.x - dx) + ',' + b.y +
                ' ' + b.x + ',' + b.y;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', d);
            path.setAttribute('class', link.status === 'duplicate' ? 'link-duplicate' : 'link-ok');
            path.dataset.dbId = String(link.db_id);
            path.dataset.apiKey = link.api_key;
            svg.appendChild(path);
        });
    }

    function scheduleDraw() {
        if (raf) {
            cancelAnimationFrame(raf);
        }
        raf = requestAnimationFrame(draw);
    }

    function highlightPair(dbId, apiKey, on) {
        const db = document.getElementById('db-node-' + dbId);
        const api = document.getElementById(apiKey);
        db?.classList.toggle('is-highlight', on);
        api?.classList.toggle('is-highlight', on);
        svg.querySelectorAll('path').forEach(function (p) {
            if (p.dataset.dbId === String(dbId) && p.dataset.apiKey === apiKey) {
                p.style.strokeOpacity = on ? '1' : '';
                p.style.strokeWidth = on ? '3' : '';
            }
        });
    }

    scrollEl.addEventListener('scroll', scheduleDraw, { passive: true });
    window.addEventListener('resize', scheduleDraw);

    function clearHighlight() {
        document.querySelectorAll('.ward-map-node.is-highlight').forEach(function (n) {
            n.classList.remove('is-highlight');
        });
        svg.querySelectorAll('path').forEach(function (p) {
            p.style.strokeOpacity = '';
            p.style.strokeWidth = '';
        });
    }

    canvas.querySelectorAll('.ward-map-node').forEach(function (node) {
        node.addEventListener('mouseenter', function () {
            clearHighlight();
            const dbId = node.dataset.dbId;
            const apiKey = node.dataset.apiKey;
            if (dbId) {
                links.filter(function (l) { return String(l.db_id) === dbId; }).forEach(function (l) {
                    highlightPair(l.db_id, l.api_key, true);
                });
            } else if (apiKey) {
                links.filter(function (l) { return l.api_key === apiKey; }).forEach(function (l) {
                    highlightPair(l.db_id, l.api_key, true);
                });
            }
        });
        node.addEventListener('mouseleave', clearHighlight);
    });

    scheduleDraw();
})();
</script>
<?php endif; ?>
