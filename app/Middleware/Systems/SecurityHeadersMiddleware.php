<?php
/**
 * MIDDLEWARE SECURITY HEADERS
 * 
 * Middleware สำหรับนระบบ หรือ Global Middleware
 *
 * จุดประสงค์: ใส่ HTTP security headers พื้นฐานให้ทั้งเว็บและ API
 * เพื่อยกระดับความปลอดภัยแบบค่าเริ่มต้น (secure-by-default)
 *
 * หมายเหตุ:
 * - CSP ที่เข้มมากอาจทำให้ assets บางอย่างพังได้ (ปรับผ่าน env)
 * - HSTS ควรเปิดเฉพาะตอนใช้ HTTPS จริง
 */

namespace App\Middleware\Systems;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Config;

class SecurityHeadersMiddleware extends Middleware
{
    /**
     * จัดการกับคำขอและเพิ่ม HTTP security headers พื้นฐานให้กับการตอบกลับ
     * จุดประสงค์: เพื่อเพิ่มความปลอดภัยให้กับเว็บและ API โดยการตั้งค่า HTTP headers ที่เหมาะสม เช่น Content-Security-Policy, Strict-Transport-Security, X-Content-Type-Options, Referrer-Policy, X-Frame-Options และ Permissions-Policy
     * 
     * @param Request|null $request คำขอที่เข้ามา ซึ่งสามารถเป็น null ได้ในกรณีที่ middleware นี้ถูกเรียกโดยไม่มีคำขอ (เช่น ในบางสถานการณ์ของ CLI)
     * @return bool|Response คืนค่า true หากการประมวลผลสำเร็จและสามารถดำเนินการต่อไปยัง middleware ถัดไปได้ หรือคืนค่า Response หากต้องการส่งการตอบกลับทันที
     */
    public function handle(?Request $request = null): bool|Response
    {
        $headers = [
            // ป้องกันการตรวจสอบ MIME type ที่ไม่ปลอดภัย
            'X-Content-Type-Options' => 'nosniff',

            // ควบคุมการส่ง referrer ในการเชื่อมโยงข้าม origin เพื่อป้องกันข้อมูลรั่วไหล
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // ป้องกันการฝังหน้าเว็บใน iframe จากโดเมนอื่นเพื่อป้องกันการโจมตีแบบ clickjacking
            'X-Frame-Options' => 'SAMEORIGIN',

            // จำกัดการเข้าถึงฟีเจอร์ของเบราว์เซอร์ เช่น geolocation, microphone, camera เพื่อป้องกันการใช้งานที่ไม่ปลอดภัย
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        // ตั้งค่า Content-Security-Policy (CSP) หากมีการกำหนดในคอนฟิก เพื่อป้องกันการโจมตีแบบ XSS และการโหลดทรัพยากรที่ไม่ปลอดภัย
        $csp = (string) Config::get('security.csp', '');
        if ($csp) {
            $headers['Content-Security-Policy'] = $csp;
        }

        // ตั้งค่า HSTS (เฉพาะเมื่อใช้ HTTPS)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        // เปิด HSTS หากกำหนดในคอนฟิกและใช้ HTTPS เพื่อบังคับให้เบราว์เซอร์เชื่อมต่อผ่าน HTTPS เท่านั้น
        if ($isHttps && Config::get('security.hsts', false) === true) {
            // กำหนดค่า HSTS โดยใช้ max-age, includeSubDomains และ preload ตามที่กำหนดในคอนฟิก
            $maxAge = (string) Config::get('security.hsts_max_age', 15552000);
            $includeSubDomains = Config::get('security.hsts_include_subdomains', false) === true ? '; includeSubDomains' : '';
            $preload = Config::get('security.hsts_preload', false) === true ? '; preload' : '';
            $headers['Strict-Transport-Security'] = 'max-age=' . $maxAge . $includeSubDomains . $preload;
        }

        // เพิ่ม headers ลงในคำขอหากมี Request object หรือใช้ header() โดยตรงหากไม่มี Request (เช่น ใน CLI)
        if ($request !== null) {
            $request->addResponseHeaders($headers);
            return true;
        }

        // ถ้าไม่มี Request (เช่น ใน CLI) ให้ตั้งค่า headers โดยตรงผ่าน header() function
        if (headers_sent()) {
            return true;
        }

        // ตั้งค่า headers โดยตรงผ่าน header() function
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        return true;
    }
}
