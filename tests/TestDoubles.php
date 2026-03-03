<?php
declare(strict_types=1);

require_once __DIR__ . '/Doubles/Config.php';
require_once __DIR__ . '/Doubles/Session.php';
require_once __DIR__ . '/Doubles/Cache.php';
require_once __DIR__ . '/Doubles/Logger.php';
require_once __DIR__ . '/Doubles/Database.php';

if (!class_exists(\App\Core\Config::class, false)) {
    class_alias(\Tests\Doubles\Config::class, \App\Core\Config::class);
}

if (!class_exists(\App\Core\Session::class, false)) {
    class_alias(\Tests\Doubles\Session::class, \App\Core\Session::class);
}

if (getenv('USE_REAL_CACHE') !== '1' && !class_exists(\App\Core\Cache::class, false)) {
    class_alias(\Tests\Doubles\Cache::class, \App\Core\Cache::class);
}

if (!class_exists(\App\Core\Logger::class, false)) {
    class_alias(\Tests\Doubles\Logger::class, \App\Core\Logger::class);
}

if (!class_exists(\App\Core\Database::class, false)) {
    class_alias(\Tests\Doubles\Database::class, \App\Core\Database::class);
}
