<?php
/**
 * ทดสอบ Router
 * เข้าถึงผ่าน: http://localhost/SimpleBiz-MVC-Framework-V2/test_router.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Router Test</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}";
echo ".section{background:white;padding:15px;margin:10px 0;border-radius:5px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}";
echo "h2{color:#333;border-bottom:2px solid #4CAF50;padding-bottom:5px;}";
echo "pre{background:#f9f9f9;padding:10px;border-left:3px solid #4CAF50;overflow-x:auto;}";
echo "</style></head><body>";

echo "<h1>🔍 SimpleBiz Router Test</h1>";

// 1. ตรวจสอบ Composer Autoload
echo "<div class='section'><h2>1. Composer Autoload</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<span class='success'>✓ Autoload loaded</span>";
} else {
    echo "<span class='error'>✗ Autoload not found</span>";
    die("</div></body></html>");
}
echo "</div>";

// 2. ตรวจสอบคลาส Router
echo "<div class='section'><h2>2. Router Class</h2>";
if (class_exists('App\Core\Router')) {
    echo "<span class='success'>✓ Router class exists</span><br>";
    $router = new App\Core\Router();
    echo "<span class='success'>✓ Router instance created</span>";
} else {
    echo "<span class='error'>✗ Router class not found</span>";
    die("</div></body></html>");
}
echo "</div>";

// 3. ตรวจสอบ Controllers
echo "<div class='section'><h2>3. Controllers</h2>";
$controllers = [
    'HomeController' => 'App\Controllers\HomeController',
    'AuthController' => 'App\Controllers\AuthController',
    'ProductController' => 'App\Controllers\Ecommerce\ProductController',
    'CartController' => 'App\Controllers\Ecommerce\CartController',
    'OrderController' => 'App\Controllers\Ecommerce\OrderController',
];

foreach ($controllers as $name => $class) {
    if (class_exists($class)) {
        echo "<span class='success'>✓ {$name}</span><br>";
    } else {
        echo "<span class='error'>✗ {$name} not found</span><br>";
    }
}
echo "</div>";

// 4. ทดสอบ getUri() logic
echo "<div class='section'><h2>4. URI Processing Test</h2>";
echo "<p>ทดสอบว่า Router จะแปลง URI อย่างไร:</p>";

// จำลอง getUri()
function testGetUri($requestUri, $scriptName) {
    $uri = $requestUri;
    
    // ลบ query string
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }
    
    // ตัด base path
    $basePath = dirname($scriptName);
    if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    
    // trim slashes
    $uri = trim($uri, '/');
    
    return '/' . $uri;
}

$testCases = [
    ['/SimpleBiz-MVC-Framework-V2/', '/SimpleBiz-MVC-Framework-V2/public/index.php', '/'],
    ['/SimpleBiz-MVC-Framework-V2/products', '/SimpleBiz-MVC-Framework-V2/public/index.php', '/products'],
    ['/SimpleBiz-MVC-Framework-V2/products/123', '/SimpleBiz-MVC-Framework-V2/public/index.php', '/products/123'],
    ['/SimpleBiz-MVC-Framework-V2/cart', '/SimpleBiz-MVC-Framework-V2/public/index.php', '/cart'],
];

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;'>";
echo "<tr style='background:#4CAF50;color:white;'><th>Request URI</th><th>Script Name</th><th>Processed URI</th><th>Status</th></tr>";

foreach ($testCases as $test) {
    $result = testGetUri($test[0], $test[1]);
    $status = ($result === $test[2]) ? "<span class='success'>✓ OK</span>" : "<span class='error'>✗ FAIL</span>";
    echo "<tr>";
    echo "<td><code>{$test[0]}</code></td>";
    echo "<td><code>{$test[1]}</code></td>";
    echo "<td><code>{$result}</code> (expected: <code>{$test[2]}</code>)</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 5. ตรวจสอบ Routes ที่ลงทะเบียน
echo "<div class='section'><h2>5. Registered Routes</h2>";
require __DIR__ . '/routes/web.php';
echo "<p><span class='success'>✓ Web routes loaded</span></p>";
require __DIR__ . '/routes/api.php';
echo "<p><span class='success'>✓ API routes loaded</span></p>";

// ใช้ reflection เพื่อดู routes ที่ลงทะเบียน
$reflection = new ReflectionClass($router);
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$routes = $property->getValue($router);

echo "<h3>GET Routes:</h3><pre>";
foreach ($routes['GET'] as $path => $route) {
    echo "{$path} => {$route['controller']}\n";
}
echo "</pre>";

echo "<h3>POST Routes:</h3><pre>";
foreach ($routes['POST'] as $path => $route) {
    echo "{$path} => {$route['controller']}\n";
}
echo "</pre>";
echo "</div>";

// 6. ตรวจสอบ .htaccess
echo "<div class='section'><h2>6. Apache Configuration</h2>";
echo "<p><strong>mod_rewrite:</strong> ";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<span class='success'>✓ Enabled</span>";
    } else {
        echo "<span class='error'>✗ Not enabled</span>";
    }
} else {
    echo "<span style='color:orange;'>⚠ Cannot detect (CLI or non-Apache)</span>";
}
echo "</p>";

echo "<p><strong>.htaccess (root):</strong> ";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<span class='success'>✓ Exists</span>";
    echo "<pre>" . htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess')) . "</pre>";
} else {
    echo "<span class='error'>✗ Not found</span>";
}
echo "</p>";

echo "<p><strong>.htaccess (public):</strong> ";
if (file_exists(__DIR__ . '/public/.htaccess')) {
    echo "<span class='success'>✓ Exists</span>";
    echo "<pre>" . htmlspecialchars(file_get_contents(__DIR__ . '/public/.htaccess')) . "</pre>";
} else {
    echo "<span class='error'>✗ Not found</span>";
}
echo "</p>";
echo "</div>";

// 7. สรุป
echo "<div class='section'><h2>7. Summary</h2>";
echo "<p><strong>Current Request:</strong></p>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "</pre>";

echo "<h3>📝 Recommendations:</h3>";
echo "<ol>";
echo "<li>ตรวจสอบว่า Apache รีสตาร์ทแล้ว</li>";
echo "<li>ลองเข้า <a href='/SimpleBiz-MVC-Framework-V2/'>http://localhost/SimpleBiz-MVC-Framework-V2/</a></li>";
echo "<li>ถ้าเจอ 404 ให้ดู Apache error log ที่ <code>C:/xampp/apache/logs/error.log</code></li>";
echo "<li>ลบไฟล์นี้หลังทดสอบเสร็จ</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
