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
        foreach ($this->routes as $route) {
            $pattern = $this->convertPatternToRegex($route['uri']);
            if (
                $route['method'] === $request->getMethod()
                && preg_match($pattern, $request->getUri(), $matches)
            ) {
                array_shift($matches); // Remove the full match
                $handler = $route['handler'];
                $handler($request, ...$matches);
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
