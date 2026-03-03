<?php
/**
 * คลาสเราเตอร์ สำหรับจัดการเส้นทางของแอปพลิเคชัน
 * 
 * จุดประสงค์: จัดเส้นทางคำขอ HTTP ไปยังตัวควบคุมและเมธอดที่เหมาะสม
 * ฟีเจอร์: รองรับ middleware, พารามิเตอร์แบบไดนามิก, เมธอด HTTP หลายแบบ
 * Router() ควรใช้กับอะไร: คำขอ HTTP ที่เข้ามาในแอปพลิเคชัน
 * 
 * วิธีการทำงาน:
 * 1. เส้นทางถูกลงทะเบียนพร้อมเมธอด HTTP และรูปแบบ
 * 2. คำขอที่เข้ามาจะถูกจับคู่กับเส้นทางที่ลงทะเบียนไว้
 * 3. Middleware ถูกเรียกใช้ก่อนตัวควบคุม
 * 4. เมธอดของตัวควบคุมถูกเรียกพร้อมพารามิเตอร์ที่แยกออกมา
 * 
 * ตัวอย่างรูปแบบเส้นทาง:
 * - /products → เส้นทางแบบคงที่
 * - /products/{id} → พารามิเตอร์แบบไดนามิก
 * - /api/products/{id} → แบบซ้อนพร้อมพารามิเตอร์
 */

namespace App\Core;
class Router
{
    /**
     * ตัวเลือกสำหรับการแก้ไขตัวควบคุมและ middleware ผ่าน Container (ถ้ามี)
     */
    private ?Container $container = null;

    /**
     * อาร์เรย์ของเส้นทางที่ลงทะเบียนไว้
     * โครงสร้าง: ['GET' => [...], 'POST' => [...], ฯลฯ]
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    /**
     * โหลดชุด routes ที่ถูก cache ไว้
     * @param array $routes รูปแบบเดียวกับ $this->routes
     * @return void
     */
    public function setRoutes(array $routes): void
    {
        $normalized = [
            'GET' => [],
            'POST' => [],
            'PUT' => [],
            'DELETE' => [],
        ];

        foreach ($normalized as $method => $_) {
            if (isset($routes[$method]) && is_array($routes[$method])) {
                $normalized[$method] = $routes[$method];
            }
        }

        $this->routes = $normalized;
    }

    /**
     * ส่งคืน routes ทั้งหมด (สำหรับ cache)
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * สร้างอินสแตนซ์ Router
     * จุดประสงค์: สร้างอินสแตนซ์ Router และกำหนด Container (ถ้ามี)
     * Router() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างอินสแตนซ์ Router ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $container = new Container();
     * $router = new Router($container);
     * ```
     * 
     * @param Container|null $container ตัวเลือกสำหรับการแก้ไขตัวควบคุมและ middleware ผ่าน Container (ถ้ามี)
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * ลงทะเบียนเส้นทาง GET
     * จุดประสงค์: ลงทะเบียนเส้นทางสำหรับคำขอ GET
     * get() ควรใช้กับอะไร: เมื่อต้องการเพิ่มเส้นทาง GET ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $router->get('/products', 'ProductController@index');
     * ```
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function get(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $controller, $middleware);
    }

    /**
     * ลงทะเบียนเส้นทาง POST
     * จุดประสงค์: ลงทะเบียนเส้นทางสำหรับคำขอ POST
     * post() ควรใช้กับอะไร: เมื่อต้องการเพิ่มเส้นทาง POST ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $router->post('/products', 'ProductController@store');
     * ```
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function post(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $controller, $middleware);
    }

    /**
     * ลงทะเบียนเส้นทาง PUT
     * จุดประสงค์: ลงทะเบียนเส้นทางสำหรับคำขอ PUT
     * put() ควรใช้กับอะไร: เมื่อต้องการเพิ่มเส้นทาง PUT ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $router->put('/products/{id}', 'ProductController@update');
     * ```
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function put(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $controller, $middleware);
    }

    /**
     * ลงทะเบียนเส้นทาง DELETE
     * จุดประสงค์: ลงทะเบียนเส้นทางสำหรับคำขอ DELETE
     * delete() ควรใช้กับอะไร: เมื่อต้องการเพิ่มเส้นทาง DELETE ใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $router->delete('/products/{id}', 'ProductController@destroy');
     * ```
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    public function delete(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $controller, $middleware);
    }

    /**
     * เพิ่มเส้นทางลงในอาร์เรย์เส้นทาง
     * จุดประสงค์: เพิ่มเส้นทางใหม่ลงในอาร์เรย์เส้นทาง
     * addRoute() ควรใช้กับอะไร: เมื่อคุณต้องการเพิ่มเส้นทางใหม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->addRoute('GET', '/products', 'ProductController@index');
     * ```
     * 
     * @param string $method เมธอด HTTP
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware
     * @return void ไม่มีค่าที่ส่งกลับ
     */
    private function addRoute(string $method, string $path, string $controller, array $middleware): void
    {
        $this->routes[$method][$path] = [
            'controller' => $controller,
            'middleware' => $middleware,
        ];
    }

