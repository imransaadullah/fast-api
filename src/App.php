<?php

namespace FASTAPI;

use FASTAPI\Router as Router;
use FASTAPI\Request as Request;

class App
{
    private $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function get($uri, $handler)
    {
        $this->router->addRoute('GET', $uri, $handler);
        return $this;
    }

    public function post($uri, $handler)
    {
        $this->router->addRoute('POST', $uri, $handler);
        return $this;
    }

    public function getRoutes(){
        return $this->router->getRoutes();
    }

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
