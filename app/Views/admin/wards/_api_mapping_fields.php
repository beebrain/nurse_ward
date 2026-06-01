<?php
/** @var list<array{ward: string, ward_name: string}> $api_ward_options */
$apiWardOptions = $api_ward_options ?? [];
$listId = 'api-ward-options-' . uniqid();
?>
<hr class="my-4">
<h5 class="mb-2 text-muted">การเชื่อมโยง HOSxP API</h5>
<p class="small text-muted mb-3">
    ต้องตรง <code>ward</code> / <code>ward_name</code> จาก API (รหัส 08 แยกหลายแผนกด้วยชื่อย่อย)
    <?php if ($apiWardOptions !== []): ?>
        — มีรายการจากการดึงล่าสุด <?= count($apiWardOptions) ?> แผนก ให้เลือกด้านล่าง
    <?php else: ?>
        — ยังไม่มี log ดึงสำเร็จ ดูรายการได้ที่ <a href="<?= base_url('admin/hosxp-logs') ?>">บันทึกดิบ HOSxP</a>
    <?php endif; ?>
</p>

<?php if ($apiWardOptions !== []): ?>
<div class="mb-3">
    <label for="api_ward_pick" class="form-label">เลือกจาก HOSxP (ล่าสุด)</label>
    <select id="api_ward_pick" class="form-select form-select-sm">
        <option value="">— เลือกเพื่อเติม Code / Name —</option>
        <?php foreach ($apiWardOptions as $opt): ?>
            <option value="<?= esc($opt['ward'], 'attr') ?>|<?= esc($opt['ward_name'], 'attr') ?>">
                <?= esc($opt['ward']) ?> — <?= esc($opt['ward_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="api_ward_code" class="form-label">API Ward Code</label>
        <input type="text" name="api_ward_code" id="api_ward_code"
               class="form-control <?= session('errors.api_ward_code') ? 'is-invalid' : '' ?>"
               value="<?= old('api_ward_code', $ward['api_ward_code'] ?? '') ?>"
               placeholder="เช่น 08" list="<?= esc($listId) ?>">
        <?php if (session('errors.api_ward_code')): ?>
            <div class="invalid-feedback"><?= esc(session('errors.api_ward_code')) ?></div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <label for="api_ward_name" class="form-label">API Ward Name</label>
        <input type="text" name="api_ward_name" id="api_ward_name"
               class="form-control <?= session('errors.api_ward_name') ? 'is-invalid' : '' ?>"
               value="<?= old('api_ward_name', $ward['api_ward_name'] ?? '') ?>"
               placeholder="เช่น ศญ1_สามัญ" list="<?= esc($listId) ?>">
        <?php if (session('errors.api_ward_name')): ?>
            <div class="invalid-feedback"><?= esc(is_array(session('errors.api_ward_name')) ? implode(' ', session('errors.api_ward_name')) : session('errors.api_ward_name')) ?></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($apiWardOptions !== []): ?>
<datalist id="<?= esc($listId) ?>">
    <?php foreach ($apiWardOptions as $opt): ?>
        <option value="<?= esc($opt['ward_name']) ?>"><?= esc($opt['ward']) ?> — <?= esc($opt['ward_name']) ?></option>
    <?php endforeach; ?>
</datalist>
<script>
(function () {
    const pick = document.getElementById('api_ward_pick');
    if (!pick) return;
    pick.addEventListener('change', () => {
        const v = pick.value;
        if (!v || !v.includes('|')) return;
        const [code, name] = v.split('|');
        const codeEl = document.getElementById('api_ward_code');
        const nameEl = document.getElementById('api_ward_name');
        if (codeEl) codeEl.value = code;
        if (nameEl) nameEl.value = name;
    });
})();
</script>
<?php endif; ?>
