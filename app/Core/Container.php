<?php
/**
 * Container
 *
 * จุดประสงค์: เป็นคลาสสำหรับจัดการการฉีดพึ่งพา (Dependency Injection) ในแอปพลิเคชัน โดยสามารถผูกประเภทนามธรรม (abstract types) กับการใช้งานจริง (concrete implementations) และสามารถแก้ไขและสร้างอินสแตนซ์ของคลาสต่างๆ ได้อย่างยืดหยุ่นผ่านการใช้ Reflection เพื่อแก้ไขข้อกำหนดของคอนสตรัคเตอร์และเรียกใช้ฟังก์ชันหรือเมธอดที่มีการฉีดพึ่งพา
 * 
 * ตัวอย่างการใช้งาน:
 * $container = new Container();
 * $container->bind(UserRepositoryInterface::class, UserRepository::class);
 * $userService = $container->make(UserService::class);
 * echo $userService->getUser(1);
 * 
 */
declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use InvalidArgumentException;
use RuntimeException;

class Container
{
    /**
    * bindings: แผนที่ของ abstract types ไปยัง concrete implementations หรือ factory closures
    */
    private array $bindings = [];

    /**
    * singletons: แผนที่ของ abstract types ที่เป็น singleton ไปยัง concrete implementations หรือ factory closures
    */
    private array $singletons = [];

    /**
    * instances: แผนที่ของ abstract types ที่เป็น singleton ไปยังอินสแตนซ์ที่ถูกสร้างแล้ว
    */
    private array $instances = [];

    /**
     * resolving: สแต็กของ abstract types ที่กำลังถูกแก้ไขอยู่ในขณะนี้ (ใช้สำหรับตรวจจับ circular dependencies)
     */
    private array $resolving = [];

    /**
     * providers: รายการของ service provider ที่ถูกลงทะเบียนแล้ว
     */
    private array $providers = [];

   /**
    * ฟังก์ชัน bind สำหรับผูก abstract type กับ concrete implementation หรือ factory closure
    * จุดประสงค์: ให้สามารถผูกประเภทนามธรรม (เช่น อินเทอร์เฟซ) กับการใช้งานจริง (เช่น คลาสที่ implements อินเทอร์เฟซนั้น) หรือกับฟังก์ชัน factory ที่สร้างอินสแตนซ์ได้
    * ตัวอย่างการใช้งาน:
    * ```php
    * $container->bind(UserRepositoryInterface::class, UserRepository::class);
    * ```
     *
     * @param string $abstract กำหนดประเภทนามธรรม (เช่น อินเทอร์เฟซหรือชื่อคลาส)
     * @param string|Closure $concrete กำหนดการใช้งานจริง (เช่น ชื่อคลาสที่ implements อินเทอร์เฟซ หรือฟังก์ชัน factory ที่รับ Container เป็นพารามิเตอร์และคืนค่าอินสแตนซ์)
     * @return void ไม่คืนค่าอะไร แต่จะบันทึกการผูกใน container
    */
   public function bind(string $abstract, $concrete): void
   {
      if (!is_string($concrete) && !($concrete instanceof Closure)) {
         throw new \InvalidArgumentException('Concrete must be a class name or Closure.');
      }

      $this->bindings[$abstract] = $concrete;

      // Remove any previous singleton registration/instance for the same abstract
      unset($this->singletons[$abstract], $this->instances[$abstract]);
   }

