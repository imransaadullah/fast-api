<?php
namespace FASTAPI;

use FASTAPI\Request;
use FASTAPI\Response;

class Router
{
    private $routes = [];

    public function addRoute($method, $uri, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request)
    {
        if($request->getMethod() == 'OPTIONS'){
            $response = new Response();
            $response->send();
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
                    $response = new Response();
                    $response->setErrorResponse('Unknown Service')->send();
                }
                
                return;
            }
        }

        // Handle 404 if no route is matched
        $response = new Response();
        $response->setErrorResponse('Unknown Service')->send();
    }

    private function convertPatternToRegex($pattern)
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\\:', ':', $pattern); // Remove escape character before ':'
        $pattern = preg_replace('/:([\w-]+)/', '([^\/]+)', $pattern);
        return "/^{$pattern}$/";
    }

    public function getRoutes() {
        return $this->routes;
    }
}
