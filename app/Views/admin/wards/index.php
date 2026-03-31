<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>จัดการแผนก</h1>
            <a href="<?= base_url('admin/wards/create') ?>" class="btn btn-primary">เพิ่มแผนกใหม่</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ชื่อแผนก</th>
                                <th>จำนวนเตียง</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wards as $ward): ?>
                                <tr>
                                    <td><?= esc($ward['name']) ?></td>
                                    <td><?= $ward['total_beds'] ?></td>
                                    <td>
                                        <span class="badge <?= $ward['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $ward['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('admin/wards/edit/' . $ward['id']) ?>" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                                        <form action="<?= base_url('admin/wards/delete/' . $ward['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($wards)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">ไม่พบข้อมูลแผนก</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>