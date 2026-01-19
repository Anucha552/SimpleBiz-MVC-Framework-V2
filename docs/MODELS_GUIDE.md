# Models Guide - Complete Reference

This comprehensive guide covers all 16 models in the SimpleBiz MVC Framework with detailed examples, relationships, and best practices.

## Table of Contents

1. [Model Basics](#model-basics)
2. [Core Models](#core-models)
   - [User](#user-model)
   - [Role](#role-model)
   - [Permission](#permission-model)
3. [E-commerce Models](#e-commerce-models)
   - [Product](#product-model)
   - [Category](#category-model)
   - [Cart](#cart-model)
   - [Order](#order-model)
   - [Review](#review-model)
4. [Content Models](#content-models)
   - [Page](#page-model)
   - [Media](#media-model)
5. [System Models](#system-models)
   - [Setting](#setting-model)
   - [ApiKey](#apikey-model)
   - [ActivityLog](#activitylog-model)
   - [Notification](#notification-model)
6. [Supporting Models](#supporting-models)
   - [Address](#address-model)
   - [TestModel](#testmodel-model)
7. [Relationships](#relationships)
8. [Advanced Queries](#advanced-queries)
9. [Best Practices](#best-practices)

---

## Model Basics

All models extend the base `Model` class which provides common database operations.

### Base Model Class

The base `Model` class (`app/Core/Model.php`) provides:
- CRUD operations (Create, Read, Update, Delete)
- Query builder
- Relationships
- Validation
- Timestamps

### Common Model Methods

```php
// Create
$model = Model::create($data);

// Find by ID
$model = Model::find($id);

// Find or fail
$model = Model::findOrFail($id);

// Get all records
$models = Model::all();

// Where query
$models = Model::where('column = ?', [$value])->get();

// First record
$model = Model::where('column = ?', [$value])->first();

// Update
$model->attribute = 'value';
$model->save();

// Delete
$model->delete();

// Count
$count = Model::count();
```

---

## Core Models

### User Model

**Location:** `app/Models/User.php`

Represents users in the system with authentication and authorization capabilities.

#### Table Structure
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    avatar VARCHAR(255),
    bio TEXT,
    is_active TINYINT(1) DEFAULT 1,
    email_verified_at DATETIME,
    remember_token VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME
);
```

#### Basic Usage

**Creating a User:**
```php
use App\Models\User;

// Create new user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('password', PASSWORD_DEFAULT),
    'role' => 'user'
]);

// Create admin user
$admin = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'role' => 'admin'
]);
```

**Finding Users:**
```php
// Find by ID
$user = User::find(1);

// Find by email
$user = User::where('email = ?', ['john@example.com'])->first();

// Get all active users
$activeUsers = User::where('is_active = ?', [1])->get();

// Get users by role
$admins = User::where('role = ?', ['admin'])->get();
```

**Updating User:**
```php
$user = User::find(1);
$user->name = 'John Smith';
$user->bio = 'Software Developer';
$user->save();

// Update password
$user->password = password_hash('newpassword', PASSWORD_DEFAULT);
$user->save();
```

**Authentication Methods:**
```php
class User extends Model
{
    /**
     * Check if password is valid
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * Get user's full name with title
     */
    public function getDisplayName()
    {
        return ucfirst($this->role) . ': ' . $this->name;
    }
}
```

#### User Relationships

```php
// Get user's orders
$orders = $user->orders();

// Get user's reviews
$reviews = $user->reviews();

// Get user's addresses
$addresses = $user->addresses();

// Get user's activity logs
$activities = $user->activityLogs();
```

---

### Role Model

**Location:** `app/Models/Role.php`

Manages user roles for role-based access control (RBAC).

#### Table Structure
```sql
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
```

#### Basic Usage

```php
use App\Models\Role;

// Create role
$role = Role::create([
    'name' => 'Administrator',
    'slug' => 'admin',
    'description' => 'Full system access'
]);

// Find role
$adminRole = Role::where('slug = ?', ['admin'])->first();

// Get all roles
$roles = Role::all();
```

#### Role Methods

```php
class Role extends Model
{
    /**
     * Get users with this role
     */
    public function users()
    {
        return User::where('role = ?', [$this->slug])->get();
    }
    
    /**
     * Get permissions for this role
     */
    public function permissions()
    {
        $sql = "SELECT p.* FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?";
        
        return Permission::query($sql, [$this->id])->fetchAll();
    }
    
    /**
     * Assign permission to role
     */
    public function assignPermission($permissionId)
    {
        $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
        return self::query($sql, [$this->id, $permissionId]);
    }
}
```

---

### Permission Model

**Location:** `app/Models/Permission.php`

Manages permissions for fine-grained access control.

#### Table Structure
```sql
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);
```

#### Basic Usage

```php
use App\Models\Permission;

// Create permissions
$permissions = [
    ['name' => 'View Products', 'slug' => 'products.view'],
    ['name' => 'Create Products', 'slug' => 'products.create'],
    ['name' => 'Edit Products', 'slug' => 'products.edit'],
    ['name' => 'Delete Products', 'slug' => 'products.delete'],
];

foreach ($permissions as $perm) {
    Permission::create($perm);
}

// Check if user has permission
$user = User::find(1);
$canEdit = $user->hasPermission('products.edit');
```

---

## E-commerce Models

### Product Model

**Location:** `app/Models/Product.php`

Represents products in the e-commerce system.

#### Table Structure
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    sku VARCHAR(100) UNIQUE,
    stock INT DEFAULT 0,
    category_id INT,
    featured TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    image VARCHAR(255),
    images TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

#### Basic Usage

**Creating Products:**
```php
use App\Models\Product;
use App\Helpers\StringHelper;

// Create product
$product = Product::create([
    'name' => 'Laptop Pro 15',
    'slug' => StringHelper::slugify('Laptop Pro 15'),
    'description' => 'High-performance laptop for professionals',
    'price' => 1299.99,
    'sale_price' => 1199.99,
    'cost_price' => 900.00,
    'sku' => 'LAP-PRO-15',
    'stock' => 50,
    'category_id' => 1,
    'featured' => 1,
    'status' => 'active'
]);
```

**Finding Products:**
```php
// Get all products
$products = Product::all();

// Get featured products
$featured = Product::where('featured = ?', [1])
                   ->orderBy('created_at', 'DESC')
                   ->limit(10)
                   ->get();

// Get products by category
$products = Product::where('category_id = ?', [1])->get();

// Search products
$products = Product::where('name LIKE ? OR description LIKE ?', 
                          ["%laptop%", "%laptop%"])
                   ->get();

// Get products in stock
$inStock = Product::where('stock > ?', [0])
                  ->where('status = ?', ['active'])
                  ->get();
```

**Product Methods:**
```php
class Product extends Model
{
    /**
     * Get product with discount applied
     */
    public function getEffectivePrice()
    {
        return $this->sale_price ?? $this->price;
    }
    
    /**
     * Check if product is on sale
     */
    public function isOnSale()
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }
    
    /**
     * Get discount percentage
     */
    public function getDiscountPercentage()
    {
        if (!$this->isOnSale()) {
            return 0;
        }
        
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }
    
    /**
     * Check if product is in stock
     */
    public function inStock()
    {
        return $this->stock > 0;
    }
    
    /**
     * Decrease stock
     */
    public function decreaseStock($quantity)
    {
        if ($this->stock < $quantity) {
            throw new Exception('Insufficient stock');
        }
        
        $this->stock -= $quantity;
        return $this->save();
    }
    
    /**
     * Get category
     */
    public function category()
    {
        return Category::find($this->category_id);
    }
    
    /**
     * Get reviews
     */
    public function reviews()
    {
        return Review::where('product_id = ?', [$this->id])->get();
    }
    
    /**
     * Get average rating
     */
    public function getAverageRating()
    {
        $sql = "SELECT AVG(rating) as avg_rating FROM reviews 
                WHERE product_id = ? AND status = 'approved'";
        
        $result = self::query($sql, [$this->id])->fetch();
        return $result ? round($result->avg_rating, 1) : 0;
    }
}
```

---

### Category Model

**Location:** `app/Models/Category.php`

Organizes products into categories.

#### Table Structure
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);
```

#### Basic Usage

```php
use App\Models\Category;

// Create category
$category = Category::create([
    'name' => 'Electronics',
    'slug' => 'electronics',
    'description' => 'Electronic devices and accessories',
    'is_active' => 1
]);

// Create subcategory
$subcategory = Category::create([
    'name' => 'Laptops',
    'slug' => 'laptops',
    'parent_id' => $category->id,
    'is_active' => 1
]);

// Get all active categories
$categories = Category::where('is_active = ?', [1])
                     ->orderBy('sort_order', 'ASC')
                     ->get();
```

#### Category Methods

```php
class Category extends Model
{
    /**
     * Get parent category
     */
    public function parent()
    {
        return $this->parent_id ? Category::find($this->parent_id) : null;
    }
    
    /**
     * Get child categories
     */
    public function children()
    {
        return Category::where('parent_id = ?', [$this->id])->get();
    }
    
    /**
     * Get products in category
     */
    public function products()
    {
        return Product::where('category_id = ?', [$this->id])
                     ->where('status = ?', ['active'])
                     ->get();
    }
    
    /**
     * Get product count
     */
    public function getProductCount()
    {
        $sql = "SELECT COUNT(*) as count FROM products 
                WHERE category_id = ? AND status = 'active'";
        
        $result = self::query($sql, [$this->id])->fetch();
        return $result ? $result->count : 0;
    }
    
    /**
     * Check if category has children
     */
    public function hasChildren()
    {
        return count($this->children()) > 0;
    }
}
```

---

### Cart Model

**Location:** `app/Models/Cart.php`

Manages shopping cart items.

#### Table Structure
```sql
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price DECIMAL(10,2),
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

#### Basic Usage

```php
use App\Models\Cart;

// Add item to cart
$cartItem = Cart::create([
    'user_id' => $_SESSION['user_id'] ?? null,
    'session_id' => session_id(),
    'product_id' => 1,
    'quantity' => 2,
    'price' => 99.99
]);

// Get user's cart
$userId = $_SESSION['user_id'];
$cartItems = Cart::where('user_id = ?', [$userId])->get();

// Get cart by session
$sessionId = session_id();
$cartItems = Cart::where('session_id = ?', [$sessionId])->get();

// Update quantity
$cartItem = Cart::find(1);
$cartItem->quantity = 5;
$cartItem->save();

// Remove from cart
$cartItem->delete();

// Clear cart
Cart::where('user_id = ?', [$userId])->delete();
```

#### Cart Methods

```php
class Cart extends Model
{
    /**
     * Get product
     */
    public function product()
    {
        return Product::find($this->product_id);
    }
    
    /**
     * Get subtotal for this item
     */
    public function getSubtotal()
    {
        return $this->price * $this->quantity;
    }
    
    /**
     * Get cart total for user
     */
    public static function getTotal($userId = null, $sessionId = null)
    {
        $sql = "SELECT SUM(price * quantity) as total FROM cart WHERE ";
        
        if ($userId) {
            $sql .= "user_id = ?";
            $params = [$userId];
        } else {
            $sql .= "session_id = ?";
            $params = [$sessionId];
        }
        
        $result = self::query($sql, $params)->fetch();
        return $result ? $result->total : 0;
    }
    
    /**
     * Get cart item count
     */
    public static function getItemCount($userId = null, $sessionId = null)
    {
        $sql = "SELECT SUM(quantity) as count FROM cart WHERE ";
        
        if ($userId) {
            $sql .= "user_id = ?";
            $params = [$userId];
        } else {
            $sql .= "session_id = ?";
            $params = [$sessionId];
        }
        
        $result = self::query($sql, $params)->fetch();
        return $result ? $result->count : 0;
    }
}
```

---

### Order Model

**Location:** `app/Models/Order.php`

Manages customer orders.

#### Table Structure
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    total DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    tax DECIMAL(10,2),
    shipping DECIMAL(10,2),
    discount DECIMAL(10,2),
    payment_method VARCHAR(50),
    payment_status VARCHAR(50) DEFAULT 'pending',
    shipping_address TEXT,
    billing_address TEXT,
    notes TEXT,
    paid_at DATETIME,
    shipped_at DATETIME,
    delivered_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### Basic Usage

```php
use App\Models\Order;

// Create order
$order = Order::create([
    'order_number' => 'ORD-' . time(),
    'user_id' => $_SESSION['user_id'],
    'status' => 'pending',
    'total' => 299.99,
    'subtotal' => 249.99,
    'tax' => 25.00,
    'shipping' => 25.00,
    'payment_method' => 'credit_card',
    'payment_status' => 'pending'
]);

// Get user orders
$orders = Order::where('user_id = ?', [$userId])
               ->orderBy('created_at', 'DESC')
               ->get();

// Get order by number
$order = Order::where('order_number = ?', ['ORD-123456'])->first();

// Update order status
$order = Order::find(1);
$order->status = 'processing';
$order->save();
```

#### Order Methods

```php
class Order extends Model
{
    /**
     * Get order items
     */
    public function items()
    {
        $sql = "SELECT oi.*, p.name as product_name 
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
        
        return self::query($sql, [$this->id])->fetchAll();
    }
    
    /**
     * Get user
     */
    public function user()
    {
        return User::find($this->user_id);
    }
    
    /**
     * Mark as paid
     */
    public function markAsPaid()
    {
        $this->payment_status = 'paid';
        $this->paid_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Mark as shipped
     */
    public function markAsShipped()
    {
        $this->status = 'shipped';
        $this->shipped_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Mark as delivered
     */
    public function markAsDelivered()
    {
        $this->status = 'delivered';
        $this->delivered_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Cancel order
     */
    public function cancel()
    {
        if ($this->status === 'delivered') {
            throw new Exception('Cannot cancel delivered order');
        }
        
        $this->status = 'cancelled';
        return $this->save();
    }
}
```

---

### Review Model

**Location:** `app/Models/Review.php`

Manages product reviews and ratings.

#### Table Structure
```sql
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(255),
    comment TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    helpful_count INT DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Basic Usage

```php
use App\Models\Review;

// Create review
$review = Review::create([
    'product_id' => 1,
    'user_id' => $_SESSION['user_id'],
    'rating' => 5,
    'title' => 'Excellent product!',
    'comment' => 'This product exceeded my expectations.',
    'status' => 'pending'
]);

// Get product reviews
$reviews = Review::where('product_id = ? AND status = ?', [1, 'approved'])
                 ->orderBy('created_at', 'DESC')
                 ->get();

// Approve review
$review = Review::find(1);
$review->status = 'approved';
$review->save();
```

#### Review Methods

```php
class Review extends Model
{
    /**
     * Get product
     */
    public function product()
    {
        return Product::find($this->product_id);
    }
    
    /**
     * Get user
     */
    public function user()
    {
        return User::find($this->user_id);
    }
    
    /**
     * Approve review
     */
    public function approve()
    {
        $this->status = 'approved';
        return $this->save();
    }
    
    /**
     * Reject review
     */
    public function reject()
    {
        $this->status = 'rejected';
        return $this->save();
    }
    
    /**
     * Increment helpful count
     */
    public function markAsHelpful()
    {
        $this->helpful_count++;
        return $this->save();
    }
}
```

---

## Content Models

### Page Model

**Location:** `app/Models/Page.php`

Manages static content pages.

#### Table Structure
```sql
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    status VARCHAR(50) DEFAULT 'draft',
    template VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME
);
```

#### Basic Usage

```php
use App\Models\Page;

// Create page
$page = Page::create([
    'title' => 'About Us',
    'slug' => 'about',
    'content' => '<p>Company information...</p>',
    'meta_title' => 'About Us - Company Name',
    'meta_description' => 'Learn more about our company',
    'status' => 'published'
]);

// Get published pages
$pages = Page::where('status = ?', ['published'])->get();

// Find page by slug
$page = Page::where('slug = ?', ['about'])->first();
```

---

### Media Model

**Location:** `app/Models/Media.php`

Manages uploaded media files.

#### Table Structure
```sql
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    mime_type VARCHAR(100),
    size INT,
    path VARCHAR(255),
    url VARCHAR(255),
    thumbnails TEXT,
    user_id INT,
    uploaded_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Basic Usage

```php
use App\Models\Media;

// Create media record
$media = Media::create([
    'filename' => 'image_123.jpg',
    'original_name' => 'product.jpg',
    'mime_type' => 'image/jpeg',
    'size' => 1024000,
    'path' => 'uploads/products/image_123.jpg',
    'user_id' => $_SESSION['user_id'],
    'uploaded_at' => date('Y-m-d H:i:s')
]);

// Get user's uploads
$media = Media::where('user_id = ?', [$userId])->get();

// Get images only
$images = Media::where('mime_type LIKE ?', ['image/%'])->get();
```

---

## System Models

### Setting Model

**Location:** `app/Models/Setting.php`

Stores application settings.

#### Table Structure
```sql
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    created_at DATETIME,
    updated_at DATETIME
);
```

#### Basic Usage

```php
use App\Models\Setting;

// Create setting
Setting::create([
    'key' => 'site_name',
    'value' => 'My Website',
    'type' => 'string'
]);

// Get setting
$siteName = Setting::get('site_name', 'Default Name');

// Update setting
Setting::set('site_name', 'New Site Name');

// Get all settings
$settings = Setting::all();
```

#### Setting Methods

```php
class Setting extends Model
{
    /**
     * Get setting value
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('`key` = ?', [$key])->first();
        return $setting ? $setting->value : $default;
    }
    
    /**
     * Set setting value
     */
    public static function set($key, $value, $type = 'string')
    {
        $setting = self::where('`key` = ?', [$key])->first();
        
        if ($setting) {
            $setting->value = $value;
            $setting->save();
        } else {
            self::create([
                'key' => $key,
                'value' => $value,
                'type' => $type
            ]);
        }
    }
}
```

---

### ApiKey Model

**Location:** `app/Models/ApiKey.php`

Manages API keys for authentication.

#### Table Structure
```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255),
    user_id INT,
    permissions TEXT,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### Basic Usage

```php
use App\Models\ApiKey;

// Create API key
$apiKey = ApiKey::create([
    'key' => bin2hex(random_bytes(32)),
    'name' => 'Mobile App Key',
    'user_id' => 1,
    'permissions' => json_encode(['read', 'write']),
    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year'))
]);

// Validate API key
$key = ApiKey::where('`key` = ?', [$requestKey])->first();

if ($key && (!$key->expires_at || strtotime($key->expires_at) > time())) {
    // Valid key
}
```

---

### ActivityLog Model

**Location:** `app/Models/ActivityLog.php`

Tracks user activities and system events.

#### Table Structure
```sql
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    properties TEXT,
    created_at DATETIME
);
```

#### Basic Usage

```php
use App\Models\ActivityLog;

// Log activity
ActivityLog::create([
    'user_id' => $_SESSION['user_id'] ?? null,
    'action' => 'product_created',
    'description' => 'Created product: Laptop Pro 15',
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'properties' => json_encode(['product_id' => 123])
]);

// Get user activities
$activities = ActivityLog::where('user_id = ?', [$userId])
                        ->orderBy('created_at', 'DESC')
                        ->limit(50)
                        ->get();

// Get recent activities
$recent = ActivityLog::orderBy('created_at', 'DESC')
                    ->limit(100)
                    ->get();
```

---

### Notification Model

**Location:** `app/Models/Notification.php`

Manages user notifications.

#### Table Structure
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    message TEXT,
    data TEXT,
    read_at DATETIME,
    created_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Basic Usage

```php
use App\Models\Notification;

// Create notification
Notification::create([
    'user_id' => 1,
    'type' => 'order_shipped',
    'title' => 'Order Shipped',
    'message' => 'Your order #12345 has been shipped.',
    'data' => json_encode(['order_id' => 12345])
]);

// Get unread notifications
$unread = Notification::where('user_id = ? AND read_at IS NULL', [$userId])
                     ->orderBy('created_at', 'DESC')
                     ->get();

// Mark as read
$notification = Notification::find(1);
$notification->read_at = date('Y-m-d H:i:s');
$notification->save();
```

---

## Supporting Models

### Address Model

**Location:** `app/Models/Address.php`

Stores user addresses.

#### Table Structure
```sql
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'shipping',
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### Basic Usage

```php
use App\Models\Address;

// Create address
$address = Address::create([
    'user_id' => 1,
    'type' => 'shipping',
    'address_line1' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'postal_code' => '10001',
    'country' => 'USA',
    'is_default' => 1
]);

// Get user addresses
$addresses = Address::where('user_id = ?', [$userId])->get();

// Get default address
$default = Address::where('user_id = ? AND is_default = ?', [$userId, 1])->first();
```

---

### TestModel Model

**Location:** `app/Models/TestModel.php`

Used for testing and development purposes.

```php
use App\Models\TestModel;

// This is a simple model for testing database operations
$test = TestModel::create(['name' => 'Test', 'value' => '123']);
```

---

## Relationships

### One-to-Many

```php
// User has many orders
class User extends Model
{
    public function orders()
    {
        return Order::where('user_id = ?', [$this->id])->get();
    }
}

// Order belongs to user
class Order extends Model
{
    public function user()
    {
        return User::find($this->user_id);
    }
}
```

### Many-to-Many

```php
// Role has many permissions (through role_permissions)
class Role extends Model
{
    public function permissions()
    {
        $sql = "SELECT p.* FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?";
        
        return Permission::query($sql, [$this->id])->fetchAll();
    }
}
```

---

## Advanced Queries

### Complex WHERE Clauses

```php
// Multiple conditions
$products = Product::where('price > ?', [100])
                  ->where('stock > ?', [0])
                  ->where('status = ?', ['active'])
                  ->get();

// OR conditions
$products = Product::where('category_id = ? OR featured = ?', [1, 1])->get();

// IN clause
$products = Product::where('id IN (?, ?, ?)', [1, 2, 3])->get();

// LIKE search
$products = Product::where('name LIKE ?', ['%laptop%'])->get();
```

### Ordering and Limiting

```php
// Order by
$products = Product::orderBy('price', 'ASC')->get();
$products = Product::orderBy('created_at', 'DESC')->get();

// Limit
$products = Product::limit(10)->get();
$products = Product::limit(10)->offset(20)->get();

// Combined
$products = Product::where('featured = ?', [1])
                  ->orderBy('created_at', 'DESC')
                  ->limit(5)
                  ->get();
```

### Aggregations

```php
// Count
$count = Product::count();
$activeCount = Product::where('status = ?', ['active'])->count();

// Sum
$sql = "SELECT SUM(total) as total_sales FROM orders WHERE status = 'completed'";
$result = Order::query($sql)->fetch();
$totalSales = $result->total_sales;

// Average
$sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?";
$result = Review::query($sql, [1])->fetch();
$avgRating = $result->avg_rating;
```

### Joins

```php
// Inner join
$sql = "SELECT p.*, c.name as category_name 
        FROM products p
        INNER JOIN categories c ON p.category_id = c.id
        WHERE p.status = ?";

$products = Product::query($sql, ['active'])->fetchAll();

// Left join
$sql = "SELECT u.*, COUNT(o.id) as order_count
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        GROUP BY u.id";

$users = User::query($sql)->fetchAll();
```

---

## Best Practices

### ✅ Do

1. **Use Mass Assignment Safely**
   ```php
   // Define fillable fields
   protected $fillable = ['name', 'email', 'password'];
   
   // Or use guarded
   protected $guarded = ['id', 'created_at'];
   ```

2. **Use Transactions for Related Operations**
   ```php
   $db = Database::getInstance();
   $pdo = $db->getPdo();
   
   try {
       $pdo->beginTransaction();
       
       // Create order
       $order = Order::create($orderData);
       
       // Create order items
       foreach ($items as $item) {
           OrderItem::create([
               'order_id' => $order->id,
               'product_id' => $item['product_id'],
               'quantity' => $item['quantity'],
               'price' => $item['price']
           ]);
           
           // Decrease stock
           $product = Product::find($item['product_id']);
           $product->decreaseStock($item['quantity']);
       }
       
       $pdo->commit();
   } catch (Exception $e) {
       $pdo->rollBack();
       throw $e;
   }
   ```

3. **Use Scopes for Reusable Queries**
   ```php
   class Product extends Model
   {
       public static function active()
       {
           return self::where('status = ?', ['active']);
       }
       
       public static function featured()
       {
           return self::where('featured = ?', [1]);
       }
   }
   
   // Usage
   $products = Product::active()->featured()->get();
   ```

4. **Validate Before Saving**
   ```php
   public function save()
   {
       $this->validate();
       return parent::save();
   }
   
   protected function validate()
   {
       if (empty($this->name)) {
           throw new Exception('Name is required');
       }
       
       if (empty($this->email)) {
           throw new Exception('Email is required');
       }
   }
   ```

### ❌ Don't

1. **Don't Use Raw Queries Without Parameters**
   ```php
   // BAD - SQL injection vulnerability
   $user = User::query("SELECT * FROM users WHERE email = '$email'")->fetch();
   
   // GOOD - Use prepared statements
   $user = User::where('email = ?', [$email])->first();
   ```

2. **Don't Load All Records When Not Needed**
   ```php
   // BAD - Loads all products into memory
   $products = Product::all();
   foreach ($products as $product) {
       // Process
   }
   
   // GOOD - Use pagination or limit
   $products = Product::limit(100)->get();
   ```

3. **Don't Ignore N+1 Query Problems**
   ```php
   // BAD - N+1 queries
   $orders = Order::all();
   foreach ($orders as $order) {
       $user = $order->user(); // Additional query for each order
   }
   
   // GOOD - Use join or eager loading
   $sql = "SELECT o.*, u.name as user_name 
           FROM orders o
           INNER JOIN users u ON o.user_id = u.id";
   $orders = Order::query($sql)->fetchAll();
   ```

---

## Related Documentation

- [Migration Guide](MIGRATION_GUIDE.md) - Database migrations for models
- [Seeding Guide](SEEDING_GUIDE.md) - Populating models with test data
- [Testing Guide](TESTING_GUIDE.md) - Testing models
- [Core Usage Guide](CORE_USAGE.md) - Database and query builder

---

**Last Updated:** January 2026  
**Version:** 2.0
