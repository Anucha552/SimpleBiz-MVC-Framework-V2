# Seeding Guide - Database Seeding

This comprehensive guide covers database seeding in the SimpleBiz MVC Framework, including creating seeders, using Faker, and best practices for populating your database with test data.

## Table of Contents

1. [Introduction to Seeding](#introduction-to-seeding)
2. [Seeder Class Basics](#seeder-class-basics)
3. [Creating Seeders](#creating-seeders)
4. [Using Faker Library](#using-faker-library)
5. [Running Seeders](#running-seeders)
6. [Example Seeders](#example-seeders)
7. [Seeding Relationships](#seeding-relationships)
8. [Advanced Techniques](#advanced-techniques)
9. [Best Practices](#best-practices)

---

## Introduction to Seeding

Database seeding is the process of populating your database with initial or test data. This is useful for:

- **Development** - Quickly populate database with realistic test data
- **Testing** - Create consistent test data for automated tests
- **Demonstration** - Set up demo environments with sample data
- **Initial Setup** - Populate required data (roles, permissions, settings)

### Benefits of Seeding

✅ **Faster Development** - No need to manually create test data  
✅ **Consistent Testing** - Same data across different environments  
✅ **Realistic Data** - Generate data that mimics production  
✅ **Time Saving** - Automate data creation process  

---

## Seeder Class Basics

### Base Seeder Class

The framework includes a base `Seeder` class (`app/Core/Seeder.php`) that provides common functionality:

```php
<?php

namespace App\Core;

abstract class Seeder
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Run the seeder
     */
    abstract public function run();
    
    /**
     * Execute a SQL query
     */
    protected function query($sql, $params = [])
    {
        return $this->db->query($sql, $params);
    }
    
    /**
     * Call another seeder
     */
    protected function call($seederClass)
    {
        $seeder = new $seederClass();
        $seeder->run();
    }
    
    /**
     * Truncate a table
     */
    protected function truncate($table)
    {
        $this->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->query("TRUNCATE TABLE {$table}");
        $this->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}
```

### Creating a Seeder

All seeders extend the base `Seeder` class and implement the `run()` method:

```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Your seeding logic here
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'admin'
        ]);
    }
}
```

---

## Creating Seeders

### Basic Seeder Structure

**Location:** `database/seeders/`

**Example: RoleSeeder.php**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        echo "Seeding roles...\n";
        
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manage products and orders',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Customer',
                'slug' => 'customer',
                'description' => 'Regular customer account',
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];
        
        foreach ($roles as $role) {
            Role::create($role);
        }
        
        echo "Roles seeded successfully!\n";
    }
}
```

### Seeder with Truncation

```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run()
    {
        echo "Seeding settings...\n";
        
        // Clear existing settings
        $this->truncate('settings');
        
        $settings = [
            ['key' => 'site_name', 'value' => 'SimpleBiz Store', 'type' => 'string'],
            ['key' => 'site_description', 'value' => 'Your one-stop shop', 'type' => 'string'],
            ['key' => 'items_per_page', 'value' => '20', 'type' => 'integer'],
            ['key' => 'enable_registration', 'value' => 'true', 'type' => 'boolean'],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'string'],
            ['key' => 'tax_rate', 'value' => '0.10', 'type' => 'decimal'],
        ];
        
        foreach ($settings as $setting) {
            Setting::create(array_merge($setting, [
                'created_at' => date('Y-m-d H:i:s')
            ]));
        }
        
        echo "Settings seeded successfully!\n";
    }
}
```

---

## Using Faker Library

Faker is a PHP library that generates realistic fake data. It's perfect for creating large amounts of test data.

### Installing Faker

Faker is typically included in development dependencies:

```bash
composer require fakerphp/faker --dev
```

### Basic Faker Usage

```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\User;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        echo "Seeding users...\n";
        
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'admin',
            'bio' => 'System administrator',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Create 50 random users
        for ($i = 0; $i < 50; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'role' => $faker->randomElement(['customer', 'manager']),
                'bio' => $faker->sentence(10),
                'is_active' => $faker->boolean(90), // 90% active
                'created_at' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s')
            ]);
        }
        
        echo "Users seeded successfully!\n";
    }
}
```

### Faker Data Types

```php
// Personal Information
$faker->name;                    // 'John Doe'
$faker->firstName;               // 'John'
$faker->lastName;                // 'Doe'
$faker->email;                   // 'john@example.com'
$faker->safeEmail;               // 'john.doe@example.com'
$faker->phoneNumber;             // '+1-555-123-4567'

