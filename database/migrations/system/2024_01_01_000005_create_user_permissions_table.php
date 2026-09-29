<?php

use App\Core\Migration;
use App\Core\Blueprint;

class CreateUserPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTable('user_permissions', function (Blueprint $table) {
            $table->increments('id')->comment('รหัสรายการ');
            $table->integer('user_id')->comment('รหัสผู้ใช้');
            $table->string('permission', 150)->comment('สิทธิ์');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('วันที่ลบ (Soft Delete)');

            $table->index('user_id');
            $table->index('permission');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS user_permissions");
    }
}
