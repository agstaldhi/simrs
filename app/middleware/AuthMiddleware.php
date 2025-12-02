<?php

/**
 * Auth Middleware
 * 
 * Checks if user is authenticated
 */

class AuthMiddleware
{

    /**
     * Handle middleware
     */
    public function handle()
    {
        if (!Auth::check()) {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];

            Session::setFlash('error', 'Silakan login terlebih dahulu');
            redirect('auth/login');
            exit;
        }
    }
}