// Address
$faker->address;                 // '123 Main St, Apt 4B, New York, NY 10001'
$faker->streetAddress;           // '123 Main St'
$faker->city;                    // 'New York'
$faker->state;                   // 'New York'
$faker->stateAbbr;              // 'NY'
$faker->postcode;               // '10001'
$faker->country;                // 'United States'

// Text
$faker->word;                    // 'lorem'
$faker->words(3);               // ['lorem', 'ipsum', 'dolor']
$faker->sentence;               // 'Lorem ipsum dolor sit amet.'
$faker->paragraph;              // Full paragraph
$faker->text(200);              // 200 characters of text

// Numbers
$faker->randomNumber(5);        // 12345
$faker->numberBetween(1, 100);  // Random number between 1-100
$faker->randomFloat(2, 0, 1000); // 123.45
$faker->randomDigit;            // 7

// Dates
$faker->dateTime;               // DateTime object
$faker->date('Y-m-d');          // '2023-01-15'
$faker->dateTimeBetween('-1 year', 'now'); // Random date in past year
$faker->time('H:i:s');          // '14:30:00'

// Internet
$faker->url;                    // 'http://example.com'
$faker->slug;                   // 'lorem-ipsum'
$faker->ipv4;                   // '192.168.1.1'
$faker->userAgent;              // Browser user agent string

// Boolean & Random
$faker->boolean;                // true or false
$faker->boolean(70);            // 70% chance of true
$faker->randomElement(['a', 'b', 'c']); // Random element from array

// Images
$faker->imageUrl(640, 480);     // 'https://via.placeholder.com/640x480'
$faker->imageUrl(640, 480, 'cats'); // Category-specific image

// Company
$faker->company;                // 'Acme Corporation'
$faker->companySuffix;          // 'Inc'
$faker->jobTitle;               // 'Software Engineer'

// File
$faker->fileExtension;          // 'pdf'
$faker->mimeType;               // 'application/pdf'

// Color
$faker->hexColor;               // '#3F5C9A'
$faker->rgbColor;               // '63,92,154'
```

---

## Running Seeders

### Main Seeder File

Create a main seeder file that calls all other seeders:

**database/seeders/DatabaseSeeder.php**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "Starting database seeding...\n\n";
        
        // Call individual seeders in order
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(SettingSeeder::class);
        
        echo "\nDatabase seeding completed!\n";
    }
}
```

### Seed Script (seed.php)

**Location:** `seed.php` (in project root)
**Recommended:** Use `php console seed` command instead.

**Note:** The `seed.php` file is maintained for backward compatibility, but the recommended way is to use the console command:

```bash
php console seed
```

### Running Seeders from Command Line

```bash
# Run all seeders
php console seed
```

**Note:** You can modify the `console` script to support specific seeders if needed.

---

## Example Seeders

### CategorySeeder

