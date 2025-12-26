<?php
/**
 * ROUTER CLASS
 * 
 * Purpose: Routes HTTP requests to appropriate controllers and methods
 * Features: Supports middleware, dynamic parameters, multiple HTTP methods
 * 
 * How It Works:
 * 1. Routes are registered with HTTP method and pattern
 * 2. Incoming requests are matched against registered routes
 * 3. Middleware is executed before controller
 * 4. Controller method is invoked with extracted parameters
 * 
 * Route Pattern Examples:
 * - /products → Static route
 * - /products/{id} → Dynamic parameter
 * - /api/v1/products/{id} → Nested with parameter
 */

namespace App\Core;

class Router
{
    /**
     * Array of registered routes
     * Structure: ['GET' => [...], 'POST' => [...], etc.]
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
    ];

    /**
     * Register a GET route
     * 
     * @param string $path Route pattern
     * @param string $controller Controller@method format
     * @param array $middleware Optional middleware classes
     */
    public function get(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $controller, $middleware);
    }

    /**
     * Register a POST route
     * 
     * @param string $path Route pattern
     * @param string $controller Controller@method format
     * @param array $middleware Optional middleware classes
     */
    public function post(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $controller, $middleware);
    }

    /**
     * Register a PUT route
     * 
     * @param string $path Route pattern
     * @param string $controller Controller@method format
     * @param array $middleware Optional middleware classes
     */
    public function put(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $controller, $middleware);
    }

    /**
     * Register a DELETE route
     * 
     * @param string $path Route pattern
     * @param string $controller Controller@method format
     * @param array $middleware Optional middleware classes
     */
    public function delete(string $path, string $controller, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $controller, $middleware);
    }

    /**
     * Add route to routes array
     * 
     * @param string $method HTTP method
     * @param string $path Route pattern
     * @param string $controller Controller@method format
     * @param array $middleware Middleware classes
     */
    private function addRoute(string $method, string $path, string $controller, array $middleware): void
    {
        $this->routes[$method][$path] = [
            'controller' => $controller,
            'middleware' => $middleware,
        ];
    }

    /**
     * Dispatch incoming request to appropriate controller
     * 
     * Process:
     * 1. Get request method and URI
     * 2. Find matching route pattern
     * 3. Execute middleware chain
     * 4. Invoke controller method with parameters
     * 
     * @throws \Exception if route not found or controller invalid
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getUri();

        // Handle PUT/DELETE methods from form _method parameter
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Find matching route
        $route = $this->matchRoute($method, $uri);

        if (!$route) {
            $this->notFound();
            return;
        }

        // Execute middleware chain
        foreach ($route['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            $result = $middleware->handle();
            
            // If middleware returns false, stop execution
            if ($result === false) {
                return;
            }
        }

        // Parse controller and method
        [$controllerClass, $methodName] = explode('@', $route['controller']);
        
        // Instantiate controller
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $methodName)) {
            throw new \Exception("Method {$methodName} not found in {$controllerClass}");
        }

        // Invoke controller method with parameters
        call_user_func_array([$controller, $methodName], $route['params']);
    }

    /**
     * Match incoming URI against registered routes
     * 
     * Converts route patterns like /products/{id} to regex
     * Extracts parameter values from URI
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array|null Matched route with params or null
     */
    private function matchRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            // Convert route pattern to regex
            // {id} becomes named capture group (?P<id>[^/]+)
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                // Extract parameter values
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
     * Get clean URI from request
     * 
     * Removes query string and leading/trailing slashes
     * 
     * @return string Clean URI path
     */
    private function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove leading and trailing slashes
        $uri = trim($uri, '/');

        return '/' . $uri;
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        
        // Check if it's an API request
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
