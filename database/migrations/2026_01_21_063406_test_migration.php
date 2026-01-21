<?php
/**
 * Migration: TestMigration
 */

namespace Database\Migrations;

use App\Core\Migration;

class TestMigration extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS example_table (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $this->execute($sql);
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS example_table");
    }
}