    /**
     * ส่งคำขอที่เข้ามาไปยังตัวควบคุมที่เหมาะสม
     * จุดประสงค์: ส่งคำขอที่เข้ามาไปยังตัวควบคุมที่เหมาะสม
     * dispatch() ควรใช้กับอะไร: เมื่อคุณต้องการจัดการคำขอและส่งไปยังตัวควบคุมที่เหมาะสม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $router->dispatch();
     * ```
     * 
     * กระบวนการ:
     * 1. รับเมธอดคำขอและ URI
     * 2. ค้นหารูปแบบเส้นทางที่ตรงกัน
     * 3. เรียกใช้ลูกโซ่ middleware
     * 4. เรียกเมธอดของตัวควบคุมพร้อมพารามิเตอร์
     * 
     * @param Request|null $request คำขอที่ส่งเข้ามา (ถ้ามี)
     * @throws \Exception ถ้าไม่พบเส้นทางหรือตัวควบคุมไม่ถูกต้อง
     */
    public function dispatch(?Request $request = null): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        // ใช้ Request ที่ส่งเข้ามา (จาก front controller) หรือสร้างใหม่
        $request = $request ?? new Request();

        // จัดการเมธอด PUT/DELETE จากพารามิเตอร์ _method ของฟอร์ม
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // ค้นหาเส้นทางที่ตรงกัน
        $route = $this->matchRoute($method, $uri);

        if (!$route) {
            // ถ้า path มีอยู่แต่ method ไม่ตรง ให้ตอบ 405
            $allowedMethods = $this->getAllowedMethodsForUri($uri);
            if (!empty($allowedMethods)) {
                $response = ErrorHandler::response(405, 'Method Not Allowed')
                    ->withHeader('Allow', implode(', ', $allowedMethods))
                    ->withHeaders($request->getResponseHeaders(), false);
                $response->send();
                return;
            }

            $this->notFound();
            return;
        }

        // เรียกใช้ลูกโซ่ middleware
        $executedMiddleware = [];

