<?php

use App\Helpers\FormHelper;

$this->section('title');
?>สมัครสมาชิก<?php
$this->endSection();
?>

<h1 class="h4 mb-3">สมัครสมาชิก</h1>

<?php if (FormHelper::hasFlash('error')): ?>
    <div class="alert alert-danger"><?= FormHelper::flash('error') ?></div>
<?php endif; ?>

<form method="post" action="/register" class="mt-3" novalidate>
    <?= FormHelper::csrfField() ?>

    <div class="mb-3">
        <label for="username" class="form-label">ชื่อผู้ใช้</label>
        <input
            type="text"
            class="form-control <?= FormHelper::invalidClass('username') ?>"
            id="username"
            name="username"
            value="<?= FormHelper::old('username') ?>"
            autocomplete="username"
            required
        >
        <?php if (FormHelper::hasError('username')): ?>
            <div class="invalid-feedback d-block"><?= FormHelper::firstError('username') ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">อีเมล</label>
        <input
            type="email"
            class="form-control <?= FormHelper::invalidClass('email') ?>"
            id="email"
            name="email"
            value="<?= FormHelper::old('email') ?>"
            autocomplete="email"
            required
        >
        <?php if (FormHelper::hasError('email')): ?>
            <div class="invalid-feedback d-block"><?= FormHelper::firstError('email') ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">รหัสผ่าน</label>
        <input
            type="password"
            class="form-control <?= FormHelper::invalidClass('password') ?>"
            id="password"
            name="password"
            autocomplete="new-password"
            required
        >
        <?php if (FormHelper::hasError('password')): ?>
            <div class="invalid-feedback d-block"><?= FormHelper::firstError('password') ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="password_confirm" class="form-label">ยืนยันรหัสผ่าน</label>
        <input
            type="password"
            class="form-control <?= FormHelper::invalidClass('password_confirm') ?>"
            id="password_confirm"
            name="password_confirm"
            autocomplete="new-password"
            required
        >
        <?php if (FormHelper::hasError('password_confirm')): ?>
            <div class="invalid-feedback d-block"><?= FormHelper::firstError('password_confirm') ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>

    <div class="mt-3 text-center">
        <a href="/login">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
    </div>
</form>
