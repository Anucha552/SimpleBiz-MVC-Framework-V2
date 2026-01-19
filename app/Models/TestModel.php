<?php
/**
 * TestModel
 * 
 * Model สำหรับใช้ในการทดสอบเท่านั้น
 */

namespace App\Models;

use App\Core\Model;

class TestModel extends Model
{
    protected string $table = 'test_table';
    
    protected array $fillable = [
        'name',
        'description',
        'status'
    ];
    
    protected array $guarded = ['id'];
    
    protected bool $timestamps = true;

    /**
     * สร้าง TestModel โดยไม่พึ่งฐานข้อมูลจริง
     */
    public function __construct(array $attributes = [])
    {
        $this->db = new \PDO('sqlite::memory:');

        if ($attributes) {
            $this->fill($attributes);
        }

        if (empty($this->table)) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower($className) . 's';
        }
    }
}
