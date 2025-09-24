<?php

namespace FASTAPI;

use FASTAPI\Middlewares\MiddlewareInterface;

/**
 * RouteBuilder provides fluent API for route definition with middleware
 * This class enables method chaining while maintaining backward compatibility
 */
class RouteBuilder
{
    private $router;
    private $method;
    private $uri;
    private $handler;
    private $middleware = [];
    private $name = null;
    private $constraints = [];
    private $built = false;

    public function __construct($router, $method, $uri, $handler)
    {
        $this->router = $router;
        $this->method = $method;
        $this->uri = $uri;
        $this->handler = $handler;
    }

    /**
     * Add middleware to this route
     *
     * @param mixed $middleware Single middleware or array of middleware
     * @return RouteBuilder
     */
    public function middleware($middleware)
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    /**
     * Set route name for URL generation
     *
     * @param string $name
     * @return RouteBuilder
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set route parameter constraints
     *
     * @param array $constraints
     * @return RouteBuilder
     */
    public function where($constraints)
    {
        $this->constraints = array_merge($this->constraints, $constraints);
        return $this;
    }

    /**
     * Build and register the route
     *
     * @return void
     */
    public function build()
    {
        if ($this->built) {
            return;
        }
        
        $this->router->addRouteWithMiddleware(
            $this->method,
            $this->uri,
            $this->handler,
            $this->middleware,
            $this->name,
            $this->constraints
        );
        
        $this->built = true;
    }

    /**
     * Automatically build when object is destroyed
     */
    public function __destruct()
    {
        $this->build();
    }

    /**
     * Get the router instance for advanced usage
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Get route information
     *
     * @return array
     */
    public function getRouteInfo()
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'handler' => $this->handler,
            'middleware' => $this->middleware,
            'name' => $this->name,
            'constraints' => $this->constraints
        ];
    }
}
