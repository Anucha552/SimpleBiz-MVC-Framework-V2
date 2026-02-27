<?php
/**
 * MakeModelCommand
 *
 * จุดประสงค์: คำสั่งสำหรับสร้าง Model ใหม่ในโครงสร้างของแอปพลิเคชัน
 */

declare(strict_types=1);

namespace App\Console\Commands;

class MakeModelCommand extends BaseCommand
{
    public function name(): string
    {
        return 'make:model';
    }

    protected function execute(array $args): void
    {
        if (empty($args)) {
            $this->error("กรุณาระบุชื่อ model");
            $this->info("วิธีใช้: php console make:model ModelName");
            return;
        }

        // แปลงชื่อโมเดลให้เป็นรูปแบบที่ถูกต้อง (PascalCase)
        // ตัวอย่าง: user_profile -> UserProfile
        $name = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $args[0])));
        $path = $this->path("app/Models/{$name}.php");

        if (file_exists($path)) {
            $this->error("Model นี้มีอยู่แล้ว!");
            return;
        }

        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $template = $this->getModelTemplate($name);
            if (file_put_contents($path, $template) === false) {
                throw new \Exception("ไม่สามารถเขียนไฟล์ได้ กรุณาตรวจสอบสิทธิ์การเขียนไฟล์");
            }

            $this->success("สร้าง Model สำเร็จ: app/Models/{$name}.php");
            echo "\n";
        } catch (\Exception $e) {
            echo "\n";
            echo "┌─ Create Model Error ────────────────────────────────┐\n";
            $this->error("เกิดข้อผิดพลาด: " . $e->getMessage());
            $this->warning("[TIP] คำแนะนำ: ตรวจสอบสิทธิ์การเขียนไฟล์");
            echo "└────────────────────────────────────────────────────┘\n";
            echo "\n";
        }
    }

    private function getModelTemplate(string $name): string
    {

        return <<<PHP
<?php
declare(strict_types=1);

/**
 * โมเดล {$name}
 *
 * จุดประสงค์: อธิบายหน้าที่ของโมเดลนี้ (เช่น จัดการข้อมูลผู้ใช้)
 */

namespace App\Models;

use App\Core\Model;

class {$name} extends Model
{
    /**
     * ชื่อตารางในฐานข้อมูล
     */
    protected static string \$table = '{$name}';

    /**
     * Primary key (ปกติใช้ id)
     */
    protected static string \$primaryKey = 'id';

    /**
     * ฟิลด์ที่อนุญาตให้ mass assignment
     * fillable: รายชื่อคอลัมน์ที่ “อนุญาต” ให้บํนทึกข้อมูล และอัพเดทได้
     */
    protected static array \$fillable = [
        // ตัวอย่าง: 'name', 'email', 'status'
    ];

    /**
     * ฟิลด์ที่ห้าม mass assignment
     * guarded: รายชื่อคอลัมน์ที่ “ห้าม” ให้บํนทึกข้อมูล และอัพเดทได้
     */
    protected static array \$guarded = ['id'];

    /**
     * เปิด/ปิด timestamps (created_at, updated_at)
     * ถ้าเปิด ระบบจะจัดการคอลัมน์ created_at และ updated_at 
     * ให้อัตโนมัติเมื่อสร้างหรืออัพเดตเรคคอร์ด
     */
    protected static bool \$timestamps = true;

    /**
     * เปิด/ปิด soft deletes (deleted_at)
     * ถ้าเปิด ระบบจะจัดการคอลัมน์ deleted_at 
     * ให้อัตโนมัติเมื่อทำการลบเรคคอร์ด
     */
    protected static bool \$softDeletes = false;

    // ใส่เมธอด query ที่ใช้ซ้ำบ่อยไว้ที่นี่ได้ เช่น scope หรือ helper
    // ตัวอย่างเมธอด query ที่ใช้ซ้ำบ่อย
    // public function active(): array
    // {
    //     return static::where('status', 'active')
    //         ->orderBy('created_at', 'DESC')
    //         ->get();
    // }
}

PHP;
    }
}
