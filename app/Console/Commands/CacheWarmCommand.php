<?php
/**
 * class CacheWarmCommand
 * 
 * จุดประสงค์: เป็นคำสั่ง CLI ที่ใช้สำหรับเตรียม cache ของแอปพลิเคชัน โดยจะทำการสร้างไฟล์ cache สำหรับ routes และ config เพื่อเพิ่มประสิทธิภาพในการโหลดข้อมูลในครั้งถัดไปที่แอปพลิเคชันถูกเรียกใช้
 * 
 * การใช้งาน:
 * ```bash
 * php console cache:warm
 * ```
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console\Commands;

class CacheWarmCommand extends BaseCommand
{
    /**
     * ชื่อของคำสั่ง เช่น 'cache:warm'
     * จุดประสงค์: ใช้ในการเรียกคำสั่งจาก CLI และในการลงทะเบียนคำสั่ง
     * ตัวอย่างหารใช้งาน:
     * ```php
     * public function name(): string {
     *     return 'cache:warm';
     * }
     * ```
     * 
     * @return string ชื่อของคำสั่ง
     */
    public function name(): string
    {
        return 'cache:warm';
    }

    /**
     * ประมวลผลคำสั่งเพื่อเตรียม cache ของแอปพลิเคชัน
     * จุดประสงค์: รับอาร์กิวเมนต์จาก CLI และบริบท context เพื่อดำเนินการตามคำสั่งที่กำหนด โดยจะทำการสร้างไฟล์ cache สำหรับ routes และ config เพื่อเพิ่มประสิทธิภาพในการโหลดข้อมูลในครั้งถัดไปที่แอปพลิเคชันถูกเรียกใช้
     * ตัวอย่างการใช้งาน:
     * ```bash
     * php console.php cache:warm
     * ```
     * 
     * @param string[] $args อาร์กิวเมนต์ที่ได้รับจาก CLI
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการประมวลผลคำสั่งตามอาร์กิวเมนต์และบริบทที่ได้รับ
     */
    protected function execute(array $args): void
    {
        $this->info("กำลังเตรียม cache...");

        $cacheDir = $this->path('storage/cache');

        // สร้างโฟลเดอร์ cache และโฟลเดอร์ย่อยสำหรับ routes และ config หากยังไม่มี
        $dirs = ['routes', 'config'];
        foreach ($dirs as $dir) {
            $path = $cacheDir . '/' . $dir;

            // ตรวจสอบว่าโฟลเดอร์ย่อยมีอยู่หรือไม่ ถ้าไม่มีก็สร้างขึ้นมา
            if (!is_dir($path)) {
                // ครวจสอบว่าสร้างโฟลเดอร์ได้หรือไม่
                if (!mkdir($path, 0755, true) && !is_dir($path)) {
                    $this->error("ไม่สามารถสร้างโฟลเดอร์ cache: {$path}");
                    echo "\n";
                    return;
                }
            }
        }

        $this->info("  - กำลัง cache routes...");

        // สร้างไฟล์ cache สำหรับ routes โดยรวบรวมเส้นทางทั้งหมดจาก routes/web.php และ routes/api.php
        $router = new \App\Core\Router();
        $routesLoaded = false;

        foreach (['web' => $this->path('routes/web.php'), 'api' => $this->path('routes/api.php')] as $type => $file) {
            if (!file_exists($file)) {
                continue;
            }

            $routesLoaded = true;
            try {
                require $file;
            } catch (\Throwable $e) {
                $this->error("โหลด routes/{$type}.php ไม่สำเร็จ: " . $e->getMessage());
                echo "\n";
                return;
            }
        }

        if (!$routesLoaded) {
            $this->warning("ไม่พบไฟล์ routes/web.php หรือ routes/api.php");
        }

        $routeCachePayload = [
            'cached_at' => time(),
            'routes' => $router->getRoutes(),
        ];

        file_put_contents(
            $cacheDir . '/routes/routes_cached.php',
            "<?php\nreturn " . var_export($routeCachePayload, true) . ";"
        );
        

        $this->info("  - กำลัง cache config...");
        
        // สร้างไฟล์ cache สำหรับ config โดยเก็บข้อมูลเมตาของ config เช่น เวลาที่ cache ถูกสร้างขึ้น และสถานะของไฟล์ config ที่ถูก cache
        $configFiles = glob($this->path('config/*.php'));
        $cachedConfig = [];

        // อ่านไฟล์ config ทั้งหมดและเก็บข้อมูลเมตาของแต่ละไฟล์ลงในอาร์เรย์ $cachedConfig โดยใช้ชื่อไฟล์เป็นคีย์และเนื้อหาของไฟล์เป็นค่า
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $cachedConfig[$key] = require $file;
        }

        // เขียนข้อมูลเมตาของ config ลงในไฟล์ cache โดยใช้ฟังก์ชัน var_export เพื่อแปลงข้อมูลเป็นรูปแบบที่สามารถนำกลับมาใช้ได้ใน PHP
        file_put_contents(
            $cacheDir . '/config/config_cached.php',
            "<?php\nreturn " . var_export($cachedConfig, true) . ";"
        );

        $this->success("เตรียม cache เรียบร้อยแล้ว");
        echo "\n";
    }
}