        foreach ($route['middleware'] as $middlewareDefinition) {
            if (is_array($middlewareDefinition)) {
                $middlewareClass = $middlewareDefinition[0] ?? null;
                $middlewareArgs = [];

                if (!is_string($middlewareClass) || $middlewareClass === '') {
                    throw new \InvalidArgumentException('Invalid middleware definition');
                }

                if (count($middlewareDefinition) === 2 && is_array($middlewareDefinition[1])) {
                    $middlewareArgs = $middlewareDefinition[1];
                } else {
                    $middlewareArgs = array_slice($middlewareDefinition, 1);
                }

                $middlewareArgs = is_array($middlewareArgs) ? $middlewareArgs : [$middlewareArgs];
                // รองรับการแก้ไข middleware ผ่าน Container (ถ้ามี) เฉพาะเมื่อไม่มีอาร์กิวเมนต์กำหนดไว้
                // ตรวจสอบว่ามีอาร์กิวเมนต์หรือไม่ ถ้าไม่มีให้พยายาม resolve ผ่าน Container
                if ($this->container instanceof Container && empty($middlewareArgs) && $this->container->has($middlewareClass)) {
                    $middleware = $this->container->make($middlewareClass); // Resolve ผ่าน Container เฉพาะเมื่อไม่มีอาร์กิวเมนต์กำหนดไว้
                } else {
                    $middleware = new $middlewareClass(...$middlewareArgs); // สร้างอินสแตนซ์ด้วยอาร์กิวเมนต์ที่กำหนดไว้ (ถ้ามี) หรือไม่มีเลย
                }
            } else {
                $middlewareClass = $middlewareDefinition; // รูปแบบเดิมสำหรับกรณีไม่มีอาร์กิวเมนต์
                // ตรวจสอบว่ามีการแก้ไข middleware ผ่าน Container หรือไม่ และสร้างอินสแตนซ์ตามนั้น
                if ($this->container instanceof Container && $this->container->has($middlewareClass)) {
                    $middleware = $this->container->make($middlewareClass);
                } else {
                    $middleware = new $middlewareClass();
                }
            }

            $result = $middleware->handle($request);

            // ถ้า middleware คืนค่า Response ให้ส่งและหยุด
            if ($result instanceof Response) {
                $result->withHeaders($request->getResponseHeaders(), false)->send();
                return;
            }
            
            // ถ้า middleware คืนค่า false ให้หยุดการทำงาน
            if ($result === false) {
                return;
            }

            $executedMiddleware[] = $middleware;
        }

        // แยกตัวควบคุมและเมธอด
        [$controllerClass, $methodName] = explode('@', $route['controller']);
        
