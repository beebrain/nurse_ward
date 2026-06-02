<?= $this->extend('layout/main') ?>

<?= $this->section('layout_class') ?>layout-fluid<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$totalNurses = count(array_unique(array_column($assignments, 'id')));
$assignedCount = 0;
$unassignedCount = 0;

foreach ($assignments as $assignment) {
    if (! empty($assignment['ward_name'])) {
        $assignedCount++;
    } else {
        $unassignedCount++;
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">assignment_ind</span>
                <div>
                    <h1 class="mb-0"><?= esc($title) ?></h1>
                    <div class="text-muted small">แสดงรายชื่อ Nurse และแผนก/Ward ที่รับผิดชอบแบบอ่านอย่างเดียว</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Nurse ทั้งหมด</div>
                        <div class="display-6 fw-bold mb-0"><?= esc((string) $totalNurses) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">กำหนดแผนกแล้ว</div>
                        <div class="display-6 fw-bold text-success mb-0"><?= esc((string) $assignedCount) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">ยังไม่ได้กำหนด</div>
                        <div class="display-6 fw-bold text-danger mb-0"><?= esc((string) $unassignedCount) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">รายการผู้รับผิดชอบแผนก</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">User</th>
                                <th>อีเมล</th>
                                <th>สถานะ</th>
                                <th>แผนก</th>
                                <th>Ward ที่รับผิดชอบ</th>
                                <th class="pe-3">กำหนดเมื่อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">ยังไม่มี Nurse ในระบบ</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assignments as $assignment): ?>
                                    <?php
                                    $statusClass = match ($assignment['approval_status'] ?? '') {
                                        'approved' => 'bg-success',
                                        'pending' => 'bg-warning text-dark',
                                        'deactivated' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                    $statusLabel = match ($assignment['approval_status'] ?? '') {
                                        'approved' => 'อนุมัติแล้ว',
                                        'pending' => 'รอการอนุมัติ',
                                        'deactivated' => 'ปิดการใช้งาน',
                                        default => '—',
                                    };
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="material-symbols-outlined text-muted">account_circle</span>
                                                <strong><?= esc($assignment['username']) ?></strong>
                                            </div>
                                        </td>
                                        <td class="text-muted small"><?= esc($assignment['email'] ?? '—') ?></td>
                                        <td><span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span></td>
                                        <td>
                                            <?php if (! empty($assignment['department_name'])): ?>
                                                <span class="badge bg-info text-dark"><?= esc($assignment['department_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (! empty($assignment['ward_name'])): ?>
                                                <span class="fw-semibold"><?= esc($assignment['ward_name']) ?></span>
                                                <?php if (! empty($assignment['ward_code'])): ?>
                                                    <span class="text-muted small">(<?= esc($assignment['ward_code']) ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-danger small">
                                                    <span class="material-symbols-outlined align-middle" style="font-size:.9rem;">warning</span>
                                                    ยังไม่ได้กำหนด Ward
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3 text-muted small">
                                            <?= ! empty($assignment['assigned_at']) ? esc(date('d/m/Y H:i', strtotime($assignment['assigned_at']))) : '—' ?>
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
