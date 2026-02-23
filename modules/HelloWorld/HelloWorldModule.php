<?php
/**
 * HelloWorldModule
 * 
 * จุดประสงค์: เป็นโมดูลตัวอย่างสำหรับแสดงการสร้างโมดูลใน SimpleBiz MVC Framework
 * โมดูลนี้จะมีเส้นทางสำหรับแสดงข้อความ "Hello, World!" ทั้งในรูปแบบ HTML และ JSON
 * 
 * ตัวอย่างการใช้งาน:
 * 1. ลงทะเบียนโมดูลในไฟล์ config/modules.php:
 * ```php
 * return [
 *     // โมดูลอื่นๆ...
 *     Modules\HelloWorld\HelloWorldModule::class,
 * ];
 * ```
 * 2. เข้าถึงเส้นทาง:
 * - http://yourapp/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ HTML
 * - http://yourapp/api/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ JSON
 */

namespace Modules\HelloWorld;

use App\Core\ModuleInterface;
use App\Core\Router;

final class HelloWorldModule implements ModuleInterface
{
    /**
     * ลงทะเบียนเส้นทางสำหรับโมดูลนี้
     * จุดประสงค์: กำหนดเส้นทางที่เกี่ยวข้องกับโมดูลนี้ เช่น เส้นทางสำหรับแสดงข้อความ "Hello, World!" ในรูปแบบ HTML และ JSON
     * ตัวอย่างการใช้งาน:
     * - http://yourapp/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ HTML
     * - http://yourapp/api/hello จะแสดงข้อความ "Hello, World!" ในรูปแบบ JSON
     *
     * @param Router $router ตัวจัดการเส้นทางที่ใช้ในการลงทะเบียนเส้นทางของโมดูลนี้
     */
    public function register(Router $router): void
    {
        $router->get('/hello', Controllers\HelloController::class . '@index');
        $router->get('/api/hello', Controllers\HelloController::class . '@api');
    }
}
