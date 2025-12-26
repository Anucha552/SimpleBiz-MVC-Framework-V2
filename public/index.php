<?php
/**
 * APPLICATION ENTRY POINT
 * 
 * Purpose: Front controller - all requests flow through this file
 * 
 * Responsibilities:
 * 1. Load dependencies (Composer autoloader)
 * 2. Load environment variables
 * 3. Set error reporting based on environment
 * 4. Start session
 * 5. Create router and load routes
 * 6. Dispatch request to appropriate controller
 * 
 * How It Works:
 * - Web server (Apache/Nginx) redirects all requests here
 * - Router matches URL to controller
 * - Controller handles request and returns response
 * 
 * SECURITY:
 * - Only this file should be in public directory
 * - All other files outside web root (protected)
 * - Error display disabled in production
 */

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set environment variable
            if (!getenv($key)) {
                putenv("{$key}={$value}");
            }
        }
    }
}

// Load application configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['env'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set timezone
date_default_timezone_set($config['timezone']);

// Create router instance
$router = new App\Core\Router();

// Load route definitions
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// Handle errors gracefully
try {
    // Dispatch request
    $router->dispatch();
} catch (\Exception $e) {
    // Log error
    $logger = new App\Core\Logger();
    $logger->error('application.exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    // Show error based on environment
    if ($config['env'] === 'production') {
        // Production: Generic error message
        http_response_code(500);
        
        // Check if API request
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
            ]);
        } else {
            echo "<h1>500 - Internal Server Error</h1>";
            echo "<p>Something went wrong. Please try again later.</p>";
        }
    } else {
        // Development: Show detailed error
        http_response_code(500);
        echo "<h1>Application Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<h2>Stack Trace:</h2>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}
