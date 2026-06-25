<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center mt-5">
        <div class="card shadow">
            <div class="card-body py-5">
                <span class="material-symbols-outlined text-danger mb-3" style="font-size:3rem;" aria-hidden="true">block</span>
                <h1 class="h3 text-danger mb-4">บัญชีถูกปิดการใช้งาน</h1>
                <p class="lead mb-4">บัญชีของคุณถูกปิดการใช้งานโดยผู้ดูแลระบบ และไม่สามารถเข้าใช้งานได้</p>
                <p class="text-muted">กรุณาติดต่อผู้ดูแลระบบของหน่วยงาน</p>
                <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger mt-3">ออกจากระบบ</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
