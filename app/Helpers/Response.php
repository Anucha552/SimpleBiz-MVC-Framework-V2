<?php
/**
 * RESPONSE HELPER
 * 
 * Purpose: Standardized JSON response formatting for API endpoints
 * 
 * Benefits:
 * - Consistent API response structure
 * - Easier to maintain
 * - Type-safe response building
 * - Proper HTTP status codes
 * 
 * Standard Response Format:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "errors": [...],
 *   "meta": {...}
 * }
 * 
 * Usage:
 * Response::success($data, 'Operation successful');
 * Response::error('Validation failed', $errors, 400);
 */

namespace App\Helpers;

class Response
{
    /**
     * Send success JSON response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param array $meta Optional metadata (pagination, etc.)
     * @param int $statusCode HTTP status code
     */
    public static function success($data = null, string $message = 'Success', array $meta = [], int $statusCode = 200): void
    {
        self::send(true, $data, $message, [], $meta, $statusCode);
    }

    /**
     * Send error JSON response
     * 
     * @param string $message Error message
     * @param array $errors Detailed error array
     * @param int $statusCode HTTP status code
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        self::send(false, null, $message, $errors, [], $statusCode);
    }

    /**
     * Send created response (201)
     * 
     * @param mixed $data Created resource data
     * @param string $message Success message
     */
    public static function created($data, string $message = 'Resource created'): void
    {
        self::success($data, $message, [], 201);
    }

    /**
     * Send no content response (204)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * Send not found response (404)
     * 
     * @param string $message Error message
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, [], 404);
    }

    /**
     * Send unauthorized response (401)
     * 
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'Authentication required'): void
    {
        self::error($message, [], 401);
    }

    /**
     * Send forbidden response (403)
     * 
     * @param string $message Error message
     */
    public static function forbidden(string $message = 'Access denied'): void
    {
        self::error($message, [], 403);
    }

    /**
     * Send validation error response (422)
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, $errors, 422);
    }

    /**
     * Send internal server error response (500)
     * 
     * @param string $message Error message
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, [], 500);
    }

    /**
     * Send JSON response
     * 
     * @param bool $success Success status
     * @param mixed $data Response data
     * @param string $message Message
     * @param array $errors Error details
     * @param array $meta Metadata
     * @param int $statusCode HTTP status code
     */
    private static function send(
        bool $success,
        $data,
        string $message,
        array $errors,
        array $meta,
        int $statusCode
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send paginated response
     * 
     * @param array $data Data array
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param int $total Total items
     * @param string $message Success message
     */
    public static function paginated(
        array $data,
        int $page,
        int $perPage,
        int $total,
        string $message = 'Data retrieved'
    ): void {
        $totalPages = ceil($total / $perPage);

        $meta = [
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ];

        self::success($data, $message, $meta);
    }
}
