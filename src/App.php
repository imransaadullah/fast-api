<?php

namespace FASTAPI;

use FASTAPI\Router as Router;
use FASTAPI\Request as Request;

/**
 * The App class represents the main application that handles incoming HTTP requests and routes them to appropriate handlers.
 */
class App
{
    /** @var Router $router The router instance used for routing incoming requests. */
    private $router;

    /** @var callable|null $notFoundHandler A handler for 404 (Not Found) responses. */
    private $notFoundHandler;

    /** @var array $middlewares List of middleware functions to be executed for every request. */
    private $middlewares = [];

    /**
     * Initializes a new instance of the App class.
     */
    public function __construct()
    {
        $this->router = new Router();
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

        // Execute middlewares
        foreach ($this->middlewares as $middleware) {
            $middleware($request);
        }

        // Dispatch the request
        $dispatched = $this->router->dispatch($request);

        if (!$dispatched && $this->notFoundHandler) {
            call_user_func($this->notFoundHandler, $request);
        }
    }
}
