<?php
/**
 * AUTHENTICATION CONTROLLER
 * 
 * Purpose: Handles user registration, login, logout
 * Security: Password hashing, session management
 * 
 * Controller Responsibility:
 * - Validate request input
 * - Call User model for business logic
 * - Handle response (redirect or error message)
 * 
 * Business logic is in User model!
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userModel = new User();
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        echo "<h1>Register</h1>";
        echo "<form method='POST' action='/register'>";
        echo "<input type='text' name='username' placeholder='Username' required><br>";
        echo "<input type='email' name='email' placeholder='Email' required><br>";
        echo "<input type='password' name='password' placeholder='Password' required><br>";
        echo "<button type='submit'>Register</button>";
        echo "</form>";
        echo "<p><a href='/login'>Already have account? Login</a></p>";
    }

    /**
     * Handle registration form submission
     */
    public function register(): void
    {
        // Validate required fields
        $missing = $this->validateRequired(['username', 'email', 'password']);
        
        if (!empty($missing)) {
            echo "Missing fields: " . implode(', ', $missing);
            return;
        }

        // Sanitize inputs
        $username = $this->sanitize($_POST['username']);
        $email = $this->sanitize($_POST['email']);
        $password = $_POST['password']; // Don't sanitize passwords!

        // Call model to register user
        $result = $this->userModel->register($username, $email, $password);

        if ($result['success']) {
            // Auto-login after registration
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $this->redirect('/products');
        } else {
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/register'>Try again</a></p>";
        }
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        echo "<h1>Login</h1>";
        echo "<form method='POST' action='/login'>";
        echo "<input type='text' name='username' placeholder='Username' required><br>";
        echo "<input type='password' name='password' placeholder='Password' required><br>";
        echo "<button type='submit'>Login</button>";
        echo "</form>";
        echo "<p><a href='/register'>Don't have account? Register</a></p>";
    }

    /**
     * Handle login form submission
     */
    public function login(): void
    {
        // Validate required fields
        $missing = $this->validateRequired(['username', 'password']);
        
        if (!empty($missing)) {
            echo "Missing fields: " . implode(', ', $missing);
            return;
        }

        $username = $this->sanitize($_POST['username']);
        $password = $_POST['password'];

        // Call model to authenticate
        $result = $this->userModel->login($username, $password);

        if ($result['success']) {
            $this->redirect('/products');
        } else {
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/login'>Try again</a></p>";
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        $this->userModel->logout();
        $this->redirect('/');
    }
}
