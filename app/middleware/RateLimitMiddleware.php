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

        try {
            // Clean up old rate limit records (reset time has passed) to keep DB clean
            Database::query("DELETE FROM rate_limits WHERE reset_time < ?", [time()]);

            // Fetch current record
            $record = Database::fetchOne("SELECT * FROM rate_limits WHERE ip_address = ?", [$ip]);

            if ($record) {
                $count = $record['request_count'] + 1;
                $resetTime = $record['reset_time'];

                // Check if limit exceeded
                if ($count > $this->maxRequests) {
                    $this->respondTooManyRequests($resetTime - time());
                }

                // Update request count
                Database::update('rate_limits', [
                    'request_count' => $count
                ], ['ip_address' => $ip]);
            } else {
                $count = 1;
                $resetTime = time() + $this->window;

                // Insert record
                Database::insert('rate_limits', [
                    'ip_address' => $ip,
                    'request_count' => $count,
                    'reset_time' => $resetTime
                ]);
            }
        } catch (Exception $e) {
            // Database fallback: session-based rate limiting
            $key = 'rate_limit_' . md5($ip);
            $requests = Session::get($key, [
                'count' => 0,
                'reset_time' => time() + $this->window
            ]);

            if (time() > $requests['reset_time']) {
                $requests = [
                    'count' => 0,
                    'reset_time' => time() + $this->window
                ];
            }

            $requests['count']++;
            $count = $requests['count'];
            $resetTime = $requests['reset_time'];

            if ($count > $this->maxRequests) {
                $this->respondTooManyRequests($resetTime - time());
            }

            Session::set($key, $requests);
        }

        // Set rate limit headers
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . max(0, $this->maxRequests - $count));
        header('X-RateLimit-Reset: ' . $resetTime);
    }

    /**
     * Send 429 Too Many Requests response
     * 
     * @param int $secondsRemaining
     */
    protected function respondTooManyRequests($secondsRemaining)
    {
        http_response_code(429);

        if (isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Terlalu banyak request. Silakan coba lagi nanti.'
            ]);
        } else {
            echo '<h1>429 Too Many Requests</h1>';
            echo '<p>Terlalu banyak request. Silakan coba lagi dalam ' . $secondsRemaining . ' detik.</p>';
        }

        exit;
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    protected function getClientIP()
    {
        return getClientIP();
    }
}
