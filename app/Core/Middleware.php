<?php
/**
 * BASE MIDDLEWARE CLASS
 * 
 * Purpose: Provides middleware functionality for request filtering
 * 
 * What is Middleware?
 * - Code that runs BEFORE a controller is executed
 * - Used for: authentication, authorization, validation, logging
 * - Can stop request processing if conditions aren't met
 * 
 * How It Works:
 * 1. Router invokes middleware before controller
 * 2. Middleware handle() method runs
 * 3. If returns false, request processing stops
 * 4. If returns true, request continues to controller
 * 
 * Use Cases:
 * - Check if user is logged in (AuthMiddleware)
 * - Verify API keys (ApiKeyMiddleware)
 * - Rate limiting
 * - CSRF token validation
 * - Request logging
 */

namespace App\Core;

abstract class Middleware
{
    /**
     * Handle incoming request
     * 
     * Child classes must implement this method
     * 
     * @return bool True to continue, false to stop
     */
    abstract public function handle(): bool;

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Send JSON error response and stop execution
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    protected function jsonError(string $message, int $statusCode = 401): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }

    /**
     * Redirect to URL and stop execution
     * 
     * @param string $url Redirect URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
