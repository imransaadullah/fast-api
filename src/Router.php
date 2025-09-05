<?php
namespace FASTAPI;

use FASTAPI\Request;
use FASTAPI\Response;
use FASTAPI\Middlewares\MiddlewareInterface;

/**
 * The Router class is responsible for routing HTTP requests to appropriate handlers based on the requested URI and method.
 * Now includes support for route groups with prefixes, middleware, and nested grouping.
 * Extended to support Laravel-style string middleware and controller syntax.
 */
class Router
{
    private static $instance = null; // Singleton instance

    /** @var array $routes An array to store the registered routes */
    private $routes = [];
    
    /** @var array $groupStack Stack to track current group context for nested groups */
    private $groupStack = [];
    
    /** @var array $currentGroup Current group attributes being applied */
    private $currentGroup = [
        'prefix' => '',
        'middleware' => [],
        'namespace' => ''
    ];
    
    /** @var array $middlewareRegistry Registry for string-based middleware resolution */
    private $middlewareRegistry = [];
    
    /** @var array $controllerNamespaces Namespaces for controller resolution */
    private $controllerNamespaces = ['App\\Controllers\\'];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Initialize router
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
     * Retrieves the singleton instance of the Router class.
     *
     * @return Router The singleton instance.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registers middleware with a string alias for easy reference.
     *
     * @param string $alias The string alias for the middleware
     * @param MiddlewareInterface|string $middleware The middleware instance or class name
     * @return $this
     */
    public function registerMiddleware(string $alias, $middleware)
    {
        $this->middlewareRegistry[$alias] = $middleware;
        return $this;
    }
    
    /**
     * Sets the controller namespaces for automatic resolution.
     *
     * @param array $namespaces Array of namespace prefixes
     * @return $this
     */
    public function setControllerNamespaces(array $namespaces)
    {
        $this->controllerNamespaces = $namespaces;
        return $this;
    }

