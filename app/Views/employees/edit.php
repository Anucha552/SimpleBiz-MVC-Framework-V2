<?php

/**
 * edit.php
 *
 * จุดประสงค์: เป็น View สำหรับแสดงฟอร์มการสร้างพนักงานใหม่ โดยจะมีฟิลด์สำหรับกรอกข้อมูลต่าง ๆ ของพนักงาน เช่น รหัสพนักงาน ชื่อ นามสกุล แผนก อีเมล และโทรศัพท์ เมื่อผู้ใช้กรอกข้อมูลและส่งฟอร์ม ข้อมูลจะถูกส่งไปยัง Controller ที่เกี่ยวข้องเพื่อทำการบันทึกลงฐานข้อมูล
 */

use App\Helpers\FormHelper;

?>

<?= $this->section('title'); ?>
แก้ไขพนักงาน
<?= $this->endSection(); ?>

<div class="card">
    <!-- แจ้งเตือนข้อผิดพลาด -->
    <?php if (FormHelper::hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= FormHelper::flash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card-header">
        <h5 class="card-title mb-0">แก้ไขพนักงาน</h5>
    </div>
    <div class="card-body">
        <?php 
        
        $this->partial('components/form.php', [
            'page' => 'edit',
            'submit' => 'แก้ไข',
            'url' => '/employees/' . $id . '/update'  // ส่ง URL สำหรับอัปเดตข้อมูลพนักงาน
        ]);
        
        ?>
    </div>
</div>
