<?php
namespace TinyApp\System;

class Router
{
    private $routing;

    public function __construct(array $routing)
    {
        $this->routing = $routing;
    }

    public function buildRequest(string $requestClass)
    {
        //@TODO here get from globals
        $host = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
        $uri = !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;

        //@TODO based on uri find route and assign controller and action and attributes
        //var_dump($this->routing);
        $attributes = ['uri_param_1' => 1];
        $controller = 'user_controller';
        $action = 'get';

        $input = file_get_contents('php://input');
        $request = new $requestClass(
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