   /**
    * ฟังก์ชัน singleton สำหรับผูก abstract type กับ concrete implementation หรือ factory closure ที่จะถูกสร้างเป็น singleton
    * จุดประสงค์: ให้สามารถผูกประเภทนามธรรมกับการใช้งานจริงที่ต้องการให้มีเพียงอินสแตนซ์เดียวตลอดอายุการใช้งานของแอปพลิเคชัน โดยเมื่อมีการเรียก make() สำหรับประเภทนั้น จะคืนค่าอินสแตนซ์เดียวกันทุกครั้ง
    * ตัวอย่างการใช้งาน:
    * ```php
    * $container->singleton(DatabaseConnection::class, function($container) {
    *     return new DatabaseConnection($container->make(Config::class));
    * });
    * ```
    *
    * @param string $abstract กำหนดประเภทนามธรรม (เช่น อินเทอร์เฟซหรือชื่อคลาส)
    * @param string|Closure $concrete กำหนดการใช้งานจริง (เช่น ชื่อคลาสที่ implements อินเทอร์เฟซ หรือฟังก์ชัน factory ที่รับ Container เป็นพารามิเตอร์และคืนค่าอินสแตนซ์)
    * @return void ไม่คืนค่าอะไร แต่จะบันทึกการผูกใน container
    */
   public function singleton(string $abstract, $concrete): void
   {
      if (!is_string($concrete) && !($concrete instanceof Closure)) {
         throw new \InvalidArgumentException('Concrete must be a class name or Closure.');
      }

      $this->singletons[$abstract] = $concrete;

      // Ensure any non-singleton binding or instance is cleared
      unset($this->bindings[$abstract], $this->instances[$abstract]);
   }

   /**
    * ฟังก์ชัน has สำหรับตรวจสอบว่า container สามารถสร้างอินสแตนซ์ของ abstract type ที่กำหนดได้หรือไม่
    * จุดประสงค์: ให้สามารถตรวจสอบล่วงหน้าว่า container มีการผูกหรือสามารถแก้ไขประเภทนามธรรมที่ต้องการได้หรือไม่ ซึ่งช่วยให้สามารถจัดการข้อผิดพลาดได้ดีขึ้นก่อนที่จะพยายามสร้างอินสแตนซ์
    * ตัวอย่างการใช้งาน:
    * ```php
    * if ($container->has(UserRepositoryInterface::class)) {
    *     $userRepo = $container->make(UserRepositoryInterface::class);
    * }
    * ```
    *
    * @param string $abstract กำหนดประเภทนามธรรม (เช่น อินเทอร์เฟซหรือชื่อคลาส)
    * @return bool คืนค่า true หาก container สามารถสร้างอินสแตนซ์ของประเภทนั้นได้
    */
   public function has(string $abstract): bool
   {
      // Deprecated: prefer `isBound()` or `canResolve()`.
      return $this->canResolve($abstract);
   }

   /**
    * ฟังก์ชัน isBound สำหรับตรวจสอบว่า container มีการผูก binding หรือ singleton สำหรับ abstract type ที่กำหนดหรือไม่
    * จุดประสงค์: ให้สามารถตรวจสอบล่วงหน้าว่า container มีการผูกประเภทนามธรรมที่ต้องการหรือไม่ ซึ่งช่วยให้สามารถจัดการข้อผิดพลาดได้ดีขึ้นก่อนที่จะพยายามสร้างอินสแตนซ์
    * ตัวอย่างการใช้งาน:
    * ```php
    * if ($container->isBound(UserRepositoryInterface::class)) {
    *     $userRepo = $container->make(UserRepositoryInterface::class);
    * }
    * ```
    *
    * @param string $abstract กำหนดประเภทนามธรรม (เช่น อินเทอร์เฟซหรือชื่อคลาส)
    * @return bool คืนค่า true หาก container มีการผูก binding หรือ singleton สำหรับประเภทนั้น
    */
   public function isBound(string $abstract): bool
   {
      return isset($this->bindings[$abstract]) || isset($this->singletons[$abstract]);
   }

