<?php
/**
 * Validator Test
 * 
 * ทดสอบการทำงานของ Validator class
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Validator;

class ValidatorTest extends TestCase
{
    public function testRequiredRulePasses(): void
    {
        $data = ['username' => 'john'];
        $rules = ['username' => 'required'];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
    }

    public function testRequiredRuleFails(): void
    {
        $data = ['username' => ''];
        $rules = ['username' => 'required'];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors());
    }

    public function testEmailRulePasses(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->passes());
    }

    public function testEmailRuleFails(): void
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function testMinRulePasses(): void
    {
        $data = ['password' => 'password123'];
        $rules = ['password' => 'min:8'];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->passes());
    }

    public function testMinRuleFails(): void
    {
        $data = ['password' => 'short'];
        $rules = ['password' => 'min:8'];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->fails());
    }

    public function testMultipleRules(): void
    {
        $data = [
            'username' => 'john123',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];
        $rules = [
            'username' => 'required|alphanumeric|min:3',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];
        
        $validator = new Validator($data, $rules);
        
        $this->assertTrue($validator->passes());
    }
}
