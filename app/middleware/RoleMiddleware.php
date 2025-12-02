<?php

/**
 * Role Middleware
 * 
 * Checks if user has required role
 */

class RoleMiddleware
{
    protected $requiredRole;

    /**
     * Constructor
     * 
     * @param string $role Required role
     */
    public function __construct($role = null)
    {
        $this->requiredRole = $role;
    }

    /**
     * Handle middleware
     */
    public function handle()
    {
        if (!Auth::check()) {
            Session::setFlash('error', 'Silakan login terlebih dahulu');
            redirect('auth/login');
            exit;
        }

        if ($this->requiredRole && !Auth::hasRole($this->requiredRole)) {
            http_response_code(403);
            require APP_PATH . '/templates/errors/403.php';
            exit;
        }
    }

    /**
     * Check admin role
     */
    public static function admin()
    {
        $instance = new self('admin');
        $instance->handle();
    }

    /**
     * Check doctor role
     */
    public static function doctor()
    {
        $instance = new self('doctor');
        $instance->handle();
    }

    /**
     * Check nurse role
     */
    public static function nurse()
    {
        $instance = new self('nurse');
        $instance->handle();
    }
}
