<?php

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        Database::beginTransaction();
        
        // Seed a temporary user to test database validations (unique, exists)
        Database::insert('users', [
            'username' => 'existing_validator_user',
            'email' => 'existing_validator_email@example.com',
            'password' => 'DummyHash123',
            'full_name' => 'Existing User',
            'is_active' => 1
        ]);
    }

    protected function tearDown(): void
    {
        Database::rollback();
    }

    public function testRequiredRule()
    {
        $rules = ['name' => 'required'];
        
        $errors = Validator::validate(['name' => ''], $rules);
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('Field Name wajib diisi', $errors['name']);

        $errors = Validator::validate(['name' => '0'], $rules);
        $this->assertEmpty($errors);

        $errors = Validator::validate(['name' => 'John'], $rules);
        $this->assertEmpty($errors);
    }

    public function testEmailRule()
    {
        $rules = ['email' => 'email'];
        
        $errors = Validator::validate(['email' => 'invalid-email'], $rules);
        $this->assertArrayHasKey('email', $errors);
        
        $errors = Validator::validate(['email' => 'john@example.com'], $rules);
        $this->assertEmpty($errors);
    }

    public function testMinMaxRules()
    {
        $rules = ['username' => 'min:5|max:10'];
        
        $errors = Validator::validate(['username' => 'abc'], $rules);
        $this->assertArrayHasKey('username', $errors);
        
        $errors = Validator::validate(['username' => 'abcdefghijklm'], $rules);
        $this->assertArrayHasKey('username', $errors);
        
        $errors = Validator::validate(['username' => 'abcdef'], $rules);
        $this->assertEmpty($errors);
    }

    public function testNumericAndIntegerRules()
    {
        $rules = [
            'age' => 'integer',
            'price' => 'numeric'
        ];
        
        $errors = Validator::validate(['age' => '12.5', 'price' => 'abc'], $rules);
        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('price', $errors);
        
        $errors = Validator::validate(['age' => '25', 'price' => '12000.50'], $rules);
        $this->assertEmpty($errors);
    }

    public function testAlphaAndAlphanumericRules()
    {
        $rules = [
            'name' => 'alpha',
            'code' => 'alphanumeric'
        ];
        
        $errors = Validator::validate(['name' => 'John123', 'code' => 'code-123!'], $rules);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('code', $errors);
        
        $errors = Validator::validate(['name' => 'John Doe', 'code' => 'code 123'], $rules);
        $this->assertEmpty($errors);
    }

    public function testDateRule()
    {
        $rules = ['birth_date' => 'date:Y-m-d'];
        
        $errors = Validator::validate(['birth_date' => '31-12-2000'], $rules);
        $this->assertArrayHasKey('birth_date', $errors);
        
        $errors = Validator::validate(['birth_date' => '2000-12-31'], $rules);
        $this->assertEmpty($errors);
    }

    public function testUrlRule()
    {
        $rules = ['website' => 'url'];
        
        $errors = Validator::validate(['website' => 'not-a-url'], $rules);
        $this->assertArrayHasKey('website', $errors);
        
        $errors = Validator::validate(['website' => 'https://google.com'], $rules);
        $this->assertEmpty($errors);
    }

    public function testMatchesRule()
    {
        $rules = ['password_confirmation' => 'matches:password'];
        
        $data = ['password' => 'secret', 'password_confirmation' => 'different'];
        $errors = Validator::validate($data, $rules);
        $this->assertArrayHasKey('password_confirmation', $errors);
        
        $data = ['password' => 'secret', 'password_confirmation' => 'secret'];
        $errors = Validator::validate($data, $rules);
        $this->assertEmpty($errors);
    }

    public function testUniqueRule()
    {
        $rules = ['username' => 'unique:users,username'];
        
        $errors = Validator::validate(['username' => 'existing_validator_user'], $rules);
        $this->assertArrayHasKey('username', $errors);
        
        $errors = Validator::validate(['username' => 'new_validator_user'], $rules);
        $this->assertEmpty($errors);
    }

    public function testExistsRule()
    {
        $rules = ['username' => 'exists:users,username'];
        
        $errors = Validator::validate(['username' => 'nonexistent_user'], $rules);
        $this->assertArrayHasKey('username', $errors);
        
        $errors = Validator::validate(['username' => 'existing_validator_user'], $rules);
        $this->assertEmpty($errors);
    }

    public function testInRule()
    {
        $rules = ['gender' => 'in:L,P'];
        
        $errors = Validator::validate(['gender' => 'X'], $rules);
        $this->assertArrayHasKey('gender', $errors);
        
        $errors = Validator::validate(['gender' => 'L'], $rules);
        $this->assertEmpty($errors);
    }

    public function testPhoneRule()
    {
        $rules = ['phone' => 'phone'];
        
        $errors = Validator::validate(['phone' => '123456'], $rules);
        $this->assertArrayHasKey('phone', $errors);
        
        $errors = Validator::validate(['phone' => '081234567890'], $rules);
        $this->assertEmpty($errors);
        
        $errors = Validator::validate(['phone' => '+6281234567890'], $rules);
        $this->assertEmpty($errors);
    }

    public function testNikRule()
    {
        $rules = ['nik' => 'nik'];
        
        $errors = Validator::validate(['nik' => '123456789'], $rules);
        $this->assertArrayHasKey('nik', $errors);
        
        $errors = Validator::validate(['nik' => '1234567890123456'], $rules);
        $this->assertEmpty($errors);
    }
}
