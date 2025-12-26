<?php
/**
 * AUTHENTICATION MIDDLEWARE
 * 
 * Purpose: Verify user is logged in before accessing protected routes
 * 
 * Usage:
 * Routes that require authentication use this middleware:
 * - /cart/* (all cart operations)
 * - /orders/* (all order operations)
 * - /checkout (checkout process)
 * 
 * How It Works:
 * 1. Check if user_id exists in session
 * 2. If yes, allow request to continue
 * 3. If no, redirect to login page (web) or return 401 (API)
 * 
 * SECURITY:
 * - Logs unauthorized access attempts
 * - Protects sensitive user data
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;

class AuthMiddleware extends Middleware
{
    private Logger $logger;

    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->logger = new Logger();
    }

    /**
     * Handle authentication check
     * 
     * @return bool True to continue, false to stop
     */
    public function handle(): bool
    {
        // Check if user is authenticated
        if ($this->isAuthenticated()) {
            return true; // User is logged in, continue
        }

        // User not authenticated - log attempt
        $this->logger->security('auth.unauthorized_access', [
            'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        ]);

        // Determine if this is an API request or web request
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($uri, '/api/') === 0;

        if ($isApiRequest) {
            // API request - return JSON error
            $this->jsonError('Authentication required', 401);
        } else {
            // Web request - redirect to login
            $this->redirect('/login');
        }

        return false; // Stop request processing
    }
}
