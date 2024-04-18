<?php
namespace FASTAPI;

use FASTAPI\Request;
use FASTAPI\Response;

/**
 * The Router class is responsible for routing HTTP requests to appropriate handlers based on the requested URI and method.
 */
class Router
{
    /** @var array $routes An array to store the registered routes */
    private $routes = [];

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
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
        ];
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
            $pattern = $this->convertPatternToRegex($route['uri']);
            if (
                $route['method'] === $request->getMethod()
                && preg_match($pattern, $request->getUri(), $matches)
            ) {
                array_shift($matches); // Remove the full match
                $handler = $route['handler'];
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
                
                return;
            }
        }
        // Handle 404 if no route is matched
        (new Response())->setJsonResponse(['error' => 1, 'message' => 'Method Not Allawed'], 405)->send();
    }

    /**
     * Converts a URI pattern into a regular expression pattern for route matching.
     *
     * @param string $pattern The URI pattern to convert.
     * @return string The regular expression pattern for route matching.
     */
    private function convertPatternToRegex($pattern)
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\\:', ':', $pattern); // Remove escape character before ':'
        $pattern = preg_replace('/:([\w-]+)/', '([^\/]+)', $pattern);
        return "/^{$pattern}$/";
    }

    /**
     * Retrieves all registered routes.
     *
     * @return array An array containing all registered routes.
     */
    public function getRoutes() {
        return $this->routes;
    }
}
