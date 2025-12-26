<?php
/**
 * BASE CONTROLLER CLASS
 * 
 * Purpose: Parent class for all controllers, provides common functionality
 * Philosophy: Keep controllers THIN - delegate business logic to models
 * 
 * Controller Responsibilities:
 * - Validate incoming requests
 * - Call model methods for business logic
 * - Pass data to views or return responses
 * - Handle HTTP responses (redirects, status codes)
 * 
 * Controller SHOULD NOT:
 * - Contain complex business logic
 * - Directly manipulate database
 * - Perform calculations or data processing
 * 
 * All business logic belongs in Model classes!
 */

namespace App\Core;

class Controller
{
    /**
     * Render a view with data
     * 
     * Views are located in app/Views/
     * Example: view('products/index', ['products' => $products])
     * Will load: app/Views/products/index.php
     * 
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to view
     */
    protected function view(string $view, array $data = []): void
    {
        // Extract data array to variables
        extract($data);

        // Build view file path
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$view}");
        }

        require $viewFile;
    }

    /**
     * Redirect to another URL
     * 
     * @param string $url URL to redirect to
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Return JSON response
     * 
     * Standard JSON format for API responses:
     * {
     *   "success": true|false,
     *   "data": {...},
     *   "message": "...",
     *   "errors": [...]
     * }
     * 
     * @param bool $success Success status
     * @param mixed $data Response data
     * @param string $message Optional message
     * @param array $errors Optional error array
     * @param int $statusCode HTTP status code
     */
    protected function json(bool $success, $data = null, string $message = '', array $errors = [], int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        $response = [
            'success' => $success,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($message)) {
            $response['message'] = $message;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Validate required POST parameters
     * 
     * Returns array of missing parameters or empty array if all present
     * 
     * @param array $required Array of required parameter names
     * @return array Missing parameter names
     */
    protected function validateRequired(array $required): array
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Sanitize input string
     * 
     * Removes HTML tags and trims whitespace
     * Use this for user-provided text inputs
     * 
     * @param string $input Raw input
     * @return string Sanitized input
     */
    protected function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Validate integer input
     * 
     * Ensures value is a positive integer
     * Used for IDs, quantities, etc.
     * 
     * @param mixed $value Value to validate
     * @return int|null Valid integer or null
     */
    protected function validateInt($value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return ($int !== false && $int > 0) ? $int : null;
    }

    /**
     * Validate decimal/float input
     * 
     * Ensures value is a positive number
     * Used for prices, amounts, etc.
     * 
     * @param mixed $value Value to validate
     * @return float|null Valid float or null
     */
    protected function validateFloat($value): ?float
    {
        $float = filter_var($value, FILTER_VALIDATE_FLOAT);
        return ($float !== false && $float >= 0) ? $float : null;
    }

    /**
     * Get current authenticated user ID
     * 
     * @return int|null User ID or null if not authenticated
     */
    protected function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}
