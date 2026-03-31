<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center mt-5">
        <div class="card shadow">
            <div class="card-body py-5">
                <h1 class="text-warning mb-4">Account Pending Approval</h1>
                <p class="lead mb-4">Your account has been created successfully, but it requires approval from a Super Admin before you can access the system.</p>
                <p>Please contact your administrator for more information.</p>
                <a href="<?= base_url('logout') ?>" class="btn btn-outline-primary mt-3">Logout</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
