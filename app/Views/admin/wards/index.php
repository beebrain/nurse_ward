<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$summary = $mapping_summary ?? ['total' => 0, 'configured' => 0, 'missing' => 0, 'duplicate' => 0];
$canHosxpLogs = auth()->loggedIn() && auth()->user()->inGroup('superadmin');
?>
<style>
    .ward-map-ok { background: #dcfce7; color: #166534; }
    .ward-map-missing { background: #fee2e2; color: #991b1b; }
    .ward-map-duplicate { background: #fef9c3; color: #854d0e; }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">จัดการ Ward</h1>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($canHosxpLogs): ?>
                    <a href="<?= base_url('admin/hosxp-logs') ?>" class="btn btn-outline-secondary btn-sm">
                        <span class="material-symbols-outlined align-middle" style="font-size:1.1rem;">receipt_long</span>
                        ตรวจสอบ Map กับ HOSxP
                    </a>
                <?php endif; ?>
                <a href="<?= base_url('admin/wards/create') ?>" class="btn btn-primary">เพิ่ม Ward ใหม่</a>
            </div>
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

        <div class="card shadow-sm mb-3">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center gap-2 small">
                    <span class="text-muted fw-semibold">สรุปการเชื่อม HOSxP API:</span>
                    <span class="badge ward-map-ok">ตั้งค่าแล้ว <?= (int) $summary['configured'] ?></span>
                    <span class="badge ward-map-missing">ยังไม่ตั้ง <?= (int) $summary['missing'] ?></span>
                    <span class="badge ward-map-duplicate">ชื่อ API ซ้ำ <?= (int) $summary['duplicate'] ?></span>
                    <span class="text-muted">จาก <?= (int) $summary['total'] ?> แผนก</span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อ Ward</th>
                                <th>กลุ่มงาน</th>
                                <th>API Code</th>
                                <th>API Name</th>
                                <th>การ map</th>
                                <th class="text-center">เตียง</th>
                                <th class="text-center">สถานะ</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($wards)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">ไม่พบข้อมูล Ward</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($wards as $ward): ?>
                                    <?php
                                    $mapStatus = $ward['mapping_status'] ?? 'missing';
                                    $mapClass = match ($mapStatus) {
                                        'ok'        => 'ward-map-ok',
                                        'duplicate' => 'ward-map-duplicate',
                                        default     => 'ward-map-missing',
                                    };
                                    $rowClass = $mapStatus !== 'ok' && ($ward['is_active'] ?? false) ? 'table-warning' : '';
                                    ?>
                                    <tr class="<?= esc($rowClass) ?>">
                                        <td><code><?= esc($ward['code'] ?? '—') ?></code></td>
                                        <td><?= esc($ward['name']) ?></td>
                                        <td>
                                            <?php if ($ward['department_name']): ?>
                                                <span class="badge bg-info text-dark"><?= esc($ward['department_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (! empty($ward['api_ward_code'])): ?>
                                                <code><?= esc($ward['api_ward_code']) ?></code>
                                            <?php else: ?>
                                                <span class="text-danger">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small">
                                            <?php if (! empty($ward['api_ward_name'])): ?>
                                                <?= esc($ward['api_ward_name']) ?>
                                                <?php if (! empty($ward['api_aliases'])): ?>
                                                    <div class="text-muted" style="font-size:.8rem;">
                                                        + <?= esc(implode(', ', $ward['api_aliases'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= esc($mapClass) ?>">
                                                <?= esc($ward['mapping_status_label'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?= $ward['total_beds'] ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $ward['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $ward['is_active'] ? 'ใช้งาน' : 'ไม่ใช้งาน' ?>
                                            </span>
                                        </td>
                                        <td class="text-center text-nowrap">
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