   /**
    * ฟังก์ชัน canResolve สำหรับตรวจสอบว่า container สามารถสร้างอินสแตนซ์ของ abstract type ที่กำหนดได้หรือไม่ โดยพิจารณาจากการผูกและการมีอยู่ของคลาส
    * จุดประสงค์: ให้สามารถตรวจสอบล่วงหน้าว่า container สามารถสร้างอินสแตนซ์ของประเภทนามธรรมที่ต้องการได้หรือไม่ โดยจะตรวจสอบทั้งการผูกใน container และการมีอยู่ของคลาสที่สามารถแก้ไขได้ ซึ่งช่วยให้สามารถจัดการข้อผิดพลาดได้ดีขึ้นก่อนที่จะพยายามสร้างอินสแตนซ์
    * ตัวอย่างการใช้งาน:
    * ```php
    * if ($container->canResolve(UserRepositoryInterface::class)) {
    *     $userRepo = $container->make(UserRepositoryInterface::class);
    * }
    * ```
    *
    * @param string $abstract กำหนดประเภทนามธรรม (เช่น อินเทอร์เฟซหรือชื่อคลาส)
    * @return bool คืนค่า true หาก container สามารถสร้างอินสแตนซ์ของประเภทนั้นได้
    */
   public function canResolve(string $abstract): bool
   {
      return $this->isBound($abstract) || class_exists($abstract);
   }

   /**
    * ฟังก์ชัน register สำหรับลงทะเบียน service provider
    * จุดประสงค์: ให้สามารถลงทะเบียน service provider ที่เป็นคลาสที่มีเมธอด register(Container $container) หรือที่ implements ServiceProviderInterface ซึ่งจะช่วยให้สามารถจัดการการผูกและการตั้งค่าต่างๆ ใน container ได้อย่างเป็นระบบและแยกส่วนกันได้ดีขึ้น
    * ตัวอย่างการใช้งาน:
    * ```php
    * $container->register(new DatabaseServiceProvider());
    * ```
     *
     * @param object $provider อินสแตนซ์ของ service provider ที่ต้องการลงทะเบียน
     * @return void ไม่คืนค่าอะไร แต่จะเรียกเมธอด register() ของ provider และบันทึกการลงทะเบียนใน container
     * @throws ContainerException หาก provider ไม่ถูกต้องหรือมีการลงทะเบียนซ้ำ
    */
   public function register(object $provider): void
   {
      $class = get_class($provider);

      // Prevent duplicate registration of the same provider class
      if (in_array($class, $this->providers, true)) {
         throw new ContainerException("Service provider {$class} is already registered.");
      }

      // If provider implements the interface, store and call it
      if ($provider instanceof ServiceProviderInterface) {
         $this->providers[] = $class;
         $provider->register($this);
         return;
      }

      // If provider has a register method, store and call it
      if (method_exists($provider, 'register') && is_callable([$provider, 'register'])) {
         $this->providers[] = $class;
         $provider->register($this);
         return;
      }

      throw new ContainerException('Invalid service provider: must implement ServiceProviderInterface or provide a register(Container $container) method.');
   }

