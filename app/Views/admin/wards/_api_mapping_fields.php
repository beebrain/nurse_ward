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
            <label for="<?= esc($uid) ?>-aliases" class="form-label">ชื่อ API เพิ่มเติม <span class="text-muted fw-normal">(รหัสเดียวกัน — รวมยอดตอนแสดงผล)</span></label>
            <select name="api_aliases[]" id="<?= esc($uid) ?>-aliases" class="form-select" multiple size="5"></select>
            <div class="form-text">เลือกชื่ออื่นในรหัสเดียวกันที่ต้องการรวมกับแผนกนี้ (ไม่รวมชื่อหลัก)</div>
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
    const aliasesEl = document.getElementById(uid + '-aliases');

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
        if (!primaryName || !aliasesEl) return;

        const names = namesForCode(code);
        const prevPrimary = primaryName.value || initialName;
        const prevAliases = Array.from(aliasesEl.selectedOptions).map(o => o.value);
        const aliasSelections = prevAliases.length ? prevAliases : initialAliases;

        primaryName.innerHTML = '<option value="">— เลือกชื่อ —</option>';
        aliasesEl.innerHTML = '';

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
        if (!aliasesEl) return;
        aliasesEl.innerHTML = '';
        namesForCode(code).forEach((row) => {
            const name = row.ward_name;
            if (isUsed(name) || name === primary) return;
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = formatNameLabel(row);
            if (aliasSelections.includes(name)) {
                opt.selected = true;
            }
            aliasesEl.appendChild(opt);
        });
    }

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
