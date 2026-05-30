<?php

/**
 * HttpClient Class
 * Simplifies making external REST API requests using cURL
 */
class HttpClient
{
    /**
     * Send HTTP request
     * 
     * @param string $method GET, POST, PUT, DELETE, etc.
     * @param string $url Target URL
     * @param array $headers Headers list
     * @param mixed $body Payload (array or raw string)
     * @param int $timeout Timeout in seconds
     * @return array [status_code, body, error]
     */
    public static function request($method, $url, $headers = [], $body = null, $timeout = 15)
    {
        $ch = curl_init();

        $method = strtoupper($method);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypassed for sandbox testing convenience
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Map HTTP method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        // Add headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        // Logging for audit trail
        self::logRequest($method, $url, $headers, $body, $statusCode, $response, $error);

        return [
            'status_code' => $statusCode,
            'body' => $response,
            'error' => $error
        ];
    }

    /**
     * Log request and response for troubleshooting
     */
    private static function logRequest($method, $url, $headers, $body, $statusCode, $response, $error)
    {
        // Sanitize sensitive headers like credentials
        $logHeaders = [];
        foreach ($headers as $h) {
            if (stripos($h, 'X-Signature') === 0 || stripos($h, 'Authorization') === 0 || stripos($h, 'user_key') === 0) {
                $parts = explode(':', $h, 2);
                $logHeaders[] = $parts[0] . ': [MASKED]';
            } else {
                $logHeaders[] = $h;
            }
        }

        $logPath = STORAGE_PATH . '/logs/integration_http.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logEntry = sprintf(
            "[%s] %s %s\nHeaders: %s\nPayload: %s\nStatus: %d\nResponse: %s\nError: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $method,
            $url,
            json_encode($logHeaders),
            is_array($body) ? json_encode($body) : (string)$body,
            $statusCode,
            $response,
            $error ? $error : 'none',
            str_repeat('-', 80)
        );

        error_log($logEntry, 3, $logPath);
    }
}
