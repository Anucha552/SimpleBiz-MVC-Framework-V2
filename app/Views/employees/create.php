<?php

/**
 * create.php
 *
 * จุดประสงค์: เป็น View สำหรับแสดงฟอร์มการสร้างพนักงานใหม่ โดยจะมีฟิลด์สำหรับกรอกข้อมูลต่าง ๆ ของพนักงาน เช่น รหัสพนักงาน ชื่อ นามสกุล แผนก อีเมล และโทรศัพท์ เมื่อผู้ใช้กรอกข้อมูลและส่งฟอร์ม ข้อมูลจะถูกส่งไปยัง Controller ที่เกี่ยวข้องเพื่อทำการบันทึกลงฐานข้อมูล
 */

use App\Helpers\UrlHelper;
use App\Helpers\FormHelper;

?>

<?= $this->start('title'); ?>
สร้างพนักงานใหม่
<?= $this->end(); ?>

<div class="card">
    <!-- แจ้งเตือนข้อผิดพลาด -->
    <?php if (FormHelper::hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= FormHelper::flash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card-header">
        <h5 class="card-title mb-0">สร้างพนักงานใหม่</h5>
    </div>
    <div class="card-body">
        <form action="<?= UrlHelper::to('/employees') ?>" method="post">
            <!-- ฟิลด์สำหรับกรอกข้อมูลพนักงาน -->
            <?= $this->csrfField() ?> 
    
            <div class="mb-3">
                <label for="first_name" class="form-label">ชื่อ</label>
                <input type="text" class="form-control <?= $this->invalidClass('first_name') ?>" id="first_name" name="first_name" value="<?= $this->old('first_name') ?>">
                
                <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
                <?php if ($this->hasError('first_name')): ?>
                    <div class="invalid-feedback"><?= $this->firstError('first_name') ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">นามสกุล</label>
                <input type="text" class="form-control <?= $this->invalidClass('last_name') ?>" id="last_name" name="last_name" value="<?= $this->old('last_name') ?>">
                
                <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
                <?php if ($this->hasError('last_name')): ?>
                    <div class="invalid-feedback"><?= $this->firstError('last_name') ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="department" class="form-label">แผนก</label>
                <select name="department" id="department" class="form-select <?= $this->invalidClass('department') ?>">
                    <option value="">-- เลือกแผนก --</option>
                    <option value="1" <?= $this->old('department') == 1 ? 'selected' : '' ?>>ฝ่ายบุคคล</option>
                    <option value="2" <?= $this->old('department') == 2 ? 'selected' : '' ?>>ฝ่ายบัญชี</option>
                    <!-- เพิ่มตัวเลือกแผนกที่นี่ -->
                </select>
                
                <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
                <?php if ($this->hasError('department')): ?>
                    <div class="invalid-feedback"><?= $this->firstError('department') ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="salary" class="form-label">เงินเดือน</label>
                <input type="number" class="form-control <?= $this->invalidClass('salary') ?>" id="salary" name="salary" value="<?= $this->old('salary') ?>">
                
                <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
                <?php if ($this->hasError('salary')): ?>
                    <div class="invalid-feedback"><?= $this->firstError('salary') ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">อีเมล</label>
                <input type="email" class="form-control <?= $this->invalidClass('email') ?>" id="email" name="email" value="<?= $this->old('email') ?>">
                
                <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
                <?php if ($this->hasError('email')): ?>
                    <div class="invalid-feedback"><?= $this->firstError('email') ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">โทรศัพท์</label>
                <input type="text" class="form-control <?= $this->invalidClass('phone') ?>" id="phone" name="phone" value="<?= $this->old('phone') ?>">
                
                <!-- แสดงข้อความแนะนำเมื่อมีข้อผิดพลาดในการกรอกข้อมูล -->
                <?php if ($this->hasError('phone')): ?>
                    <div class="invalid-feedback"><?= $this->firstError('phone') ?></div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">บันทึก</button>
            <a href="<?= UrlHelper::to('/employees') ?>" class="btn btn-secondary">ยกเลิก</a>
        </form>
    </div>
</div>
