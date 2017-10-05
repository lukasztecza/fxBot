<?php
namespace TinyApp\System;

use TinyApp\System\Request;

class Router
{
    private $routing;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function buildRequest()
    {
        //@TODO here get from globals
        $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? null;
        $uri = $_SERVER['REQUEST_URI'] ?? null;

$uri = 'user/2/test';
        //@TODO based on uri find route and assign controller and action and attributes
        //@TODO get http verb GET POST PUT PATCH DELETE etc. and compare routes against it

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

            if (0 /* @TODO here compare if http verb matches the route */) {
                continue;
            }

            foreach ($uriElements as $key => $element) {

                if (strpos($routeElements[$key], '{') !== false) {
                    $attribute = rtrim(ltrim($routeElements[$key], '{'), '}');
                    if (empty($parameters['requirements'][$attribute])) {
                        throw new \Exception('No requirement set for route attribute ' . $attribute);
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
            throw new \Exception('No route found');
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
            (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $host . $uri,
            $attributes,
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
