<?php
/**
 * Sample Model
 * 
 * จุดประสงค์: [อธิบายหน้าที่ของ model]
 */

namespace App\Models;

use App\Core\Model;

class Sample extends Model
{
    protected string $table = 'samples';
    
    protected array $fillable = [
        // TODO: กำหนด fillable fields
    ];
    
    protected array $guarded = ['id'];
    
    protected bool $timestamps = true;
}
