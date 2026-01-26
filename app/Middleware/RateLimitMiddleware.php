<?php
/**
 * MIDDLEWARE RATE LIMITING
 * 
 * จุดประสงค์: จำกัดจำนวนคำขอเพื่อป้องกัน abuse และ DDoS attacks
 * 
 * การใช้งาน:
 * ใช้กับ endpoints ที่ละเอียดอ่อนหรือใช้ทรัพยากรมาก:
 * - API endpoints ทั้งหมด
 * - ฟอร์มการเข้าสู่ระบบ
 * - ฟอร์มการสมัครสมาชิก
 * - การค้นหา
 * 
 * วิธีการทำงาน:
 * 1. ติดตามจำนวนคำขอต่อ IP address ในช่วงเวลาที่กำหนด
 * 2. ถ้าเกินขอบเขต ปฏิเสธคำขอ
 * 3. ใช้ Cache หรือ Session เก็บข้อมูลการจำกัดอัตรา
 * 
 * อัลกอริทึม:
 * - Token Bucket: อนุญาตระดับ burst สั้นๆ
 * - Fixed Window: นับคำขอในช่วงเวลาที่แน่นอน
 * 
 * การกำหนดค่า:
 * - maxRequests: จำนวนคำขอสูงสุดต่อ window
 * - windowSeconds: ระยะเวลา window ในวินาที
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Cache;
use App\Core\Response;
use App\Core\Session;

class RateLimitMiddleware extends Middleware
{
    private Logger $logger;
    private Cache $cache;
    private Session $session;
    // การกำหนดค่าเริ่มต้น - สามารถปรับแต่งได้
    private int $maxRequests = 60;      // คำขอสูงสุดต่อ window
    private int $windowSeconds = 60;    // 1 นาที
    private bool $useCache = true;      // ใช้ Cache แทน Session

    /**
     * Constructor
     * 
     * @param int|null $maxRequests จำนวนคำขอสูงสุด (null = ใช้ค่าเริ่มต้น)
     * @param int|null $windowSeconds ระยะเวลา window (null = ใช้ค่าเริ่มต้น)
     */
    public function __construct(?int $maxRequests = null, ?int $windowSeconds = null)
    {
        $this->logger = new Logger();

        // อัปเดตการกำหนดค่าถ้ามี
        if ($maxRequests !== null) {
            $this->maxRequests = $maxRequests;
        }
        if ($windowSeconds !== null) {
            $this->windowSeconds = $windowSeconds;
        }

        // ลองใช้ Cache ถ้าสามารถใช้ได้
        try {
            $this->cache = new Cache();
        } catch (\Exception $e) {
            $this->useCache = false;
            // ถ้า Cache ไม่สามารถใช้ได้ ใช้ Session แทน
            Session::start();
        }
    }

    /**
     * จัดการการจำกัดอัตรา
     * 
        * @return bool|Response True เพื่อดำเนินการต่อ, false เพื่อหยุด, หรือ Response เพื่อส่งกลับทันที
     */
        public function handle(?\App\Core\Request $request = null): bool|Response
    {
        // รับ identifier ที่ไม่ซ้ำสำหรับผู้ใช้ (IP address)
        $identifier = $this->getIdentifier();

        // รับจำนวนคำขอปัจจุบันและเวลา reset
        $rateLimitData = $this->getRateLimitData($identifier);
        $currentCount = $rateLimitData['count'];
        $resetTime = $rateLimitData['reset_time'];

        // ตรวจสอบว่าถึงเวลา reset หรือไม่
        $now = time();
        if ($now >= $resetTime) {
            // Reset counter
            $currentCount = 0;
            $resetTime = $now + $this->windowSeconds;
        }

        // เพิ่มจำนวนคำขอ
        $currentCount++;

        // ตรวจสอบว่าเกินขอบเขตหรือไม่
        if ($currentCount > $this->maxRequests) {
            $this->logger->security('rate_limit.exceeded', [
                'identifier' => $identifier,
                'count' => $currentCount,
                'limit' => $this->maxRequests,
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            // ส่ง headers ที่เกี่ยวข้อง
            $this->setRateLimitHeaders($request, $currentCount, $resetTime);

            return $this->jsonError('Rate limit exceeded. Try again later.', 429);
        }

        // บันทึกข้อมูลที่อัปเดต
        $this->saveRateLimitData($identifier, $currentCount, $resetTime);

        // ส่ง headers ให้ client ทราบสถานะ
        $this->setRateLimitHeaders($request, $currentCount, $resetTime);

        return true;
    }

    /**
     * รับ identifier สำหรับผู้ใช้
     * 
     * @return string
     */
    private function getIdentifier(): string
    {
        // ใช้ user_id ถ้าเข้าสู่ระบบแล้ว
        if ($this->isAuthenticated()) {
            return 'user_' . $this->getUserId();
        }

        // ถ้าไม่ได้เข้าสู่ระบบ ใช้ IP address
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // ถ้ามีหลาย IP (proxy chain) ใช้ตัวแรก
        if (strpos($ip, ',') !== false) {
            $ip = explode(',', $ip)[0];
        }

        return 'ip_' . trim($ip);
    }

    /**
     * รับข้อมูลการจำกัดอัตราสำหรับ identifier
     * 
     * @param string $identifier
     * @return array
     */
    private function getRateLimitData(string $identifier): array
    {
        $key = "rate_limit_{$identifier}";

        if ($this->useCache) {
            $data = $this->cache->get($key);
            if ($data) {
                return json_decode($data, true);
            }
        } else {
            Session::start();
            $all = Session::get('rate_limits', []);
            if (isset($all[$identifier])) {
                return $all[$identifier];
            }
        }

        // ข้อมูลเริ่มต้น
        return [
            'count' => 0,
            'reset_time' => time() + $this->windowSeconds,
        ];
    }

    /**
     * บันทึกข้อมูลการจำกัดอัตรา
     * 
     * @param string $identifier
     * @param int $count
     * @param int $resetTime
     */
    private function saveRateLimitData(string $identifier, int $count, int $resetTime): void
    {
        $key = "rate_limit_{$identifier}";
        $data = [
            'count' => $count,
            'reset_time' => $resetTime,
        ];

        if ($this->useCache) {
            // บันทึกใน Cache พร้อม TTL
            $this->cache->set($key, json_encode($data), $this->windowSeconds);
        } else {
            // บันทึกใน Session
            Session::start();
            $all = Session::get('rate_limits', []);
            $all[$identifier] = $data;
            Session::set('rate_limits', $all);
        }
    }

    /**
     * ตั้งค่า HTTP headers สำหรับการจำกัดอัตรา
     * 
     * @param int $currentCount
     * @param int $resetTime
     */
    private function setRateLimitHeaders(?\App\Core\Request $request, int $currentCount, int $resetTime): void
    {
        if ($request === null) {
            return;
        }

        $request->setResponseHeader('X-RateLimit-Limit', (string) $this->maxRequests);
        $request->setResponseHeader('X-RateLimit-Remaining', (string) max(0, $this->maxRequests - $currentCount));
        $request->setResponseHeader('X-RateLimit-Reset', (string) $resetTime);
    }
}
