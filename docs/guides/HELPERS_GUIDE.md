# คู่มือการใช้งาน Helpers (อัปเดตตามโค้ดจริง)

ไฟล์นี้สรุป Helpers ที่มีอยู่ใน `app/Helpers/` พร้อมรายการเมธอดสาธารณะสำคัญและตัวอย่างการใช้งาน (ปรับให้ตรงกับโค้ดปัจจุบัน)

สรุป Helpers และเมธอดสำคัญ:

- `ArrayHelper`
	- เมธอดหลัก: `get`, `set`, `has`, `forget`, `pluck`, `flatten`, `groupBy`, `sortBy`, `filter`, `map`, `only`, `except`, `chunk`, `every`, `some`, `first`, `last`, `wrap`, `removeNull`, `removeEmpty`, `unique`, `random`, `shuffle`, `merge`, `isAssoc`, `fromObject`, `prepend`, `firstValue`, `lastValue`, `take`, `skip`, `paginate`, `countValues`, `zip`

- `DateHelper`
	- เมธอดหลัก: `thaiDate`, `thaiDateTime`, `thaiDay`, `humanDate`, `timeAgo`, `format`, `diff`, `addDays`, `subDays`, `addMonths`, `addYears`, `isToday`, `isYesterday`, `isTomorrow`, `isPast`, `isFuture`, `startOfMonth`, `endOfMonth`, `fromTimestamp`, `toTimestamp`, `now`, `today`, `yesterday`, `tomorrow`, `isWeekend`, `isWeekday`

- `FormHelper`
	- เมธอดหลัก: `flash`, `hasFlash`, `allFlash`, `old`, `oldRaw`, `hasOld`, `errors`, `hasError`, `firstError`, `invalidClass`, `csrfField`, `csrfMeta`

- `NumberHelper`
	- เมธอดหลัก: `money`, `baht`, `format`, `percent`, `percentage`, `fileSize`, `abbreviate`, `ordinal`, `ordinalThai`, `toThai`, `fromThai`, `toWord`, `ceil`, `floor`, `round`, `average`, `median`, `min`, `max`, `sum`, `isEven`, `isOdd`, `inRange`, `clamp`, `toRoman`, `random`, `vat`, `priceWithVat`, `priceBeforeVat`, `discount`, `priceAfterDiscount`, `toBinary`, `toHex`, `toOctal`, `fromBinary`, `fromHex`, `fromOctal`

- `ResponseHelper`
	- เมธอดหลัก: `success`, `error`, `created`, `noContent`, `notFound`, `unauthorized`, `forbidden`, `validationError`, `serverError`, `paginated`

- `SecurityHelper`
	- เมธอดหลัก: `escape`, `escapeHtml`, `sanitize`, `sanitizeEmail`, `sanitizeUrl`, `sanitizeInt`, `sanitizeFloat`, `stripTags`, `stripJavaScript`, `stripSqlKeywords`, `isValidEmail`, `isValidUrl`, `isValidIp`, `base64Encode`, `base64Decode`, `base64UrlEncode`, `base64UrlDecode`, `hashPassword`, `verifyPassword`, `generateToken`, `uuid`, `generateCsrfToken`, `verifyCsrfToken`, `encrypt`, `decrypt`, `hash`, `hmac`, `hashEquals`, `mask`, `maskEmail`, `maskPhone`, `cleanFilename`, `isAllowedExtension`, `isAllowedMimeType`, `escapeJson`, `escapeJs`, `escapeAttr`, `preventClickjacking`, `setCSP`, `setSecurityHeaders`, `forceHttps`, `preventMimeSniffing`, `rateLimitCheck`, `clearRateLimit`, `checkPasswordStrength`

- `StringHelper`
	- เมธอดหลัก: `slug`, `truncate`, `words`, `random`, `camelCase`, `studlyCase`, `snakeCase`, `kebabCase`, `startsWith`, `endsWith`, `contains`, `replaceFirst`, `replaceLast`, `stripTags`, `upper`, `lower`, `title`, `isJson`, `bahtText`, `collapseWhitespace`, `replaceArray`

- `UrlHelper`
	- เมธอดหลัก: `base`, `to`, `current`, `previous`, `isSecure`, `redirect`, `back`, `addQuery`, `removeQuery`, `query`, `is`, `asset`, `encode`, `decode`, `isValid`, `parse`, `domain`, `path`, `cacheBust`, `page`, `removeTrailingSlash`, `addTrailingSlash`, `signed`, `verifySignature`, `api`, `join`

ตัวอย่างการใช้งานที่อัปเดต (ใช้ namespace และเมธอดจริง):

```php
// ดึงค่าเก่าจากฟอร์ม (old input) ใน view
<?= \App\Helpers\FormHelper::old('email') ?>

// แสดง error แรกของฟิลด์
<?= \App\Helpers\FormHelper::firstError('email') ?>

// ดึงค่าสถานะจาก array ด้วย dot notation
$city = \App\Helpers\ArrayHelper::get($data, 'user.address.city', 'ไม่ระบุ');

// สร้าง URL เต็ม
$link = \App\Helpers\UrlHelper::to('/products/123');

// ส่ง API response แบบ paginated
return \App\Helpers\ResponseHelper::paginated($items, $page, $perPage, $total);
```

คำแนะนำถัดไป:
- หากต้องการให้ผมอัปเดตรายการเมธอดโดยอัตโนมัติจากการสแกนไฟล์ทั้งหมด (เพื่อความแม่นยำ 100%) ให้อนุญาต ผมจะอ่านไฟล์ `app/Helpers/*.php` และอัปเดตรายการนี้แบบ exact method list
- หรือให้ผมแก้ตัวอย่างเพิ่มเติมในเอกสารเพื่อสะท้อนการใช้งานจริง — ระบุจุดที่ต้องการให้ปรับ
