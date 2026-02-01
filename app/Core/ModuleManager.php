<?php
/**
 * คลาสนี้เป็นตัวจัดการโมดูล ซึ่งรับผิดชอบในการโหลดและลงทะเบียนโมดูลที่เปิดใช้งาน
 * 
 * จุดประสงค์: จัดการโมดูลที่เปิดใช้งานและลงทะเบียนเส้นทาง/บริการของพวกเขา
 * ModuleManager() ควรใช้กับอะไร: Router ที่ใช้ในการจัดการเส้นทางของแอปพลิเคชัน
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $moduleManager = new ModuleManager();
 * $moduleManager->registerEnabled($router);
 * ```
 */

namespace App\Core;

final class ModuleManager
{
    /**
     * ดึงรายชื่อโมดูลที่เปิดใช้งานจากไฟล์การตั้งค่า
     * จุดประสงค์: โหลดรายชื่อโมดูลที่เปิดใช้งานจากไฟล์การตั้งค่า
     * enabledModules() ควรใช้กับอะไร: ไม่มีพารามิเตอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $modules = $moduleManager->enabledModules();
     * ```
     * 
     * @return array รายชื่อคลาสโมดูลที่เปิดใช้งาน
     */
    public function enabledModules(): array
    {
        // ตรวจสอบว่าไฟล์การตั้งค่ามีอยู่หรือไม่
        $configPath = __DIR__ . '/../../config/modules.php';
        if (!file_exists($configPath)) {
            return [];
        }

        // โหลดรายชื่อโมดูลจากไฟล์การตั้งค่า
        $modules = require $configPath;
        if (!is_array($modules)) {
            return [];
        }

        // กรองและคืนค่ารายชื่อโมดูลที่ถูกต้อง
        $normalized = [];
        foreach ($modules as $moduleClass) {
            if (is_string($moduleClass) && $moduleClass !== '') {
                $normalized[] = $moduleClass;
            }
        }

        return $normalized; // คืนค่ารายชื่อโมดูลที่เปิดใช้งาน
    }

    /**
     * ลงทะเบียนโมดูลที่เปิดใช้งานกับ Router
     * จุดประสงค์: ลงทะเบียนเส้นทาง/บริการของโมดูลที่เปิดใช้งานกับ Router
     * registerEnabled() ควรใช้กับอะไร: Router ที่ใช้ในการจัดการเส้นทางของแอปพลิเคชัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $moduleManager->registerEnabled($router);
     * ```
     * 
     * @param Router $router ตัวจัดการเส้นทางของแอปพลิเคชัน
     * @return void ไม่มีค่าที่ส่งกลับ
     * @throws \RuntimeException เมื่อไม่พบคลาสโมดูลหรือโมดูลไม่เป็นไปตามสัญญา
     */
    public function registerEnabled(Router $router): void
    {
        // วนลูปรายชื่อโมดูลที่เปิดใช้งานและลงทะเบียนกับ Router
        foreach ($this->enabledModules() as $moduleClass) {

            // ตรวจสอบว่าโมดูลมีคลาสที่ถูกต้อง
            if (!class_exists($moduleClass)) {
                throw new \RuntimeException("Module class not found: {$moduleClass}");
            }

            // สร้างอินสแตนซ์ของโมดูลและตรวจสอบว่าเป็นไปตามสัญญา
            $module = new $moduleClass();
            if (!$module instanceof ModuleInterface) {
                throw new \RuntimeException("Module must implement ModuleInterface: {$moduleClass}");
            }

            $module->register($router); // ลงทะเบียนโมดูลกับ Router
        }
    }
}
