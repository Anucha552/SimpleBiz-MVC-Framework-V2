<?php

use App\Helpers\UrlHelper;
use App\Helpers\NumberHelper;

?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">รายละเอียดพนักงาน</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th>รหัสพนักงาน</th>
                <td><?= $this->e($employee['employee_code']) ?></td>
            </tr>
            <tr>
                <th>ชื่อ</th>
                <td><?= $this->e($employee['first_name']) ?></td>
            </tr>
            <tr>
                <th>นามสกุล</th>
                <td><?= $this->e($employee['last_name']) ?></td>
            </tr>
            <tr>
                <th>แผนก</th>
                <td><?= $this->e($employee['department_name']) ?></td> <!-- ใช้ชื่อแผนกที่ดึงมาจากการ join -->
            </tr>
            <tr>
                <th>เงินเดือน</th>
                <td><?= NumberHelper::baht($employee['salary']) ?></td>
            </tr>
            <tr>
                <th>โทรศัพท์</th>
                <td><?= $this->e($employee['phone']) ?></td>
            </tr>
            <tr>
                <th>อีเมล</th>
                <td><?= $this->e($employee['email']) ?></td>
            </tr>
        </table>

        <a href="<?= UrlHelper::to('/employees') ?>" class="btn btn-secondary">กลับไปหน้ารายการพนักงาน</a>
    </div>
</div>