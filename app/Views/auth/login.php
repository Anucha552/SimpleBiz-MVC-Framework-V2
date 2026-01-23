<?php

use App\Helpers\FormHelper;

$this->section('title');
?>เข้าสู่ระบบ<?php
$this->endSection();
?>

<h1 class="h4 mb-3">เข้าสู่ระบบ</h1>

<?php if (FormHelper::hasFlash('error')): ?>
    <div class="alert alert-danger"><?= FormHelper::flash('error') ?></div>
<?php endif; ?>

<form method="post" action="/login" class="mt-3" novalidate>
    <?= FormHelper::csrfField() ?>

    <div class="mb-3">
        <label for="login" class="form-label">อีเมล หรือ ชื่อผู้ใช้</label>
        <input
            type="text"
            class="form-control <?= FormHelper::invalidClass('login') ?>"
            id="login"
            name="login"
            value="<?= FormHelper::old('login') ?>"
            autocomplete="username"
            required
        >
        <?php if (FormHelper::hasError('login')): ?>
            <div class="invalid-feedback d-block"><?= FormHelper::firstError('login') ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input
            type="password"
            class="form-control <?= FormHelper::invalidClass('password') ?>"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >
        <?php if (FormHelper::hasError('password')): ?>
            <div class="invalid-feedback d-block"><?= FormHelper::firstError('password') ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>

    <div class="mt-3 text-center">
        <a href="/register">ยังไม่มีบัญชี? สมัครสมาชิก</a>
    </div>
</form>