   /**
    * ฟังก์ชัน make สำหรับสร้างอินสแตนซ์ของ abstract type ที่กำหนด โดยจะพิจารณาจากการผูกใน container และการมีอยู่ของคลาสที่สามารถแก้ไขได้ และจะจัดการกับ circular dependencies ได้อย่างเหมาะสม
    * จุดประสงค์: ให้สามารถสร้างอินสแตนซ์ของประเภทนามธรรมที่ต้องการได้อย่างยืดหยุ่นและมีประสิทธิภาพ โดยจะตรวจสอบการผูกใน container และการมีอยู่ของคลาสที่สามารถแก้ไขได้ และจะจัดการกับ circular dependencies ได้อย่างเหมาะสมเพื่อป้องกันการเกิด infinite loop ในกรณีที่มีการพึ่งพาแบบหมุนเวียนกัน
    * ตัวอย่างการใช้งาน:
    * ```php
    * $userService = $container->make(UserService::class);
    * ```
    *
    * @param string $abstract กำหนดประเภทนามธรรม (เช่น อินเทอร์เฟซหรือชื่อคลาส)
    * @return object คืนค่าอินสแตนซ์ของประเภทนั้นที่ถูกสร้างขึ้น
    * @throws ContainerException หากไม่สามารถสร้างอินสแตนซ์ได้หรือเกิด circular dependency
    */
   public function make(string $abstract): object
   {
      // Return existing singleton instance
      if (isset($this->instances[$abstract])) {
         return $this->instances[$abstract];
      }

      // Circular dependency guard: if currently resolving this abstract, we have a cycle
      if (in_array($abstract, $this->resolving, true)) {
         $start = array_search($abstract, $this->resolving, true);
         $cycle = array_slice($this->resolving, $start !== false ? $start : 0);
         $cycle[] = $abstract;
         throw new ContainerException('Circular dependency detected: ' . implode(' -> ', $cycle));
      }

      // Push to resolving stack
      $this->resolving[] = $abstract;

      try {
         // Singleton binding
         if (isset($this->singletons[$abstract])) {
            $instance = $this->resolve($this->singletons[$abstract]);
            if (!is_object($instance)) {
               throw new ContainerException("Singleton binding for {$abstract} did not return an object.");
            }
            $this->instances[$abstract] = $instance;
            return $instance;
         }

         // Normal binding
         if (isset($this->bindings[$abstract])) {
            $instance = $this->resolve($this->bindings[$abstract]);
            if (!is_object($instance)) {
               throw new ContainerException("Binding for {$abstract} did not return an object.");
            }
            return $instance;
         }

            // If the abstract is an interface and not explicitly bound, provide a clearer error
            if (interface_exists($abstract) && !$this->isBound($abstract)) {
               throw new ContainerException("Interface {$abstract} is not bound in the container.");
            }

            // No binding: attempt auto-resolution if class exists
            if (class_exists($abstract)) {
               return $this->build($abstract);
            }

         throw new ContainerException("Type {$abstract} is not bound and class does not exist.");
      } finally {
         // Ensure we always pop the resolving stack even if an exception occurs
         array_pop($this->resolving);
      }
   }

   /**
    * ฟังก์ชัน resolve สำหรับสร้างอินสแตนซ์จากการผูกที่กำหนด ซึ่งอาจเป็นชื่อคลาสหรือฟังก์ชัน factory
    * จุดประสงค์: ให้สามารถสร้างอินสแตนซ์จากการผูกที่กำหนดใน container ได้อย่างยืดหยุ่น โดยจะตรวจสอบว่าการผูกเป็นชื่อคลาสหรือฟังก์ชัน factory และจะจัดการกับการสร้างอินสแตนซ์ได้อย่างเหมาะสม
    * ตัวอย่างการใช้งาน:
    * ```php
    * $instance = $this->resolve($this->bindings[$abstract]);
    * ```
    *
    * @param string|Closure $concrete กำหนดการใช้งานจริง (เช่น ชื่อคลาสที่ implements อินเทอร์เฟซ หรือฟังก์ชัน factory ที่รับ Container เป็นพารามิเตอร์และคืนค่าอินสแตนซ์)
    * @return object คืนค่าอินสแตนซ์ที่ถูกสร้างขึ้นจากการผูก
    * @throws ContainerException หากไม่สามารถสร้างอินสแตนซ์ได้
    */
   private function resolve($concrete): object
   {
      if ($concrete instanceof Closure) {
         $result = $concrete($this);
         if (!is_object($result)) {
            throw new ContainerException('Factory Closure must return an object.');
         }
         return $result;
      }

      if (is_string($concrete)) {
         return $this->build($concrete);
      }

      throw new ContainerException('Invalid concrete provided to container.');
   }

