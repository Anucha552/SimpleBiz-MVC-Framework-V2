<?php
require __DIR__ . '/../vendor/autoload.php';

// ensure sqlite storage DB for constructor
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=storage/simplebiz_test.sqlite');
putenv('APP_ENV=testing');

use App\Core\Database;
use App\Core\Model;

// create DB instance (will create sqlite file)
$db = Database::getInstance();
Model::setConnection($db);

// minimal test model
class TmpChainModel extends App\Core\Model
{
    protected static string $table = 'tmp_chain_test_table';
}

try {
    $qb = TmpChainModel::withTrashed();
    echo "Returned class: " . get_class($qb) . PHP_EOL;
    echo "has where: " . (is_callable([$qb, 'where']) ? 'yes' : 'no') . PHP_EOL;
    echo "has forceDelete: " . (is_callable([$qb, 'forceDelete']) ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
