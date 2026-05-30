<?php

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        // Start transaction before each test
        Database::beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction after each test to keep DB clean
        if (Database::getConnection()->inTransaction()) {
            Database::rollback();
        }
    }

    public function testDatabaseConnection()
    {
        $pdo = Database::getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testInsertAndFetchOne()
    {
        $username = 'db_test_user_unique_123';
        $email = 'db_test_user_unique_123@example.com';
        
        $insertedId = Database::insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => 'secret',
            'full_name' => 'Database Test User',
            'is_active' => 1
        ]);
        
        $this->assertGreaterThan(0, $insertedId);
        
        $fetched = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$insertedId]);
        $this->assertNotEmpty($fetched);
        $this->assertEquals($username, $fetched['username']);
        $this->assertEquals($email, $fetched['email']);
    }

    public function testUpdate()
    {
        $insertedId = Database::insert('users', [
            'username' => 'update_test_user',
            'email' => 'update_test_user@example.com',
            'password' => 'secret',
            'full_name' => 'Old Name',
            'is_active' => 1
        ]);
        
        $affectedRows = Database::update(
            'users', 
            ['full_name' => 'New Name', 'is_active' => 0], 
            ['id' => $insertedId]
        );
        
        $this->assertEquals(1, $affectedRows);
        
        $fetched = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$insertedId]);
        $this->assertEquals('New Name', $fetched['full_name']);
        $this->assertEquals(0, intval($fetched['is_active']));
    }

    public function testDelete()
    {
        $insertedId = Database::insert('users', [
            'username' => 'delete_test_user',
            'email' => 'delete_test_user@example.com',
            'password' => 'secret',
            'full_name' => 'Delete Me',
            'is_active' => 1
        ]);
        
        $affectedRows = Database::delete('users', ['id' => $insertedId]);
        $this->assertEquals(1, $affectedRows);
        
        $fetched = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$insertedId]);
        $this->assertFalse($fetched);
    }

    public function testTransactionRollback()
    {
        // Insert inside the outer transaction
        $insertedId = Database::insert('users', [
            'username' => 'rollback_test_user',
            'email' => 'rollback_test_user@example.com',
            'password' => 'secret',
            'full_name' => 'Rollback User',
            'is_active' => 1
        ]);
        
        // Let's roll back
        Database::rollback();
        
        // Assert record is not there
        $fetched = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$insertedId]);
        $this->assertFalse($fetched);
        
        // Re-start transaction since tearDown will try to roll back
        Database::beginTransaction();
    }
}
