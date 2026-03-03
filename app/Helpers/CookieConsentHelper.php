<?php
/**
 * Cookie Consent Helper
 *
 * จุดประสงค์: เรนเดอร์แบนเนอร์ขอความยินยอมคุกกี้แบบจัดหมวดหมู่
 * การใช้งาน:
 * 1 ) แสดงแบนเนอร์ใน layout หลัก
 * ใส่ helper ใน layout ที่ใช้งานจริง เช่น main.php
 * <?php
 *use App\Helpers\CookieConsentHelper;
 * ?>
 * ...
 * <?= CookieConsentHelper::render(); ?>
 * 
 * 2) ปรับข้อความ/ปุ่ม/หมวด (ถ้าต้องการ)
 * <?= CookieConsentHelper::render([
 *   'message' => 'เราใช้คุกกี้เพื่อปรับปรุงบริการของเรา',
 *   'acceptLabel' => 'ยอมรับทั้งหมด',
 *   'rejectLabel' => 'ปฏิเสธ',
 *   'saveLabel' => 'บันทึกการตั้งค่า',
 *   'categories' => [
 *       'necessary' => [
 *           'label' => 'จำเป็น',
 *           'description' => 'ช่วยให้เว็บทำงานได้ปกติ',
 *           'required' => true,
 *       ],
 *       'analytics' => [
 *           'label' => 'สถิติ',
 *           'description' => 'ช่วยวิเคราะห์การใช้งาน',
 *           'required' => false,
 *       ],
 *       'marketing' => [
 *           'label' => 'การตลาด',
 *           'description' => 'ใช้เพื่อปรับเนื้อหาและโฆษณา',
 *           'required' => false,
 *       ],
 *   ],
 *]); ?>
 *
 * 3) คุมการโหลดสคริปต์ตามหมวด
 * ใส่ data-consent="analytics" หรือ data-consent="marketing"
 * ตั้ง type="text/plain" เพื่อไม่ให้รันจนกว่าจะยินยอม
 *<script type="text/plain" data-consent="analytics" src="https://example.com/analytics.js"></script>
 *<script type="text/plain" data-consent="marketing">
 *    console.log('marketing script');
 *</script>
 *
 * 4) พฤติกรรมที่เกิดขึ้น
 * กด “ยอมรับทั้งหมด” จะเปิดทุกหมวด
 * กด “ปฏิเสธ” จะเหลือเฉพาะหมวดที่บังคับ (required: true)
 * กด “บันทึกการตั้งค่า” จะใช้ค่าที่เลือกไว้
 * สถานะถูกเก็บในคุกกี้ชื่อ cookie_consent เป็น JSON
 */

namespace App\Helpers;

