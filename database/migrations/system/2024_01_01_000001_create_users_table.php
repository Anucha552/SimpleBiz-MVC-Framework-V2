<?php

use App\Core\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTable('users', function($table) {
            $table->increments('id')->comment('รหัสผู้ใช้');
            $table->string('username', 50)->comment('ชื่อผู้ใช้')->unique();
            $table->string('email', 100)->comment('อีเมล')->unique();
            $table->string('password', 255)->comment('รหัสผ่าน (Hashed)');
            $table->string('name', 150)->nullable()->comment('ชื่อ-นามสกุล (full name)');
            $table->string('first_name', 100)->nullable()->comment('ชื่อจริง');
            $table->string('last_name', 100)->nullable()->comment('นามสกุล');
            $table->string('phone', 20)->nullable()->comment('เบอร์โทรศัพท์');
            $table->string('role', 50)->default('user')->comment('บทบาทหลักของผู้ใช้');
            $table->integer('role_id')->nullable()->comment('อ้างอิง roles.id (ถ้ามี)');
            $table->tinyInteger('is_admin')->default(0)->comment('ผู้ดูแลระบบ (1=ใช่, 0=ไม่ใช่)');
            $table->text('roles')->nullable()->comment('บทบาทหลายค่า (JSON/CSV)');
            $table->text('permissions')->nullable()->comment('สิทธิ์แบบกำหนดเอง (JSON/CSV)');
            $table->string('status', 20)->default('active')->comment('สถานะ: active=ใช้งาน, inactive=ปิดใช้งาน, banned=ระงับ');
            $table->timestamp('email_verified_at')->nullable()->comment('วันที่ยืนยันอีเมล');
            $table->string('remember_token', 100)->nullable()->comment('Token จดจำการเข้าสู่ระบบ');
            $table->timestamp('last_login_at')->nullable()->comment('วันที่เข้าสู่ระบบล่าสุด');
            $table->string('last_login_ip', 45)->nullable()->comment('IP Address ล่าสุด');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('วันที่ลบ (Soft Delete)');

            // ดัชนีสำหรับการค้นหาที่รวดเร็ว
            $table->index('username');
            $table->index('email');
            $table->index('status');
            $table->index('role');
            $table->index('role_id');
            $table->index('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     * จุดประสงค์: ลบตาราง users ออกจากฐานข้อมูลเมื่อทำการย้อนกลับการย้ายข้อมูล
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS users");
    }
}
