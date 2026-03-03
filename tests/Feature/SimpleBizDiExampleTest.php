<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SimpleBizDiExampleTest extends TestCase
{
    public function test_container_resolves_service_and_singleton_database(): void
    {
        $container = new \App\Core\Container();

        // bind interface -> implementation and register singleton
        $container->bind(TestUserRepositoryInterfaceX::class, TestUserRepositoryX::class);
        $container->singleton(TestDatabaseX::class, TestDatabaseX::class);

        // Explicitly bind TestUserServiceX to itself for Container resolution
        $container->bind(TestUserServiceX::class, TestUserServiceX::class);

        $userService = $container->make(TestUserServiceX::class);

        $this->assertInstanceOf(TestUserServiceX::class, $userService);

        $db1 = $container->make(TestDatabaseX::class);
        $db2 = $container->make(TestDatabaseX::class);

        $this->assertSame($db1, $db2, 'Database should be registered as singleton');

        // Assert the service returns expected user data
        $user = $userService->getUser(42);
        $this->assertIsArray($user);
        $this->assertSame(42, $user['id']);
        $this->assertStringContainsString('User 42', $user['name']);

        // Also test Router dispatch with a small controller that uses DI
        // Define a controller class that will be resolved via the Container
        $container->bind(TestUserRepositoryInterfaceX::class, TestUserRepositoryX::class);
        $container->singleton(TestDatabaseX::class, TestDatabaseX::class);

        // simple controller that relies on TestUserServiceX via constructor
        if (!class_exists('\\Tests\\Feature\\TestUserControllerX')) {
            // Make TestUserServiceX available in global namespace for Container resolution
            if (!class_exists('\\TestUserServiceX') && class_exists('\\Tests\\Feature\\TestUserServiceX')) {
                class_alias('Tests\\Feature\\TestUserServiceX', 'TestUserServiceX');
            }
            eval(<<<'PHP'
            namespace Tests\Feature;
            class TestUserControllerX
            {
                private TestUserServiceX $service;
                public function __construct(TestUserServiceX $service)
                {
                    $this->service = $service;
                }

                public function show($id): string
                {
                    return json_encode($this->service->getUser((int)$id));
                }
            }
            PHP
            );
        }

        $router = new \App\Core\Router($container);
        $router->get('/user/{id}', '\\Tests\\Feature\\TestUserControllerX@show');

        // simulate request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/7';

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Router should output response');
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertSame(7, $decoded['id']);
    }
}
