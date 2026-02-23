<?php
/**
 * config/logging.php
 *
 * จุดประสงค์: จัดการการตั้งค่าที่เกี่ยวข้องกับระบบการบันทึก (Logging) เช่น ขนาดสูงสุดของไฟล์บันทึก จำนวนวันที่จะเก็บบันทึก และระดับรายละเอียดของข้อมูลที่บันทึก
 *
 * ตัวอย่างการใช้งาน:
 * ```php
 * $maxLogSize = config('logging.max_log_size');
 * ```
 */

require_once __DIR__ . '/env.php';

$env = env('APP_ENV', 'development', 'string');
$detailed = env('LOG_DETAILED', false, 'bool');
$requestBody = env('LOG_REQUEST_BODY', false, 'bool');
if ($env === 'production') {
    $detailed = false;
    $requestBody = false;
}

return [
    'max_log_size' => env('MAX_LOG_SIZE', 0, 'int'),
    'retention_days' => env('LOG_RETENTION_DAYS', 7, 'int'),
    'detailed' => $detailed,
    'request_body' => $requestBody,
];
