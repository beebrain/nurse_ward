<?php
/** @var list<array{ward: string, ward_name: string, ward_name_ward?: string}> $api_ward_options */
$apiWardOptions   = $api_ward_options ?? [];
$ward             = $ward ?? [];
$selectedNames    = $ward_selected_names ?? [];
$usedApiNames     = $used_api_names ?? [];

$currentCode = trim((string) old('api_ward_code', $ward['api_ward_code'] ?? ''));

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
$allSelectedInList = true;
if ($codeInList && $selectedNames !== []) {
    $namesInCode = array_column($namesByCode[$currentCode], 'ward_name');
    foreach ($selectedNames as $n) {
        if (! in_array($n, $namesInCode, true)) {
            $allSelectedInList = false;
            break;
        }
    }
} elseif ($selectedNames !== [] && ! $codeInList) {
    $allSelectedInList = false;
}

$useCustom = $apiWardOptions !== [] && $currentCode !== '' && (! $codeInList || ! $allSelectedInList);
$namesText = $useCustom ? implode("\n", $selectedNames) : '';
if ($postedText = old('api_ward_names_text')) {
    $namesText = (string) $postedText;
}

$uid = 'api-map-' . uniqid();
?>
<style>
    .api-alias-list { max-height: 280px; overflow-y: auto; }
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
    → <strong>ขั้นที่ 2</strong> ติ๊กชื่อที่ต้องการ<strong>รวมยอด</strong> (รหัสเดียวกัน — เลือกได้หลายชื่อ)
</p>

