<?php
namespace TinyApp\Model\System;

use TinyApp\Model\System\Request;

class Router
{
    private $routing;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function buildRequest()
    {
        $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = str_replace('app.php/', '', $uri);
        $queryStart = strpos($uri, '?');
        if ($queryStart !== false) {
            $uri = substr($uri, 0, $queryStart);
        }
        $uriElements = explode('/', $uri);
        $uriElementsCount = count($uriElements);

        foreach ($this->routes as $route => $parameters) {
            $counter = 0;
            $key = 0;
            $routeElements = explode('/', $route);
            $routeElementsCount = count($routeElements);

            if ($uriElementsCount !== $routeElementsCount) {
                continue;
            }

            if (!empty($parameters['method']) && !in_array($method, $parameters['method'])) {
                continue;
            }

            foreach ($uriElements as $key => $element) {

                if (strpos($routeElements[$key], '{') !== false) {
                    $attribute = rtrim(ltrim($routeElements[$key], '{'), '}');
                    if (empty($parameters['requirements'][$attribute])) {
                        throw new \Exception('No requirement set for route attribute ' . var_export($attribute, true));
                    }
                    $pattern = $parameters['requirements'][$attribute];
                    if (!preg_match('/^' . $pattern . '$/', $element)) {
                        continue(2);
                    }
                } elseif ($element !== $routeElements[$key]) {
                    continue(2);
                }

                if ($key === $uriElementsCount - 1) {
                    $found = $route;
                    break(2);
                }
            }
        }

        if (empty($found)) {
            throw new \Exception('No route found for uri ' . var_export($uri, true));
        }

        $attributes = [];
        foreach ($routeElements as $key => $element) {
            if (strpos($element, '{') !== false) {
                $attribute = rtrim(ltrim($routeElements[$key], '{'), '}');
                $attributes[$attribute] = $uriElements[$key];
            }
        }

        $controller = $this->routes[$found]['controller'];
        $action = $this->routes[$found]['action'];

        $input = file_get_contents('php://input');
        $request = new Request(
            (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $host,
            $uri,
            $attributes,
            $method,
            $_GET,
            $_POST,
            $_FILES,
            $input,
            $_COOKIE,
            $_SERVER,
            $controller,
            $action
        );

        return $request;
    }
}
