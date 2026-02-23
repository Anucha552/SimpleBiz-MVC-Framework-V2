<?php

use App\Core\Migration;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTable('roles', function ($table) {
            $table->increments('id')->comment('รหัสบทบาท');
            $table->string('name', 100)->comment('ชื่อบทบาท')->unique();
            $table->string('slug', 100)->comment('ชื่อย่อบทบาท')->unique();
            $table->text('description')->nullable()->comment('คำอธิบาย');
            $table->timestamps();

            $table->index('name');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     * จุดประสงค์: ลบตาราง roles ออกจากฐานข้อมูลเมื่อทำการย้อนกลับการย้ายข้อมูล
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS roles");
    }
}
