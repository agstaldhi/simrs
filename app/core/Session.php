<?php

/**
 * Session Class
 * 
 * Handles secure session management
 */

class Session
{

    /**
     * Initialize session with security settings
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Session configuration for security
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');

            // Use secure cookies in production
            if (APP_ENV === 'production') {
                ini_set('session.cookie_secure', 1);
            }

            // Session name
            session_name(SESSION_NAME ?? 'SIMRS_SESSION');

            // Session lifetime
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME ?? 7200);

            // Start session
            session_start();

            // Regenerate session ID periodically to prevent fixation
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                // Regenerate every 30 minutes
                self::regenerate();
            }

            // Check session timeout
            self::checkTimeout();
        }
    }

    /**
     * Set session value
     * 
     * @param string $key Session key
     * @param mixed $value Session value
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     * 
     * @param string $key Session key
     * @param mixed $default Default value if not exists
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     * 
     * @param string $key Session key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     * 
     * @param string $key Session key
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session data
     */
    public static function clear()
    {
        $_SESSION = [];
    }

    /**
     * Destroy session
     */
    public static function destroy()
    {
        self::clear();

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld Delete old session
     */
    public static function regenerate($deleteOld = true)
    {
        session_regenerate_id($deleteOld);
        $_SESSION['created'] = time();
    }

    /**
     * Check session timeout
     */
    protected static function checkTimeout()
    {
        $timeout = SESSION_LIFETIME ?? 7200;

        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];

            if ($elapsed > $timeout) {
                self::destroy();
                header('Location: ' . BASE_URL . '/auth/login?timeout=1');
                exit;
            }
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Set flash message
     * 
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message text
     */
    public static function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash message
     * 
     * @return array|null
     */
    public static function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function isLoggedIn()
    {
        return self::has('user_id') && !empty(self::get('user_id'));
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public static function getUserId()
    {
        return self::get('user_id');
    }

    /**
     * Get current username
     * 
     * @return string|null
     */
    public static function getUsername()
    {
        return self::get('username');
    }

    /**
     * Get user data
     * 
     * @return array
     */
    public static function getUser()
    {
        return [
            'id' => self::get('user_id'),
            'username' => self::get('username'),
            'full_name' => self::get('full_name'),
            'email' => self::get('email'),
            'roles' => self::get('roles', []),
            'permissions' => self::get('permissions', [])
        ];
    }

    /**
     * Set user data after login
     * 
     * @param array $user User data
     */
    public static function setUser($user)
    {
        self::regenerate(true);

        self::set('user_id', $user['id']);
        self::set('username', $user['username']);
        self::set('full_name', $user['full_name']);
        self::set('email', $user['email']);
        self::set('roles', $user['roles'] ?? []);
        self::set('permissions', $user['permissions'] ?? []);
        self::set('login_time', time());
        self::set('ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        self::destroy();
    }

    /**
     * Check if user has permission
     * 
     * @param string $permission Permission name
     * @return bool
     */
    public static function hasPermission($permission)
    {
        $permissions = self::get('permissions', []);
        return in_array($permission, $permissions);
    }

    /**
     * Check if user has role
     * 
     * @param string $role Role name
     * @return bool
     */
    public static function hasRole($role)
    {
        $roles = self::get('roles', []);
        return in_array($role, $roles);
    }

    /**
     * Get session ID
     * 
     * @return string
     */
    public static function getId()
    {
        return session_id();
    }

    /**
     * Get all session data
     * 
     * @return array
     */
    public static function all()
    {
        return $_SESSION;
    }
}
