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
     * Retrieves all registered routes.
     *
     * @return array An array containing all registered routes.
     */
    public function getRoutes(){
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
                    // Log the error or take appropriate action
                }
            }
        } elseif ($requestMethod === 'POST') {
            $data = $_POST;
        } elseif ($requestMethod === 'GET') {
            $data = $_GET;
        }

        // Use default value if $data is still null
        $data = $data ?? [];

        $request = new Request($requestMethod, $requestUri, $data);
        
        $this->router->dispatch($request);
    }
}
