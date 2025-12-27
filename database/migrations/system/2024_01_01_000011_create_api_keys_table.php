<?php

use App\Core\Migration;

class CreateApiKeysTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create api_keys table
        $apiKeys = "
        CREATE TABLE IF NOT EXISTS api_keys (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัส API Key',
            user_id INT UNSIGNED NULL COMMENT 'รหัสผู้ใช้ (NULL สำหรับ API ทั่วไป)',
            name VARCHAR(100) NOT NULL COMMENT 'ชื่อ API Key เช่น Mobile App, Third Party',
            api_key VARCHAR(64) NOT NULL UNIQUE COMMENT 'API Key',
            api_secret VARCHAR(64) NULL COMMENT 'API Secret',
            scopes TEXT NULL COMMENT 'ขอบเขตสิทธิ์ (JSON array)',
            rate_limit INT UNSIGNED DEFAULT 1000 COMMENT 'จำนวน request ต่อชั่วโมง',
            is_active BOOLEAN DEFAULT TRUE COMMENT 'เปิดใช้งาน',
            last_used_at TIMESTAMP NULL COMMENT 'วันที่ใช้งานล่าสุด',
            expires_at TIMESTAMP NULL COMMENT 'วันหมดอายุ',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่แก้ไข',
            
            INDEX idx_api_key (api_key),
            INDEX idx_user_id (user_id),
            INDEX idx_is_active (is_active),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตาราง API Keys สำหรับ Authentication'
        ";

        $this->execute($apiKeys);

        // Create api_key_requests table for rate limiting
        $apiKeyRequests = "
        CREATE TABLE IF NOT EXISTS api_key_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'รหัสล็อก Request',
            api_key_id INT UNSIGNED NOT NULL COMMENT 'รหัส API Key',
            endpoint VARCHAR(255) NOT NULL COMMENT 'API Endpoint',
            method VARCHAR(10) NOT NULL COMMENT 'HTTP Method (GET, POST, PUT, DELETE)',
            ip_address VARCHAR(45) NULL COMMENT 'IP Address',
            status_code SMALLINT UNSIGNED NULL COMMENT 'HTTP Status Code',
            response_time INT UNSIGNED NULL COMMENT 'เวลาตอบสนอง (มิลลิวินาที)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่เรียก API',
            
            INDEX idx_api_key_id (api_key_id),
            INDEX idx_created_at (created_at),
            INDEX idx_endpoint (endpoint),
            
            FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางล็อก API Requests สำหรับ Rate Limiting'
        ";

        $this->execute($apiKeyRequests);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS api_key_requests");
        $this->execute("DROP TABLE IF EXISTS api_keys");
    }
}