<div id="<?= esc($uid) ?>-hosxp" class="<?= $useCustom ? 'd-none' : '' ?>">
    <div class="mb-3">
        <label for="<?= esc($uid) ?>-code-pick" class="form-label">API Ward Code <span class="text-danger">*</span></label>
        <select id="<?= esc($uid) ?>-code-pick" class="form-select">
            <option value="">— เลือกรหัส ward —</option>
            <?php foreach ($namesByCode as $code => $names):
                $group = $groupLabelForCode($names);
                $namePreview = implode(', ', array_slice(array_column($names, 'ward_name'), 0, 3));
                if (count($names) > 3) {
                    $namePreview .= '…';
                }
                $label = 'รหัส ' . $code;
                if ($group !== '') {
                    $label .= ' — ' . $group;
                    if (count($names) === 1) {
                        $label .= ' · ' . $names[0]['ward_name'];
                    }
                } elseif ($namePreview !== '') {
                    $label .= ' — ' . $namePreview;
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
    <input type="hidden" name="api_ward_name" id="<?= esc($uid) ?>-name-sync" value="">

    <div id="<?= esc($uid) ?>-names-panel" class="<?= $currentCode === '' ? 'd-none' : '' ?>">
        <div id="<?= esc($uid) ?>-group-hint" class="alert alert-light border small py-2 mb-3 d-none"></div>
        <div class="mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <label class="form-label mb-0">ชื่อ API ที่รวมยอด <span class="text-danger">*</span></label>
                <span id="<?= esc($uid) ?>-name-count" class="badge bg-secondary">เลือกแล้ว 0 ชื่อ</span>
            </div>
            <div id="<?= esc($uid) ?>-names-list" class="api-alias-list"></div>
            <?php if (session('errors.api_ward_name')): ?>
                <div class="text-danger small mt-1"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="<?= esc($uid) ?>-names-all">เลือกทั้งหมด</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="<?= esc($uid) ?>-names-none">ล้างทั้งหมด</button>
            </div>
            <div class="form-text mt-2">ติ๊กทุกชื่อที่ต้องการให้ระบบรวมยอดเมื่อแสดงผล (Handover / รายงาน)</div>
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
            <label for="<?= esc($uid) ?>-names-manual" class="form-label">ชื่อ API ที่รวมยอด</label>
            <textarea name="api_ward_names_text" id="<?= esc($uid) ?>-names-manual" rows="4"
                      class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
                      placeholder="หนึ่งชื่อต่อบรรทัด&#10;เช่น อช2_พิเศษ&#10;อช2_สามัญ"><?= esc($namesText) ?></textarea>
            <?php if (session('errors.api_ward_name')): ?>
                <div class="invalid-feedback d-block"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <p class="small text-muted">โหมดพิมพ์เอง — หนึ่งชื่อต่อบรรทัด</p>
</div>

<script>
(function () {
    const uid = <?= json_encode($uid) ?>;
    const namesByCode = <?= json_encode($namesByCode, JSON_UNESCAPED_UNICODE) ?>;
    const usedNames = <?= json_encode($usedApiNames, JSON_UNESCAPED_UNICODE) ?>;
    const initialCode = <?= json_encode($currentCode) ?>;
    const initialSelected = <?= json_encode(array_values($selectedNames), JSON_UNESCAPED_UNICODE) ?>;

    const hosxp = document.getElementById(uid + '-hosxp');
    const manual = document.getElementById(uid + '-manual');
    const codePick = document.getElementById(uid + '-code-pick');
    const codeH = document.getElementById(uid + '-code');
    const codeManual = document.getElementById(uid + '-code-manual');
    const namesPanel = document.getElementById(uid + '-names-panel');
    const namesList = document.getElementById(uid + '-names-list');
    const nameCount = document.getElementById(uid + '-name-count');
    const btnAll = document.getElementById(uid + '-names-all');
    const btnNone = document.getElementById(uid + '-names-none');
    const nameSync = document.getElementById(uid + '-name-sync');

    let currentSelections = initialSelected.slice();

    function syncLegacyPrimaryName() {
        let first = '';
        const names = getSelectedNames();
        if (names.length) {
            first = names[0];
        } else {
            const manual = document.getElementById(uid + '-names-manual');
            if (manual) {
                const line = manual.value.split(/\r?\n/).map((s) => s.trim()).find(Boolean);
                first = line || '';
            }
        }
        if (nameSync) {
            nameSync.value = first;
        }
    }

    function isUsed(name) {
        return Object.prototype.hasOwnProperty.call(usedNames, name);
    }

    function namesForCode(code) {
        return namesByCode[code] || [];
    }

    function getSelectedNames() {
        if (!namesList) return [];
        return Array.from(namesList.querySelectorAll('input[type="checkbox"]:checked')).map((cb) => cb.value);
    }

    function updateNameCount() {
        if (!nameCount) return;
        const n = getSelectedNames().length;
        nameCount.textContent = 'เลือกแล้ว ' + n + ' ชื่อ';
        nameCount.className = 'badge ' + (n > 0 ? 'bg-primary' : 'bg-secondary');
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

    function fillNameCheckboxes(code) {
        if (!namesList) return;

        const prev = getSelectedNames();
        const selections = prev.length ? prev : (code === initialCode ? currentSelections : []);
        namesList.innerHTML = '';
        let available = 0;

        namesForCode(code).forEach((row) => {
            const name = row.ward_name;
            if (isUsed(name) && !selections.includes(name)) return;
            available++;

            const g = (row.ward_name_ward || '').trim();
            const label = document.createElement('label');
            label.className = 'api-alias-item';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'api_ward_names[]';
            cb.value = name;
            if (selections.includes(name)) {
                cb.checked = true;
            }
            cb.addEventListener('change', () => {
                updateNameCount();
                syncLegacyPrimaryName();
            });

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
            namesList.appendChild(label);
        });

        if (available === 0) {
            namesList.innerHTML = '<p class="text-muted small mb-0 px-1">ไม่มีชื่อในรหัสนี้ (หรือถูกใช้โดยแผนกอื่นแล้วทั้งหมด)</p>';
        }
        updateNameCount();
        syncLegacyPrimaryName();
    }

    btnAll?.addEventListener('click', () => {
        namesList?.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = true; });
        updateNameCount();
        syncLegacyPrimaryName();
    });
    btnNone?.addEventListener('click', () => {
        namesList?.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
        updateNameCount();
        syncLegacyPrimaryName();
    });

    function setHosxpMode(code) {
        if (codeH) codeH.value = code;
        if (namesPanel) {
            namesPanel.classList.toggle('d-none', !code);
        }
        updateGroupHint(code);
        if (code) fillNameCheckboxes(code);
    }

    function toggleCustom(isCustom) {
        hosxp?.classList.toggle('d-none', isCustom);
        manual?.classList.toggle('d-none', !isCustom);
        if (isCustom) {
            if (codeH) {
                codeH.removeAttribute('name');
                codeH.disabled = true;
            }
        } else {
            if (codeH) {
                codeH.setAttribute('name', 'api_ward_code');
                codeH.disabled = false;
            }
            if (codeManual) codeManual.removeAttribute('name');
        }
    }

    codePick?.addEventListener('change', () => {
        const v = codePick.value;
        if (v === '__custom__') {
            toggleCustom(true);
            if (codeManual && codeH) codeManual.value = codeH.value;
            return;
        }
        toggleCustom(false);
        currentSelections = [];
        setHosxpMode(v);
    });

    if (initialCode && !<?= $useCustom ? 'true' : 'false' ?>) {
        setHosxpMode(initialCode);
    }

    const form = hosxp?.closest('form');
    form?.addEventListener('submit', syncLegacyPrimaryName);
    syncLegacyPrimaryName();
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
        <label for="api_ward_names_text" class="form-label">ชื่อ API ที่รวมยอด</label>
        <textarea name="api_ward_names_text" id="api_ward_names_text" rows="4"
                  class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
                  placeholder="หนึ่งชื่อต่อบรรทัด"><?= esc(implode("\n", $selectedNames)) ?></textarea>
        <?php if (session('errors.api_ward_name')): ?>
            <div class="invalid-feedback d-block"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
