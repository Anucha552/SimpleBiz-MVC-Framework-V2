<?php

use App\Helpers\UrlHelper;
use App\Helpers\FormHelper;

?>

<?php $this->section('title'); ?>
พนักงานทั้งหมด
<?php $this->endSection(); ?>

<!-- แจ้งเตือนความสำเร็จ -->
<?php if (FormHelper::hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= FormHelper::flash('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- แจ้งเตือนข้อผิดพลาด -->
<?php if (FormHelper::hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= FormHelper::flash('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="table-responsive-md">
    <table class="table table-striped table-hover">
        <thead>
            <tr class="table-primary">
                <th>รหัสพนักงาน</th>
                <th>ชื่อ</th>
                <th>นามสกุล</th>
                <th>โทรศัพท์</th>
                <th class="text-center">การกระทำ</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($employees === []): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่มีพนักงานในระบบ</td>
                </tr>
            <?php else: ?>
                <?php foreach ($employees as $employee): ?>
                    <tr class="align-middle">
                        <td><?= $this->e($employee['employee_code']) ?></td>
                        <td><?= $this->e($employee['first_name']) ?></td>
                        <td><?= $this->e($employee['last_name']) ?></td>
                        <td><?= $this->e($employee['phone']) ?></td>
                        <td>
                            <div class="d-flex gap-2 justify-content-center flex-nowrap">
                                <a href="<?= UrlHelper::to('/employees/' . $this->e($employee['id']) . '/edit') ?>" class="btn btn-sm btn-primary d-flex align-items-center">แก้ไข</a>
                                <form action="<?= UrlHelper::to('/employees/' . $this->e($employee['id']) . '/delete') ?>" method="post">
                                    
                                    <button type="submit" class="btn btn-sm btn-danger d-flex align-items-center" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบพนักงานนี้?')">ลบ</button>
                                </form>
                                <a href="<?= UrlHelper::to('/employees/' . $this->e($employee['id'])) ?>" class="btn btn-sm btn-info d-flex align-items-center">ดูรายละเอียด</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>