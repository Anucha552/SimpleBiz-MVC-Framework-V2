# SimpleBiz Container Guide

คู่มือการใช้งาน Dependency Injection Container สำหรับ SimpleBiz Framework

---

## ภาพรวม

Container คือระบบจัดการ Dependency Injection (DI)  
ทำหน้าที่สร้าง object และ inject dependency ให้อัตโนมัติ

รองรับความสามารถดังนี้:

- Bind interface → implementation
- Singleton binding
- Auto constructor injection
- Method injection (call)
- Service Providers
- Circular dependency detection
- Nullable dependency
- Parameter override

---

# 1. การสร้าง Container

```php
use App\Core\Container;

$container = new Container();
```

---

# 2. bind() – ผูก Interface กับ Implementation

ใช้เมื่อคุณต้องการกำหนดว่า interface ควรถูกสร้างด้วย class ใด

```php
$container->bind(
    App\Contracts\LoggerInterface::class,
    App\Services\FileLogger::class
);
```

หรือใช้ Closure

```php
$container->bind(
    App\Contracts\LoggerInterface::class,
    function ($c) {
        return new App\Services\FileLogger('/logs/app.log');
    }
);
```

---

# 3. singleton() – สร้าง instance เดียวทั้งระบบ

ทุกครั้งที่เรียก make() จะได้ instance เดิม

```php
$container->singleton(
    App\Core\Database::class,
    function ($c) {
        return App\Core\Database::getInstance();
    }
);
```

---

# 4. make() – สร้าง Object

## กรณี class ธรรมดา (Auto Resolution)

```php
$userService = $container->make(App\Services\UserService::class);
```

Container จะ:

- ตรวจ constructor
- สร้าง dependency ให้อัตโนมัติ
- Inject เข้า constructor

---

## กรณี interface

ต้อง bind ก่อน

```php
$container->bind(
    App\Contracts\PaymentGateway::class,
    App\Services\StripeGateway::class
);

$gateway = $container->make(App\Contracts\PaymentGateway::class);
```

หากไม่ bind จะเกิด Exception:

```
Interface App\Contracts\PaymentGateway is not bound in the container.
```

---

# 5. Constructor Injection

ตัวอย่าง Controller

```php
class EmployeeController
{
    private Employees $employees;
    private Logger $logger;

    public function __construct(
        Employees $employees,
        Logger $logger
    ) {
        $this->employees = $employees;
        $this->logger = $logger;
    }
}
```

เรียกผ่าน container

```php
$controller = $container->make(EmployeeController::class);
```

---

# 6. Nullable Dependency

รองรับ nullable type

```php
public function __construct(?Logger $logger)
```

ถ้า resolve ไม่ได้ จะ inject `null`

---

# 7. call() – Method Injection

ใช้เรียก method พร้อม inject dependency อัตโนมัติ

```php
$container->call([EmployeeController::class, 'index']);
```

Container จะ:

1. สร้าง EmployeeController
2. Inject dependency เข้า method
3. เรียก method ให้

---

## 7.1 Parameter Override

สามารถ override parameter เองได้

```php
$container->call(
    [EmployeeController::class, 'show'],
    ['id' => 10]
);
```

Parameter `id` จะใช้ค่าที่ส่งเข้ามา แทนการ resolve อัตโนมัติ

---

# 8. Service Provider

ใช้สำหรับรวมการลงทะเบียน binding หลายตัว

## ตัวอย่าง Provider

```php
class AppServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(
            Logger::class,
            FileLogger::class
        );
    }
}
```

## ลงทะเบียน

```php
$container->register(new AppServiceProvider());
```

หาก register ซ้ำ จะเกิด Exception

---

# 9. Circular Dependency Detection

Container จะตรวจสอบ dependency loop อัตโนมัติ

ตัวอย่างที่ผิด:

```
A → B → C → A
```

จะ throw exception:

```
Circular dependency detected: A -> B -> C -> A
```

---

# 10. Helper Methods

## has()

```php
$container->has(UserService::class);
```

## isBound()

ตรวจเฉพาะ binding ที่ลงทะเบียนไว้

```php
$container->isBound(LoggerInterface::class);
```

## canResolve()

ตรวจว่าระบบสามารถสร้าง object ได้หรือไม่

```php
$container->canResolve(UserService::class);
```

---

# 11. Error Handling

Container จะ throw `ContainerException` เมื่อ:

- Interface ไม่ได้ bind
- Class ไม่มีอยู่จริง
- Class ไม่ instantiable
- Parameter resolve ไม่ได้
- Circular dependency
- Singleton factory ไม่คืน object

ควรจับ exception ที่ระดับ Kernel หรือ Front Controller

---

# 12. แนวทางการใช้งานที่แนะนำ

✅ ใช้ singleton กับ:

- Database
- Logger
- Config
- Cache

✅ ใช้ bind กับ:

- Interface
- Strategy pattern
- Repository pattern

✅ ปล่อย auto-resolution กับ:

- Controllers
- Services
- UseCases

---

# สรุป

Container นี้รองรับ:

- Constructor Injection
- Method Injection
- Interface Binding
- Singleton Pattern
- Provider-based registration
- Safe circular detection

เหมาะสำหรับ:

- MVC Framework
- Modular Application
- Clean Architecture
- Domain-driven design

---

SimpleBiz DI Container  
Production-ready for lightweight framework.