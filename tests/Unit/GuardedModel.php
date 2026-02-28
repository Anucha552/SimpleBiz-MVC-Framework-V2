<?php
namespace Tests\Unit;

class GuardedModel extends \App\Core\Model
{
    protected static string $table = 'g_table';
    protected static array $guarded = ['id', 'secret'];
}
