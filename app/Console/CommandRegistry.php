<?php
/**
 * CommandRegistry
 * 
 * คลาสสำหรับจัดการคำสั่ง CLI ในแอปพลิเคชัน
 * 
 * จุดประสงค์: ลงทะเบียนและค้นหาคำสั่งที่สามารถเรียกใช้จาก CLI ได้อย่างง่ายดาย
 * 
 * ฟีเจอร์หลัก:
 * - ลงทะเบียนคำสั่งที่เป็น instance ของ CommandInterface
 * - ค้นหาคำสั่งตามชื่อหรือ alias
 * - ดึงรายชื่อคำสั่งทั้งหมด (รวม alias)
 * - ค้นหาและลงทะเบียนคำสั่งจากไดเรกทอรีที่กำหนด
 * 
 * การใช้งาน:
 * 1. สร้างคลาสคำสั่งที่ implements CommandInterface
 * 2. ลงทะเบียนคำสั่งด้วย CommandRegistry::register()
 * 3. ค้นหาคำสั่งด้วย CommandRegistry::find() และเรียกใช้ handle()
 * 
 * ตัวอย่างการสร้างคำสั่ง:
 * class ClearCacheCommand implements CommandInterface {
 *    public function name(): string {
 *        return 'cache:clear';
 *   }
 *   public function aliases(): array {
 *       return ['cc'];
 *  }
 *  public function handle(array $args, ConsoleContext $context): void {
 *      // Logic ล้าง cache
 *     echo "Cache cleared!\n";
 * }
 * }
 */

// Namespace และการประกาศ strict types เพื่อความปลอดภัยและความชัดเจนในการใช้งานประเภทข้อมูล
declare(strict_types=1);

namespace App\Console;

class CommandRegistry
{
    /** 
     * เก็บคำสั่งที่ลงทะเบียน โดยใช้ชื่อคำสั่งเป็น key และ instance ของ CommandInterface เป็น value
     */
    private array $commands = [];

    /**
     * เก็บ alias ของคำสั่ง โดยใช้ alias เป็น key และชื่อคำสั่งหลักเป็น value
    */
    private array $aliases = [];

    /**
     * ลงทะเบียนคำสั่งใหม่
     * จุดประสงค์: เพิ่มคำสั่งใหม่ที่สามารถเรียกใช้จาก CLI ได้ โดยรับ instance ของ CommandInterface
     * ตัวอย่างการใช้งาน:
     * ```php
     * $registry = new CommandRegistry();
     * $registry->register(new ClearCacheCommand());
     * ```
     * 
     * @param CommandInterface $command instance ของคำสั่งที่ต้องการลงทะเบียน
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function register(CommandInterface $command): void
    {
        $name = $command->name();
        $this->commands[$name] = $command;

        foreach ($command->aliases() as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    /**
     * ค้นหาคำสั่งตามชื่อหรือ alias
     * จุดประสงค์: ให้สามารถค้นหาคำสั่งได้ทั้งจากชื่อหลักและ alias เพื่อความสะดวกในการใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $command = $registry->find('cache:clear'); // คืนค่า ClearCacheCommand instance
     * $command = $registry->find('cc'); // คืนค่า ClearCacheCommand instance
     * $command = $registry->find('nonexistent'); // คืนค่า null
     * ```
     * @param string $name ชื่อคำสั่งหรือ alias
     * @return CommandInterface|null คืนค่า instance ของคำสั่งหรือ null หากไม่พบ
     */
    public function find(string $name): ?CommandInterface
    {
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        if (isset($this->aliases[$name])) {
            $target = $this->aliases[$name];
            return $this->commands[$target] ?? null;
        }

        return null;
    }

    /**
     * ดึงรายชื่อคำสั่งทั้งหมด
     * จุดประสงค์: ให้สามารถดึงรายชื่อคำสั่งทั้งหมดที่ลงทะเบียนไว้ รวมถึง alias เพื่อความสะดวกในการแสดงผลหรือการใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $names = $registry->getCommandNames(); // คืนค่ารายชื่อคำสั่งทั้งหมดรวม alias
     * $names = $registry->getCommandNames(false); // คืนค่ารายชื่อคำสั่งหลักเท่านั้น
     * ```
     * @param bool $includeAliases กำหนดว่าจะรวม alias หรือไม่
     * @return string[] คืนค่ารายชื่อคำสั่งทั้งหมด
     */
    public function getCommandNames(bool $includeAliases = true): array
    {
        $names = array_keys($this->commands);

        if ($includeAliases) {
            $names = array_merge($names, array_keys($this->aliases));
        }

        sort($names);
        return $names;
    }

    /**
     * ค้นหาและลงทะเบียนคำสั่งจากไดเรกทอรีที่กำหนด
     * จุดประสงค์: ให้สามารถค้นหาไฟล์คำสั่งในไดเรกทอรีที่กำหนด และลงทะเบียนคำสั่งเหล่านั้นโดยอัตโนมัติ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $registry->discover(__DIR__ . '/Commands', 'App\Console\Commands');
     * ```
     * @param string $directory ไดเรกทอรีที่ต้องการค้นหาไฟล์คำสั่ง
     * @param string $baseNamespace เนมสเปซหลักของคำสั่งที่ค้นพบ เช่น 'App\Console\Commands'
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function discover(string $directory, string $baseNamespace): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $pattern = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php';
        $files = glob($pattern) ?: [];

        foreach ($files as $file) {
            $class = $baseNamespace . '\\' . basename($file, '.php');
            if (!class_exists($class)) {
                continue;
            }

            try {
                $ref = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                continue;
            }

            if ($ref->isAbstract()) {
                continue;
            }

            $instance = $ref->newInstance();
            if (!$instance instanceof CommandInterface) {
                continue;
            }

            $this->register($instance);
        }
    }
}
