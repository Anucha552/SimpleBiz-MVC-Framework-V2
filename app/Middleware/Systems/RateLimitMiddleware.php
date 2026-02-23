<?php
/**
 * MIDDLEWARE RATE LIMITING
 * 
 * Middleware สำหรับนระบบ หรือ API Global Middleware
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

namespace App\Middleware\Systems;

use App\Core\Middleware;
use App\Core\Logger;
use App\Core\Cache;
use App\Core\Response;
use App\Core\Session;

class RateLimitMiddleware extends Middleware
{
    /**
     * Logger สำหรับบันทึกเหตุการณ์ที่เกี่ยวข้องกับการจำกัดอัตรา เช่น การเกินขอบเขตหรือความพยายามที่น่าสงสัย
     */
    private Logger $logger;

    /**
    * Cache สำหรับเก็บข้อมูลการจำกัดอัตรา เช่น จำนวนคำขอและเวลาที่จะรีเซ็ต เพื่อให้สามารถตรวจสอบและจัดการการจำกัดอัตราได้อย่างมีประสิทธิภาพ
    */
    private Cache $cache;

    /**
    * Session สำหรับเก็บข้อมูลการจำกัดอัตราในกรณีที่ไม่สามารถใช้ Cache ได้ เพื่อให้ยังสามารถจัดการการจำกัดอัตราได้แม้ในสภาพแวดล้อมที่ไม่มีระบบ Cache
    */
    private Session $session;
    
    /**
    * จำนวนคำขอสูงสุดที่อนุญาตในแต่ละ window
    */
    private int $maxRequests = 30; 

    /**
     * ระยะเวลา window ในวินาทีที่ใช้ในการจำกัดอัตรา เช่น 60 วินาที เพื่อให้สามารถกำหนดช่วงเวลาที่จะนับคำขอและรีเซ็ตข้อมูลการจำกัดอัตราได้อย่างเหมาะสมตามความต้องการของแอปพลิเคชัน
     */
    private int $windowSeconds = 60;

    /**
    * ตัวแปรเพื่อระบุว่าควรใช้ Cache หรือไม่ โดยจะพยายามใช้ Cache ก่อน และถ้าไม่สามารถใช้ได้ จะใช้ Session แทน เพื่อให้การจัดการการจำกัดอัตรามีความยืดหยุ่นและสามารถทำงานได้ในสภาพแวดล้อมที่แตกต่างกัน
    */
    private bool $useCache = true;

    /**
     * สร้าง instance ของ RateLimitMiddleware
     * จุดประสงค์: กำหนดค่าพื้นฐานสำหรับการจำกัดอัตรา เช่น จำนวนคำขอสูงสุดและระยะเวลา window รวมถึงการตั้งค่า Logger และการพยายามใช้ Cache เพื่อให้การจัดการการจำกัดอัตรามีประสิทธิภาพและสามารถปรับแต่งได้ตามความต้องการของแอปพลิเคชัน
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
     * จุดประสงค์: ตรวจสอบจำนวนคำขอที่ผู้ใช้หรือ IP address ได้ทำในช่วงเวลาที่กำหนด และถ้าเกินขอบเขตที่อนุญาต ให้ปฏิเสธคำขอและส่งข้อความแจ้งเตือนกลับไปยังผู้ใช้ รวมถึงการบันทึกเหตุการณ์ที่เกี่ยวข้องกับการจำกัดอัตราเพื่อให้สามารถตรวจสอบและวิเคราะห์ได้ในภายหลัง
     *
     * @param \App\Core\Request|null $request คำขอที่เข้ามา (สามารถเป็น null ได้ในกรณีที่ไม่มีคำขอ)
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
            // รีเซ็ตข้อมูลการจำกัดอัตรา
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

            // บันทึกข้อมูลที่อัปเดตแม้เกินขอบเขต เพื่อให้รีเซ็ตถูกต้อง
            $this->saveRateLimitData($identifier, $currentCount, $resetTime);

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
     * จุดประสงค์: สร้าง identifier ที่ไม่ซ้ำสำหรับผู้ใช้หรือ IP address เพื่อใช้ในการติดตามจำนวนคำขอที่ทำโดยแต่ละผู้ใช้หรือ IP address ในการจำกัดอัตรา โดยจะพยายามใช้ user_id ถ้าเข้าสู่ระบบแล้ว และถ้าไม่ได้เข้าสู่ระบบ จะใช้ IP address แทน เพื่อให้สามารถจัดการการจำกัดอัตราได้อย่างมีประสิทธิภาพและแม่นยำ
     * 
     * @return string identifier ที่ไม่ซ้ำสำหรับผู้ใช้หรือ IP address เพื่อใช้ในการติดตามจำนวนคำขอในระบบจำกัดอัตรา
     */
    private function getIdentifier(): string
    {
        // ใช้ user_id ถ้าเข้าสู่ระบบแล้ว
        if ($this->isAuthenticated()) {
            return 'user_' . $this->getUserId();
        }

        // ถ้าไม่ได้เข้าสู่ระบบ ใช้ IP address
        $ip = $this->getClientIp();

        return 'ip_' . $ip;
    }

    /**
     * รับข้อมูลการจำกัดอัตราสำหรับ identifier
     * จุดประสงค์: ดึงข้อมูลการจำกัดอัตราสำหรับผู้ใช้หรือ IP address ที่ระบุ เพื่อใช้ในการตรวจสอบว่าผู้ใช้หรือ IP address นั้นได้ทำคำขอเกินขอบเขตที่กำหนดหรือไม่
     * 
     * @param string $identifier identifier ที่ไม่ซ้ำสำหรับผู้ใช้หรือ IP address เพื่อใช้ในการดึงข้อมูลการจำกัดอัตรา
     * @return array ข้อมูลการจำกัดอัตราที่ประกอบด้วยจำนวนคำขอและเวลาที่จะรีเซ็ต เพื่อใช้ในการตรวจสอบและจัดการการจำกัดอัตราได้อย่างมีประสิทธิภาพ
     */
    private function getRateLimitData(string $identifier): array
    {
        $key = "rate_limit_{$identifier}";

        if ($this->useCache) {
            $data = $this->cache->get($key);
            if (is_string($data) && $data !== '') {
                $decoded = json_decode($data, true);
                if (is_array($decoded) && isset($decoded['count'], $decoded['reset_time'])) {
                    return $decoded;
                }
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
     * บันทึกข้อมูลการจำกัดอัตราสำหรับ identifier
     * จุดประสงค์: บันทึกข้อมูลการจำกัดอัตราสำหรับผู้ใช้หรือ IP address ที่ระบุ เพื่อใช้ในการตรวจสอบและจัดการการจำกัดอัตรา
     * 
     * @param string $identifier identifier ที่ไม่ซ้ำสำหรับผู้ใช้หรือ IP address เพื่อใช้ในการบันทึกข้อมูลการจำกัดอัตรา
     * @param int $count จำนวนคำขอที่ทำไปแล้ว
     * @param int $resetTime เวลาที่จะรีเซ็ตข้อมูลการจำกัดอัตรา
     */
    private function saveRateLimitData(string $identifier, int $count, int $resetTime): void
    {
        $key = "rate_limit_{$identifier}";
        $data = [
            'count' => $count,
            'reset_time' => $resetTime,
        ];

        if ($this->useCache) {
            // บันทึกใน Cache พร้อม TTL ตามเวลาที่เหลือจริง
            $ttl = max(1, $resetTime - time());
            $this->cache->set($key, json_encode($data), $ttl);
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
     * จุดประสงค์: ส่ง headers ให้ client ทราบสถานะการจำกัดอัตรา เช่น จำนวนคำขอที่เหลือและเวลาที่จะรีเซ็ต
     * 
     * @param int $currentCount จำนวนคำขอที่ทำไปแล้วในช่วงเวลาปัจจุบัน
     * @param int $resetTime เวลาที่จะรีเซ็ตข้อมูลการจำกัดอัตรา
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า HTTP headers ที่เกี่ยวข้องกับการจำกัดอัตราเพื่อให้ client ทราบสถานะการจำกัดอัตรา
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
