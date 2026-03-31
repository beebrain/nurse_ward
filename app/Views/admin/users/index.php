<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?= $title ?></h1>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= esc($user->username) ?></td>
                                    <td><?= esc($user->email) ?></td>
                                    <td><?= implode(', ', $user->getGroups()) ?></td>
                                    <td>
                                        <span class="badge <?= $user->approval_status === 'approved' ? 'bg-success' : ($user->approval_status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                            <?= ucfirst($user->approval_status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user->approval_status === 'pending'): ?>
                                            <form action="<?= base_url('admin/users/approve/' . $user->id) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                        <?php elseif ($user->approval_status === 'approved'): ?>
                                            <form action="<?= base_url('admin/users/deactivate/' . $user->id) ?>" method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this account?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                            </form>
                                        <?php elseif ($user->approval_status === 'deactivated'): ?>
                                            <form action="<?= base_url('admin/users/activate/' . $user->id) ?>" method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                            </form>
                                        <?php endif; ?>
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
