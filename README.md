# SimpleBiz MVC Framework (Version 2)

**A clean, secure, extensible MVC framework with E-Commerce foundation ready.**

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Core Principles](#core-principles)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Architecture](#architecture)
- [E-Commerce Features](#e-commerce-features)
- [API Documentation](#api-documentation)
- [Security](#security)
- [Usage Examples](#usage-examples)
- [Development](#development)
- [Production Deployment](#production-deployment)
- [License](#license)

---

## 🎯 Overview

SimpleBiz MVC Framework V2 is a **professional PHP framework** built from scratch with:

- **Clean MVC architecture** - Separation of concerns
- **E-Commerce foundation** - Products, cart, orders ready to extend
- **RESTful API** - JSON API for modern frontends
- **Security-first approach** - PDO prepared statements, input validation, logging
- **Teaching-focused** - Extensive comments explaining WHY, not just HOW

**Important:** This is NOT a complete e-commerce shop. It's a **secure, extendable foundation** for building custom solutions.

---

## 🎓 Core Principles

1. **PHP 8+ Modern Standards**
2. **MVC Architecture** - Models handle business logic, Controllers coordinate, Views display
3. **PSR-4 Autoloading** - Clean namespace structure
4. **Thin Controllers** - Business logic lives in Models
5. **Single-Responsibility Functions** - Each function does ONE thing well
6. **PDO Prepared Statements ONLY** - No SQL injection vulnerabilities
7. **No Heavy Dependencies** - Lightweight, understandable codebase
8. **Teaching Comments** - Learn by reading the code

---

## ✨ Features

### Core Framework
- ✅ **Router** - Dynamic routes with parameters and middleware
- ✅ **Database Layer** - Secure PDO singleton with connection pooling
- ✅ **Controllers** - Base controller with helpers
- ✅ **Models** - Business logic layer
- ✅ **Views** - Template rendering with layout support
- ✅ **Middleware** - Authentication and API key validation
- ✅ **Logger** - Semantic logging (info, security, error)

### E-Commerce Foundation
- ✅ **Product Catalog** - Products with stock management
- ✅ **Shopping Cart** - Add/update/remove items with validation
- ✅ **Order Management** - Checkout with server-side validation
- ✅ **User Authentication** - Registration and login with bcrypt
- ✅ **Stock Control** - Prevent overselling with atomic operations
- ✅ **Price Integrity** - Server-side price calculation (tamper-proof)

### API
- ✅ **RESTful Endpoints** - Standard JSON responses
- ✅ **Authentication** - Session-based auth
- ✅ **API Keys** - Secure endpoint protection
- ✅ **Versioning** - `/api/v1` namespace for future compatibility

### Security
- ✅ **SQL Injection Prevention** - Prepared statements everywhere
- ✅ **Password Hashing** - bcrypt with automatic salting
- ✅ **Input Validation** - Server-side validation for all inputs
- ✅ **Security Logging** - Track suspicious activities
- ✅ **Stock Validation** - Prevent inventory fraud
- ✅ **Price Recalculation** - Never trust client prices

---

## 📦 Requirements

- **PHP 8.0 or higher**
- **MySQL 5.7+ or MariaDB 10.3+**
- **Apache with mod_rewrite** (or Nginx)
- **Composer** (for autoloading)

---

## 🚀 Installation

### 1. Clone or Download

```bash
git clone https://github.com/yourusername/SimpleBiz-MVC-Framework-V2.git
cd SimpleBiz-MVC-Framework-V2
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_HOST=localhost
DB_DATABASE=simplebiz_mvc
DB_USERNAME=root
DB_PASSWORD=your_password

APP_ENV=development
APP_DEBUG=true
```

### 4. Create Database

```sql
CREATE DATABASE simplebiz_mvc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Run Migrations

Execute SQL files in order:

```bash
# From MySQL command line or phpMyAdmin
USE simplebiz_mvc;
SOURCE database/migrations/users.sql;
SOURCE database/migrations/products.sql;
SOURCE database/migrations/carts.sql;
SOURCE database/migrations/orders.sql;
```

### 6. Set Permissions

```bash
chmod -R 755 storage/logs
```

### 7. Start Development Server

```bash
composer serve
# OR
php -S localhost:8000 -t public
```

Visit: http://localhost:8000

---

## 💾 Database Setup

### Tables Overview

**users** - User accounts
- `id`, `username`, `email`, `password` (hashed), `created_at`

**products** - Product catalog
- `id`, `name`, `description`, `price` (DECIMAL), `stock`, `status`, `created_at`

**carts** - Shopping carts
- `id`, `user_id`, `created_at`

**cart_items** - Cart contents
- `id`, `cart_id`, `product_id`, `qty`, `price`, `created_at`

**orders** - Order headers
- `id`, `user_id`, `total`, `status` (pending|paid|shipped|cancelled), `created_at`

**order_items** - Order line items
- `id`, `order_id`, `product_id`, `product_name`, `qty`, `price`, `subtotal`

### Sample Data (Optional)

```sql
-- Create test user (password: password123)
INSERT INTO users (username, email, password) VALUES 
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create sample products
INSERT INTO products (name, description, price, stock, status) VALUES
('Laptop', 'High-performance laptop', 999.99, 10, 'active'),
('Mouse', 'Wireless mouse', 29.99, 50, 'active'),
('Keyboard', 'Mechanical keyboard', 79.99, 30, 'active');
```

---

## ⚙️ Configuration

### Environment Variables (.env)

```env
# Application
APP_NAME="SimpleBiz MVC Framework V2"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=simplebiz_mvc
DB_USERNAME=root
DB_PASSWORD=

# API
API_KEY=demo-api-key-12345
```

### Configuration Files

- `config/app.php` - Application settings
- `config/database.php` - Database connection
- `routes/web.php` - Web routes (HTML responses)
- `routes/api.php` - API routes (JSON responses)

---

## 🏗️ Architecture

### Directory Structure

```
SimpleBiz-MVC-Framework-V2/
│
├── app/
│   ├── Core/              # Framework core classes
│   │   ├── Router.php     # Request routing
│   │   ├── Controller.php # Base controller
│   │   ├── Database.php   # Database connection
│   │   ├── View.php       # View rendering
│   │   ├── Middleware.php # Base middleware
│   │   └── Logger.php     # Logging system
│   │
│   ├── Controllers/       # Application controllers
│   │   ├── HomeController.php
│   │   ├── AuthController.php
│   │   ├── Ecommerce/     # E-commerce controllers
│   │   │   ├── ProductController.php
│   │   │   ├── CartController.php
│   │   │   └── OrderController.php
│   │   └── Api/V1/        # API controllers
│   │       ├── ProductApiController.php
│   │       ├── CartApiController.php
│   │       └── OrderApiController.php
│   │
│   ├── Models/            # Business logic
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Cart.php
│   │   └── Order.php
│   │
│   ├── Middleware/        # Request filters
│   │   ├── AuthMiddleware.php
│   │   └── ApiKeyMiddleware.php
│   │
│   └── Helpers/           # Utility classes
│       └── Response.php
│
├── config/                # Configuration files
│   ├── app.php
│   └── database.php
│
├── database/
│   └── migrations/        # SQL migration files
│
├── public/                # Public web root
│   ├── index.php          # Entry point
│   └── .htaccess          # Apache config
│
├── routes/                # Route definitions
│   ├── web.php
│   └── api.php
│
├── storage/
│   └── logs/              # Application logs
│
├── .env.example           # Environment template
├── .gitignore
├── .htaccess
├── composer.json
└── README.md
```

### MVC Flow

```
Request → index.php → Router → Middleware → Controller → Model → Response
```

1. **Router** matches URL to controller
2. **Middleware** validates authentication/permissions
3. **Controller** coordinates request handling
4. **Model** executes business logic
5. **Response** returned to client (HTML or JSON)

---

## 🛒 E-Commerce Features (Foundation)

### What's Included

#### Product Management
- List active products
- View product details
- Stock tracking
- Product search (API)

#### Shopping Cart
- Add products to cart
- Update quantities
- Remove items
- Stock validation
- Price integrity checks

#### Order Processing
- Checkout from cart
- Server-side price recalculation
- Atomic stock decrementation
- Order history
- Order status tracking

### Security Rules

**CRITICAL:** This framework enforces strict security rules:

1. **Price Calculation**
   - NEVER trust client-submitted prices
   - Server recalculates totals from database
   - Logs price tampering attempts

2. **Stock Management**
   - Validates availability before adding to cart
   - Atomic stock updates prevent race conditions
   - Returns stock on order cancellation

3. **Input Validation**
   - All user inputs sanitized
   - IDs and quantities validated as integers
   - SQL injection prevented via prepared statements

### What's NOT Included

This is a **foundation**, not a complete shop:

- ❌ Payment gateway integration (Stripe, PayPal)
- ❌ Shipping calculations
- ❌ Tax calculations
- ❌ Email notifications
- ❌ Admin panel
- ❌ Product categories
- ❌ Product images
- ❌ Reviews/ratings

**These are intentionally left out** so you can customize to your needs.

### Extending for Production

To build a real shop, add:

1. **Payment Integration**
   ```php
   // In OrderController::confirm()
   $stripe = new StripeClient($apiKey);
   $charge = $stripe->charges->create([...]);
   ```

2. **Email Notifications**
   ```php
   // After order creation
   Mail::send($user->email, 'order_confirmation', $orderData);
   ```

3. **Admin Panel**
   - Create `AdminController` with authentication
   - Add product CRUD operations
   - Order management interface

---

## 📡 API Documentation

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication

Most endpoints require authentication:

**Session-based:**
```bash
# Login first to create session
curl -X POST http://localhost:8000/login \
  -d "username=testuser&password=password123"
```

**API Key:**
```bash
# Include in header
curl -H "X-API-Key: demo-api-key-12345" \
  http://localhost:8000/api/v1/orders/create
```

### Response Format

All API responses follow this structure:

```json
{
  "success": true,
  "data": {...},
  "message": "Success message",
  "errors": []
}
```

### Endpoints

#### Products

**GET /api/v1/products**
List all active products

```bash
curl http://localhost:8000/api/v1/products
```

Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Laptop",
      "price": "999.99",
      "stock": 10,
      "status": "active"
    }
  ],
  "message": "Products retrieved successfully"
}
```

**GET /api/v1/products/{id}**
Get product details

```bash
curl http://localhost:8000/api/v1/products/1
```

**GET /api/v1/products/search?q=laptop**
Search products

```bash
curl http://localhost:8000/api/v1/products/search?q=laptop
```

#### Cart

**GET /api/v1/cart** (Auth Required)
Get cart contents

```bash
curl -b cookies.txt http://localhost:8000/api/v1/cart
```

**POST /api/v1/cart/add** (Auth Required)
Add item to cart

```bash
curl -X POST -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 2}' \
  http://localhost:8000/api/v1/cart/add
```

**PUT /api/v1/cart/update** (Auth Required)
Update quantity

```bash
curl -X PUT -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 3}' \
  http://localhost:8000/api/v1/cart/update
```

**DELETE /api/v1/cart/remove/{product_id}** (Auth Required)
Remove item

```bash
curl -X DELETE -b cookies.txt \
  http://localhost:8000/api/v1/cart/remove/1
```

#### Orders

**GET /api/v1/orders** (Auth Required)
List user's orders

```bash
curl -b cookies.txt http://localhost:8000/api/v1/orders
```

**GET /api/v1/orders/{id}** (Auth Required)
Get order details

```bash
curl -b cookies.txt http://localhost:8000/api/v1/orders/1
```

**POST /api/v1/orders/create** (Auth + API Key Required)
Create order from cart

```bash
curl -X POST -b cookies.txt \
  -H "X-API-Key: demo-api-key-12345" \
  http://localhost:8000/api/v1/orders/create
```

**PUT /api/v1/orders/{id}/status** (Auth + API Key Required)
Update order status

```bash
curl -X PUT -b cookies.txt \
  -H "X-API-Key: demo-api-key-12345" \
  -H "Content-Type: application/json" \
  -d '{"status": "paid"}' \
  http://localhost:8000/api/v1/orders/1/status
```

---

## 🔒 Security

### Built-in Protections

1. **SQL Injection Prevention**
   - All database queries use PDO prepared statements
   - Parameters bound separately from SQL

2. **Password Security**
   - Passwords hashed with `password_hash()` (bcrypt)
   - Automatic salt generation
   - Never stored as plain text

3. **Price Tampering Prevention**
   - Client prices ignored
   - Server recalculates from database
   - Discrepancies logged as security events

4. **Stock Fraud Prevention**
   - Atomic stock updates with optimistic locking
   - Validates availability before checkout
   - Returns stock on cancellation

5. **Input Validation**
   - All inputs sanitized
   - Type validation (integers, floats, strings)
   - SQL injection impossible with prepared statements

6. **Authentication**
   - Session-based authentication
   - Middleware protects sensitive routes
   - Logs unauthorized access attempts

7. **Logging**
   - All security events logged
   - Failed login attempts tracked
   - Price manipulation logged
   - Audit trail for compliance

### Production Security Checklist

Before deploying:

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Change default API keys
- [ ] Use HTTPS (SSL certificate)
- [ ] Restrict database user permissions
- [ ] Enable firewall on server
- [ ] Set up log rotation
- [ ] Regular security audits
- [ ] Keep PHP and MySQL updated
- [ ] Use strong session configuration
- [ ] Implement rate limiting (optional)
- [ ] Add CSRF token protection (optional)

---

## 📚 Usage Examples

### Creating a New Route

**1. Define Route** (`routes/web.php`)

```php
$router->get('/about', 'App\Controllers\PageController@about');
```

**2. Create Controller** (`app/Controllers/PageController.php`)

```php
<?php
namespace App\Controllers;

use App\Core\Controller;

class PageController extends Controller
{
    public function about(): void
    {
        echo "<h1>About Us</h1>";
        // Or use view:
        // $this->view('pages/about', ['title' => 'About']);
    }
}
```

### Adding Business Logic to Model

**Example: Add product discount**

```php
// In app/Models/Product.php

public function applyDiscount(int $productId, float $percentage): bool
{
    if ($percentage < 0 || $percentage > 100) {
        return false;
    }
    
    $stmt = $this->db->prepare("
        UPDATE products 
        SET price = price * (1 - ? / 100)
        WHERE id = ?
    ");
    
    $stmt->execute([$percentage, $productId]);
    
    $this->logger->info('product.discount_applied', [
        'product_id' => $productId,
        'discount' => $percentage,
    ]);
    
    return true;
}
```

### Creating API Endpoint

**1. Define API Route** (`routes/api.php`)

```php
$router->get('/api/v1/stats', 'App\Controllers\Api\V1\StatsApiController@index');
```

**2. Create API Controller**

```php
<?php
namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Order;

class StatsApiController extends Controller
{
    public function index(): void
    {
        $orderModel = new Order();
        $stats = [
            'total_orders' => $orderModel->getCount(),
            'total_revenue' => $orderModel->getTotalRevenue(),
        ];
        
        $this->json(true, $stats, 'Stats retrieved');
    }
}
```

---

## 🛠️ Development

### Running Development Server

```bash
composer serve
# OR
php -S localhost:8000 -t public
```

### Viewing Logs

```bash
tail -f storage/logs/app.log
```

### Testing API with curl

```bash
# Get products
curl http://localhost:8000/api/v1/products

# Login
curl -X POST -c cookies.txt \
  -d "username=testuser&password=password123" \
  http://localhost:8000/login

# Add to cart
curl -X POST -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 1}' \
  http://localhost:8000/api/v1/cart/add
```

### Code Style

Follow these conventions:

- **PSR-4 Autoloading** - Namespaces match directory structure
- **Camel Case** - Methods: `getUserOrders()`, Variables: `$userId`
- **Pascal Case** - Classes: `OrderController`
- **Comments** - Explain WHY, not WHAT
- **Single Responsibility** - One function, one purpose

---

## 🚀 Production Deployment

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/simplebiz/public
    
    <Directory /var/www/simplebiz/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/simplebiz_error.log
    CustomLog ${APACHE_LOG_DIR}/simplebiz_access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/simplebiz/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Optimization

```bash
# Optimize Composer autoloader
composer install --optimize-autoloader --no-dev

# Set proper permissions
chmod -R 755 storage
chown -R www-data:www-data storage

# Enable opcache (php.ini)
opcache.enable=1
opcache.memory_consumption=128
```

---

## 📖 Learning Resources

### Understanding the Code

Start reading in this order:

1. `public/index.php` - Entry point
2. `app/Core/Router.php` - Request routing
3. `app/Core/Controller.php` - Base controller
4. `app/Models/Product.php` - Business logic example
5. `app/Controllers/Ecommerce/ProductController.php` - Controller example

### Key Concepts

- **MVC Pattern** - Separation of concerns
- **Dependency Injection** - Pass dependencies to constructors
- **Prepared Statements** - SQL injection prevention
- **Middleware** - Request filtering
- **PSR-4 Autoloading** - Class loading standard

---

## 🤝 Contributing

Contributions welcome! Please:

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgments

Built with ❤️ as a teaching framework for learning:
- Modern PHP practices
- MVC architecture
- E-commerce fundamentals
- API development
- Security best practices

---

## 📞 Support

For questions or issues:
- Create an issue on GitHub
- Review code comments (extensive documentation)
- Check logs in `storage/logs/app.log`

---

**Remember:** This is a secure, extendable e-commerce starter foundation — not a full shop. Customize it for your needs!

Happy Coding! 🚀
