<?php

use App\Core\Migration;

class CreateUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTable('user_roles', function ($table) {
            $table->increments('id')->comment('รหัสรายการ');
            $table->integer('user_id')->comment('รหัสผู้ใช้');
            $table->integer('role_id')->comment('รหัสบทบาท');
            $table->timestamps();

            $table->index('user_id');
            $table->index('role_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS user_roles");
    }
}
