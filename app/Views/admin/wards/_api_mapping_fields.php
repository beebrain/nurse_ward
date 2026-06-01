<?php
/** @var list<array{ward: string, ward_name: string}> $api_ward_options */
$apiWardOptions = $api_ward_options ?? [];
$ward           = $ward ?? [];

$currentCode = trim((string) old('api_ward_code', $ward['api_ward_code'] ?? ''));
$currentName = trim((string) old('api_ward_name', $ward['api_ward_name'] ?? ''));
$currentPair = ($currentCode !== '' && $currentName !== '') ? $currentCode . '|' . $currentName : '';

$pairInList = false;
foreach ($apiWardOptions as $opt) {
    $pair = trim((string) ($opt['ward'] ?? '')) . '|' . trim((string) ($opt['ward_name'] ?? ''));
    if ($pair === $currentPair) {
        $pairInList = true;
        break;
    }
}
$useCustom = $apiWardOptions !== [] && $currentPair !== '' && ! $pairInList;
$usePicker   = $apiWardOptions !== [] && ! $useCustom;
$uid         = 'api-map-' . uniqid();
?>
<hr class="my-4">
<h5 class="mb-2 text-muted">การเชื่อมโยง HOSxP API</h5>

<?php if ($apiWardOptions !== []): ?>
<p class="small text-muted mb-3">
    เลือก <strong>API Ward Name</strong> จากรายการ (<?= count($apiWardOptions) ?> แผนกจากการดึงล่าสุด) — รหัส ward เติมให้อัตโนมัติ
    <br>รหัสเดียวกันหลายแผนก (เช่น <code>08</code>) แยกด้วยชื่อ เช่น <code>ศญ1_สามัญ</code>, <code>ศญ2_สามัญ</code>
</p>

<div class="mb-3">
    <label for="<?= esc($uid) ?>-pick" class="form-label">API Ward Name <span class="text-danger">*</span></label>
    <select id="<?= esc($uid) ?>-pick" class="form-select <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>">
        <option value="">— เลือกจากรายการ HOSxP —</option>
        <?php foreach ($apiWardOptions as $opt):
            $code = trim((string) ($opt['ward'] ?? ''));
            $name = trim((string) ($opt['ward_name'] ?? ''));
            $val  = $code . '|' . $name;
            ?>
            <option value="<?= esc($val, 'attr') ?>" <?= $val === $currentPair ? 'selected' : '' ?>>
                <?= esc($name) ?> (รหัส <?= esc($code) ?>)
            </option>
        <?php endforeach; ?>
        <option value="__custom__" <?= $useCustom ? 'selected' : '' ?>>อื่นๆ — ไม่มีในรายการ</option>
    </select>
    <?php if (session('errors.api_ward_name')): ?>
        <div class="invalid-feedback d-block"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
    <?php endif; ?>
</div>

<div id="<?= esc($uid) ?>-auto" class="<?= $useCustom ? 'd-none' : '' ?>">
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label text-muted small">API Ward Code (อัตโนมัติ)</label>
            <input type="text" id="<?= esc($uid) ?>-code-display" class="form-control bg-light"
                   value="<?= esc($currentCode) ?>" readonly tabindex="-1">
            <input type="hidden" name="api_ward_code" id="<?= esc($uid) ?>-code"
                   value="<?= esc($currentCode) ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label text-muted small">API Ward Name (อัตโนมัติ)</label>
            <input type="text" id="<?= esc($uid) ?>-name-display" class="form-control bg-light"
                   value="<?= esc($currentName) ?>" readonly tabindex="-1">
            <input type="hidden" name="api_ward_name" id="<?= esc($uid) ?>-name"
                   value="<?= esc($currentName) ?>">
        </div>
    </div>
</div>

<div id="<?= esc($uid) ?>-manual" class="<?= $useCustom ? '' : 'd-none' ?>">
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="<?= esc($uid) ?>-code-manual" class="form-label">API Ward Code</label>
            <input type="text" name="api_ward_code" id="<?= esc($uid) ?>-code-manual"
                   class="form-control <?= session('errors.api_ward_code') ? 'is-invalid' : '' ?>"
                   value="<?= esc($useCustom ? $currentCode : '') ?>"
                   placeholder="เช่น 08">
            <?php if (session('errors.api_ward_code')): ?>
                <div class="invalid-feedback"><?= esc(session('errors.api_ward_code')) ?></div>
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <label for="<?= esc($uid) ?>-name-manual" class="form-label">API Ward Name</label>
            <input type="text" name="api_ward_name" id="<?= esc($uid) ?>-name-manual"
                   class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
                   value="<?= esc($useCustom ? $currentName : '') ?>"
                   placeholder="เช่น ศญ1_สามัญ">
        </div>
    </div>
</div>

<script>
(function () {
    const pick = document.getElementById(<?= json_encode($uid . '-pick') ?>);
    const auto = document.getElementById(<?= json_encode($uid . '-auto') ?>);
    const manual = document.getElementById(<?= json_encode($uid . '-manual') ?>);
    const codeH = document.getElementById(<?= json_encode($uid . '-code') ?>);
    const nameH = document.getElementById(<?= json_encode($uid . '-name') ?>);
    const codeD = document.getElementById(<?= json_encode($uid . '-code-display') ?>);
    const nameD = document.getElementById(<?= json_encode($uid . '-name-display') ?>);
    const codeM = document.getElementById(<?= json_encode($uid . '-code-manual') ?>);
    const nameM = document.getElementById(<?= json_encode($uid . '-name-manual') ?>);

    function applyPair(code, name) {
        if (codeH) codeH.value = code;
        if (nameH) nameH.value = name;
        if (codeD) codeD.value = code;
        if (nameD) nameD.value = name;
    }

    function toggleMode(isCustom) {
        auto?.classList.toggle('d-none', isCustom);
        manual?.classList.toggle('d-none', !isCustom);
        if (codeH) codeH.disabled = isCustom;
        if (nameH) nameH.disabled = isCustom;
        if (codeM) codeM.disabled = !isCustom;
        if (nameM) nameM.disabled = !isCustom;
    }

    pick?.addEventListener('change', () => {
        const v = pick.value;
        if (v === '__custom__') {
            toggleMode(true);
            if (codeM && codeH) codeM.value = codeH.value;
            if (nameM && nameH) nameM.value = nameH.value;
            return;
        }
        if (!v || !v.includes('|')) {
            toggleMode(false);
            applyPair('', '');
            return;
        }
        const i = v.indexOf('|');
        toggleMode(false);
        applyPair(v.slice(0, i), v.slice(i + 1));
    });

    toggleMode(<?= $useCustom ? 'true' : 'false' ?>);
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
        <?php if (session('errors.api_ward_code')): ?>
            <div class="invalid-feedback"><?= esc(session('errors.api_ward_code')) ?></div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <label for="api_ward_name" class="form-label">API Ward Name</label>
        <input type="text" name="api_ward_name" id="api_ward_name"
               class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
               value="<?= esc($currentName) ?>" placeholder="เช่น ศญ1_สามัญ">
        <?php if (session('errors.api_ward_name')): ?>
            <div class="invalid-feedback"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
