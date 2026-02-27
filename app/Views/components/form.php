<?php

use App\Helpers\UrlHelper;
use App\Helpers\FormHelper;

?>
<form action="<?= UrlHelper::to($url) ?>" method="post">
    <!-- ฟิลด์สำหรับกรอกข้อมูลพนักงาน -->
    <?= FormHelper::csrfField() ?> 

    <div class="mb-3">
        <label for="first_name" class="form-label">ชื่อ</label>
        <input type="text" class="form-control <?= FormHelper::invalidClass('first_name') ?>" id="first_name" name="first_name" value="<?= FormHelper::old('first_name', $employee['first_name'] ?? '') ?>">
        
        <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
        <?php if (FormHelper::hasError('first_name')): ?>
            <div class="invalid-feedback"><?= FormHelper::firstError('first_name') ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="last_name" class="form-label">นามสกุล</label>
        <input type="text" class="form-control <?= FormHelper::invalidClass('last_name') ?>" id="last_name" name="last_name" value="<?= FormHelper::old('last_name', $employee['last_name'] ?? '') ?>">
        
        <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
        <?php if (FormHelper::hasError('last_name')): ?>
            <div class="invalid-feedback"><?= FormHelper::firstError('last_name') ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="department" class="form-label">แผนก</label>
        <select name="department" id="department" class="form-select <?= FormHelper::invalidClass('department') ?>">
            <option value="">-- เลือกแผนก --</option>
            <option value="1" <?= FormHelper::old('department', $employee['department_id'] ?? '') == 1 ? 'selected' : '' ?>>ฝ่ายบุคคล</option>
            <option value="2" <?= FormHelper::old('department', $employee['department_id'] ?? '') == 2 ? 'selected' : '' ?>>ฝ่ายบัญชี</option>
            <!-- เพิ่มตัวเลือกแผนกที่นี่ -->
        </select>
        
        <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
        <?php if (FormHelper::hasError('department')): ?>
            <div class="invalid-feedback"><?= FormHelper::firstError('department') ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="salary" class="form-label">เงินเดือน</label>
        <input type="number" class="form-control <?= FormHelper::invalidClass('salary') ?>" id="salary" name="salary" value="<?= FormHelper::old('salary', $employee['salary'] ?? '') ?>">
        
        <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
        <?php if (FormHelper::hasError('salary')): ?>
            <div class="invalid-feedback"><?= FormHelper::firstError('salary') ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">อีเมล</label>
        <input type="email" class="form-control <?= FormHelper::invalidClass('email') ?>" id="email" name="email" value="<?= FormHelper::old('email', $employee['email'] ?? '') ?>">
        
        <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
        <?php if (FormHelper::hasError('email')): ?>
            <div class="invalid-feedback"><?= FormHelper::firstError('email') ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="phone" class="form-label">โทรศัพท์</label>
        <input type="text" class="form-control <?= FormHelper::invalidClass('phone') ?>" id="phone" name="phone" value="<?= FormHelper::old('phone', $employee['phone'] ?? '') ?>">
        
        <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
        <?php if (FormHelper::hasError('phone')): ?>
            <div class="invalid-feedback"><?= FormHelper::firstError('phone') ?></div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary"><?= $submit ?></button>
    <a href="<?= UrlHelper::to('/employees') ?>" class="btn btn-secondary">ยกเลิก</a>
</form>