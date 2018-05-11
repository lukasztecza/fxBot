<?php
use TinyApp\Model\System\Request;
use Codeception\Example;

class RequestCest
{
    public $host;
    public $path;
    public $route;
    public $attributes;
    public $method;
    public $query;
    public $payload;
    public $files;
    public $input;
    public $cookies;
    public $server;
    public $routedController;
    public $routedAction;


    private function callNonPublic($object, string $method, array $params)
    {
        return (function () use ($object, $method, $params) {
            return call_user_func_array([$object, $method], $params);
        })->bindTo($object, $object)();
    }

    public function _before()
    {
        $this->host = $host;
        $this->path = $path;
        $this->route = $route;
        $this->attributes = $attributes;
        $this->method = $method;
        $this->query = $query;
        $this->payload = $payload;
        $this->files = $files;
        $this->input = $input;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->routedController = $routedController;
        $this->routedAction = $routedAction;
    }

    public function _after()
    {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
    }

  
}
