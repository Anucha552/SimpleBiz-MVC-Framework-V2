<?php
namespace Tests\Unit;

class BadModel extends \App\Core\Model
{
    // Intentionally no $table to trigger query() error in tests
}
