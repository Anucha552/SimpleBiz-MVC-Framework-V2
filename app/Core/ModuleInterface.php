<?php
/**
 * คลาสนี้เป็นส่วนหนึ่งของระบบโมดูล สำหรับการลงทะเบียนเส้นทางและบริการต่างๆ
 * 
 * จุดประสงค์: กำหนดสัญญาสำหรับโมดูลในการลงทะเบียนเส้นทางและบริการ
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * class UserModule implements ModuleInterface {
 *     public function register(Router $router): void {
 *         $router->addRoute('/users', 'UserController@index');
 *     }
 * }
 * ```
 */

namespace App\Core;

interface ModuleInterface
{
    /**
     * ลงทะเบียนเส้นทาง/บริการสำหรับโมดูลนี้
     * register() ควรใช้กับอะไร: Router ที่ใช้ในการจัดการเส้นทางของแอปพลิเคชัน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $router->addRoute('/example', 'ExampleController@method');
     * ```
     * 
     * @param Router $router ตัวจัดการเส้นทางของแอปพลิเคชัน
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function register(Router $router): void;
}
