# Testing Guide - PHPUnit Testing

This comprehensive guide covers testing in the SimpleBiz MVC Framework using PHPUnit, including unit tests, feature tests, and best practices.

## Table of Contents

1. [Introduction to Testing](#introduction-to-testing)
2. [PHPUnit Configuration](#phpunit-configuration)
3. [Test Structure](#test-structure)
4. [Writing Unit Tests](#writing-unit-tests)
5. [Writing Feature Tests](#writing-feature-tests)
6. [Mocking and Stubs](#mocking-and-stubs)
7. [Assertions Reference](#assertions-reference)
8. [Database Testing](#database-testing)
9. [Running Tests](#running-tests)
10. [Best Practices](#best-practices)

---

## Introduction to Testing

Testing ensures your application works as expected and helps prevent bugs from being introduced during development.

### Types of Tests

**Unit Tests:**
- Test individual components in isolation
- Fast execution
- No external dependencies
- Example: Testing a helper function, model method, or validator

**Feature Tests (Integration Tests):**
- Test complete features end-to-end
- May involve database, sessions, routing
- Slower than unit tests
- Example: Testing user registration flow, API endpoints

### Benefits of Testing

✅ **Confidence** - Refactor code without breaking functionality  
✅ **Documentation** - Tests serve as documentation for how code works  
✅ **Bug Prevention** - Catch bugs before they reach production  
✅ **Faster Development** - Less time debugging, more time building  

---

## PHPUnit Configuration

### Installation

PHPUnit is already included in the framework via Composer:

```bash
composer require --dev phpunit/phpunit
```

### Configuration File (phpunit.xml)

The framework includes a pre-configured `phpunit.xml` file:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false"
         failOnWarning="true"
         failOnRisky="true">
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">app</directory>
            <exclude>
                <directory>app/Views</directory>
            </exclude>
        </whitelist>
    </filter>
    
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_DEBUG" value="true"/>
        <env name="DB_DATABASE" value="test_database"/>
    </php>
</phpunit>
```

### Environment Variables for Testing

Create a `.env.testing` file for test-specific configuration:

```env
APP_ENV=testing
APP_DEBUG=true
APP_KEY=test-key-for-testing-only

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test_simplebiz
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=array
SESSION_DRIVER=array
MAIL_DRIVER=log
```

---

## Test Structure

### Directory Structure

```
tests/
├── TestCase.php              # Base test class
├── Unit/                     # Unit tests
│   ├── ModelTest.php
│   ├── ValidatorTest.php
│   ├── HelperTest.php
│   └── ...
└── Feature/                  # Feature tests
    ├── AuthTest.php
    ├── ProductTest.php
    ├── ApiTest.php
    └── ...
```

### Base Test Class (tests/TestCase.php)

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Database;

abstract class TestCase extends BaseTestCase
{
    protected $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load test environment
        $this->loadEnvironment();
        
        // Initialize database
        $this->db = Database::getInstance();
        
        // Start transaction
        $this->beginTransaction();
    }
    
    protected function tearDown(): void
    {
        // Rollback transaction
        $this->rollbackTransaction();
        
        parent::tearDown();
    }
    
    protected function loadEnvironment()
    {
        $envFile = __DIR__ . '/../.env.testing';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                list($name, $value) = explode('=', $line, 2);
                putenv(sprintf('%s=%s', trim($name), trim($value)));
            }
        }
    }
    
    protected function beginTransaction()
    {
        if ($this->db) {
            $this->db->getPdo()->beginTransaction();
        }
    }
    
    protected function rollbackTransaction()
    {
        if ($this->db && $this->db->getPdo()->inTransaction()) {
            $this->db->getPdo()->rollBack();
        }
    }
    
    /**
     * Create a user for testing
     */
    protected function createUser($attributes = [])
    {
        $defaults = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        $data = array_merge($defaults, $attributes);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
        $this->db->query($sql, array_values($data));
        
        return $this->db->getPdo()->lastInsertId();
    }
    
    /**
     * Assert JSON response
     */
    protected function assertJsonResponse($expected, $actual)
    {
        $this->assertJson($actual);
        $actualArray = json_decode($actual, true);
        
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actualArray);
            $this->assertEquals($value, $actualArray[$key]);
        }
    }
}
```

---

## Writing Unit Tests

Unit tests focus on testing individual components in isolation.

### Testing Helper Functions

**tests/Unit/StringHelperTest.php:**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\StringHelper;

class StringHelperTest extends TestCase
{
    public function testSlugify()
    {
        // Test basic slugification
        $result = StringHelper::slugify('Hello World');
        $this->assertEquals('hello-world', $result);
        
        // Test with special characters
        $result = StringHelper::slugify('Hello, World!');
        $this->assertEquals('hello-world', $result);
        
        // Test with unicode
        $result = StringHelper::slugify('Héllo Wörld');
        $this->assertEquals('hello-world', $result);
        
        // Test with multiple spaces
        $result = StringHelper::slugify('Hello    World');
        $this->assertEquals('hello-world', $result);
    }
    
    public function testTruncate()
    {
        $text = 'This is a long text that needs to be truncated';
        
        // Test basic truncation
        $result = StringHelper::truncate($text, 10);
        $this->assertEquals('This is a...', $result);
        
        // Test without ellipsis
        $result = StringHelper::truncate($text, 10, '');
        $this->assertEquals('This is a', $result);
        
        // Test when text is shorter than limit
        $result = StringHelper::truncate('Short', 10);
        $this->assertEquals('Short', $result);
    }
    
    public function testCamelCase()
    {
        $this->assertEquals('helloWorld', StringHelper::camelCase('hello world'));
        $this->assertEquals('helloWorld', StringHelper::camelCase('hello-world'));
        $this->assertEquals('helloWorld', StringHelper::camelCase('hello_world'));
    }
    
    public function testSnakeCase()
    {
        $this->assertEquals('hello_world', StringHelper::snakeCase('helloWorld'));
        $this->assertEquals('hello_world', StringHelper::snakeCase('HelloWorld'));
        $this->assertEquals('hello_world', StringHelper::snakeCase('hello-world'));
    }
    
    public function testRandomString()
    {
        $result = StringHelper::random(16);
        
        $this->assertEquals(16, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result);
        
        // Test uniqueness
        $result2 = StringHelper::random(16);
        $this->assertNotEquals($result, $result2);
    }
}
```

### Testing Validator Class

**tests/Unit/ValidatorTest.php:**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Validator;

class ValidatorTest extends TestCase
{
    private $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new Validator();
    }
    
    public function testRequiredValidation()
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];
        
        $this->assertTrue($this->validator->validate($data, $rules));
        
        // Test failure
        $data = ['name' => ''];
        $this->assertFalse($this->validator->validate($data, $rules));
        $this->assertArrayHasKey('name', $this->validator->errors());
    }
    
    public function testEmailValidation()
    {
        $rules = ['email' => 'required|email'];
        
        // Valid emails
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.co.uk'
        ];
        
        foreach ($validEmails as $email) {
            $result = $this->validator->validate(['email' => $email], $rules);
            $this->assertTrue($result, "Failed for valid email: $email");
        }
        
        // Invalid emails
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user @example.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $result = $this->validator->validate(['email' => $email], $rules);
            $this->assertFalse($result, "Failed for invalid email: $email");
        }
    }
    
    public function testMinMaxValidation()
    {
        $rules = [
            'username' => 'required|min:3|max:20'
        ];
        
        // Valid
        $this->assertTrue($this->validator->validate(['username' => 'john'], $rules));
        $this->assertTrue($this->validator->validate(['username' => 'john_doe_123'], $rules));
        
        // Too short
        $this->assertFalse($this->validator->validate(['username' => 'ab'], $rules));
        
        // Too long
        $this->assertFalse($this->validator->validate(['username' => str_repeat('a', 21)], $rules));
    }
    
    public function testNumericValidation()
    {
        $rules = ['age' => 'required|numeric|min:0|max:120'];
        
        // Valid
        $this->assertTrue($this->validator->validate(['age' => 25], $rules));
        $this->assertTrue($this->validator->validate(['age' => '30'], $rules));
        $this->assertTrue($this->validator->validate(['age' => 0], $rules));
        
        // Invalid
        $this->assertFalse($this->validator->validate(['age' => 'abc'], $rules));
        $this->assertFalse($this->validator->validate(['age' => -1], $rules));
        $this->assertFalse($this->validator->validate(['age' => 121], $rules));
    }
    
    public function testInValidation()
    {
        $rules = ['status' => 'required|in:active,pending,inactive'];
        
        // Valid
        $this->assertTrue($this->validator->validate(['status' => 'active'], $rules));
        $this->assertTrue($this->validator->validate(['status' => 'pending'], $rules));
        
        // Invalid
        $this->assertFalse($this->validator->validate(['status' => 'deleted'], $rules));
        $this->assertFalse($this->validator->validate(['status' => 'unknown'], $rules));
    }
    
    public function testConfirmedValidation()
    {
        $rules = ['password' => 'required|confirmed'];
        
        // Valid - matching confirmation
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ];
        $this->assertTrue($this->validator->validate($data, $rules));
        
        // Invalid - non-matching confirmation
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'different'
        ];
        $this->assertFalse($this->validator->validate($data, $rules));
        
        // Invalid - missing confirmation
        $data = ['password' => 'secret123'];
        $this->assertFalse($this->validator->validate($data, $rules));
    }
}
```

### Testing Model Methods

**tests/Unit/ModelTest.php:**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;

class ModelTest extends TestCase
{
    public function testCreateUser()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT)
        ];
        
        $user = User::create($data);
        
        $this->assertNotNull($user->id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }
    
    public function testFindUser()
    {
        // Create a user
        $userId = $this->createUser([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);
        
        // Find the user
        $user = User::find($userId);
        
        $this->assertNotNull($user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
    }
    
    public function testUpdateUser()
    {
        $userId = $this->createUser();
        $user = User::find($userId);
        
        $user->name = 'Updated Name';
        $user->save();
        
        $updatedUser = User::find($userId);
        $this->assertEquals('Updated Name', $updatedUser->name);
    }
    
    public function testDeleteUser()
    {
        $userId = $this->createUser();
        $user = User::find($userId);
        
        $user->delete();
        
        $deletedUser = User::find($userId);
        $this->assertNull($deletedUser);
    }
    
    public function testWhereQuery()
    {
        $this->createUser(['email' => 'user1@example.com']);
        $this->createUser(['email' => 'user2@example.com']);
        
        $users = User::where('email LIKE ?', ['%@example.com'])->get();
        
        $this->assertGreaterThanOrEqual(2, count($users));
    }
}
```

---

## Writing Feature Tests

Feature tests test complete application features, including routing, controllers, and database interactions.

### Testing Authentication

**tests/Feature/AuthTest.php:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    public function testUserCanRegister()
    {
        $data = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
        
        // Simulate POST request to register endpoint
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/register';
        
        // Call the register method
        $controller = new \App\Controllers\AuthController();
        $response = $controller->register();
        
        // Assert user was created
        $user = User::where('email = ?', ['newuser@example.com'])->first();
        $this->assertNotNull($user);
        $this->assertEquals('New User', $user->name);
    }
    
    public function testUserCanLogin()
    {
        // Create a user
        $password = 'password123';
        $userId = $this->createUser([
            'email' => 'login@example.com',
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        
        // Simulate login request
        $_POST = [
            'email' => 'login@example.com',
            'password' => $password
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/login';
        
        session_start();
        
        $controller = new \App\Controllers\AuthController();
        $response = $controller->login();
        
        // Assert user is logged in
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals($userId, $_SESSION['user_id']);
    }
    
    public function testUserCannotLoginWithInvalidCredentials()
    {
        $this->createUser([
            'email' => 'user@example.com',
            'password' => password_hash('correct', PASSWORD_DEFAULT)
        ]);
        
        $_POST = [
            'email' => 'user@example.com',
            'password' => 'wrong'
        ];
        
        session_start();
        
        $controller = new \App\Controllers\AuthController();
        $response = $controller->login();
        
        // Assert user is NOT logged in
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
    
    public function testUserCanLogout()
    {
        session_start();
        $_SESSION['user_id'] = 123;
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/logout';
        
        $controller = new \App\Controllers\AuthController();
        $response = $controller->logout();
        
        // Assert user is logged out
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
}
```

### Testing API Endpoints

**tests/Feature/ApiTest.php:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;

class ApiTest extends TestCase
{
    private $apiKey;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create API key for testing
        $this->apiKey = 'test-api-key-123';
        
        $sql = "INSERT INTO api_keys (key, name, created_at) VALUES (?, ?, ?)";
        $this->db->query($sql, [$this->apiKey, 'Test Key', date('Y-m-d H:i:s')]);
    }
    
    public function testGetProductsEndpoint()
    {
        // Create test products
        Product::create([
            'name' => 'Product 1',
            'slug' => 'product-1',
            'price' => 99.99,
            'stock' => 10
        ]);
        
        Product::create([
            'name' => 'Product 2',
            'slug' => 'product-2',
            'price' => 149.99,
            'stock' => 5
        ]);
        
        // Simulate API request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/products';
        $_SERVER['HTTP_X_API_KEY'] = $this->apiKey;
        
        // Call API controller
        $controller = new \App\Controllers\Api\ProductController();
        $response = $controller->index();
        
        // Assert response
        $data = json_decode($response, true);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));
    }
    
    public function testCreateProductEndpoint()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/products';
        $_SERVER['HTTP_X_API_KEY'] = $this->apiKey;
        
        $_POST = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 199.99,
            'stock' => 20,
            'description' => 'A new product'
        ];
        
        $controller = new \App\Controllers\Api\ProductController();
        $response = $controller->store();
        
        $data = json_decode($response, true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('id', $data);
        
        // Verify product was created
        $product = Product::find($data['id']);
        $this->assertNotNull($product);
        $this->assertEquals('New Product', $product->name);
    }
    
    public function testUnauthorizedAccessWithoutApiKey()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/products';
        // No API key set
        
        $controller = new \App\Controllers\Api\ProductController();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $response = $controller->index();
    }
}
```

---

## Mocking and Stubs

Mocking allows you to replace real objects with test doubles to isolate the code under test.

### Mocking External Services

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Mail;
use PHPUnit\Framework\MockObject\MockObject;

class OrderTest extends TestCase
{
    public function testOrderConfirmationEmailIsSent()
    {
        // Create a mock of the Mail class
        /** @var Mail|MockObject $mailMock */
        $mailMock = $this->createMock(Mail::class);
        
        // Set expectations
        $mailMock->expects($this->once())
                 ->method('to')
                 ->with('customer@example.com')
                 ->willReturnSelf();
        
        $mailMock->expects($this->once())
                 ->method('subject')
                 ->with('Order Confirmation')
                 ->willReturnSelf();
        
        $mailMock->expects($this->once())
                 ->method('send')
                 ->willReturn(true);
        
        // Use the mock in your code
        $order = new \App\Models\Order();
        $order->sendConfirmationEmail($mailMock, 'customer@example.com');
        
        // Assertions are automatically verified by PHPUnit
    }
}
```

### Stubbing Database Queries

```php
public function testProductSearch()
{
    // Create stub for database
    $dbStub = $this->createMock(Database::class);
    
    // Configure stub to return fake data
    $dbStub->method('query')
           ->willReturn([
               (object)['id' => 1, 'name' => 'Product 1', 'price' => 99.99],
               (object)['id' => 2, 'name' => 'Product 2', 'price' => 149.99],
           ]);
    
    // Inject stub into code under test
    $searchService = new ProductSearchService($dbStub);
    $results = $searchService->search('product');
    
    $this->assertCount(2, $results);
    $this->assertEquals('Product 1', $results[0]->name);
}
```

---

## Assertions Reference

### Common Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertNotEquals($notExpected, $actual);
$this->assertSame($expected, $actual); // Strict comparison (===)

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($variable);
$this->assertNotNull($variable);

// Arrays
$this->assertIsArray($variable);
$this->assertCount(5, $array);
$this->assertArrayHasKey('key', $array);
$this->assertContains('value', $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);

// Strings
$this->assertStringContainsString('substring', $string);
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Numbers
$this->assertGreaterThan(10, $actual);
$this->assertGreaterThanOrEqual(10, $actual);
$this->assertLessThan(100, $actual);
$this->assertLessThanOrEqual(100, $actual);

// Types
$this->assertIsInt($variable);
$this->assertIsString($variable);
$this->assertIsBool($variable);
$this->assertIsFloat($variable);
$this->assertIsObject($variable);

// Exceptions
$this->expectException(Exception::class);
$this->expectExceptionMessage('Error message');
$this->expectExceptionCode(404);

// JSON
$this->assertJson($string);
$this->assertJsonStringEqualsJsonString($expected, $actual);

// Files
$this->assertFileExists('/path/to/file');
$this->assertFileIsReadable('/path/to/file');
```

---

## Database Testing

### Using Transactions

The base `TestCase` class automatically wraps each test in a transaction and rolls it back after the test completes.

```php
public function testDatabaseTransaction()
{
    // This will be rolled back automatically
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => password_hash('password', PASSWORD_DEFAULT)
    ]);
    
    $this->assertNotNull($user->id);
    
    // After test completes, the user is rolled back
}
```

### Database Assertions

```php
public function testUserExistsInDatabase()
{
    $userId = $this->createUser(['email' => 'exists@example.com']);
    
    // Assert record exists
    $user = User::where('email = ?', ['exists@example.com'])->first();
    $this->assertNotNull($user);
    $this->assertEquals($userId, $user->id);
}

public function testUserWasDeleted()
{
    $userId = $this->createUser();
    
    User::find($userId)->delete();
    
    // Assert record doesn't exist
    $user = User::find($userId);
    $this->assertNull($user);
}
```

---

## Running Tests

### Run All Tests

```bash
# Run all tests
./vendor/bin/phpunit

# or on Windows
vendor\bin\phpunit.bat
```

### Run Specific Test Suites

```bash
# Run only unit tests
./vendor/bin/phpunit --testsuite=Unit

# Run only feature tests
./vendor/bin/phpunit --testsuite=Feature
```

### Run Specific Test File

```bash
./vendor/bin/phpunit tests/Unit/ValidatorTest.php
```

### Run Specific Test Method

```bash
./vendor/bin/phpunit --filter testEmailValidation
```

### Run with Coverage Report

```bash
# Generate HTML coverage report
./vendor/bin/phpunit --coverage-html coverage/

# Generate text coverage report
./vendor/bin/phpunit --coverage-text
```

### Run with Verbose Output

```bash
./vendor/bin/phpunit --verbose
```

### Stop on First Failure

```bash
./vendor/bin/phpunit --stop-on-failure
```

---

## Best Practices

### ✅ Do

1. **Write Tests First (TDD)**
   - Write failing test
   - Write minimal code to pass
   - Refactor

2. **One Assertion Per Test (When Possible)**
   ```php
   // Good
   public function testUserNameIsRequired()
   {
       $validator = new Validator();
       $result = $validator->validate(['name' => ''], ['name' => 'required']);
       $this->assertFalse($result);
   }
   
   public function testUserNameMinLength()
   {
       $validator = new Validator();
       $result = $validator->validate(['name' => 'ab'], ['name' => 'min:3']);
       $this->assertFalse($result);
   }
   ```

3. **Use Descriptive Test Names**
   ```php
   // Good
   public function testUserCannotLoginWithInvalidPassword()
   
   // Bad
   public function testLogin()
   ```

4. **Arrange-Act-Assert Pattern**
   ```php
   public function testProductCreation()
   {
       // Arrange - Set up test data
       $data = ['name' => 'Product', 'price' => 99.99];
       
       // Act - Perform the action
       $product = Product::create($data);
       
       // Assert - Verify the result
       $this->assertNotNull($product->id);
       $this->assertEquals('Product', $product->name);
   }
   ```

5. **Test Edge Cases**
   ```php
   public function testDivisionByZero()
   {
       $calculator = new Calculator();
       
       $this->expectException(DivisionByZeroException::class);
       $calculator->divide(10, 0);
   }
   ```

6. **Keep Tests Independent**
   - Each test should work in isolation
   - Don't rely on other tests' data
   - Use setUp() and tearDown() for common setup

7. **Use Data Providers for Similar Tests**
   ```php
   /**
    * @dataProvider emailProvider
    */
   public function testEmailValidation($email, $expected)
   {
       $validator = new Validator();
       $result = $validator->validate(['email' => $email], ['email' => 'email']);
       $this->assertEquals($expected, $result);
   }
   
   public function emailProvider()
   {
       return [
           ['test@example.com', true],
           ['invalid@', false],
           ['@example.com', false],
           ['test+tag@example.co.uk', true],
       ];
   }
   ```

### ❌ Don't

1. **Don't Test Framework Code**
   - Don't test PHPUnit itself
   - Don't test third-party libraries
   - Focus on YOUR code

2. **Don't Write Tests That Depend on External Services**
   - Mock external APIs
   - Mock mail services
   - Use test databases

3. **Don't Ignore Failed Tests**
   - Fix or remove broken tests immediately
   - Broken tests reduce confidence

4. **Don't Test Implementation Details**
   ```php
   // Bad - testing implementation
   public function testMethodCallsAnotherMethod()
   {
       $mock = $this->createMock(Service::class);
       $mock->expects($this->once())->method('internalMethod');
   }
   
   // Good - testing behavior
   public function testServiceReturnsCorrectResult()
   {
       $service = new Service();
       $result = $service->process($data);
       $this->assertEquals($expected, $result);
   }
   ```

5. **Don't Have Empty Tests**
   ```php
   // Bad
   public function testSomething()
   {
       // TODO: Write test
       $this->assertTrue(true);
   }
   ```

---

## Continuous Integration

### GitHub Actions Example

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: test_simplebiz
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, pdo_mysql
          
      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist
        
      - name: Run Tests
        run: ./vendor/bin/phpunit
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: test_simplebiz
          DB_USERNAME: root
          DB_PASSWORD: password
```

---

## Related Documentation

- [Core Usage Guide](CORE_USAGE.md) - Core classes reference
- [Models Guide](MODELS_GUIDE.md) - Model testing examples
- [API Reference](API_REFERENCE.md) - API endpoint testing

---

**Last Updated:** January 2026  
**Version:** 2.0
