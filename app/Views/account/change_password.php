<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid px-0" style="max-width: 720px;">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h1 class="h4 mb-1">เปลี่ยนรหัสผ่าน</h1>
      <div class="text-muted small">ตั้งรหัสผ่านใหม่สำหรับบัญชีของคุณ</div>
    </div>
  </div>

  <?php
    $errors = session()->getFlashdata('errors');
    if (! is_array($errors)) {
      $errors = [];
    }
    $errorMsg = session()->getFlashdata('error');
    if (! is_string($errorMsg)) {
      $errorMsg = $errorMsg ? (string) $errorMsg : '';
    }
  ?>

  <?php if ($errorMsg !== ''): ?>
    <div class="alert alert-danger" role="alert">
      <?= esc($errorMsg) ?>
    </div>
  <?php endif; ?>

  <?php if (! empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
      <div class="fw-semibold mb-1">กรุณาตรวจสอบข้อมูล</div>
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?= esc(is_string($e) ? $e : (string) $e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="post" action="<?= base_url('account/change-password') ?>" autocomplete="off" aria-label="เปลี่ยนรหัสผ่าน">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label" for="current_password">รหัสผ่านเดิม</label>
          <input
            type="password"
            name="current_password"
            id="current_password"
            class="form-control"
            autocomplete="current-password"
            required
          >
        </div>

        <div class="mb-3">
          <label class="form-label" for="new_password">รหัสผ่านใหม่</label>
          <input
            type="password"
            name="new_password"
            id="new_password"
            class="form-control"
            autocomplete="new-password"
            minlength="8"
            required
          >
          <div class="form-text">อย่างน้อย 8 ตัวอักษร</div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
          <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            class="form-control"
            autocomplete="new-password"
            minlength="8"
            required
          >
        </div>

        <div class="d-flex gap-2 justify-content-end">
          <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">ยกเลิก</a>
          <button type="submit" class="btn btn-primary">
            บันทึกรหัสผ่านใหม่
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
