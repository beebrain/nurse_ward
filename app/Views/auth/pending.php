<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6 text-center mt-5">
        <div class="card shadow">
            <div class="card-body py-5">
                <span class="material-symbols-outlined text-warning mb-3" style="font-size:3rem;" aria-hidden="true">hourglass_top</span>
                <h1 class="h3 text-warning mb-4">รอการอนุมัติบัญชี</h1>
                <p class="lead mb-4">สร้างบัญชีเรียบร้อยแล้ว แต่ต้องได้รับการอนุมัติจากผู้ดูแลระบบก่อนเข้าใช้งาน</p>
                <p class="text-muted">กรุณาติดต่อผู้ดูแลระบบของหน่วยงาน</p>
                <a href="<?= base_url('logout') ?>" class="btn btn-outline-primary mt-3">ออกจากระบบ</a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
