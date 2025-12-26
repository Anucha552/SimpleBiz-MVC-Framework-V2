<?php
/**
 * HOME CONTROLLER
 * 
 * Purpose: Handles homepage and general pages
 */

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    /**
     * Display homepage
     */
    public function index(): void
    {
        echo "<h1>SimpleBiz MVC Framework V2</h1>";
        echo "<p>Welcome to the E-Commerce Foundation</p>";
        echo "<ul>";
        echo "<li><a href='/products'>View Products</a></li>";
        echo "<li><a href='/cart'>View Cart</a></li>";
        echo "<li><a href='/orders'>My Orders</a></li>";
        echo "<li><a href='/login'>Login</a></li>";
        echo "<li><a href='/register'>Register</a></li>";
        echo "</ul>";
    }
}
