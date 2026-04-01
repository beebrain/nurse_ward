<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="color:var(--primary);font-size:2rem;">group</span>
                <h1 class="mb-0"><?= esc($title) ?></h1>
            </div>
            <a href="<?= base_url('admin/users/create') ?>" class="btn btn-primary">
                <span class="material-symbols-outlined align-middle me-1">person_add</span>
                เพิ่มผู้ใช้งาน
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

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ผู้ใช้งาน</th>
                                <th>อีเมล</th>
                                <th>บทบาท</th>
                                <th>สถานะ</th>
                                <th class="text-end pe-3">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="material-symbols-outlined text-muted">account_circle</span>
                                            <strong><?= esc($user->username) ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= esc($user->email) ?></td>
                                    <td>
                                        <?php $groups = $user->getGroups(); ?>
                                        <?php foreach ($groups as $group): ?>
                                            <?php
                                            $badgeClass = match($group) {
                                                'superadmin' => 'bg-primary',
                                                'manager'    => 'bg-info text-dark',
                                                default      => 'bg-secondary',
                                            };
                                            $groupLabel = match($group) {
                                                'superadmin' => 'Super Admin',
                                                'manager'    => 'Manager',
                                                default      => 'Nurse',
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $groupLabel ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = match($user->approval_status) {
                                            'approved'    => 'bg-success',
                                            'pending'     => 'bg-warning text-dark',
                                            'deactivated' => 'bg-danger',
                                            default       => 'bg-secondary',
                                        };
                                        $statusLabel = match($user->approval_status) {
                                            'approved'    => 'อนุมัติแล้ว',
                                            'pending'     => 'รอการอนุมัติ',
                                            'deactivated' => 'ปิดการใช้งาน',
                                            default       => $user->approval_status,
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex gap-1 justify-content-end flex-wrap">
                                            <!-- Approve / Deactivate / Activate -->
                                            <?php if ($user->approval_status === 'pending'): ?>
                                                <form action="<?= base_url('admin/users/approve/' . $user->id) ?>" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-success" title="อนุมัติ">
                                                        <span class="material-symbols-outlined" style="font-size:.9rem;">check</span>
                                                    </button>
                                                </form>
                                            <?php elseif ($user->approval_status === 'approved'): ?>
                                                <form action="<?= base_url('admin/users/deactivate/' . $user->id) ?>" method="post" class="d-inline"
                                                      onsubmit="return confirm('ปิดการใช้งานบัญชีนี้?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="ปิดการใช้งาน">
                                                        <span class="material-symbols-outlined" style="font-size:.9rem;">block</span>
                                                    </button>
                                                </form>
                                            <?php elseif ($user->approval_status === 'deactivated'): ?>
                                                <form action="<?= base_url('admin/users/activate/' . $user->id) ?>" method="post" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="เปิดการใช้งาน">
                                                        <span class="material-symbols-outlined" style="font-size:.9rem;">check_circle</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Edit -->
                                            <a href="<?= base_url('admin/users/edit/' . $user->id) ?>"
                                               class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                <span class="material-symbols-outlined" style="font-size:.9rem;">edit</span>
                                            </a>

                                            <!-- Delete -->
                                            <?php if ($user->id !== auth()->id()): ?>
                                                <form action="<?= base_url('admin/users/delete/' . $user->id) ?>" method="post" class="d-inline"
                                                      onsubmit="return confirm('ลบผู้ใช้งาน \'<?= esc($user->username) ?>\' ? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบ">
                                                        <span class="material-symbols-outlined" style="font-size:.9rem;">delete</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
