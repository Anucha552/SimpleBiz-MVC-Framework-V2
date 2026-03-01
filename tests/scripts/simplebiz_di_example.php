<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Container.php';

use App\Core\Container;

// Example interfaces and classes (minimal) ----------------------------------
// สร้างตัวอย่างอินเทอร์เฟซและคลาสเพื่อแสดงการทำงานของ Container
interface UserRepositoryInterface
{
    public function findUser(int $id): array;
}

class Database
{
    public function __construct()
    {
        // imagine connecting to DB here
    }
}

class UserRepository implements UserRepositoryInterface
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findUser(int $id): array
    {
        return ['id' => $id, 'name' => 'User ' . $id];
    }
}

class UserService
{
    private UserRepositoryInterface $repo;
    private Database $db;

    public function __construct(UserRepositoryInterface $repo, Database $db)
    {
        $this->repo = $repo;
        $this->db = $db;
    }

    public function getUser(int $id): array
    {
        return $this->repo->findUser($id);
    }
}

// Usage --------------------------------------------------------------------
$container = new Container();

// เชื่อมโยงอินเทอร์เฟซเข้ากับการใช้งานจริง
$container->bind(UserRepositoryInterface::class, UserRepository::class);

// ลงทะเบียน Database เป็น singleton
$container->singleton(Database::class, Database::class);

// แก้ไข UserService (ระบบจะแก้ไขการพึ่งพาโดยอัตโนมัติ)
/** @var UserService $userService */
$userService = $container->make(UserService::class);

echo "UserService->getUser(1):\n";
print_r($userService->getUser(1));

// แสดงพฤติกรรมแบบซิงเกิลตัน
$db1 = $container->make(Database::class);
$db2 = $container->make(Database::class);

echo "Database is singleton: " . ($db1 === $db2 ? 'true' : 'false') . "\n";

// ---------------- Router integration example ----------------
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Core/Request.php';
require_once __DIR__ . '/../app/Core/Response.php';
use App\Core\Router;

class UserController
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    // controller method: returns string (will be sent as HTML by Router)
    public function show($id): string
    {
        $user = $this->service->getUser((int)$id);
        return json_encode($user);
    }
}

$router = new Router($container);
$router->get('/user/{id}', UserController::class . '@show');

// Simulate a basic request environment for demonstration
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/user/1';

// Dispatch the request (Router will resolve UserController via the Container)
$router->dispatch();








































































