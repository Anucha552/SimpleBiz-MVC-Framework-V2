<?php
/**
 * API KEY MIDDLEWARE
 * 
 * Purpose: Verify API key for sensitive API endpoints
 * 
 * Usage:
 * Protected API routes require valid API key:
 * - POST /api/v1/orders/* (order creation)
 * - PUT /api/v1/orders/* (order updates)
 * 
 * How It Works:
 * 1. Check for API key in header (X-API-Key) or query string
 * 2. Validate key against stored keys
 * 3. Allow or deny request
 * 
 * SECURITY:
 * - Log all invalid API key attempts
 * - Rate limit failures (future enhancement)
 * - Support multiple valid keys
 * 
 * API Key Format:
 * - Header: X-API-Key: your-api-key-here
 * - Query: ?api_key=your-api-key-here
 * 
 * Where to Store Keys:
 * - Environment variables (.env)
 * - Database (for multi-tenant apps)
 * - Config files (for simple apps)
 */

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Logger;

class ApiKeyMiddleware extends Middleware
{
    private Logger $logger;

    /**
     * Valid API keys
     * 
     * In production:
     * - Load from database
     * - Load from environment variables
     * - Use hashed keys
     * 
     * Example keys for demo purposes:
     */
    private array $validKeys = [
        'demo-api-key-12345',
        'test-key-67890',
    ];

    public function __construct()
    {
        $this->logger = new Logger();

        // Load API keys from environment if available
        $envKey = getenv('API_KEY');
        if ($envKey) {
            $this->validKeys[] = $envKey;
        }
    }

    /**
     * Handle API key verification
     * 
     * @return bool True to continue, false to stop
     */
    public function handle(): bool
    {
        // Get API key from header or query string
        $apiKey = $this->getApiKey();

        if (!$apiKey) {
            $this->logger->security('api.missing_key', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            ]);

            $this->jsonError('API key required', 401);
            return false;
        }

        // Validate API key
        if (!$this->isValidKey($apiKey)) {
            $this->logger->security('api.invalid_key', [
                'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'key_provided' => substr($apiKey, 0, 10) . '...', // Log partial key only
            ]);

            $this->jsonError('Invalid API key', 401);
            return false;
        }

        // Valid key - log successful access
        $this->logger->info('api.key_validated', [
            'route' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        ]);

        return true; // Continue to controller
    }

    /**
     * Get API key from request
     * 
     * Checks:
     * 1. X-API-Key header
     * 2. Authorization: Bearer {key} header
     * 3. api_key query parameter
     * 
     * @return string|null API key or null
     */
    private function getApiKey(): ?string
    {
        // Check X-API-Key header
        $headers = getallheaders();
        if (isset($headers['X-API-Key'])) {
            return trim($headers['X-API-Key']);
        }

        // Check Authorization Bearer header
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
        }

        // Check query parameter
        if (isset($_GET['api_key'])) {
            return trim($_GET['api_key']);
        }

        return null;
    }

    /**
     * Check if API key is valid
     * 
     * @param string $key API key to validate
     * @return bool True if valid
     */
    private function isValidKey(string $key): bool
    {
        return in_array($key, $this->validKeys, true);
    }

    /**
     * Add API key to valid keys list
     * 
     * Used by admin functions to manage API keys
     * 
     * @param string $key New API key
     */
    public function addKey(string $key): void
    {
        if (!in_array($key, $this->validKeys, true)) {
            $this->validKeys[] = $key;
        }
    }

    /**
     * Remove API key from valid keys list
     * 
     * @param string $key API key to remove
     */
    public function removeKey(string $key): void
    {
        $this->validKeys = array_filter($this->validKeys, function($k) use ($key) {
            return $k !== $key;
        });
    }
}
