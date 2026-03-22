<?php

/**
 * App Class
 * Main Application Bootstrap
 * 
 * Handles routing, controller loading, and method execution
 */

class App
{
    protected $controller = 'DashboardController';
    protected $method = 'index';
    protected $params = [];

    /**
     * Constructor - Parse URL and route to controller
     */
    public function __construct()
    {
        $url = $this->parseUrl();

        // Check if controller exists
        if (isset($url[0])) {
            // Sanitize module name to prevent path traversal (LFI)
            $moduleName = preg_replace('/[^a-zA-Z0-9_-]/', '', $url[0]);
            
            // If the module name is effectively empty after sanitization, show 404
            if (empty($moduleName) || $moduleName !== $url[0]) {
                $this->show404();
                return;
            }

            $controllerName = ucfirst($moduleName) . 'Controller';
            $controllerFile = __DIR__ . '/../modules/' . $moduleName . '/' . $controllerName . '.php';

            if (file_exists($controllerFile)) {
                $this->controller = $controllerName;
                unset($url[0]);

                // Include controller file
                require_once $controllerFile;
            } else {
                // Controller not found, show 404
                $this->show404();
                return;
            }
        } else {
            // Default dashboard controller
            require_once __DIR__ . '/../modules/dashboard/DashboardController.php';
        }

        // Instantiate controller
        $this->controller = new $this->controller;

        // Check if method exists
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                // Method not found, show 404
                $this->show404();
                return;
            }
        }

        // Get remaining parameters
        $this->params = $url ? array_values($url) : [];

        // Call controller method with parameters
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Parse URL from GET request
     * 
     * @return array
     */
    protected function parseUrl()
    {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }

    /**
     * Show 404 page
     */
    protected function show404()
    {
        http_response_code(404);
        require_once __DIR__ . '/../templates/errors/404.php';
        exit;
    }
}
