<?php

namespace FASTAPI;

use FASTAPI\Middlewares\MiddlewareInterface;
use FASTAPI\Router as Router;
use FASTAPI\Request as Request;
use FASTAPI\RateLimiter\RateLimiter as RateLimiter;
use FASTAPI\RouteBuilder;

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
    
    /** @var RateLimiter */
    private $rateLimiter;

    /** @var array Rate limiting configuration */
    private $config = [];
    
    /** @var bool Whether rate limiting is enabled */
    private $rateLimitEnabled = false;
    
    /** @var bool Whether rate limiting fails silently (logs but doesn't block) */
    private $rateLimitSilentMode = false;

    /**
     * Initializes a new instance of the App class.
     */
    public function __construct()
    {
        $this->router = Router::getInstance();
        $this->rateLimiter = RateLimiter::getInstance();
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
     * Set rate limiting configuration (wrapper for RateLimiter)
     * 
     * @param int $maxRequests Maximum requests per time window
     * @param int $timeWindow Time window in seconds
     * @param bool $enabled Whether rate limiting is enabled (default: true for backward compatibility)
     * @param bool $silentMode If true, logs violations but doesn't block requests (default: false)
     * @return App
     */
    public function setRateLimit(int $maxRequests, int $timeWindow, bool $enabled = true, bool $silentMode = false)
    {
        $this->config = [
            'max_requests' => $maxRequests,
            'time_window' => $timeWindow,
            'enabled' => $enabled,
            'silent_mode' => $silentMode
        ];
        
        $this->rateLimitEnabled = $enabled;
        $this->rateLimitSilentMode = $silentMode;
        $this->rateLimiter->configure($this->config);
        return $this;
    }
    
    /**
     * Enable rate limiting
     * 
     * @return App
     */
    public function enableRateLimit(): App
    {
        $this->rateLimitEnabled = true;
        $this->config['enabled'] = true;
        return $this;
    }
    
    /**
     * Disable rate limiting
     * 
     * @return App
     */
    public function disableRateLimit(): App
    {
        $this->rateLimitEnabled = false;
        $this->config['enabled'] = false;
        return $this;
    }
    
    /**
     * Set silent mode (log only, don't block)
     * 
     * @param bool $silent
     * @return App
     */
    public function setRateLimitSilentMode(bool $silent = true): App
    {
        $this->rateLimitSilentMode = $silent;
        $this->config['silent_mode'] = $silent;
        return $this;
    }
    
    /**
     * Check if rate limiting is enabled
     * 
     * @return bool
     */
    public function isRateLimitEnabled(): bool
    {
        return $this->rateLimitEnabled;
    }
    
    /**
     * Check if rate limit is exceeded (for manual checking)
     * 
     * @param Request $request
     * @return array|null Returns null if not limited, or array with limit info if limited
     */
    public function checkRateLimit(Request $request): ?array
    {
        if (!$this->rateLimitEnabled || empty($this->config)) {
            return null;
        }
        
        $ip = $this->getClientIp();
        $key = "rate_limit:{$ip}";
        $maxRequests = $this->config['max_requests'] ?? $_ENV['RATE_LIMIT_MAX'] ?? 100;
        $timeWindow = $this->config['time_window'] ?? $_ENV['RATE_LIMIT_WINDOW'] ?? 60;
        
        $isLimited = $this->rateLimiter->isLimited($key, $maxRequests, $timeWindow);
        
        if ($isLimited) {
            return [
                'limited' => true,
                'limit' => $maxRequests,
                'window' => $timeWindow,
                'storage' => $this->rateLimiter->getActiveStorage(),
                'current_count' => $this->rateLimiter->getCurrentCount($key, $timeWindow),
                'ttl' => $this->rateLimiter->getTTL($key)
            ];
        }
        
        return null;
    }

    /**
     * Get the RateLimiter instance for advanced configuration
     */
    public function getRateLimiter()
    {
        return $this->rateLimiter;
    }

    /**
     * Enforce rate limiting for incoming requests
     * This is called automatically for every request if rate limiting is enabled
     * 
     * Behavior:
     * - If disabled: Does nothing (backward compatible - no config means no rate limiting)
     * - If enabled + silent mode: Logs violations but doesn't block
     * - If enabled + normal mode: Blocks request with 429 response
     */
    private function enforceRateLimit(Request $request): void
    {
        // Only enforce if rate limiting is explicitly enabled
        if (!$this->rateLimitEnabled || empty($this->config)) {
            return;
        }

        $ip = $this->getClientIp();
        $key = "rate_limit:{$ip}";
        
        // Get limits from configuration or environment
        $maxRequests = $this->config['max_requests'] ?? $_ENV['RATE_LIMIT_MAX'] ?? 100;
        $timeWindow = $this->config['time_window'] ?? $_ENV['RATE_LIMIT_WINDOW'] ?? 60;
        
        $limitInfo = $this->checkRateLimit($request);
        
        if ($limitInfo !== null) {
            // Rate limit exceeded
            $message = "Rate limit exceeded for IP: {$ip}";
            $details = [
                'limit' => $limitInfo['limit'],
                'window' => $limitInfo['window'],
                'storage' => $limitInfo['storage'],
                'current_count' => $limitInfo['current_count'],
                'ttl' => $limitInfo['ttl']
            ];
            
            if ($this->rateLimitSilentMode) {
                // Silent mode: Log only, don't block
                error_log("[RATE LIMIT] {$message} - " . json_encode($details));
                // Add rate limit info to request for middleware/handlers to inspect
                $request->setAttribute('rate_limit_exceeded', $limitInfo);
            } else {
                // Normal mode: Block the request
                error_log("[RATE LIMIT] Blocking request - {$message}");
                (new Response())->setJsonResponse([
                    'error' => 1, 
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'data' => $details
                ], 429)->send();
                exit; // Ensure script stops after sending response
            }
        }
    }

    /**
     * Get client IP address with proxy support
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
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
     * Registers a new route for handling HTTP PATCH requests.
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
     * Create a fluent route builder
     *
     * @param string $method
     * @param string $uri
     * @param mixed $handler
     * @return RouteBuilder
     */
    public function route($method, $uri, $handler)
    {
        return $this->router->route($method, $uri, $handler);
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
     * Runs the application, dispatching the incoming HTTP request to the appropriate route handler.
     * 
     * Rate Limiting Behavior:
     * - Rate limiting is OPT-IN via setRateLimit() - it will NOT run unless explicitly enabled
     * - When enabled, can be set to silent mode (logs only, doesn't block) or blocking mode
     * - Use enableRateLimit()/disableRateLimit() to control at runtime
     * - Use setRateLimitSilentMode() to enable logging without blocking
     * - Use checkRateLimit() to manually check status in middleware/handlers
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

        // Enforce rate limiting if explicitly enabled
        // Note: Rate limiting is OPT-IN and will not run unless setRateLimit() is called
        $this->enforceRateLimit($request);

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
}