   /**
    * ฟังก์ชัน build สำหรับสร้างอินสแตนซ์ของคลาสที่กำหนดโดยใช้ Reflection เพื่อแก้ไขข้อกำหนดของคอนสตรัคเตอร์และจัดการกับ dependencies
    * จุดประสงค์: ให้สามารถสร้างอินสแตนซ์ของคลาสที่กำหนดได้อย่างยืดหยุ่นและมีประสิทธิภาพ โดยใช้ Reflection เพื่อแก้ไขข้อกำหนดของคอนสตรัคเตอร์และจัดการกับ dependencies ได้อย่างเหมาะสม รวมถึงการจัดการกับกรณีที่ไม่มีคอนสตรัคเตอร์หรือคอนสตรัคเตอร์ที่ไม่มีพารามิเตอร์
    * ตัวอย่างการใช้งาน:
    * ```php
    * $instance = $this->build(SomeClass::class);
    * ```
    *
    * @param string $concrete ชื่อคลาสที่ต้องการสร้างอินสแตนซ์
    * @return object คืนค่าอินสแตนซ์ของคลาสที่สร้างขึ้น
    * @throws ContainerException หากไม่สามารถสร้างอินสแตนซ์ได้
    */
   private function build(string $concrete): object
   {
      try {
         $reflector = new ReflectionClass($concrete);
      } catch (ReflectionException $e) {
         throw new ContainerException("Unable to reflect class {$concrete}: " . $e->getMessage(), 0, $e);
      }

      if (!$reflector->isInstantiable()) {
         throw new ContainerException("Class {$concrete} is not instantiable.");
      }

      $constructor = $reflector->getConstructor();

      if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
         return $reflector->newInstance();
      }

      $dependencies = $this->resolveParameters($constructor);

