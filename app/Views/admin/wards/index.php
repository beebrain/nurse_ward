<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>จัดการ Ward</h1>
            <a href="<?= base_url('admin/wards/create') ?>" class="btn btn-primary">เพิ่ม Ward ใหม่</a>
        </div>

        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= session()->getFlashdata('message') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อ Ward</th>
                                <th>กลุ่มงาน</th>
                                <th class="text-center">จำนวนเตียง</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($wards)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูล Ward</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($wards as $ward): ?>
                                    <tr>
                                        <td><code><?= esc($ward['code'] ?? '—') ?></code></td>
                                        <td><?= esc($ward['name']) ?></td>
                                        <td>
                                            <?php if ($ward['department_name']): ?>
                                                <span class="badge bg-info text-dark"><?= esc($ward['department_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $ward['total_beds'] ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $ward['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $ward['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('admin/wards/edit/' . $ward['id']) ?>" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                                            <form action="<?= base_url('admin/wards/delete/' . $ward['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('ลบ Ward นี้ใช่หรือไม่?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                            </form>
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
