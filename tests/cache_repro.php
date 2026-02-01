<?php
require_once __DIR__ . '/../app/Core/Cache.php';

use App\Core\Cache;

// Clean up any existing cache file for this key
Cache::forget('repro_key');

// Set cache for 2 seconds TTL
Cache::set('repro_key', 'repro_value', 2);

$before = glob(__DIR__ . '/../storage/cache/*');
echo "Files before get():\n";
print_r($before);

$value = Cache::get('repro_key', 'default');

echo "Returned value: ";
var_export($value);
echo "\n";

$after = glob(__DIR__ . '/../storage/cache/*');
echo "Files after get():\n";
print_r($after);

// Show specific cache file path
$hash = md5('repro_key');
$path = __DIR__ . "/../storage/cache/{$hash}.cache";
echo "Cache file exists? ";
var_export(file_exists($path));
echo "\n";

// Wait 3 seconds and call get() again to trigger expiration
sleep(3);
$value2 = Cache::get('repro_key', 'default2');
echo "Returned value after expiration: ";
var_export($value2);
echo "\n";

$after_exp = file_exists($path);
echo "Cache file exists after expiration? ";
var_export($after_exp);
echo "\n";
