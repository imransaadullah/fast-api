<?php

namespace FASTAPI;

use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Router as Router;
use FASTAPI\Request as Request;

/**
 * The App class represents the main application that handles incoming HTTP requests and routes them to appropriate handlers.
 */
class App
{
    private static $instance = null; // Singleton instance

    /** @var Router $router The router instance used for routing incoming requests. */
    private $router;

    /** @var callable|null $notFoundHandler A handler for 404 (Not Found) responses. */
    private $notFoundHandler;

    /** @var array $middlewares List of middleware functions to be executed for every request. */
    private $middlewares = [];
    
    /** @var \FASTAPI\RateLimiter\RateLimiter */
    private $rateLimiter;

    /**
     * Initializes a new instance of the App class.
     */
    public function __construct()
    {
        $this->router = Router::getInstance();
        $this->rateLimiter = \FASTAPI\RateLimiter\RateLimiter::getInstance();
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the instance.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Retrieves the singleton instance of the App class.
     *
     * @return App The singleton instance.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get the router instance for middleware registration and advanced configuration.
     *
     * @return Router The router instance
     */
    public function getRouter()
    {
        return $this->router;
    }

    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Register middleware with alias for route-specific middleware.
     * This delegates to the router's middleware registration system.
     *
     * @param string $alias The middleware alias
     * @param mixed $middleware The middleware class or factory
     * @return App Returns the current App instance for method chaining.
     */
    public function registerMiddleware(string $alias, $middleware)
    {
        $this->router->registerMiddleware($alias, $middleware);
        return $this;
    }

    /**
     * Set controller namespaces for automatic resolution.
     * This delegates to the router's namespace configuration.
     *
     * @param array $namespaces Array of namespace prefixes
     * @return App Returns the current App instance for method chaining.
     */
    public function setControllerNamespaces(array $namespaces)
    {
        $this->router->setControllerNamespaces($namespaces);
        return $this;
    }

    /**
     * Registers a new route for handling HTTP GET requests.
     *
     * @param string $uri The URI pattern of the route.
     * @param mixed $handler The handler for the route, which can be a callable or an array containing an object and method name.
     * @return App Returns the current App instance for method chaining.
     */
    public function get($uri, $handler)
    {
        $this->router->addRoute('GET', $uri, $handler);
        return $this;
    }

    /**
     * Registers a new route for handling HTTP POST requests.
     *
     * @param string $uri The URI pattern of the route.
     * @param mixed $handler The handler for the route, which can be a callable or an array containing an object and method name.
     * @return App Returns the current App instance for method chaining.
     */
    public function post($uri, $handler)
    {
        $this->router->addRoute('POST', $uri, $handler);
        return $this;
    }

    /**
     * Registers a new route for handling HTTP PUT requests.
     *
     * @param string $uri The URI pattern of the route.
     * @param mixed $handler The handler for the route.
     * @return App
     */
    public function put($uri, $handler)
    {
        $this->router->addRoute('PUT', $uri, $handler);
        return $this;
    }

    /**
     * Registers a new route for handling HTTP DELETE requests.
     *
     * @param string $uri The URI pattern of the route.
     * @param mixed $handler The handler for the route.
     * @return App
     */
    public function delete($uri, $handler)
    {
        $this->router->addRoute('DELETE', $uri, $handler);
        return $this;
    }

    /**
     * Registers a new route for handling HTTP DELETE requests.
     *
     * @param string $uri The URI pattern of the route.
     * @param mixed $handler The handler for the route.
     * @return App
     */
    public function patch($uri, $handler)
    {
        $this->router->addRoute('PATCH', $uri, $handler);
        return $this;
    }

    /**
     * Creates a route group with common attributes.
     *
     * @param array $attributes Group attributes (prefix, middleware, namespace)
     * @param callable $callback Callback function to define routes within the group
     * @return App Returns the current App instance for method chaining.
     */
    public function group(array $attributes, callable $callback)
    {
        $this->router->group($attributes, $callback);
        return $this;
    }

    /**
     * Create a WebSocket server instance
     *
     * @param int $port WebSocket server port
     * @param string $host WebSocket server host
     * @return \FASTAPI\WebSocket\WebSocketServer
     */
    public function websocket($port = 8080, $host = '0.0.0.0')
    {
        $server = \FASTAPI\WebSocket\WebSocketServer::getInstance($this);
        $server->port($port)->host($host);
        return $server;
    }

    /**
     * Adds a middleware function to be executed before the request handler.
     *
     * @param callable $middleware A function that takes a Request object and can modify it.
     * @return App
     */
    public function use(callable $middleware)
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Sets a handler for 404 Not Found errors.
     *
     * @param callable $handler A function to handle 404 responses.
     * @return App
     */
    public function setNotFoundHandler(callable $handler)
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Retrieves all registered routes.
     *
     * @return array An array containing all registered routes.
     */
    public function getRoutes()
    {
        return $this->router->getRoutes();
    }

    /**
     * Set rate limiting configuration
     */
    public function setRateLimit(int $maxRequests, int $timeWindow)
    {
        $this->rateLimiter->configure([
            'max_requests' => $maxRequests,
            'time_window' => $timeWindow
        ]);
        return $this;
    }

    /**
     * Check rate limiting for a request
     */
    private function rateLimit(Request $request)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if ($this->rateLimiter->isLimited($ip)) {
            // Block the request
            (new Response())->setJsonResponse([
                'error' => 1, 
                'message' => 'Too many requests. Please try again later.'
            ], 429)->send();
        }
    }

