<?php

/**
 * Auth Class
 * 
 * Handles authentication and authorization
 */

class Auth
{

    /**
     * Attempt login
     * 
     * @param string $username Username or email
     * @param string $password Password
     * @return array Result with success status and message
     */
    public static function attempt($username, $password)
    {
        // Check rate limiting
        if (!self::checkRateLimit($username)) {
            self::logLoginAttempt($username, 'blocked', 'Too many failed attempts');
            return [
                'success' => false,
                'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.'
            ];
        }

        // Find user by username or email
        $query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1";
        $user = Database::fetchOne($query, [$username, $username]);

        if (!$user) {
            self::logLoginAttempt($username, 'failed', 'User not found');
            return [
                'success' => false,
                'message' => 'Username atau password salah'
            ];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            self::logLoginAttempt($username, 'failed', 'Wrong password');
            return [
                'success' => false,
                'message' => 'Username atau password salah'
            ];
        }

        // Get user roles and permissions
        $roles = self::getUserRoles($user['id']);
        $permissions = self::getUserPermissions($user['id']);

        // Set session
        Session::setUser([
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'roles' => $roles,
            'permissions' => $permissions
        ]);

        // Update last login
        Database::update('users', [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ], ['id' => $user['id']]);

        // Log successful login
        self::logLoginAttempt($username, 'success', null);

        // Log audit
        self::logAudit($user['id'], 'login', 'auth', 'users', $user['id'], 'Login berhasil');

        return [
            'success' => true,
            'message' => 'Login berhasil',
            'user' => $user
        ];
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        $userId = Session::getUserId();

        if ($userId) {
            self::logAudit($userId, 'logout', 'auth', 'users', $userId, 'Logout');
        }

        Session::logout();
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function check()
    {
        return Session::isLoggedIn();
    }

    /**
     * Get current user
     * 
     * @return array|null
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }
        return Session::getUser();
    }

    /**
     * Get user ID
     * 
     * @return int|null
     */
    public static function id()
    {
        return Session::getUserId();
    }

    /**
     * Check if user has permission
     * 
     * @param string $permission Permission name
     * @return bool
     */
    public static function can($permission)
    {
        return Session::hasPermission($permission);
    }

    /**
     * Check if user has role
     * 
     * @param string $role Role name
     * @return bool
     */
    public static function hasRole($role)
    {
        return Session::hasRole($role);
    }

    /**
     * Get user roles
     * 
     * @param int $userId User ID
     * @return array
     */
    protected static function getUserRoles($userId)
    {
        $query = "SELECT r.name 
                  FROM roles r
                  JOIN user_roles ur ON r.id = ur.role_id
                  WHERE ur.user_id = ? AND r.is_active = 1";

        $results = Database::fetchAll($query, [$userId]);

        return array_column($results, 'name');
    }

    /**
     * Get user permissions
     * 
     * @param int $userId User ID
     * @return array
     */
    protected static function getUserPermissions($userId)
    {
        $query = "SELECT DISTINCT p.name
                  FROM permissions p
                  JOIN role_permissions rp ON p.id = rp.permission_id
                  JOIN user_roles ur ON rp.role_id = ur.role_id
                  WHERE ur.user_id = ?";

        $results = Database::fetchAll($query, [$userId]);

        return array_column($results, 'name');
    }

    /**
     * Check rate limiting for login attempts
     * 
     * @param string $username Username
     * @return bool True if allowed, false if blocked
     */
    protected static function checkRateLimit($username)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $maxAttempts = LOGIN_MAX_ATTEMPTS ?? 5;
        $lockoutDuration = LOGIN_LOCKOUT_MINUTES ?? 15;

        // Count failed attempts in last X minutes
        $query = "SELECT COUNT(*) as count 
                  FROM login_attempts 
                  WHERE (username = ? OR ip_address = ?)
                  AND attempt_result = 'failed'
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";

        $result = Database::fetchOne($query, [$username, $ip, $lockoutDuration]);

        return $result['count'] < $maxAttempts;
    }

    /**
     * Log login attempt
     * 
     * @param string $username Username
     * @param string $result Result (success, failed, blocked)
     * @param string|null $reason Failure reason
     */
    protected static function logLoginAttempt($username, $result, $reason = null)
    {
        try {
            Database::insert('login_attempts', [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'attempt_result' => $result,
                'failure_reason' => $reason,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'attempted_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
    }

    /**
     * Log audit trail
     * 
     * @param int $userId User ID
     * @param string $action Action
     * @param string $module Module
     * @param string|null $table Table name
     * @param int|null $recordId Record ID
     * @param string|null $description Description
     */
    protected static function logAudit($userId, $action, $module, $table = null, $recordId = null, $description = null)
    {
        try {
            Database::insert('audit_logs', [
                'user_id' => $userId,
                'username' => Session::getUsername(),
                'action' => $action,
                'module' => $module,
                'table_name' => $table,
                'record_id' => $recordId,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'request_url' => $_SERVER['REQUEST_URI'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log audit: " . $e->getMessage());
        }
    }

    /**
     * Hash password
     * 
     * @param string $password Plain password
     * @return string Hashed password
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     * 
     * @param string $password Plain password
     * @param string $hash Hashed password
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate password reset token
     * 
     * @param string $email User email
     * @return string|false Token or false on failure
     */
    public static function generatePasswordResetToken($email)
    {
        $user = Database::fetchOne("SELECT id FROM users WHERE email = ? AND is_active = 1", [$email]);

        if (!$user) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        Database::update('users', [
            'password_reset_token' => $token,
            'password_reset_expires' => $expires
        ], ['id' => $user['id']]);

        return $token;
    }

    /**
     * Verify password reset token
     * 
     * @param string $token Reset token
     * @return array|false User data or false
     */
    public static function verifyPasswordResetToken($token)
    {
        $query = "SELECT * FROM users 
                  WHERE password_reset_token = ? 
                  AND password_reset_expires > NOW()
                  AND is_active = 1
                  LIMIT 1";

        return Database::fetchOne($query, [$token]);
    }

    /**
     * Reset password
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool
     */
    public static function resetPassword($token, $newPassword)
    {
        $user = self::verifyPasswordResetToken($token);

        if (!$user) {
            return false;
        }

        $hashedPassword = self::hashPassword($newPassword);

        Database::update('users', [
            'password' => $hashedPassword,
            'password_reset_token' => null,
            'password_reset_expires' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $user['id']]);

        return true;
    }
}
