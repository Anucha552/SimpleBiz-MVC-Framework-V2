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
            $table->string('first_name', 100)->nullable()->comment('ชื่อจริง');
            $table->string('last_name', 100)->nullable()->comment('นามสกุล');
            $table->string('phone', 20)->nullable()->comment('เบอร์โทรศัพท์');
            $table->string('status', 20)->default('active')->comment('สถานะ: active=ใช้งาน, inactive=ปิดใช้งาน, banned=ระงับ');
            $table->timestamp('email_verified_at')->nullable()->comment('วันที่ยืนยันอีเมล');
            $table->string('remember_token', 100)->nullable()->comment('Token จดจำการเข้าสู่ระบบ');
            $table->timestamp('last_login_at')->nullable()->comment('วันที่เข้าสู่ระบบล่าสุด');
            $table->string('last_login_ip', 45)->nullable()->comment('IP Address ล่าสุด');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('วันที่ลบ (Soft Delete)');

            // additional indexes
            $table->index('username');
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS users");
    }
}
