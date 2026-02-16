<?php
/**
 * DemoModel Model
 *
 * จุดประสงค์: [อธิบายหน้าที่ของ model]
 */

namespace App\Models;

use App\Core\Model;

class DemoModel extends Model
{
    protected string $table = 'demomodels';

    protected array $fillable = [
        // TODO: กำหนด fillable fields
    ];

    protected array $guarded = ['id'];

    protected bool $timestamps = true;
}
