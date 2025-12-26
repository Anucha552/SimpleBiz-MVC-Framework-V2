<?php
/**
 * คลาสเราเตอร์
 * 
 * จุดประสงค์: จัดเส้นทางคำขอ HTTP ไปยังตัวควบคุมและเมธอดที่เหมาะสม
 * ฟีเจอร์: รองรับ middleware, พารามิเตอร์แบบไดนามิก, เมธอด HTTP หลายแบบ
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
 * - /api/v1/products/{id} → แบบซ้อนพร้อมพารามิเตอร์
 */

namespace App\Core;

class Router
{
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
     * ลงทะเบียนเส้นทาง GET
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     */
    public function get(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $controller, $middleware);
    }

    /**
     * ลงทะเบียนเส้นทาง POST
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     */
    public function post(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $controller, $middleware);
    }

    /**
     * ลงทะเบียนเส้นทาง PUT
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     */
    public function put(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $controller, $middleware);
    }

    /**
     * ลงทะเบียนเส้นทาง DELETE
     * 
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware (ไม่บังคับ)
     */
    public function delete(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $controller, $middleware);
    }

    /**
     * เพิ่มเส้นทางลงในอาร์เรย์เส้นทาง
     * 
     * @param string $method เมธอด HTTP
     * @param string $path รูปแบบเส้นทาง
     * @param string $controller รูปแบบ Controller@method
     * @param array $middleware คลาส middleware
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
     * 
     * กระบวนการ:
     * 1. รับเมธอดคำขอและ URI
     * 2. ค้นหารูปแบบเส้นทางที่ตรงกัน
     * 3. เรียกใช้ลูกโซ่ middleware
     * 4. เรียกเมธอดของตัวควบคุมพร้อมพารามิเตอร์
     * 
     * @throws \Exception ถ้าไม่พบเส้นทางหรือตัวควบคุมไม่ถูกต้อง
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        // จัดการเมธอด PUT/DELETE จากพารามิเตอร์ _method ของฟอร์ม
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // ค้นหาเส้นทางที่ตรงกัน
        $route = $this->matchRoute($method, $uri);

        if (!$route) {
            $this->notFound();
            return;
        }

        // เรียกใช้ลูกโซ่ middleware
        foreach ($route['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $result = $middleware->handle();
            
            // ถ้า middleware คืนค่า false ให้หยุดการทำงาน
            if ($result === false) {
                return;
            }
        }

        // แยกตัวควบคุมและเมธอด
        [$controllerClass, $methodName] = explode('@', $route['controller']);
        
        // สร้างอินสแตนซ์ตัวควบคุม
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new \Exception("Method {$methodName} not found in {$controllerClass}");
        }

        // เรียกเมธอดของตัวควบคุมพร้อมพารามิเตอร์
        call_user_func_array([$controller, $methodName], $route['params']);
    }

    /**
     * จับคู่ URI ที่เข้ามากับเส้นทางที่ลงทะเบียนไว้
     * 
     * แปลงรูปแบบเส้นทางเช่น /products/{id} เป็น regex
     * แยกค่าพารามิเตอร์จาก URI
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
                    'params' => array_values($params),
                ];
            }
        }

        return null;
    }

    /**
     * ดึง URI ที่สะอาดจากคำขอ
     * 
     * ลบสตริงคิวรีและเครื่องหมายทับนำและท้าย
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

        // ลบเครื่องหมายทับนำและท้าย
        $uri = trim($uri, '/');

        return '/' . $uri;
    }

    /**
     * จัดการ 404 ไม่พบหน้า
     */
    private function notFound(): void
    {
        http_response_code(404);
        
        // ตรวจสอบว่าเป็นคำขอ API หรือไม่
        $uri = $this->getUri();
        if (strpos($uri, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint not found',
            ]);
        } else {
            echo "404 - Page Not Found";
        }
    }
}
