<?php
namespace TinyApp\Model\System;

use TinyApp\Model\System\Request;

class Router
{
    private $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function buildRequest() : Request
    {
        $host = $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] : $_SERVER['HTTP_HOST'] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'] ?? null;
        $uri = str_replace('/app.php', '', $uri);
        $queryStart = strpos($uri, '?');
        $path = $queryStart !== false ? substr($uri, 0, $queryStart) : $uri;

        $routeKey = $this->getMatchingRoute($path, $method);
        $input = file_get_contents('php://input');
        $request = new Request(
            (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $host,
            $path,
            $this->routes[$routeKey]['path'],
            $this->getRouteAttributes($routeKey, $path),
            $method,
            $_GET,
            $_POST,
            $_FILES,
            $input,
            $_COOKIE,
            $_SERVER,
            $this->routes[$routeKey]['controller'],
            $this->routes[$routeKey]['action']
        );

        return $request;
    }

    private function getMatchingRoute(string $path, string $method) : int
    {
        $pathElements = explode('/', $path);
        $pathElementsCount = count($pathElements);

        foreach ($this->routes as $routeKey => $parameters) {
            $key = 0;
            $routeElements = explode('/', $parameters['path']);
            $routeElementsCount = count($routeElements);

            if ($pathElementsCount !== $routeElementsCount) {
                continue;
            }
            if (isset($parameters['methods']) && !in_array($method, $parameters['methods'])) {
                continue;
            }

            foreach ($pathElements as $key => $element) {
                if (strpos($routeElements[$key], '{') !== false) {
                    $attribute = rtrim(ltrim($routeElements[$key], '{'), '}');
                    if (!isset($parameters['requirements'][$attribute])) {
                        throw new \Exception('No requirement set for route path attribute ' . var_export($attribute, true));
                    }
                    $pattern = $parameters['requirements'][$attribute];
                    if (!preg_match('/^' . $pattern . '$/', $element)) {
                        continue(2);
                    }
                } elseif ($element !== $routeElements[$key]) {
                    continue(2);
                }

                if ($key === $pathElementsCount - 1) {
                    $found = $routeKey;
                    break(2);
                }
            }
        }

        if (!isset($found)) {
            throw new \Exception('No route found for path ' . var_export($path, true), 404);
        }

        return $found;
    }

    private function getRouteAttributes(int $routeKey, string $path) : array
    {
        $attributes = [];
        $pathElements = explode('/', $path);
        $routeElements = explode('/', $this->routes[$routeKey]['path']);
        foreach ($routeElements as $key => $element) {
            if (strpos($element, '{') !== false) {
                $attribute = rtrim(ltrim($routeElements[$key], '{'), '}');
                $attributes[$attribute] = $pathElements[$key];
            }
        }

        return $attributes;
    }
}
