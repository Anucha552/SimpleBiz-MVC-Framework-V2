<?php
/**
 * TestAuthMiddleware
 * 
 * จุดประสงค์: [อธิบายหน้าที่ของ middleware]
 */

namespace App\Middleware;

use App\Core\Middleware;

class TestAuthMiddleware extends Middleware
{
    /**
     * จัดการคำขอ
     * 
     * @return bool คืนค่า true เพื่อดำเนินการต่อ, false เพื่อหยุด
     */
    public function handle(): bool
    {
        // TODO: Implement middleware logic
        
        return true;
    }
}
