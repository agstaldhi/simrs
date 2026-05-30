<?php

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $testUsername = 'test_user_auth';
    private $testEmail = 'test_user_auth@simrs.local';
    private $testPassword = 'Password123!';
    private $userId = null;

    protected function setUp(): void
    {
        // Start database transaction before each test
        Database::beginTransaction();

        // Ensure session is started for CLI testing
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // Clear session
        $_SESSION = [];

        // Insert a test user
        $hashedPassword = Auth::hashPassword($this->testPassword);
        $this->userId = Database::insert('users', [
            'username' => $this->testUsername,
            'email' => $this->testEmail,
            'password' => $hashedPassword,
            'full_name' => 'Test User Auth',
            'is_active' => 1
        ]);
        
        // Link to role if present
        $role = Database::fetchOne("SELECT id FROM roles WHERE name = 'admin' LIMIT 1");
        if ($role) {
            Database::insert('user_roles', [
                'user_id' => $this->userId,
                'role_id' => $role['id']
            ]);
        }
    }

    protected function tearDown(): void
    {
        // Rollback database transaction after each test to keep DB clean
        Database::rollback();

        // Clear session
        $_SESSION = [];
    }

    public function testPasswordHashingAndVerification()
    {
        $password = 'SecretPass123!';
        $hash = Auth::hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertTrue(Auth::verifyPassword($password, $hash));
        $this->assertFalse(Auth::verifyPassword('WrongPass', $hash));
    }

    public function testSuccessfulLogin()
    {
        $result = Auth::attempt($this->testUsername, $this->testPassword);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Login berhasil', $result['message']);
        $this->assertEquals($this->userId, $result['user']['id']);
        
        // Verify session data
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->userId, Auth::id());
        $this->assertEquals($this->testUsername, Auth::user()['username']);
    }

    public function testSuccessfulLoginWithEmail()
    {
        $result = Auth::attempt($this->testEmail, $this->testPassword);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Login berhasil', $result['message']);
    }

    public function testFailedLoginWithWrongPassword()
    {
        $result = Auth::attempt($this->testUsername, 'WrongPassword!');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Username atau password salah', $result['message']);
        $this->assertFalse(Auth::check());
    }

    public function testFailedLoginWithNonExistentUser()
    {
        $result = Auth::attempt('nonexistent_user_xyz', 'Password123!');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Username atau password salah', $result['message']);
        $this->assertFalse(Auth::check());
    }

    public function testLogout()
    {
        Auth::attempt($this->testUsername, $this->testPassword);
        $this->assertTrue(Auth::check());
        
        Auth::logout();
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::user());
    }

    public function testRateLimiting()
    {
        // Max attempts is 5. Let's trigger 5 failed attempts.
        for ($i = 0; $i < 5; $i++) {
            $result = Auth::attempt($this->testUsername, 'WrongPassword!');
            $this->assertFalse($result['success']);
        }
        
        // The 6th attempt (even with CORRECT password) should be blocked by rate limit
        $result = Auth::attempt($this->testUsername, $this->testPassword);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Terlalu banyak percobaan login', $result['message']);
    }

    public function testPasswordResetTokenLifecycle()
    {
        $token = Auth::generatePasswordResetToken($this->testEmail);
        $this->assertNotEmpty($token);
        
        // Verify valid token
        $user = Auth::verifyPasswordResetToken($token);
        $this->assertNotEmpty($user);
        $this->assertEquals($this->userId, $user['id']);
        
        // Verify invalid/expired token
        $invalidUser = Auth::verifyPasswordResetToken('invalid_token_123');
        $this->assertFalse($invalidUser);
        
        // Reset password
        $resetSuccess = Auth::resetPassword($token, 'NewPassword123!');
        $this->assertTrue($resetSuccess);
        
        // Verify new password works
        $loginResult = Auth::attempt($this->testUsername, 'NewPassword123!');
        $this->assertTrue($loginResult['success']);
        
        // Verify old password no longer works
        $oldLoginResult = Auth::attempt($this->testUsername, $this->testPassword);
        $this->assertFalse($oldLoginResult['success']);
    }
}
