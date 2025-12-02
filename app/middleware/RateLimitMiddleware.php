<?php

/**
 * Rate Limit Middleware
 * 
 * Limits requests per IP address to prevent abuse
 */

class RateLimitMiddleware
{
    protected $maxRequests = 100; // Max requests per window
    protected $window = 60; // Time window in seconds

    /**
     * Handle middleware
     */
    public function handle()
    {
        $ip = $this->getClientIP();
        $key = 'rate_limit_' . md5($ip);

        // Get request count from session
        $requests = Session::get($key, [
            'count' => 0,
            'reset_time' => time() + $this->window
        ]);

        // Check if window has expired
        if (time() > $requests['reset_time']) {
            $requests = [
                'count' => 0,
                'reset_time' => time() + $this->window
            ];
        }

        // Increment counter
        $requests['count']++;

        // Check if limit exceeded
        if ($requests['count'] > $this->maxRequests) {
            http_response_code(429);

            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Terlalu banyak request. Silakan coba lagi nanti.'
                ]);
            } else {
                echo '<h1>429 Too Many Requests</h1>';
                echo '<p>Terlalu banyak request. Silakan coba lagi dalam ' .
                    ($requests['reset_time'] - time()) . ' detik.</p>';
            }

            exit;
        }

        // Save updated count
        Session::set($key, $requests);

        // Set rate limit headers
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . ($this->maxRequests - $requests['count']));
        header('X-RateLimit-Reset: ' . $requests['reset_time']);
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    protected function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
