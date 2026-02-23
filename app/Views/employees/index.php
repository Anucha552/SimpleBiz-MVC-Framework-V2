
<?= $this->start('title'); ?>
รายชื่อพนักงาน
<?= $this->end(); ?>

<h1>รายชื่อพนักงาน</h1>
<a href="<?= $this->url('/employees/create') ?>">สร้างพนักงานใหม่</a>

<?php if ($employees === []): ?>
    <p>ไม่มีพนักงานในระบบ</p>
<?php else: ?>
    <?php foreach ($employees as $employee): ?>
        <h2>
            รหัสพนักงาน : <?= $this->e($employee->employee_code) ?>
            ชื่อ : <?= $this->e($employee->first_name) ?>
            นามสกุล : <?= $this->e($employee->last_name) ?>
            แผนก : <?= $this->e($employee->department_name) ?>
            อีเมล : <?= $this->e($employee->email) ?>
        </h2>
    <?php endforeach; ?>
<?php endif; ?>