        // สร้างอินสแตนซ์ตัวควบคุม
        // ตรวจสอบว่าคลาสตัวควบคุมมีอยู่
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }
        
        // สร้างอินสแตนซ์ตัวควบคุม (resolve via container when available)
        if ($this->container instanceof Container && $this->container->has($controllerClass)) {
            $controller = $this->container->make($controllerClass);
        } else {
            $controller = new $controllerClass();
        }

        // ถ้า controller มีเมธอด setRequest ให้ส่ง Request เข้าไป
        if (method_exists($controller, 'setRequest')) {
            $controller->setRequest($request);
        }

        // ตรวจสอบว่าเมธอดมีอยู่ในตัวควบคุม
        if (!method_exists($controller, $methodName)) {
            throw new \Exception("Method {$methodName} not found in {$controllerClass}");
        }

        // รองรับ controller method ที่รับ Request เป็นพารามิเตอร์ตัวแรก
        $params = $route['params'];
        $controllerMethod = new \ReflectionMethod($controller, $methodName);
        $parameters = $controllerMethod->getParameters();
        if (isset($parameters[0])) {
            $type = $parameters[0]->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && $type->getName() === Request::class) {
                array_unshift($params, $request);
            }
        }

        // เรียกเมธอดของตัวควบคุมพร้อมพารามิเตอร์ที่สร้างขึ้นใน Controller
        try {
            $result = call_user_func_array([$controller, $methodName], $params);
        } catch (\Throwable $e) {
            $logger = new \App\Core\Logger();
            $logger->error('controller.exception', [
                'controller' => $controllerClass,
                'method' => $methodName,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'route' => $_SERVER['REQUEST_URI'] ?? '',
            ]);

            $isApiRequest = preg_match('#^/api(/|$)#', $_SERVER['REQUEST_URI'] ?? '');
            if ($isApiRequest) {
                ErrorHandler::response(500, 'เกิดข้อผิดพลาดระหว่างประมวลผล กรุณาลองใหม่อีกครั้ง')
                    ->withHeaders($request->getResponseHeaders(), false)
                    ->send();
                return;
            }

            Session::flash('error', 'เกิดข้อผิดพลาดระหว่างประมวลผล กรุณาลองใหม่อีกครั้ง');
            \App\Helpers\UrlHelper::back('/')
                ->withHeaders($request->getResponseHeaders(), false)
                ->send();
            return;
        }
        
        $response = null;

        // รองรับ controller ที่ return Response หรือ string (ไม่บังคับ)
        if ($result instanceof Response) {
            $response = $result;
        } elseif (is_string($result) && $result !== '') {
            $response = Response::html($result);
        }

        // เรียก after hook ของ middleware (ย้อนลำดับ)
        if (!empty($executedMiddleware)) {
            for ($i = count($executedMiddleware) - 1; $i >= 0; $i--) {
                $middleware = $executedMiddleware[$i];
                if (method_exists($middleware, 'after')) {
                    $afterResult = $middleware->after($request, $response);
                    if ($afterResult instanceof Response) {
                        $response = $afterResult;
                    } elseif (is_string($afterResult) && $afterResult !== '') {
                        $response = Response::html($afterResult);
                    }
                }
            }
        }

        if ($response instanceof Response) {
            $response->withHeaders($request->getResponseHeaders(), false)->send();
        }
    }

    /**
     * จับคู่ URI ที่เข้ามากับเส้นทางที่ลงทะเบียนไว้
     * 
     * แปลงรูปแบบเส้นทางเช่น /products/{id} เป็น regex
     * แยกค่าพารามิเตอร์จาก URI
     * จุดประสงค์: จับคู่ URI ที่เข้ามากับเส้นทางที่ลงทะเบียนไว้
     * matchRoute() ควรใช้กับอะไร: เมธอด HTTP และ URI ที่ต้องการจับคู่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $route = $this->matchRoute('GET', '/products/123');
     * ```
     * 
     * @param string $method เมธอด HTTP
     * @param string $uri URI ของคำขอ
     * @return array|null เส้นทางที่ตรงกันพร้อมพารามิเตอร์หรือ null
     */
    private function matchRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            // แปลงรูปแบบเส้นทางเป็น regex
            // {id} กลายเป็นกลุ่มจับที่มีชื่อ (?P<id>[^/]+)
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                // แยกค่าพารามิเตอร์
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                return [
                    'controller' => $route['controller'],
                    'middleware' => $route['middleware'],
                    // Positional params for backward compatibility
                    'params' => array_values($params),
                    // Named parameters available under 'namedParams'
                    'namedParams' => $params,
                ];
            }
        }

        return null;
    }

    /**
     * หาเมธอดทั้งหมดที่รองรับสำหรับ URI เดียวกัน (เพื่อใช้ตอบ 405)
     *
     * จุดประสงค์: หาเมธอด HTTP ที่รองรับสำหรับ URI ที่กำหนด
     * getAllowedMethodsForUri() ควรใช้กับอะไร: URI ที่ต้องการตรวจสอบเมธอดที่รองรับ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $methods = $this->getAllowedMethodsForUri('/products/123');
     * ```
     * 
     * @param string $uri URI ของคำขอ
     * @return array รายการเมธอด HTTP ที่รองรับ
     */
    private function getAllowedMethodsForUri(string $uri): array
    {
        $allowed = [];

        foreach ($this->routes as $httpMethod => $methodRoutes) {
            foreach ($methodRoutes as $pattern => $route) {
                $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';

                if (preg_match($regex, $uri)) {
                    $allowed[] = $httpMethod;
                    break;
                }
            }
        }

        return $allowed;
    }

    /**
     * ดึง URI ที่สะอาดจากคำขอ
     * 
     * ลบสตริงคิวรีและเครื่องหมายทับนำและท้าย
     * จุดประสงค์: ดึง URI ที่สะอาดจากคำขอ
     * getUri() ควรใช้กับอะไร: เมื่อคุณต้องการดึง URI ที่สะอาดจากคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uri = $this->getUri();
     * ```
     * 
     * @return string เส้นทาง URI ที่สะอาด
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // ลบสตริงคิวรี
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // ตัด base path (สำหรับการติดตั้งในโฟลเดอร์ย่อย)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        // ลบเครื่องหมายทับนำและท้าย
        $uri = trim($uri, '/');

        return '/' . $uri;
    }

    /**
     * จัดการ 404 ไม่พบหน้า
     * 
     * จุดประสงค์: แสดงหน้าข้อผิดพลาด 404 เมื่อไม่พบเส้นทางที่ตรงกัน
     * notFound() ควรใช้กับอะไร: เมื่อไม่พบเส้นทางที่ตรงกับคำขอ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->notFound();
     * ```
     */
    private function notFound(): void
    {
        ErrorHandler::notFound();
    }
}
