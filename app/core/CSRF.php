<?php

/**
 * CSRF Class
 * 
 * Handles CSRF token generation and validation
 */

class CSRF
{

    /**
     * Token name in session
     */
    const TOKEN_NAME = 'csrf_token';

    /**
     * Token name in forms
     */
    const FORM_TOKEN_NAME = '_token';

    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generate()
    {
        if (!Session::has(self::TOKEN_NAME)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::TOKEN_NAME, $token);
        }

        return Session::get(self::TOKEN_NAME);
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool
     */
    public static function validate($token)
    {
        if (!Session::has(self::TOKEN_NAME)) {
            return false;
        }

        $sessionToken = Session::get(self::TOKEN_NAME);

        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }

    /**
     * Get CSRF token
     * 
     * @return string
     */
    public static function getToken()
    {
        return self::generate();
    }

    /**
     * Get HTML input field for CSRF token
     * 
     * @return string
     */
    public static function getField()
    {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::FORM_TOKEN_NAME . '" value="' . $token . '">';
    }

    /**
     * Verify request token
     * 
     * @param string|null $token Token from request (if null, will get from POST)
     * @return bool
     */
    public static function verify($token = null)
    {
        if ($token === null) {
            $token = $_POST[self::FORM_TOKEN_NAME] ?? $_GET[self::FORM_TOKEN_NAME] ?? '';
        }

        return self::validate($token);
    }

    /**
     * Require valid CSRF token or die
     * 
     * @param string|null $token Token to verify
     */
    public static function require($token = null)
    {
        if (!self::verify($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerate()
    {
        Session::remove(self::TOKEN_NAME);
        return self::generate();
    }
}
