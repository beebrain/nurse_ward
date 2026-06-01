<?php
/** @var list<array{ward: string, ward_name: string, ward_name_ward?: string}> $api_ward_options */
$apiWardOptions = $api_ward_options ?? [];
$ward           = $ward ?? [];

$currentCode = trim((string) old('api_ward_code', $ward['api_ward_code'] ?? ''));
$currentName = trim((string) old('api_ward_name', $ward['api_ward_name'] ?? ''));

$wardAliases = $ward_aliases ?? [];
if ($posted = old('api_aliases')) {
    $wardAliases = is_array($posted) ? $posted : $wardAliases;
}
$usedApiNames = $used_api_names ?? [];

$namesByCode = [];
foreach ($apiWardOptions as $opt) {
    $code = trim((string) ($opt['ward'] ?? ''));
    $name = trim((string) ($opt['ward_name'] ?? ''));
    if ($code === '' || $name === '') {
        continue;
    }
    $namesByCode[$code][] = [
        'ward'           => $code,
        'ward_name'      => $name,
        'ward_name_ward' => trim((string) ($opt['ward_name_ward'] ?? '')),
    ];
}
ksort($namesByCode);

$groupLabelForCode = static function (array $names): string {
    $groups = [];
    foreach ($names as $n) {
        $g = trim((string) ($n['ward_name_ward'] ?? ''));
        if ($g !== '') {
            $groups[$g] = true;
        }
    }

    return implode(', ', array_keys($groups));
};