    /**
     * Adds a new route to the router.
     *
     * @param string $method The HTTP method of the route (e.g., GET, POST, etc.).
     * @param string $uri The URI pattern of the route.
     * @param mixed $handler The handler for the route, which can be a callable or an array containing an object and method name.
     * @return void
     */
    public function addRoute($method, $uri, $handler)
    {
        // Apply current group context
        $finalUri = $this->applyGroupPrefix($uri);
        $finalMiddleware = $this->getCurrentMiddleware();
        
        // BACKWARD COMPATIBLE: Maintain original structure + add new fields
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,                    // Keep original URI for backward compatibility
            'handler' => $handler,
            // New fields for route groups (backward compatible additions)
            '_final_uri' => $finalUri,        // Internal: final URI with prefixes
            '_middleware' => $finalMiddleware, // Internal: middleware stack
            '_group' => $this->currentGroup,  // Internal: group context
            '_original_uri' => $uri          // Internal: original URI (redundant but clear)
        ];
    }
    
    /**
     * Creates a route group with common attributes.
     *
     * @param array $attributes Group attributes (prefix, middleware, namespace)
     * @param callable $callback Callback function to define routes within the group
     * @return void
     */
    public function group(array $attributes, callable $callback)
    {
        // Save current group state
        $this->groupStack[] = $this->currentGroup;
        
        // Merge attributes with current group
        $this->currentGroup = $this->mergeGroupAttributes($this->currentGroup, $attributes);
        
        // Execute the callback to define routes within this group
        $callback($this);
        
        // Restore previous group state
        $this->currentGroup = array_pop($this->groupStack);
    }
    
    /**
     * Convenience method to create a GET route.
     *
     * @param string $uri The URI pattern
     * @param mixed $handler The route handler
     * @return void
     */
    public function get($uri, $handler)
    {
        $this->addRoute('GET', $uri, $handler);
    }
    
    /**
     * Convenience method to create a POST route.
     *
     * @param string $uri The URI pattern
     * @param mixed $handler The route handler
     * @return void
     */
    public function post($uri, $handler)
    {
        $this->addRoute('POST', $uri, $handler);
    }
    
    /**
     * Convenience method to create a PUT route.
     *
     * @param string $uri The URI pattern
     * @param mixed $handler The route handler
     * @return void
     */
    public function put($uri, $handler)
    {
        $this->addRoute('PUT', $uri, $handler);
    }
    
    /**
     * Convenience method to create a DELETE route.
     *
     * @param string $uri The URI pattern
     * @param mixed $handler The route handler
     * @return void
     */
    public function delete($uri, $handler)
    {
        $this->addRoute('DELETE', $uri, $handler);
    }
    
    /**
     * Convenience method to create a PATCH route.
     *
     * @param string $uri The URI pattern
     * @param mixed $handler The route handler
     * @return void
     */
    public function patch($uri, $handler)
    {
        $this->addRoute('PATCH', $uri, $handler);
    }
    
    /**
     * Convenience method to create an OPTIONS route.
     *
     * @param string $uri The URI pattern
     * @param mixed $handler The route handler
     * @return void
     */
    public function options($uri, $handler)
    {
        $this->addRoute('OPTIONS', $uri, $handler);
    }

    /**
     * Dispatches an incoming HTTP request to the appropriate handler based on the requested URI and method.
     *
     * @param Request $request The incoming HTTP request object.
     * @return void
     */
    public function dispatch(Request $request)
    {
        if($request->getMethod() == 'OPTIONS'){
            (new Response())->send();
        }
        
        foreach ($this->routes as $route) {
            // BACKWARD COMPATIBLE: Use final URI if available, otherwise use original URI
            $routeUri = isset($route['_final_uri']) ? $route['_final_uri'] : $route['uri'];
            $pattern = $this->convertPatternToRegex($routeUri);
            
            // Match against PATH only (ignore query string) for better compatibility
            $requestPath = parse_url($request->getUri(), PHP_URL_PATH);
            if (
                $route['method'] === $request->getMethod()
                && preg_match($pattern, $requestPath, $matches)
            ) {
                array_shift($matches); // Remove the full match
                
                // BACKWARD COMPATIBLE: Use middleware if available, otherwise empty array
                $middleware = isset($route['_middleware']) ? $route['_middleware'] : [];
                
                // Resolve middleware from strings if needed
                $resolvedMiddleware = $this->resolveMiddleware($middleware);
                
                // Execute middleware chain
                $this->executeMiddleware($resolvedMiddleware, $request, function() use ($route, $request, $matches) {
                    $resolvedHandler = $this->resolveHandler($route['handler']);
                    $this->executeHandler($resolvedHandler, $request, $matches);
                });
                
                return;
            }
        }
        // Handle 404 if no route is matched
        (new Response())->setJsonResponse(['error' => 1, 'message' => 'Method Not Allowed'], 405)->send();
    }
    
    /**
     * Resolves middleware from string references to actual instances.
     *
     * @param array $middleware Array of middleware (strings or instances)
     * @return array Array of resolved middleware instances
     */
    private function resolveMiddleware(array $middleware): array
    {
        $resolved = [];
        
        foreach ($middleware as $middlewareItem) {
            if (is_string($middlewareItem)) {
                // Handle parameterized middleware (e.g., "role:doctor")
                if (strpos($middlewareItem, ':') !== false) {
                    [$name, $parameter] = explode(':', $middlewareItem, 2);
                    $resolved[] = $this->createParameterizedMiddleware($name, $parameter);
                } else {
                    // Simple string middleware
                    $resolved[] = $this->createMiddleware($middlewareItem);
                }
            } else {
                // Already an instance
                $resolved[] = $middlewareItem;
            }
        }
        
        return array_filter($resolved); // Remove null values
    }
    
    /**
     * Creates middleware instance from string alias.
     *
     * @param string $alias The middleware alias
     * @return MiddlewareInterface|null
     */
    private function createMiddleware(string $alias): ?MiddlewareInterface
    {
        if (isset($this->middlewareRegistry[$alias])) {
            $middleware = $this->middlewareRegistry[$alias];
            
            if (is_string($middleware)) {
                // Class name - instantiate it
                if (class_exists($middleware)) {
                    $instance = new $middleware();
                    if ($instance instanceof MiddlewareInterface) {
                        return $instance;
                    }
                    // If not implementing the interface, return null
                    return null;
                }
            } elseif ($middleware instanceof MiddlewareInterface) {
                return $middleware;
            } elseif (is_callable($middleware)) {
                $instance = $middleware();
                if ($instance instanceof MiddlewareInterface) {
                    return $instance;
                }
                // If not implementing the interface, return null
                return null;
            }
        }
        
        // Try to auto-resolve common middleware names
        return $this->autoResolveMiddleware($alias);
    }
    
    /**
     * Creates parameterized middleware instance.
     *
     * @param string $name The middleware name
     * @param string $parameter The parameter
     * @return MiddlewareInterface|null
     */
    private function createParameterizedMiddleware(string $name, string $parameter): ?MiddlewareInterface
    {
        if (isset($this->middlewareRegistry[$name])) {
            $middleware = $this->middlewareRegistry[$name];
            
            if (is_string($middleware)) {
                // Class name - instantiate with parameter
                if (class_exists($middleware)) {
                    $instance = new $middleware($parameter);
                    if ($instance instanceof MiddlewareInterface) {
                        return $instance;
                    }
                    // If not implementing the interface, return null
                    return null;
                }
            } elseif (is_callable($middleware)) {
                $instance = $middleware($parameter);
                if ($instance instanceof MiddlewareInterface) {
                    return $instance;
                }
                // If not implementing the interface, return null
                return null;
            }
        }
        
        // Try to auto-resolve parameterized middleware
        return $this->autoResolveParameterizedMiddleware($name, $parameter);
    }
    
    /**
     * Auto-resolves common middleware names.
     *
     * @param string $alias The middleware alias
     * @return MiddlewareInterface|null
     */
    private function autoResolveMiddleware(string $alias): ?MiddlewareInterface
    {
        // Common middleware mappings
        $commonMiddleware = [
            'auth' => 'App\\Middleware\\AuthMiddleware',
            'guest' => 'App\\Middleware\\GuestMiddleware',
            'cors' => 'App\\Middleware\\CorsMiddleware',
            'throttle' => 'App\\Middleware\\ThrottleMiddleware',
        ];
        
        if (isset($commonMiddleware[$alias])) {
            $className = $commonMiddleware[$alias];
            if (class_exists($className)) {
                $instance = new $className();
                // Check if the instance implements the required interface
                if ($instance instanceof MiddlewareInterface) {
                    return $instance;
                }
                // If not, return null instead of throwing an error
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Auto-resolves common parameterized middleware.
     *
     * @param string $name The middleware name
     * @param string $parameter The parameter
     * @return MiddlewareInterface|null
     */
    private function autoResolveParameterizedMiddleware(string $name, string $parameter): ?MiddlewareInterface
    {
        // Common parameterized middleware mappings
        $commonMiddleware = [
            'role' => 'App\\Middleware\\RoleMiddleware',
            'permission' => 'App\\Middleware\\PermissionMiddleware',
            'throttle' => 'App\\Middleware\\ThrottleMiddleware',
        ];
        
        if (isset($commonMiddleware[$name])) {
            $className = $commonMiddleware[$name];
            if (class_exists($className)) {
                $instance = new $className($parameter);
                // Check if the instance implements the required interface
                if ($instance instanceof MiddlewareInterface) {
                    return $instance;
                }
                // If not, return null instead of throwing an error
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Resolves handler from string notation to callable.
     *
     * @param mixed $handler The handler (string, array, or callable)
     * @return mixed Resolved handler
     */
    private function resolveHandler($handler)
    {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            // Laravel-style Controller@method syntax
            return $this->resolveControllerAction($handler);
        }
        
        return $handler; // Return as-is for backward compatibility
    }
    
    /**
     * Resolves Controller@method string to callable array.
     *
     * @param string $action The action string (Controller@method)
     * @return array|null Callable array or null if not found
     */
    private function resolveControllerAction(string $action): ?array
    {
        [$controller, $method] = explode('@', $action);
        
        // Try to resolve controller class
        $controllerClass = $this->resolveControllerClass($controller);
        
        if ($controllerClass && method_exists($controllerClass, $method)) {
            return [new $controllerClass(), $method];
        }
        
        return null;
    }
    
    /**
     * Resolves controller class name.
     *
     * @param string $controller The controller name
     * @return string|null The full class name or null if not found
     */
    private function resolveControllerClass(string $controller): ?string
    {
        // If already a full class name
        if (class_exists($controller)) {
            return $controller;
        }
        
        // Try with registered namespaces
        foreach ($this->controllerNamespaces as $namespace) {
            $fullClassName = rtrim($namespace, '\\') . '\\' . $controller;
            if (class_exists($fullClassName)) {
                return $fullClassName;
            }
        }
        
        return null;
    }
    
    /**
     * Executes the middleware chain for a route.
     *
     * @param array $middleware Array of middleware instances
     * @param Request $request The request object
     * @param callable $next The next function to call
     * @return void
     */
    private function executeMiddleware(array $middleware, Request $request, callable $next)
    {
        if (empty($middleware)) {
            $next();
            return;
        }
        
        $middlewareInstance = array_shift($middleware);
        
        if ($middlewareInstance instanceof MiddlewareInterface) {
            $middlewareInstance->handle($request, function() use ($middleware, $request, $next) {
                $this->executeMiddleware($middleware, $request, $next);
            });
        } else {
            // If not a proper middleware instance, skip and continue
            $this->executeMiddleware($middleware, $request, $next);
        }
    }
    
    /**
     * Executes the route handler.
     *
     * @param mixed $handler The route handler
     * @param Request $request The request object
     * @param array $matches Route parameters
     * @return void
     */
    private function executeHandler($handler, Request $request, array $matches)
    {
        if (is_callable($handler) && !is_array($handler)) {
            // If the handler is callable (a closure or function), invoke it
            $handler($request, ...$matches);
        } elseif (is_array($handler) && count($handler) === 2 && is_object($handler[0]) && method_exists($handler[0], $handler[1])) {
            // If the handler is an array [$object, 'method'], invoke the method on the object
            $object = $handler[0];
            $method = $handler[1];
            call_user_func_array([$object, $method], array_merge([$request], $matches));
        } else {
            // Handle unknown handler type
            (new Response())->setJsonResponse(['error' => 1, 'message' => 'Resource Not Found'], 404)->send();
        }
    }
    
    /**
     * Applies the current group prefix to a URI.
     *
     * @param string $uri The original URI
     * @return string The URI with group prefix applied
     */
    private function applyGroupPrefix($uri)
    {
        $prefix = $this->currentGroup['prefix'];
        if (empty($prefix)) {
            return $uri;
        }
        
        // Ensure prefix starts with / and doesn't end with /
        $prefix = '/' . trim($prefix, '/');
        // Ensure URI starts with /
        $uri = '/' . ltrim($uri, '/');
        
        return $prefix . $uri;
    }
    
    /**
     * Gets the current middleware stack including inherited middleware.
     *
     * @return array Array of middleware instances
     */
    private function getCurrentMiddleware()
    {
        $middleware = [];
        
        // Collect middleware from all group levels
        foreach ($this->groupStack as $group) {
            $middleware = array_merge($middleware, $group['middleware']);
        }
        
        // Add current group middleware
        $middleware = array_merge($middleware, $this->currentGroup['middleware']);
        
        return $middleware;
    }
    
    /**
     * Merges group attributes with parent group attributes.
     *
     * @param array $parent Parent group attributes
     * @param array $child Child group attributes
     * @return array Merged attributes
     */
    private function mergeGroupAttributes(array $parent, array $child)
    {
        $merged = $parent;
        
        // Merge prefix
        if (isset($child['prefix'])) {
            $parentPrefix = $parent['prefix'];
            $childPrefix = $child['prefix'];
            
            if (!empty($parentPrefix)) {
                $parentPrefix = '/' . trim($parentPrefix, '/');
            }
            if (!empty($childPrefix)) {
                $childPrefix = '/' . trim($childPrefix, '/');
            }
            
            $merged['prefix'] = $parentPrefix . $childPrefix;
        }
        
        // Merge middleware (parent middleware executes first)
        if (isset($child['middleware'])) {
            $merged['middleware'] = array_merge($parent['middleware'], (array)$child['middleware']);
        }
        
        // Merge namespace
        if (isset($child['namespace'])) {
            $merged['namespace'] = $child['namespace'];
        }
        
        return $merged;
    }

    /**
     * Converts a URI pattern into a regular expression pattern for route matching.
     * Supports both :param and {param} syntax.
     *
     * @param string $pattern The URI pattern to convert.
     * @return string The regular expression pattern for route matching.
     */
    private function convertPatternToRegex($pattern)
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\\:', ':', $pattern); // Remove escape character before ':'
        
        // Support both :param and {param} syntax
        $pattern = preg_replace('/:([\w-]+)/', '([^\/]+)', $pattern);        // :param
        $pattern = preg_replace('/\\\\{([\w-]+)\\\\}/', '([^\/]+)', $pattern); // {param}
        
        // Allow optional trailing slash for easier adoption
        return "/^{$pattern}\\/?$/";
    }

    /**
     * Retrieves all registered routes.
     * BACKWARD COMPATIBLE: Returns routes in original format.
     *
     * @return array An array containing all registered routes.
     */
    public function getRoutes() {
        return $this->routes;
    }
    
    /**
     * Retrieves all registered routes with their final URIs (including group prefixes).
     * Use this to see the actual URLs that will be matched.
     *
     * @return array An array containing all routes with final URIs.
     */
    public function getCompiledRoutes() {
        $compiledRoutes = [];
        foreach ($this->routes as $route) {
            $compiledRoute = $route;
            if (isset($route['_final_uri'])) {
                $compiledRoute['final_uri'] = $route['_final_uri'];
                $compiledRoute['middleware'] = $route['_middleware'];
                $compiledRoute['group'] = $route['_group'];
            }
            $compiledRoutes[] = $compiledRoute;
        }
        return $compiledRoutes;
    }
    
    /**
     * Adds middleware to the current group context.
     *
     * @param MiddlewareInterface|array $middleware Single middleware or array of middleware
     * @return $this
     */
    public function middleware($middleware)
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }
        
        $this->currentGroup['middleware'] = array_merge($this->currentGroup['middleware'], $middleware);
        return $this;
    }
    
    /**
     * Sets a prefix for the current group context.
     *
     * @param string $prefix The prefix to apply
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->currentGroup['prefix'] = $prefix;
        return $this;
    }
    
    /**
     * Sets a namespace for the current group context.
     *
     * @param string $namespace The namespace to apply
     * @return $this
     */
    public function namespace($namespace)
    {
        $this->currentGroup['namespace'] = $namespace;
        return $this;
    }
}
