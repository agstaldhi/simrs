<?php

/**
 * Base Controller Class
 * 
 * Provides common functionality for all controllers
 */

class Controller
{

    /**
     * Load model
     * 
     * @param string $model Model name
     * @return object Model instance
     */
    protected function model($model)
    {
        $modelFile = __DIR__ . '/../modules/' . strtolower($model) . '/' . $model . 'Model.php';

        if (file_exists($modelFile)) {
            require_once $modelFile;
            $modelClass = $model . 'Model';
            return new $modelClass();
        }

        throw new Exception("Model {$model} not found");
    }

    /**
     * Load view
     * 
     * @param string $view View file path (relative to modules)
     * @param array $data Data to pass to view
     * @param bool $useLayout Use layout wrapper or not
     */
    protected function view($view, $data = [], $useLayout = true)
    {
        // Extract data array to variables
        extract($data);

        // Start output buffering
        ob_start();

        // Determine view file path
        $viewFile = __DIR__ . '/../modules/' . $view . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new Exception("View {$view} not found");
        }

        // Get content
        $content = ob_get_clean();

        // If using layout, wrap content
        if ($useLayout) {
            require __DIR__ . '/../templates/layout.php';
        } else {
            echo $content;
        }
    }

    /**
     * Load partial view (without layout)
     * 
     * @param string $view View file path
     * @param array $data Data to pass to view
     */
    protected function partial($view, $data = [])
    {
        $this->view($view, $data, false);
    }

    /**
     * Redirect to URL
     * 
     * @param string $url URL to redirect to
     */
    protected function redirect($url)
    {
        header('Location: ' . BASE_URL . '/' . $url);
        exit;
    }

    /**
     * Return JSON response
     * 
     * @param mixed $data Data to return
     * @param int $statusCode HTTP status code
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Set flash message
     * 
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message text
     */
    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash message
     * 
     * @return array|null Flash message
     */
    protected function getFlash()
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
    protected function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Require authentication
     * Redirect to login if not authenticated
     */
    protected function requireAuth()
    {
        if (!$this->isLoggedIn()) {
            $this->setFlash('error', 'Silakan login terlebih dahulu');
            $this->redirect('auth/login');
        }
    }

    /**
     * Check if user has permission
     * 
     * @param string $permission Permission name
     * @return bool
     */
    protected function hasPermission($permission)
    {
        if (!isset($_SESSION['permissions'])) {
            return false;
        }
        return in_array($permission, $_SESSION['permissions']);
    }

    /**
     * Require permission
     * Show 403 if user doesn't have permission
     * 
     * @param string $permission Permission name
     */
    protected function requirePermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            $this->view('errors/403', [], false);
            exit;
        }
    }

    /**
     * Get current user data
     * 
     * @return array|null User data
     */
    protected function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'roles' => $_SESSION['roles'] ?? [],
            'permissions' => $_SESSION['permissions'] ?? []
        ];
    }

    /**
     * Validate CSRF token
     * 
     * @return bool
     */
    protected function validateCsrf()
    {
        $token = $_POST['_token'] ?? $_GET['_token'] ?? '';
        return CSRF::validate($token);
    }

    /**
     * Require CSRF token
     * Show error if invalid
     */
    protected function requireCsrf()
    {
        if (!$this->validateCsrf()) {
            http_response_code(403);
            $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ], 403);
        }
    }

    /**
     * Get POST data
     * 
     * @param string|null $key Specific key or null for all
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     * 
     * @param string|null $key Specific key or null for all
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Sanitize input
     * 
     * @param mixed $data Data to sanitize
     * @return mixed
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate input
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Errors (empty if valid)
     */
    protected function validate($data, $rules)
    {
        return Validator::validate($data, $rules);
    }

    /**
     * Log audit trail
     * 
     * @param string $action Action performed
     * @param string $module Module name
     * @param string|null $table Table name
     * @param int|null $recordId Record ID
     * @param string|null $description Description
     */
    protected function logAudit($action, $module, $table = null, $recordId = null, $description = null)
    {
        $user = $this->getCurrentUser();

        $data = [
            'user_id' => $user['id'] ?? null,
            'username' => $user['username'] ?? 'guest',
            'action' => $action,
            'module' => $module,
            'table_name' => $table,
            'record_id' => $recordId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_url' => $_SERVER['REQUEST_URI'] ?? null
        ];

        try {
            Database::insert('audit_logs', $data);
        } catch (Exception $e) {
            error_log("Failed to log audit: " . $e->getMessage());
        }
    }
}
