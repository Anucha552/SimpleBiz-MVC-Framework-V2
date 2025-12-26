<?php
/**
 * LOGGER CLASS
 * 
 * Purpose: Provides semantic logging with security focus
 * Security: Logs suspicious activities for audit trail
 * 
 * Log Levels:
 * - info: General application events (cart.add, order.created)
 * - security: Security-related events (login.failed, price.tamper)
 * - error: Application errors (db.failure, validation.failed)
 * 
 * Log Format:
 * [timestamp] [level] [event] [context] [user_id] [ip] [route]
 * 
 * Why Logging Matters:
 * - Track user behavior and system health
 * - Detect security threats and fraud attempts
 * - Debug production issues
 * - Compliance and audit requirements
 * 
 * SECURITY FOCUS:
 * - Log all authentication attempts
 * - Log price manipulation attempts
 * - Log suspicious cart activities
 * - Log stock validation failures
 */

namespace App\Core;

class Logger
{
    /**
     * Log file path
     */
    private string $logFile;

    /**
     * Create logger instance
     * 
     * @param string $logFile Path to log file
     */
    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../storage/logs/app.log';
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log informational message
     * 
     * Use for: Normal application events
     * Examples: cart.add, product.view, order.created
     * 
     * @param string $event Event identifier
     * @param array $context Additional context data
     */
    public function info(string $event, array $context = []): void
    {
        $this->log('INFO', $event, $context);
    }

    /**
     * Log security event
     * 
     * Use for: Security-related events
     * Examples: login.failed, price.tamper, api.unauthorized
     * 
     * @param string $event Event identifier
     * @param array $context Additional context data
     */
    public function security(string $event, array $context = []): void
    {
        $this->log('SECURITY', $event, $context);
    }

    /**
     * Log error
     * 
     * Use for: Application errors
     * Examples: db.failure, validation.failed, exception.thrown
     * 
     * @param string $event Event identifier
     * @param array $context Additional context data
     */
    public function error(string $event, array $context = []): void
    {
        $this->log('ERROR', $event, $context);
    }

    /**
     * Write log entry
     * 
     * Log Format:
     * [2024-01-15 10:30:45] [SECURITY] order.price_tamper 
     * {"expected":100,"received":50,"product_id":5} 
     * user_id=12 ip=192.168.1.1 route=/checkout
     * 
     * @param string $level Log level
     * @param string $event Event identifier
     * @param array $context Context data
     */
    private function log(string $level, string $event, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // Get request context
        $userId = $_SESSION['user_id'] ?? 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';

        // Build log message
        $contextJson = !empty($context) ? json_encode($context) : '{}';
        
        $logMessage = sprintf(
            "[%s] [%s] %s %s user_id=%s ip=%s method=%s route=%s\n",
            $timestamp,
            $level,
            $event,
            $contextJson,
            $userId,
            $ip,
            $method,
            $route
        );

        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // For critical security events, also log to PHP error log
        if ($level === 'SECURITY') {
            error_log("SECURITY EVENT: {$event} - " . json_encode($context));
        }
    }

    /**
     * Get recent log entries
     * 
     * Useful for admin dashboards
     * 
     * @param int $lines Number of lines to retrieve
     * @return array Log entries
     */
    public function getRecent(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $file = file($this->logFile);
        return array_slice($file, -$lines);
    }

    /**
     * Clear log file
     * 
     * WARNING: Use with caution
     */
    public function clear(): void
    {
        if (file_exists($this->logFile)) {
            file_put_contents($this->logFile, '');
        }
    }
}
