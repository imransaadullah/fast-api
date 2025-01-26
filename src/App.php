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
    private $rateLimitFile = __DIR__ . '/rate_limit.json'; // File to store rate-limit data
    private $rateLimitTimeWindow = 60; // 1 minute
    private $rateLimitMaxRequests = 100;

    /**
     * Initializes a new instance of the App class.
     */
    public function __construct()
    {
        $this->router = new Router();
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

    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
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

    public function setRateLimit(int $maxRequests, int $timeWindow)
    {
        $this->rateLimitMaxRequests = $maxRequests;
        $this->rateLimitTimeWindow = $timeWindow;
        return $this;
    }

    private function rateLimit(Request $request)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $currentTime = time();

        // Load rate limit data from file
        $rateData = $this->readRateLimitFile();

        // Check and update rate limit for the current IP
        if (isset($rateData[$ip])) {
            $timeDifference = $currentTime - $rateData[$ip]['timestamp'];

            if ($timeDifference > $this->rateLimitTimeWindow) {
                // Reset count if time window has passed
                $rateData[$ip] = ['timestamp' => $currentTime, 'count' => 1];
            } else {
                // Increment request count within the time window
                $rateData[$ip]['count']++;

                if ($rateData[$ip]['count'] > $this->rateLimitMaxRequests) {
                    // Block the request
                    (new Response())->setJsonResponse(['error' => 1, 'message' => 'Too many requests. Please try again later.'], 429)->send();
                }
            }
        } else {
            // Initialize rate limit data for the first request
            $rateData[$ip] = ['timestamp' => $currentTime, 'count' => 1];
        }

        // Save updated rate limit data back to file
        $this->writeRateLimitFile($rateData);
    }

    private function readRateLimitFile()
    {
        // Create the file if it doesn't exist
        if (!file_exists($this->rateLimitFile)) {
            file_put_contents($this->rateLimitFile, json_encode([]));
        }

        // Read and decode the file content
        $data = file_get_contents($this->rateLimitFile);
        return json_decode($data, true) ?: [];
    }

    private function writeRateLimitFile(array $rateData)
    {
        // Use file locking for concurrency safety
        $fp = fopen($this->rateLimitFile, 'c+');
        if (flock($fp, LOCK_EX)) {
            // Truncate file before writing
            ftruncate($fp, 0);
            fwrite($fp, json_encode($rateData));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
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
