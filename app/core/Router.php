<?php

/**
 * Router Class
 * 
 * Handles URL routing with support for middleware
 */

class Router
{
    protected $routes = [];
    protected $middlewares = [];
    protected $groupStack = [];

    /**
     * Add GET route
     * 
     * @param string $path Route path
     * @param string|callable $handler Controller@method or callable
     * @return Route
     */
    public function get($path, $handler)
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Add POST route
     * 
     * @param string $path Route path
     * @param string|callable $handler Controller@method or callable
     * @return Route
     */
    public function post($path, $handler)
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add PUT route
     * 
     * @param string $path Route path
     * @param string|callable $handler Controller@method or callable
     * @return Route
     */
    public function put($path, $handler)
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Add DELETE route
     * 
     * @param string $path Route path
     * @param string|callable $handler Controller@method or callable
     * @return Route
     */
    public function delete($path, $handler)
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Add route for any method
     * 
     * @param string $path Route path
     * @param string|callable $handler Controller@method or callable
     * @return Route
     */
    public function any($path, $handler)
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], $path, $handler);
    }

    /**
     * Add route
     * 
     * @param string|array $method HTTP method(s)
     * @param string $path Route path
     * @param string|callable $handler Handler
     * @return Route
     */
    protected function addRoute($method, $path, $handler)
    {
        $methods = is_array($method) ? $method : [$method];

        // Apply group prefix if exists
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            if (isset($group['prefix'])) {
                $path = $group['prefix'] . $path;
            }
        }

        $route = [
            'methods' => $methods,
            'path' => $path,
            'handler' => $handler,
            'middleware' => []
        ];

        // Apply group middleware
        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);
            if (isset($group['middleware'])) {
                $route['middleware'] = array_merge($route['middleware'], (array)$group['middleware']);
            }
        }

        $this->routes[] = $route;

        return new Route($route, $this);
    }

    /**
     * Create route group
     * 
     * @param array $attributes Group attributes (prefix, middleware)
     * @param callable $callback Callback function
     */
    public function group($attributes, $callback)
    {
        $this->groupStack[] = $attributes;
        call_user_func($callback, $this);
        array_pop($this->groupStack);
    }

    /**
     * Add middleware to last route
     * 
     * @param string|array $middleware Middleware name(s)
     * @return $this
     */
    public function middleware($middleware)
    {
        if (!empty($this->routes)) {
            $lastIndex = count($this->routes) - 1;
            $this->routes[$lastIndex]['middleware'] = array_merge(
                $this->routes[$lastIndex]['middleware'],
                (array)$middleware
            );
        }
        return $this;
    }

    /**
     * Dispatch request to matching route
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     */
    public function dispatch($method, $uri)
    {
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Clean URI
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'])) {
                continue;
            }

            // Check if route matches
            $pattern = $this->convertRouteToRegex($route['path']);

            if (preg_match($pattern, $uri, $matches)) {
                // Remove full match
                array_shift($matches);

                // Run middleware
                foreach ($route['middleware'] as $middleware) {
                    $this->runMiddleware($middleware);
                }

                // Execute handler
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        // No route found
        $this->show404();
    }

    /**
     * Convert route path to regex pattern
     * 
     * @param string $route Route path
     * @return string Regex pattern
     */
    protected function convertRouteToRegex($route)
    {
        // Replace {param} with named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $route);

        // Escape forward slashes
        $pattern = str_replace('/', '\/', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Execute route handler
     * 
     * @param string|callable $handler Handler
     * @param array $params Route parameters
     */
    protected function executeHandler($handler, $params = [])
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);

            $controllerClass = $controller . 'Controller';
            $controllerFile = __DIR__ . '/../modules/' . strtolower($controller) . '/' . $controllerClass . '.php';

            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                $instance = new $controllerClass();

                if (method_exists($instance, $method)) {
                    call_user_func_array([$instance, $method], $params);
                } else {
                    throw new Exception("Method {$method} not found in {$controllerClass}");
                }
            } else {
                throw new Exception("Controller file not found: {$controllerFile}");
            }
        }
    }

    /**
     * Run middleware
     * 
     * @param string $middleware Middleware name
     */
    protected function runMiddleware($middleware)
    {
        $middlewareClass = ucfirst($middleware) . 'Middleware';
        $middlewareFile = __DIR__ . '/../middleware/' . $middlewareClass . '.php';

        if (file_exists($middlewareFile)) {
            require_once $middlewareFile;
            $instance = new $middlewareClass();
            $instance->handle();
        } else {
            throw new Exception("Middleware not found: {$middlewareClass}");
        }
    }

    /**
     * Show 404 page
     */
    protected function show404()
    {
        http_response_code(404);
        require __DIR__ . '/../templates/errors/404.php';
        exit;
    }
}

/**
 * Route Class
 * Helper class for fluent route definition
 */
class Route
{
    protected $route;
    protected $router;

    public function __construct($route, $router)
    {
        $this->route = $route;
        $this->router = $router;
    }

    /**
     * Add middleware to this route
     * 
     * @param string|array $middleware Middleware name(s)
     * @return $this
     */
    public function middleware($middleware)
    {
        $this->router->middleware($middleware);
        return $this;
    }
}