**database/seeders/CategorySeeder.php**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Category;
use App\Helpers\StringHelper;
use Faker\Factory as Faker;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        echo "Seeding categories...\n";
        
        // Clear existing categories
        $this->truncate('categories');
        
        // Create main categories
        $mainCategories = [
            'Electronics',
            'Clothing',
            'Books',
            'Home & Garden',
            'Sports & Outdoors',
            'Toys & Games',
            'Health & Beauty',
            'Automotive',
        ];
        
        $categoryIds = [];
        
        foreach ($mainCategories as $index => $categoryName) {
            $category = Category::create([
                'name' => $categoryName,
                'slug' => StringHelper::slugify($categoryName),
                'description' => $faker->sentence(10),
                'sort_order' => $index + 1,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $categoryIds[$categoryName] = $category->id;
        }
        
        // Create subcategories
        $subcategories = [
            'Electronics' => ['Laptops', 'Smartphones', 'Tablets', 'Cameras', 'Audio'],
            'Clothing' => ['Men', 'Women', 'Kids', 'Shoes', 'Accessories'],
            'Books' => ['Fiction', 'Non-Fiction', 'Children', 'Educational', 'Comics'],
            'Home & Garden' => ['Furniture', 'Decor', 'Kitchen', 'Garden Tools', 'Lighting'],
            'Sports & Outdoors' => ['Fitness', 'Camping', 'Cycling', 'Swimming', 'Team Sports'],
        ];
        
        foreach ($subcategories as $parent => $subs) {
            foreach ($subs as $index => $subName) {
                Category::create([
                    'name' => $subName,
                    'slug' => StringHelper::slugify($subName),
                    'description' => $faker->sentence(8),
                    'parent_id' => $categoryIds[$parent],
                    'sort_order' => $index + 1,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        
        echo "Categories seeded successfully!\n";
    }
}
```

### ProductSeeder

**database/seeders/ProductSeeder.php**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Helpers\StringHelper;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        echo "Seeding products...\n";
        
        // Clear existing products
        $this->truncate('products');
        
        // Get all categories
        $categories = Category::all();
        
        if (empty($categories)) {
            echo "No categories found. Please run CategorySeeder first.\n";
            return;
        }
        
        // Create 100 products
        for ($i = 1; $i <= 100; $i++) {
            $name = $faker->words(3, true);
            $name = ucwords($name);
            
            $price = $faker->randomFloat(2, 10, 1000);
            $costPrice = $price * 0.6; // 40% margin
            $salePrice = $faker->boolean(30) ? $price * 0.85 : null; // 30% on sale
            
            Product::create([
                'name' => $name,
                'slug' => StringHelper::slugify($name) . '-' . $i,
                'description' => $faker->paragraph(3),
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $costPrice,
                'sku' => 'SKU-' . strtoupper($faker->bothify('???-###')),
                'stock' => $faker->numberBetween(0, 100),
                'category_id' => $faker->randomElement($categories)->id,
                'featured' => $faker->boolean(20), // 20% featured
                'status' => $faker->randomElement(['active', 'active', 'active', 'draft']), // 75% active
                'image' => 'https://via.placeholder.com/400x400?text=' . urlencode($name),
                'meta_title' => $name . ' - SimpleBiz Store',
                'meta_description' => $faker->sentence(15),
                'created_at' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo "Products seeded successfully!\n";
    }
}
```

### ReviewSeeder

**database/seeders/ReviewSeeder.php**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Faker\Factory as Faker;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        echo "Seeding reviews...\n";
        
        $this->truncate('reviews');
        
        $products = Product::where('status = ?', ['active'])->get();
        $users = User::where('role = ?', ['customer'])->get();
        
        if (empty($products) || empty($users)) {
            echo "No products or users found. Please run ProductSeeder and UserSeeder first.\n";
            return;
        }
        
        // Add 2-5 reviews per product (for first 30 products)
        $productsToReview = array_slice($products, 0, 30);
        
        foreach ($productsToReview as $product) {
            $reviewCount = $faker->numberBetween(2, 5);
            
            for ($i = 0; $i < $reviewCount; $i++) {
                $rating = $faker->numberBetween(1, 5);
                
                // Better products get better reviews
                if ($product->featured) {
                    $rating = $faker->numberBetween(4, 5);
                }
                
                $titles = [
                    1 => ['Terrible', 'Awful', 'Disappointed', 'Not recommended'],
                    2 => ['Not great', 'Below average', 'Could be better', 'Mediocre'],
                    3 => ['It\'s okay', 'Average', 'Decent', 'Fair'],
                    4 => ['Good product', 'Happy with purchase', 'Recommended', 'Nice'],
                    5 => ['Excellent!', 'Love it!', 'Outstanding', 'Perfect!', 'Amazing!']
                ];
                
                Review::create([
                    'product_id' => $product->id,
                    'user_id' => $faker->randomElement($users)->id,
                    'rating' => $rating,
                    'title' => $faker->randomElement($titles[$rating]),
                    'comment' => $faker->paragraph(2),
                    'status' => $faker->randomElement(['approved', 'approved', 'approved', 'pending']), // 75% approved
                    'helpful_count' => $faker->numberBetween(0, 20),
                    'created_at' => $faker->dateTimeBetween('-3 months', 'now')->format('Y-m-d H:i:s'),
                ]);
            }
        }
        
        echo "Reviews seeded successfully!\n";
    }
}
```

### OrderSeeder

**database/seeders/OrderSeeder.php**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        echo "Seeding orders...\n";
        
        $this->truncate('order_items');
        $this->truncate('orders');
        
        $users = User::where('role = ?', ['customer'])->get();
        $products = Product::where('status = ? AND stock > ?', ['active', 0])->get();
        
        if (empty($users) || empty($products)) {
            echo "No users or products found.\n";
            return;
        }
        
        // Create 50 orders
        for ($i = 0; $i < 50; $i++) {
            $user = $faker->randomElement($users);
            $orderDate = $faker->dateTimeBetween('-6 months', 'now');
            
            $status = $faker->randomElement([
                'pending', 'processing', 'processing', 
                'shipped', 'shipped', 'delivered', 'delivered', 'delivered'
            ]);
            
            $paymentStatus = $status === 'pending' ? 'pending' : 'paid';
            
            // Calculate order totals
            $itemCount = $faker->numberBetween(1, 5);
            $orderProducts = $faker->randomElements($products, $itemCount);
            
            $subtotal = 0;
            foreach ($orderProducts as $product) {
                $quantity = $faker->numberBetween(1, 3);
                $price = $product->sale_price ?? $product->price;
                $subtotal += $price * $quantity;
            }
            
            $tax = $subtotal * 0.10;
            $shipping = $subtotal > 100 ? 0 : 10;
            $total = $subtotal + $tax + $shipping;
            
            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper($faker->bothify('??##??##')),
                'user_id' => $user->id,
                'status' => $status,
                'total' => $total,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'payment_method' => $faker->randomElement(['credit_card', 'paypal', 'bank_transfer']),
                'payment_status' => $paymentStatus,
                'shipping_address' => $faker->address,
                'billing_address' => $faker->address,
                'notes' => $faker->boolean(20) ? $faker->sentence : null,
                'created_at' => $orderDate->format('Y-m-d H:i:s'),
                'paid_at' => $paymentStatus === 'paid' ? $orderDate->format('Y-m-d H:i:s') : null,
                'shipped_at' => in_array($status, ['shipped', 'delivered']) ? 
                    $faker->dateTimeBetween($orderDate, 'now')->format('Y-m-d H:i:s') : null,
                'delivered_at' => $status === 'delivered' ? 
                    $faker->dateTimeBetween($orderDate, 'now')->format('Y-m-d H:i:s') : null,
            ]);
            
            // Create order items
            foreach ($orderProducts as $product) {
                $quantity = $faker->numberBetween(1, 3);
                $price = $product->sale_price ?? $product->price;
                
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                $this->query($sql, [$order->id, $product->id, $quantity, $price]);
            }
        }
        
        echo "Orders seeded successfully!\n";
    }
}
```

---

## Seeding Relationships

### Many-to-Many Relationships

**PermissionSeeder with Role Assignments:**
```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        echo "Seeding permissions...\n";
        
        $this->truncate('role_permissions');
        $this->truncate('permissions');
        
        // Create permissions
        $permissions = [
            // Product permissions
            ['name' => 'View Products', 'slug' => 'products.view'],
            ['name' => 'Create Products', 'slug' => 'products.create'],
            ['name' => 'Edit Products', 'slug' => 'products.edit'],
            ['name' => 'Delete Products', 'slug' => 'products.delete'],
            
            // Order permissions
            ['name' => 'View Orders', 'slug' => 'orders.view'],
            ['name' => 'Edit Orders', 'slug' => 'orders.edit'],
            ['name' => 'Delete Orders', 'slug' => 'orders.delete'],
            
            // User permissions
            ['name' => 'View Users', 'slug' => 'users.view'],
            ['name' => 'Edit Users', 'slug' => 'users.edit'],
            ['name' => 'Delete Users', 'slug' => 'users.delete'],
        ];
        
        $permissionIds = [];
        foreach ($permissions as $perm) {
            $permission = Permission::create(array_merge($perm, [
                'created_at' => date('Y-m-d H:i:s')
            ]));
            $permissionIds[$perm['slug']] = $permission->id;
        }
        
        // Assign permissions to roles
        $adminRole = Role::where('slug = ?', ['admin'])->first();
        $managerRole = Role::where('slug = ?', ['manager'])->first();
        
        if ($adminRole) {
            // Admin gets all permissions
            foreach ($permissionIds as $permId) {
                $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
                $this->query($sql, [$adminRole->id, $permId]);
            }
        }
        
        if ($managerRole) {
            // Manager gets product and order permissions
            $managerPermissions = [
                'products.view', 'products.create', 'products.edit',
                'orders.view', 'orders.edit'
            ];
            
            foreach ($managerPermissions as $slug) {
                if (isset($permissionIds[$slug])) {
                    $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
                    $this->query($sql, [$managerRole->id, $permissionIds[$slug]]);
                }
            }
        }
        
        echo "Permissions seeded successfully!\n";
    }
}
```

---

## Advanced Techniques

### Factory Pattern for Seeders

Create a factory class to generate model instances:

**database/factories/ProductFactory.php**
```php
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Helpers\StringHelper;
use Faker\Factory as Faker;

class ProductFactory
{
    private $faker;
    
    public function __construct()
    {
        $this->faker = Faker::create();
    }
    
    public function make($attributes = [])
    {
        $name = $this->faker->words(3, true);
        $name = ucwords($name);
        
        $defaults = [
            'name' => $name,
            'slug' => StringHelper::slugify($name) . '-' . $this->faker->unique()->randomNumber(5),
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return array_merge($defaults, $attributes);
    }
    
    public function create($attributes = [])
    {
        return Product::create($this->make($attributes));
    }
    
    public function count($count, $attributes = [])
    {
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $products[] = $this->create($attributes);
        }
        return $products;
    }
}

// Usage in seeder:
$factory = new ProductFactory();
$factory->count(50); // Create 50 products
$factory->create(['featured' => 1]); // Create featured product
```

### Conditional Seeding

```php
public function run()
{
    // Only seed if table is empty
    $count = User::count();
    
    if ($count > 0) {
        echo "Users table already has data. Skipping...\n";
        return;
    }
    
    // Seed data...
}
```

### Progress Indication

```php
public function run()
{
    $total = 1000;
    
    echo "Creating {$total} products...\n";
    
    for ($i = 1; $i <= $total; $i++) {
        // Create product...
        Product::create([...]);
        
        // Show progress every 100 items
        if ($i % 100 === 0) {
            $percentage = ($i / $total) * 100;
            echo "Progress: {$i}/{$total} ({$percentage}%)\n";
        }
    }
    
    echo "Complete!\n";
}
```

### Batch Insertion for Performance

```php
public function run()
{
    echo "Seeding large dataset...\n";
    
    $faker = Faker::create();
    $batchSize = 100;
    $totalRecords = 10000;
    
    for ($batch = 0; $batch < $totalRecords / $batchSize; $batch++) {
        $values = [];
        $params = [];
        
        for ($i = 0; $i < $batchSize; $i++) {
            $values[] = "(?, ?, ?, ?)";
            $params[] = $faker->name;
            $params[] = $faker->unique()->safeEmail;
            $params[] = password_hash('password', PASSWORD_DEFAULT);
            $params[] = date('Y-m-d H:i:s');
        }
        
        $sql = "INSERT INTO users (name, email, password, created_at) VALUES " . 
               implode(", ", $values);
        
        $this->query($sql, $params);
        
        echo "Batch " . ($batch + 1) . " completed\n";
    }
    
    echo "Seeding complete!\n";
}
```

---

## Best Practices

### ✅ Do

1. **Use Meaningful Data**
   ```php
   // Good - realistic data
   User::create([
       'name' => $faker->name,
       'email' => $faker->unique()->safeEmail,
       'bio' => $faker->paragraph
   ]);
   
   // Bad - meaningless data
   User::create([
       'name' => 'Test User ' . $i,
       'email' => 'user' . $i . '@test.com',
       'bio' => 'Test bio'
   ]);
   ```

2. **Seed in Logical Order**
   ```php
   // Seed parent tables first
   $this->call(CategorySeeder::class);
   $this->call(ProductSeeder::class);  // Requires categories
   $this->call(ReviewSeeder::class);   // Requires products and users
   ```

3. **Make Seeders Idempotent**
   ```php
   public function run()
   {
       // Truncate first to ensure clean state
       $this->truncate('products');
       
       // Then seed
       // ...
   }
   ```

4. **Use Transactions for Consistency**
   ```php
   public function run()
   {
       $pdo = $this->db->getPdo();
       
       try {
           $pdo->beginTransaction();
           
           // Seeding operations...
           
           $pdo->commit();
       } catch (Exception $e) {
           $pdo->rollBack();
           throw $e;
       }
   }
   ```

5. **Document Seeder Dependencies**
   ```php
   /**
    * ReviewSeeder
    * 
    * Dependencies:
    * - ProductSeeder (requires products)
    * - UserSeeder (requires users)
    */
   class ReviewSeeder extends Seeder
   {
       // ...
   }
   ```

6. **Use Constants for Magic Numbers**
   ```php
   class ProductSeeder extends Seeder
   {
       const PRODUCT_COUNT = 100;
       const FEATURED_PERCENTAGE = 20;
       const SALE_PERCENTAGE = 30;
       
       public function run()
       {
           for ($i = 0; $i < self::PRODUCT_COUNT; $i++) {
               // ...
           }
       }
   }
   ```

### ❌ Don't

1. **Don't Seed Sensitive Production Data**
   ```php
   // Bad - real user data
   User::create([
       'email' => 'real.user@company.com',
       'password' => 'realpassword'
   ]);
   
   // Good - fake data
   User::create([
       'email' => $faker->safeEmail,
       'password' => password_hash('password', PASSWORD_DEFAULT)
   ]);
   ```

2. **Don't Ignore Foreign Key Constraints**
   ```php
   // Bad - may fail if category doesn't exist
   Product::create([
       'name' => 'Product',
       'category_id' => 999
   ]);
   
   // Good - use existing category
   $category = Category::first();
   Product::create([
       'name' => 'Product',
       'category_id' => $category->id
   ]);
   ```

3. **Don't Create Duplicate Unique Values**
   ```php
   // Good - ensure unique emails
   $faker->unique()->safeEmail;
   
   // Bad - may create duplicates
   $faker->safeEmail;
   ```

4. **Don't Seed Too Much Data in Development**
   ```php
   // Good - reasonable amount
   const PRODUCT_COUNT = 100;
   
   // Bad - too much for dev
   const PRODUCT_COUNT = 1000000; // Will be slow
   ```

---

## Testing Seeders

### Test Seeder Output

```php
public function testUserSeederCreatesUsers()
{
    $seeder = new UserSeeder();
    $seeder->run();
    
    $count = User::count();
    $this->assertGreaterThan(0, $count);
    
    $admin = User::where('role = ?', ['admin'])->first();
    $this->assertNotNull($admin);
}
```

---

## Related Documentation

- [Migration Guide](MIGRATION_GUIDE.md) - Database migrations
- [Models Guide](MODELS_GUIDE.md) - Model usage and relationships
- [Testing Guide](TESTING_GUIDE.md) - Testing seeders
- [CLI Guide](CLI_GUIDE.md) - Command line tools

---

## Quick Reference

### Common Seeder Commands

```bash
# Run all seeders
php console seed

# Fresh migration + seed
php console migrate:fresh
php console seed
```

### Seeder Template

```php
<?php

namespace Database\Seeders;

use App\Core\Seeder;
use App\Models\YourModel;
use Faker\Factory as Faker;

class YourSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        
        echo "Seeding your_table...\n";
        
        $this->truncate('your_table');
        
        for ($i = 0; $i < 50; $i++) {
            YourModel::create([
                'field1' => $faker->value1,
                'field2' => $faker->value2,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        echo "Seeding complete!\n";
    }
}
```

---

**Last Updated:** January 2026  
**Version:** 2.0
