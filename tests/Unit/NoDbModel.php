<?php
namespace Tests\Unit;

use App\Core\Database;

class NoDbModel extends \App\Core\Model
{
    protected static string $table = 'no_db_table';
    // redeclare static $db without initialization to simulate missing DB
    protected static Database $db;
}