    /**
     * Get rate limit information for a specific key
     */
    public function getRateLimitInfo(string $key = null): array
    {
        $key = $key ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $this->rateLimiter->getInfo($key);
    }

    /**
     * Reset rate limit for a specific key
     */
    public function resetRateLimit(string $key = null): bool
    {
        $key = $key ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $this->rateLimiter->reset($key);
    }

    /**
     * Get current rate limiting storage backend
     */
    public function getRateLimitStorage(): string
    {
        return $this->rateLimiter->getActiveStorage();
    }

    /**
     * Get available rate limiting storage backends
     */
    public function getAvailableRateLimitStorages(): array
    {
        return $this->rateLimiter->getAvailableStorages();
    }

    /**
     * Runs the application, dispatching the incoming HTTP request to the appropriate route handler.
     *
     * @return void
     */
    public function run()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $files = $_FILES;

        $data = null;

        if ($contentType === 'application/json' && $requestMethod && $requestUri) {
            $jsonInput = file_get_contents('php://input');

            if ($jsonInput !== false) {
                $data = json_decode($jsonInput, true);

                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    // Handle JSON decoding error
                }
            }
        } elseif ($requestMethod === 'POST') {
            $data = $_POST;
        } elseif ($requestMethod === 'GET') {
            $data = $_GET;
        }

        $data = $data ?? [];
        $request = new Request($requestMethod, $requestUri, $data);

        // Enforce rate limiting
        $this->rateLimit($request);

        // Execute middlewares using chaining
        $middlewareIndex = 0;
        $runner = function ($request) use (&$middlewareIndex, &$runner) {
            if ($middlewareIndex < count($this->middlewares)) {
                $middleware = $this->middlewares[$middlewareIndex];
                $middlewareIndex++;

                // If middleware supports chaining (expects $next), pass the runner
                if (is_callable($middleware)) {
                    $middleware($request, $runner);
                } else {
                    // Legacy middleware handling
                    $middleware($request);
                    $runner($request);
                }
            } else {
                // Dispatch the request after middlewares are executed
                $this->dispatchRequest($request);
            }
        };

        // Start the middleware chain
        $runner($request);
    }

    /**
     * Dispatch the request to the router or not found handler.
     *
     * @param Request $request
     * @return void
     */
    protected function dispatchRequest(Request $request): void
    {
        $dispatched = $this->router->dispatch($request);

        if (!$dispatched && $this->notFoundHandler) {
            call_user_func($this->notFoundHandler, $request);
        }
    }
    // public function run()
    // {
    //     $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    //     $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    //     $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    //     $files = $_FILES;

    //     $data = null;

    //     if ($contentType === 'application/json' && $requestMethod && $requestUri) {
    //         $jsonInput = file_get_contents('php://input');

    //         if ($jsonInput !== false) {
    //             $data = json_decode($jsonInput, true);

    //             if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    //                 // Handle JSON decoding error
    //             }
    //         }
    //     } elseif ($requestMethod === 'POST') {
    //         $data = $_POST;
    //     } elseif ($requestMethod === 'GET') {
    //         $data = $_GET;
    //     }

    //     $data = $data ?? [];
    //     $request = new Request($requestMethod, $requestUri, $data);

    //     // Enforce rate limiting
    //     $this->rateLimit($request);

    //     // Execute middlewares
    //     foreach ($this->middlewares as $middleware) {
    //         $middleware($request);
    //     }

    //     // Dispatch the request
    //     $dispatched = $this->router->dispatch($request);

    //     if (!$dispatched && $this->notFoundHandler) {
    //         call_user_func($this->notFoundHandler, $request);
    //     }
    // }
}