      return $reflector->newInstanceArgs($dependencies);
   }

   /**
    * ฟังก์ชัน resolveParameters สำหรับแก้ไขพารามิเตอร์ของ reflection (ฟังก์ชันหรือเมธอด) ให้เป็นลำดับของอาร์กิวเมนต์
    * จุดประสงค์: ให้สามารถสร้างลิสต์ของอาร์กิวเมนต์ที่ถูกต้องสำหรับการเรียกใช้งานฟังก์ชันหรือเมธอด โดยจะตรวจสอบพารามิเตอร์แต่ละตัวว่ามีค่า default หรือสามารถ resolve ได้จาก container หรือไม่
    * ตัวอย่างการใช้งาน:
    * ```php
    * $args = $this->resolveParameters($reflection, $overrides);
    * ```
    *
    * @param \ReflectionFunctionAbstract $reflection วัตถุ Reflection ของฟังก์ชันหรือเมธอดที่ต้องการแก้ไขพารามิเตอร์    
    * @param array $overrides แผนที่ของชื่อพารามิเตอร์ => ค่าที่ใช้ก่อนพยายามแก้ไข
    * @return array ลิสต์ของอาร์กิวเมนต์ที่ถูกแก้ไขแล้ว
    * @throws ContainerException หากไม่สามารถแก้ไขพารามิเตอร์ได้
    */
   private function resolveParameters(\ReflectionFunctionAbstract $reflection, array $overrides = []): array
   {
      $args = [];

      foreach ($reflection->getParameters() as $param) {
         $name = $param->getName();

         if (array_key_exists($name, $overrides)) {
            $args[] = $overrides[$name];
            continue;
         }

         $type = $param->getType();

         if ($type === null) {
            if ($param->isDefaultValueAvailable()) {
               $args[] = $param->getDefaultValue();
               continue;
            }
            throw new ContainerException('Unresolvable parameter $' . $name . ': missing type hint.');
         }

         if (!($type instanceof ReflectionNamedType)) {
            throw new ContainerException('Unsupported parameter type for $' . $name . '.');
         }

         // Class / interface type-hint
         if (!$type->isBuiltin()) {
            $depClass = $type->getName();
            try {
               $args[] = $this->make($depClass);
               continue;
            } catch (ContainerException $e) {
               if ($type->allowsNull()) {
                  $args[] = null;
                  continue;
               }
               throw $e;
            }
         }

         // Built-in types (int, string, etc.)
         if ($type->isBuiltin()) {
            if ($param->isDefaultValueAvailable()) {
               $args[] = $param->getDefaultValue();
               continue;
            }
            if ($type->allowsNull()) {
               $args[] = null;
               continue;
            }
            throw new ContainerException('Unresolvable builtin parameter $' . $name . ': no default value.');
         }
      }

      return $args;
   }

   /**
    * ฟังก์ชัน call สำหรับเรียกใช้ฟังก์ชันหรือเมธอดที่มีการฉีดพึ่งพา โดยจะจัดการกับการแก้ไขพารามิเตอร์และการสร้างอินสแตนซ์ของคลาสที่เกี่ยวข้องได้อย่างเหมาะสม
    * จุดประสงค์: ให้สามารถเรียกใช้ฟังก์ชันหรือเมธอดที่มีการฉีดพึ่งพาได้อย่างสะดวกและมีประสิทธิภาพ โดยจะจัดการกับการแก้ไขพารามิเตอร์และการสร้างอินสแตนซ์ของคลาสที่เกี่ยวข้องได้อย่างเหมาะสม รวมถึงการจัดการกับกรณีที่เป็นเมธอดของคลาสที่ต้องสร้างอินสแตนซ์ก่อนเรียกใช้
    * ตัวอย่างการใช้งาน:
    * ```php
    * $result = $this->call([SomeClass::class, 'someMethod'], ['param' => $value]);
    * ```
    *
    * @param callable|array $callback กำหนดฟังก์ชันหรือเมธอดที่ต้องการเรียกใช้ ซึ่งสามารถเป็น Closure, ชื่อฟังก์ชัน, หรือ array ที่ประกอบด้วยชื่อคลาส/อินสแตนซ์และชื่อเมธอด
    * @param array $overrides แผนที่ของชื่อพารามิเตอร์ => ค่าที่ใช้ก่อนพยายามแก้ไข
    * @return mixed ผลลัพธ์ที่ได้จากการเรียกใช้ฟังก์ชันหรือเมธอด
    * @throws ContainerException หากไม่สามารถเรียกใช้ฟังก์ชันหรือเมธอดได้
    */
   public function call(callable|array $callback, array $overrides = [])
   {
      // If array callback and first element is a class name, resolve it
      if (is_array($callback)) {
         $classOrObject = $callback[0] ?? null;
         $method = $callback[1] ?? null;

         if ($method === null) {
            throw new ContainerException('Invalid callback array provided to call().');
         }

         if (is_string($classOrObject) && class_exists($classOrObject)) {
            $instance = $this->make($classOrObject);
            $callback[0] = $instance;
         }

         try {
            $ref = new \ReflectionMethod($callback[0], $method);
         } catch (\ReflectionException $e) {
            throw new ContainerException('Unable to reflect method for callback: ' . $e->getMessage(), 0, $e);
         }

         $args = $this->resolveParameters($ref, $overrides);

         return $ref->invokeArgs($callback[0], $args);
      }

      // Non-array callables (Closure or function name)
      try {
         $refFunc = new \ReflectionFunction($callback);
      } catch (\ReflectionException $e) {
         throw new ContainerException('Unable to reflect function callback: ' . $e->getMessage(), 0, $e);
      }

      $args = $this->resolveParameters($refFunc, $overrides);

      return $refFunc->invokeArgs($args);
   }
}

/**
 * ContainerException
 * 
 * จุดประสงค์: เป็นคลาสสำหรับจัดการข้อผิดพลาดที่เกิดขึ้นใน Container เช่น การไม่สามารถสร้างอินสแตนซ์ได้, การเกิด circular dependency, หรือการใช้ service provider ที่ไม่ถูกต้อง
 */
class ContainerException extends \RuntimeException
{
}
