<?php

use App\Core\Migration;

class CreateRolePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTable('role_permissions', function ($table) {
            $table->increments('id')->comment('รหัสรายการ');
            $table->integer('role_id')->comment('รหัสบทบาท');
            $table->string('permission', 150)->comment('สิทธิ์');
            $table->timestamps();

            $table->index('role_id');
            $table->index('permission');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS role_permissions");
    }
}