$codeInList = $currentCode !== '' && isset($namesByCode[$currentCode]);
$nameInList = false;
if ($codeInList) {
    foreach ($namesByCode[$currentCode] as $opt) {
        if ($opt['ward_name'] === $currentName) {
            $nameInList = true;
            break;
        }
    }
}
$useCustom = $apiWardOptions !== [] && $currentCode !== '' && (! $codeInList || ! $nameInList);
$uid       = 'api-map-' . uniqid();
?>
<style>
    .api-alias-list { max-height: 220px; overflow-y: auto; }
    .api-alias-item {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.55rem 0.65rem;
        margin-bottom: 0.35rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
    }
    .api-alias-item:hover { border-color: #0d6efd; background: #f8fbff; }
    .api-alias-item:has(input:checked) {
        border-color: #0d6efd;
        background: #eef4ff;
    }
    .api-alias-item input { width: 1.1rem; height: 1.1rem; margin-top: 0.15rem; flex-shrink: 0; cursor: pointer; }
    .api-alias-item .alias-title { font-weight: 600; font-size: 0.9rem; }
    .api-alias-item .alias-group { font-size: 0.8rem; color: #6c757d; }
</style>
<hr class="my-4">
<h5 class="mb-2 text-muted">การเชื่อมโยง HOSxP API</h5>

<?php if ($apiWardOptions !== []): ?>
<p class="small text-muted mb-3">
    <strong>ขั้นที่ 1</strong> เลือกรหัส ward จาก HOSxP (<?= count($namesByCode) ?> รหัส จาก <?= count($apiWardOptions) ?> ชื่อ)
    → <strong>ขั้นที่ 2</strong> เลือกชื่อหลัก และติ๊กชื่ออื่น<strong>รหัสเดียวกัน</strong>เพื่อรวมยอดตอนแสดงผล
</p>

<div id="<?= esc($uid) ?>-hosxp" class="<?= $useCustom ? 'd-none' : '' ?>">
    <div class="mb-3">
        <label for="<?= esc($uid) ?>-code-pick" class="form-label">API Ward Code <span class="text-danger">*</span></label>
        <select id="<?= esc($uid) ?>-code-pick" class="form-select">
            <option value="">— เลือกรหัส ward —</option>
            <?php foreach ($namesByCode as $code => $names):
                $group = $groupLabelForCode($names);
                $label = 'รหัส ' . $code;
                if ($group !== '') {
                    $label .= ' — ' . $group;
                }
                $label .= ' (' . count($names) . ' ชื่อ)';
                ?>
                <option value="<?= esc($code, 'attr') ?>" <?= $code === $currentCode ? 'selected' : '' ?>>
                    <?= esc($label) ?>
                </option>
            <?php endforeach; ?>
            <option value="__custom__" <?= $useCustom ? 'selected' : '' ?>>อื่นๆ — ไม่มีในรายการ</option>
        </select>
    </div>

    <input type="hidden" name="api_ward_code" id="<?= esc($uid) ?>-code" value="<?= esc($currentCode) ?>">

    <div id="<?= esc($uid) ?>-names-panel" class="<?= $currentCode === '' ? 'd-none' : '' ?>">
        <div id="<?= esc($uid) ?>-group-hint" class="alert alert-light border small py-2 mb-3 d-none"></div>
        <div class="mb-3">
            <label for="<?= esc($uid) ?>-primary-name" class="form-label">API Ward Name หลัก <span class="text-danger">*</span></label>
            <select id="<?= esc($uid) ?>-primary-name" name="api_ward_name"
                    class="form-select <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>">
                <option value="">— เลือกชื่อ —</option>
            </select>
            <?php if (session('errors.api_ward_name')): ?>
                <div class="invalid-feedback d-block"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <label class="form-label mb-0">ชื่อ API เพิ่มเติม <span class="text-muted fw-normal">(ติ๊กเพื่อรวมยอดตอนแสดงผล)</span></label>
                <span id="<?= esc($uid) ?>-alias-count" class="badge bg-secondary">เลือกแล้ว 0 ชื่อ</span>
            </div>
            <div id="<?= esc($uid) ?>-aliases-list" class="api-alias-list"></div>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="<?= esc($uid) ?>-alias-all">เลือกทั้งหมด</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= esc($uid) ?>-alias-none">ล้างทั้งหมด</button>
            </div>
            <div class="form-text mt-2">คลิกที่แถวหรือช่องติ๊กเพื่อเพิ่มชื่อ (รหัสเดียวกับชื่อหลัก — ไม่รวมชื่อหลัก)</div>
        </div>
    </div>
</div>

<div id="<?= esc($uid) ?>-manual" class="<?= $useCustom ? '' : 'd-none' ?>">
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="<?= esc($uid) ?>-code-manual" class="form-label">API Ward Code</label>
            <input type="text" name="api_ward_code" id="<?= esc($uid) ?>-code-manual"
                   class="form-control <?= session('errors.api_ward_code') ? 'is-invalid' : '' ?>"
                   value="<?= esc($useCustom ? $currentCode : '') ?>" placeholder="เช่น 08">
        </div>
        <div class="col-md-8">
            <label for="<?= esc($uid) ?>-name-manual" class="form-label">API Ward Name หลัก</label>
            <input type="text" name="api_ward_name" id="<?= esc($uid) ?>-name-manual"
                   class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
                   value="<?= esc($useCustom ? $currentName : '') ?>" placeholder="เช่น ศญ1_สามัญ">
        </div>
    </div>
    <p class="small text-muted">โหมดพิมพ์เอง — ไม่แสดงรายการจาก HOSxP</p>
</div>

<script>
(function () {
    const uid = <?= json_encode($uid) ?>;
    const namesByCode = <?= json_encode($namesByCode, JSON_UNESCAPED_UNICODE) ?>;
    const usedNames = <?= json_encode($usedApiNames, JSON_UNESCAPED_UNICODE) ?>;
    const initialCode = <?= json_encode($currentCode) ?>;
    const initialName = <?= json_encode($currentName) ?>;
    const initialAliases = <?= json_encode(array_values($wardAliases), JSON_UNESCAPED_UNICODE) ?>;

    const hosxp = document.getElementById(uid + '-hosxp');
    const manual = document.getElementById(uid + '-manual');
    const codePick = document.getElementById(uid + '-code-pick');
    const codeH = document.getElementById(uid + '-code');
    const codeManual = document.getElementById(uid + '-code-manual');
    const nameManual = document.getElementById(uid + '-name-manual');
    const namesPanel = document.getElementById(uid + '-names-panel');
    const primaryName = document.getElementById(uid + '-primary-name');
    const aliasesList = document.getElementById(uid + '-aliases-list');
    const aliasCount = document.getElementById(uid + '-alias-count');
    const btnAliasAll = document.getElementById(uid + '-alias-all');
    const btnAliasNone = document.getElementById(uid + '-alias-none');

    function isUsed(name) {
        return Object.prototype.hasOwnProperty.call(usedNames, name);
    }

    function namesForCode(code) {
        return namesByCode[code] || [];
    }

    function formatNameLabel(row) {
        const g = (row.ward_name_ward || '').trim();
        return g ? row.ward_name + ' · กลุ่ม: ' + g : row.ward_name;
    }

    function getSelectedAliases() {
        if (!aliasesList) return [];
        return Array.from(aliasesList.querySelectorAll('input[type="checkbox"]:checked')).map((cb) => cb.value);
    }

    function updateAliasCount() {
        if (!aliasCount) return;
        const n = getSelectedAliases().length;
        aliasCount.textContent = 'เลือกแล้ว ' + n + ' ชื่อ';
        aliasCount.className = 'badge ' + (n > 0 ? 'bg-primary' : 'bg-secondary');
    }

    function groupLabelsForCode(code) {
        const groups = new Set();
        namesForCode(code).forEach((row) => {
            const g = (row.ward_name_ward || '').trim();
            if (g) groups.add(g);
        });
        return Array.from(groups).join(', ');
    }

    function updateGroupHint(code) {
        const el = document.getElementById(uid + '-group-hint');
        if (!el) return;
        const groups = groupLabelsForCode(code);
        if (!code || !groups) {
            el.classList.add('d-none');
            return;
        }
        el.classList.remove('d-none');
        el.innerHTML = '<strong>กลุ่มวอร์ด (ward_name_ward):</strong> ' + escapeHtml(groups);
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function fillNameSelects(code) {
        if (!primaryName || !aliasesList) return;

        const names = namesForCode(code);
        const prevPrimary = primaryName.value || initialName;
        const prevAliases = getSelectedAliases();
        const aliasSelections = prevAliases.length ? prevAliases : initialAliases;

        primaryName.innerHTML = '<option value="">— เลือกชื่อ —</option>';
        aliasesList.innerHTML = '';

        let selectedPrimary = prevPrimary;
        if (!selectedPrimary && initialName && code === initialCode) {
            selectedPrimary = initialName;
        }

        names.forEach((row) => {
            const name = row.ward_name;
            if (isUsed(name)) return;

            const optPrimary = document.createElement('option');
            optPrimary.value = name;
            optPrimary.textContent = formatNameLabel(row);
            if (name === selectedPrimary) {
                optPrimary.selected = true;
            }
            primaryName.appendChild(optPrimary);
        });

        rebuildAliasOptions(code, selectedPrimary, aliasSelections);
    }

    function rebuildAliasOptions(code, primary, aliasSelections) {
        if (!aliasesList) return;
        aliasesList.innerHTML = '';
        let available = 0;

        namesForCode(code).forEach((row) => {
            const name = row.ward_name;
            if (isUsed(name) || name === primary) return;
            available++;

            const g = (row.ward_name_ward || '').trim();
            const label = document.createElement('label');
            label.className = 'api-alias-item';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'api_aliases[]';
            cb.value = name;
            if (aliasSelections.includes(name)) {
                cb.checked = true;
            }
            cb.addEventListener('change', updateAliasCount);

            const text = document.createElement('span');
            const title = document.createElement('span');
            title.className = 'alias-title';
            title.textContent = name;
            text.appendChild(title);
            if (g) {
                const sub = document.createElement('span');
                sub.className = 'alias-group d-block';
                sub.textContent = 'กลุ่ม: ' + g;
                text.appendChild(sub);
            }

            label.appendChild(cb);
            label.appendChild(text);

            aliasesList.appendChild(label);
        });

        if (available === 0) {
            aliasesList.innerHTML = '<p class="text-muted small mb-0 px-1">ไม่มีชื่อเพิ่มเติมในรหัสนี้ (หรือถูกใช้โดยแผนกอื่นแล้ว)</p>';
        }
        updateAliasCount();
    }

    btnAliasAll?.addEventListener('click', () => {
        aliasesList?.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = true; });
        updateAliasCount();
    });
    btnAliasNone?.addEventListener('click', () => {
        aliasesList?.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
        updateAliasCount();
    });

    function setHosxpMode(code) {
        if (codeH) codeH.value = code;
        if (namesPanel) {
            namesPanel.classList.toggle('d-none', !code);
        }
        updateGroupHint(code);
        if (code) fillNameSelects(code);
    }

    function toggleCustom(isCustom) {
        hosxp?.classList.toggle('d-none', isCustom);
        manual?.classList.toggle('d-none', !isCustom);
        if (isCustom) {
            if (codeH) codeH.removeAttribute('name');
            codeH && (codeH.disabled = true);
            primaryName?.removeAttribute('name');
        } else {
            if (codeH) {
                codeH.setAttribute('name', 'api_ward_code');
                codeH.disabled = false;
            }
            primaryName?.setAttribute('name', 'api_ward_name');
            if (nameManual) nameManual.removeAttribute('name');
            if (codeManual) codeManual.removeAttribute('name');
        }
    }

    codePick?.addEventListener('change', () => {
        const v = codePick.value;
        if (v === '__custom__') {
            toggleCustom(true);
            if (codeManual && codeH) codeManual.value = codeH.value;
            if (nameManual && primaryName) nameManual.value = primaryName.value;
            return;
        }
        toggleCustom(false);
        setHosxpMode(v);
    });

    primaryName?.addEventListener('change', () => {
        const code = codeH?.value || codePick?.value || '';
        rebuildAliasOptions(code, primaryName.value, []);
    });

    if (initialCode && !<?= $useCustom ? 'true' : 'false' ?>) {
        setHosxpMode(initialCode);
    }
})();
</script>

<?php else: ?>
<p class="small text-muted mb-3">
    ยังไม่มี log ดึง HOSxP สำเร็จ — พิมพ์เองหรือดูรายการที่
    <a href="<?= base_url('admin/hosxp-logs') ?>">บันทึกดิบ HOSxP</a>
</p>
<div class="row mb-3">
    <div class="col-md-4">
        <label for="api_ward_code" class="form-label">API Ward Code</label>
        <input type="text" name="api_ward_code" id="api_ward_code"
               class="form-control <?= session('errors.api_ward_code') ? 'is-invalid' : '' ?>"
               value="<?= esc($currentCode) ?>" placeholder="เช่น 08">
    </div>
    <div class="col-md-8">
        <label for="api_ward_name" class="form-label">API Ward Name หลัก</label>
        <input type="text" name="api_ward_name" id="api_ward_name"
               class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
               value="<?= esc($currentName) ?>" placeholder="เช่น ศญ1_สามัญ">
    </div>
</div>
<?php endif; ?>
