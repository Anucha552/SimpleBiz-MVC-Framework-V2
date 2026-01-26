<?php
/**
 * MIDDLEWARE SECURITY HEADERS
 *
 * จุดประสงค์: ใส่ HTTP security headers พื้นฐานให้ทั้งเว็บและ API
 * เพื่อยกระดับความปลอดภัยแบบค่าเริ่มต้น (secure-by-default)
 *
 * หมายเหตุ:
 * - CSP ที่เข้มมากอาจทำให้ assets บางอย่างพังได้ (ปรับผ่าน env)
 * - HSTS ควรเปิดเฉพาะตอนใช้ HTTPS จริง
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class SecurityHeadersMiddleware extends Middleware
{
    public function handle(?Request $request = null): bool|Response
    {
        $headers = [
            // Prevent MIME sniffing
            'X-Content-Type-Options' => 'nosniff',

            // Reduce referrer leakage
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Basic clickjacking protection (CSP frame-ancestors is stronger)
            'X-Frame-Options' => 'SAMEORIGIN',

            // Privacy / feature restrictions (modern browsers)
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];

        // Optional CSP (keep it lenient by default to avoid breaking projects)
        $csp = \env('SECURITY_CSP');
        if ($csp) {
            $headers['Content-Security-Policy'] = $csp;
        }

        // Optional HSTS (only if HTTPS)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        if ($isHttps && \env('SECURITY_HSTS') === 'true') {
            // 6 months by default
            $maxAge = \env('SECURITY_HSTS_MAX_AGE') ?: '15552000';
            $includeSubDomains = \env('SECURITY_HSTS_INCLUDE_SUBDOMAINS') === 'true' ? '; includeSubDomains' : '';
            $preload = \env('SECURITY_HSTS_PRELOAD') === 'true' ? '; preload' : '';
            $headers['Strict-Transport-Security'] = 'max-age=' . $maxAge . $includeSubDomains . $preload;
        }

        // Preferred path: aggregate into Request so Router/Response send once.
        if ($request !== null) {
            $request->addResponseHeaders($headers);
            return true;
        }

        // Fallback: legacy direct header() if no Request is provided.
        if (headers_sent()) {
            return true;
        }

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        return true;
    }
}
