<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
  <div class="col-xl-8">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center gap-2">
        <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">assignment_ind</span>
        <h1 class="mb-0"><?= esc($title) ?></h1>
      </div>
      <a href="<?= base_url('admin/nurse-wards') ?>" class="btn btn-sm btn-outline-secondary">
        <span class="material-symbols-outlined align-middle" style="font-size:.9rem;">arrow_back</span>
        กลับ
      </a>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="alert alert-info mb-3">
      <strong><?= esc($nurseUser->username) ?></strong> จะบันทึกข้อมูลได้เฉพาะ Ward ที่ติ๊กเลือกด้านล่างนี้
    </div>

    <form action="<?= base_url('admin/nurse-wards/update/' . $nurseUser->id) ?>" method="post">
      <?= csrf_field() ?>

      <?php
      $currentDept = '';
      foreach ($allWards as $ward):
          $dept = $ward['department_name'] ?? 'ไม่ระบุกลุ่มงาน';
          if ($dept !== $currentDept):
              if ($currentDept !== '') echo '</div></div>'; // close prev card
              $currentDept = $dept;
      ?>
        <div class="card shadow-sm mb-3">
          <div class="card-header bg-light fw-semibold">
            <span class="material-symbols-outlined align-middle me-1" style="font-size:1rem;">folder</span>
            <?= esc($dept) ?>
          </div>
          <div class="card-body">
            <div class="row g-2">
      <?php endif; ?>

          <div class="col-md-4 col-sm-6">
            <div class="form-check border rounded p-2 <?= in_array($ward['id'], $assignedIds) ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
              <input class="form-check-input ward-check" type="checkbox"
                     name="ward_ids[]"
                     value="<?= $ward['id'] ?>"
                     id="ward_<?= $ward['id'] ?>"
                     <?= in_array($ward['id'], $assignedIds) ? 'checked' : '' ?>>
              <label class="form-check-label" for="ward_<?= $ward['id'] ?>">
                <?php if ($ward['code']): ?>
                  <code class="me-1"><?= esc($ward['code']) ?></code>
                <?php endif; ?>
                <?= esc($ward['name']) ?>
              </label>
            </div>
          </div>

      <?php endforeach; ?>
      <?php if ($currentDept !== '') echo '</div></div></div>'; ?>

      <div class="d-flex gap-2 justify-content-between mt-3">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectAll">เลือกทั้งหมด</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearAll">ยกเลิกทั้งหมด</button>
        </div>
        <button type="submit" class="btn btn-primary px-4">บันทึกสิทธิ์</button>
      </div>
    </form>

  </div>
</div>

<script>
document.getElementById('btnSelectAll').addEventListener('click', () => {
    document.querySelectorAll('.ward-check').forEach(cb => { cb.checked = true; updateStyle(cb); });
});
document.getElementById('btnClearAll').addEventListener('click', () => {
    document.querySelectorAll('.ward-check').forEach(cb => { cb.checked = false; updateStyle(cb); });
});

function updateStyle(cb) {
    const card = cb.closest('.form-check');
    if (cb.checked) {
        card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
    } else {
        card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
    }
}

document.querySelectorAll('.ward-check').forEach(cb => {
    cb.addEventListener('change', () => updateStyle(cb));
});
</script>
<?= $this->endSection() ?>
