<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
  <div class="col-12">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center gap-2">
        <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">assignment_ind</span>
        <h1 class="mb-0"><?= esc($title) ?></h1>
      </div>
      <a href="<?= base_url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">
        <span class="material-symbols-outlined align-middle" style="font-size:.9rem;">arrow_back</span>
        จัดการผู้ใช้งาน
      </a>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <span class="material-symbols-outlined align-middle me-1">check_circle</span>
        <?= esc(session()->getFlashdata('message')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <span class="material-symbols-outlined align-middle me-1">error</span>
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="alert alert-info mb-3">
      <span class="material-symbols-outlined align-middle me-1">info</span>
      Nurse สามารถบันทึกข้อมูลได้เฉพาะ Ward ที่กำหนดเท่านั้น | Manager และ Superadmin บันทึกได้ทุก Ward
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Nurse</th>
                <th>อีเมล</th>
                <th>สถานะบัญชี</th>
                <th>Ward ที่รับผิดชอบ</th>
                <th class="text-end pe-3">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($nurses)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">ยังไม่มี Nurse ในระบบ</td>
                </tr>
              <?php else: ?>
                <?php foreach ($nurses as $nurse): ?>
                  <tr>
                    <td class="ps-3">
                      <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined text-muted">account_circle</span>
                        <strong><?= esc($nurse['username']) ?></strong>
                      </div>
                    </td>
                    <td class="text-muted small"><?= esc($nurse['email'] ?? '—') ?></td>
                    <td>
                      <?php
                      $sc = match($nurse['approval_status'] ?? '') {
                          'approved'    => 'bg-success',
                          'pending'     => 'bg-warning text-dark',
                          'deactivated' => 'bg-danger',
                          default       => 'bg-secondary',
                      };
                      $sl = match($nurse['approval_status'] ?? '') {
                          'approved'    => 'อนุมัติแล้ว',
                          'pending'     => 'รอการอนุมัติ',
                          'deactivated' => 'ปิดการใช้งาน',
                          default       => '—',
                      };
                      ?>
                      <span class="badge <?= $sc ?>"><?= $sl ?></span>
                    </td>
                    <td>
                      <?php if ((int)$nurse['ward_count'] === 0): ?>
                        <span class="text-danger small"><span class="material-symbols-outlined align-middle" style="font-size:.9rem;">warning</span> ยังไม่ได้กำหนด Ward</span>
                      <?php else: ?>
                        <span class="badge bg-primary me-1"><?= (int)$nurse['ward_count'] ?> Ward</span>
                        <span class="text-muted small"><?= esc($nurse['wards_list']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                      <a href="<?= base_url('admin/nurse-wards/edit/' . $nurse['id']) ?>"
                         class="btn btn-sm btn-outline-primary">
                        <span class="material-symbols-outlined align-middle" style="font-size:.9rem;">edit</span>
                        กำหนด Ward
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
<?= $this->endSection() ?>