class CookieConsentHelper
{
    /**
    * เรนเดอร์แบนเนอร์ Cookie Consent พร้อม CSS/JS
    * จุดประสงค์: แสดงแบนเนอร์และเก็บสถานะความยินยอมไว้ในคุกกี้ฝั่ง client
    * รองรับการปฏิเสธและเลือกหมวดคุกกี้
     * ตัวอย่างการใช้งาน:
     * ```php
     * echo CookieConsentHelper::render([
     *     'message' => 'เราใช้คุกกี้เพื่อปรับปรุงประสบการณ์ของคุณ',
    *     'acceptLabel' => 'ยอมรับทั้งหมด',
    *     'rejectLabel' => 'ปฏิเสธ',
    *     'saveLabel' => 'บันทึกการตั้งค่า',
     * ]);
     * ```
     *
     * @param array $options ตัวเลือกสำหรับการปรับแต่งแบนเนอร์
     * @return string HTML/CSS/JS ที่พร้อมใช้งาน
     */
    public static function render(array $options = []): string
    {
        $defaults = [
            'cookieName' => 'cookie_consent',
            'maxAge' => 60 * 60 * 24 * 365,
            'path' => '/',
            'sameSite' => 'Lax',
            'message' => 'เราใช้คุกกี้เพื่อปรับปรุงประสบการณ์ใช้งานของคุณ หากกดยอมรับ ถือว่าคุณยินยอมให้ใช้งานคุกกี้',
            'acceptLabel' => 'ยอมรับทั้งหมด',
            'rejectLabel' => 'ปฏิเสธ',
            'saveLabel' => 'บันทึกการตั้งค่า',
            'id' => 'cookie-consent',
            'acceptId' => 'cookie-consent-accept',
            'rejectId' => 'cookie-consent-reject',
            'saveId' => 'cookie-consent-save',
            'categories' => [
                'necessary' => [
                    'label' => 'จำเป็น',
                    'description' => 'ช่วยให้เว็บทำงานได้ปกติ ไม่สามารถปิดได้',
                    'required' => true,
                ],
                'analytics' => [
                    'label' => 'สถิติ',
                    'description' => 'ช่วยวิเคราะห์การใช้งานเพื่อปรับปรุงบริการ',
                    'required' => false,
                ],
                'marketing' => [
                    'label' => 'การตลาด',
                    'description' => 'ใช้เพื่อปรับเนื้อหาและโฆษณาให้เหมาะสม',
                    'required' => false,
                ],
            ],
        ];

        $config = array_merge($defaults, $options);

        $cookieName = (string) $config['cookieName'];
        $maxAge = (int) $config['maxAge'];
        $path = (string) $config['path'];
        $sameSite = (string) $config['sameSite'];
        $message = htmlspecialchars((string) $config['message'], ENT_QUOTES, 'UTF-8');
        $acceptLabel = htmlspecialchars((string) $config['acceptLabel'], ENT_QUOTES, 'UTF-8');
        $rejectLabel = htmlspecialchars((string) $config['rejectLabel'], ENT_QUOTES, 'UTF-8');
        $saveLabel = htmlspecialchars((string) $config['saveLabel'], ENT_QUOTES, 'UTF-8');
        $id = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $config['id']) ?: 'cookie-consent';
        $acceptId = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $config['acceptId']) ?: 'cookie-consent-accept';
        $rejectId = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $config['rejectId']) ?: 'cookie-consent-reject';
        $saveId = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $config['saveId']) ?: 'cookie-consent-save';

        $categories = is_array($config['categories']) ? $config['categories'] : [];
        if ($categories === []) {
            $categories = $defaults['categories'];
        }

        $normalizedCategories = [];
        foreach ($categories as $key => $category) {
            $safeKey = preg_replace('/[^a-z0-9_\-]/i', '', (string) $key);
            if ($safeKey === '') {
                continue;
            }

            $label = htmlspecialchars((string) ($category['label'] ?? $safeKey), ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars((string) ($category['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $required = (bool) ($category['required'] ?? false);
            $normalizedCategories[$safeKey] = [
                'label' => $label,
                'description' => $description,
                'required' => $required,
            ];
        }

        if ($normalizedCategories === []) {
            $normalizedCategories = $defaults['categories'];
        }

        $categoryHtml = '';
        foreach ($normalizedCategories as $key => $category) {
            $checkboxId = $id . '-' . $key;
            $checked = $category['required'] ? 'checked disabled' : '';
            $requiredBadge = $category['required'] ? '<span class="cookie-consent__badge">จำเป็น</span>' : '';
            $categoryHtml .= <<<HTML
            <label class="cookie-consent__option" for="{$checkboxId}">
                <input type="checkbox" id="{$checkboxId}" data-consent-category="{$key}" {$checked}>
                <div class="cookie-consent__option-content">
                    <div class="cookie-consent__option-title">{$category['label']} {$requiredBadge}</div>
                    <div class="cookie-consent__option-desc">{$category['description']}</div>
                </div>
            </label>
HTML;
        }

        $cookieJsName = json_encode($cookieName, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '"cookie_consent"';
        $cookieJsPath = json_encode($path, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '"/"';
        $cookieJsSameSite = json_encode($sameSite, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '"Lax"';
        $categoryKeys = json_encode(array_keys($normalizedCategories), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';

        $html = <<<HTML
<div id="{$id}" class="cookie-consent shadow" aria-live="polite" aria-label="Cookie consent">
    <div class="cookie-consent__inner">
        <div class="cookie-consent__text">{$message}</div>
        <div class="cookie-consent__actions">
            <button type="button" class="btn btn-sm btn-outline-light" id="{$rejectId}">
                {$rejectLabel}
            </button>
            <button type="button" class="btn btn-sm btn-primary" id="{$acceptId}">
                {$acceptLabel}
            </button>
        </div>
    </div>
    <div class="cookie-consent__options">
{$categoryHtml}
    </div>
    <div class="cookie-consent__footer">
        <button type="button" class="btn btn-sm btn-success" id="{$saveId}">
            {$saveLabel}
        </button>
    </div>
</div>
<style>
    .cookie-consent {
        position: fixed;
        left: 1rem;
        right: 1rem;
        bottom: 1rem;
        background: #111;
        color: #fff;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        display: none;
        z-index: 1050;
    }
    .cookie-consent__inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .cookie-consent__actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .cookie-consent__text {
        font-size: 0.9rem;
    }
    .cookie-consent__options {
        margin-top: 0.75rem;
        display: grid;
        gap: 0.5rem;
    }
    .cookie-consent__option {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
        background: #1a1a1a;
        padding: 0.5rem 0.75rem;
        border-radius: 0.4rem;
        cursor: pointer;
    }
    .cookie-consent__option input {
        margin-top: 0.2rem;
    }
    .cookie-consent__option-title {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .cookie-consent__option-desc {
        font-size: 0.8rem;
        color: #cfcfcf;
    }
    .cookie-consent__badge {
        display: inline-block;
        font-size: 0.7rem;
        padding: 0.05rem 0.4rem;
        border-radius: 999px;
        background: #2b2b2b;
        color: #e0e0e0;
        margin-left: 0.3rem;
    }
    .cookie-consent__footer {
        margin-top: 0.75rem;
        display: flex;
        justify-content: flex-end;
    }
    @media (max-width: 576px) {
        .cookie-consent__inner {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
<script>
    (function () {
        var consentKey = {$cookieJsName};
        var banner = document.getElementById('{$id}');
        var accept = document.getElementById('{$acceptId}');
        var reject = document.getElementById('{$rejectId}');
        var save = document.getElementById('{$saveId}');
        var categoryKeys = {$categoryKeys};

        if (!banner || !accept || !reject || !save) {
            return;
        }

        function getCookieValue() {
            var match = document.cookie.split('; ').find(function (row) {
                return row.indexOf(consentKey + '=') === 0;
            });
            if (!match) {
                return null;
            }
            return match.substring(consentKey.length + 1);
        }

        function parseConsent(rawValue) {
            if (!rawValue) {
                return null;
            }

            if (rawValue === 'accepted') {
                var all = {};
                categoryKeys.forEach(function (key) {
                    all[key] = true;
                });
                return all;
            }

            try {
                var decoded = decodeURIComponent(rawValue);
                var parsed = JSON.parse(decoded);
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
            } catch (err) {
                return null;
            }

            return null;
        }

        function setConsent(consent) {
            var maxAge = {$maxAge};
            var payload = encodeURIComponent(JSON.stringify(consent));
            var cookie = consentKey + '=' + payload + '; Max-Age=' + maxAge + '; Path=' + {$cookieJsPath} + '; SameSite=' + {$cookieJsSameSite};
            if (window.location.protocol === 'https:') {
                cookie += '; Secure';
            }
            document.cookie = cookie;
        }

        function getCurrentSelection() {
            var consent = {};
            var inputs = banner.querySelectorAll('[data-consent-category]');
            inputs.forEach(function (input) {
                consent[input.getAttribute('data-consent-category')] = input.checked;
            });
            return consent;
        }

        function applyConsent(consent) {
            if (!consent || typeof consent !== 'object') {
                return;
            }

            var inputs = banner.querySelectorAll('[data-consent-category]');
            inputs.forEach(function (input) {
                var key = input.getAttribute('data-consent-category');
                if (input.disabled) {
                    input.checked = true;
                    return;
                }
                input.checked = Boolean(consent[key]);
            });

            var scripts = document.querySelectorAll('script[data-consent]');
            scripts.forEach(function (script) {
                var category = script.getAttribute('data-consent');
                if (!category || !consent[category]) {
                    return;
                }

                if (script.getAttribute('data-consent-loaded') === '1') {
                    return;
                }

                var replacement = document.createElement('script');
                for (var i = 0; i < script.attributes.length; i++) {
                    var attr = script.attributes[i];
                    if (attr.name === 'type' || attr.name === 'data-consent') {
                        continue;
                    }
                    replacement.setAttribute(attr.name, attr.value);
                }
                if (script.src) {
                    replacement.src = script.src;
                } else {
                    replacement.text = script.text || script.textContent || '';
                }
                replacement.setAttribute('data-consent-loaded', '1');
                script.parentNode.insertBefore(replacement, script.nextSibling);
                script.setAttribute('data-consent-loaded', '1');
            });
        }

        var storedConsent = parseConsent(getCookieValue());

        if (!storedConsent) {
            banner.style.display = 'block';
        } else {
            applyConsent(storedConsent);
        }

        accept.addEventListener('click', function () {
            var consent = {};
            categoryKeys.forEach(function (key) {
                consent[key] = true;
            });
            setConsent(consent);
            applyConsent(consent);
            banner.style.display = 'none';
        });

        reject.addEventListener('click', function () {
            var consent = {};
            var inputs = banner.querySelectorAll('[data-consent-category]');
            inputs.forEach(function (input) {
                var key = input.getAttribute('data-consent-category');
                consent[key] = input.disabled ? true : false;
            });
            setConsent(consent);
            applyConsent(consent);
            banner.style.display = 'none';
        });

        save.addEventListener('click', function () {
            var consent = getCurrentSelection();
            setConsent(consent);
            applyConsent(consent);
            banner.style.display = 'none';
        });
    })();
</script>
HTML;

        return $html;
    }
}
