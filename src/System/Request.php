<?php
namespace TinyApp\System;

class Request
{
    private $path;
    private $attributes;
    private $query;
    private $payload;
    private $input;
    private $cookie;
    private $server;
    private $files;
    private $routedController;
    private $routedAction;

    public function __construct(
        string $path,
        array $attributes,
        array $query,
        array $payload,
        array $files,
        string $input,
        array $cookie,
        array $server,
        string $routedController,
        string $routedAction
    ) {
        $this->path = $path;
        $this->attributes = $attributes;
        $this->query = $query;
        $this->payload = $payload;
        $this->input = $input;
        $this->cookie = $cookie;
        $this->server = $server;
        $this->files = $files;
        $this->routedController = $routedController;
        $this->routedAction = $routedAction;
    }

    public function path() : string
    {
        return $this->path;
    }

    public function attributes(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->attributes) : $this->attributes;
    }

    public function get(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->query) : $this->query;
    }

    public function post(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->payload) : $this->payload;
    }

    public function input() : string
    {
        return $this->input;
    }

    public function cookie(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->cookie) : $this->cookie;
    }

    public function server(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->server) : $this->server;
    }

    public function files(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->files) : $this->files;
    }

    public function controller() : string
    {
        return $this->routedController;
    }

    public function action() : string
    {
        return $this->routedAction;
    }

    private function getFromArray(array $combinedKeys, array $arrayToFilter)
    {
        $return = [];
        foreach ($combinedKeys as $combinedKey) {
            $counter = 0;
            $nesting = explode('.', $combinedKey);
            $return[$combinedKey] = $this->getValue($counter ,$arrayToFilter, $nesting);
        }   
        return $return;
    }

    private function getValue(int &$counter, array $arrayToFilter, $nesting)
    {
        $counter++;
        if (1000 < $counter) {
            throw new \Exception('Too deep array or danger of infinite recurrence, reached counter ' . $counter);
        }   

        $key = array_shift($nesting);
        if (array_key_exists($key, $arrayToFilter)) {
            if (is_array($arrayToFilter[$key]) && count($nesting)) {
                return $this->getValue($counter, $arrayToFilter[$key], $nesting);
            } else {
                return $arrayToFilter[$key];
            }
        } else {
            return null;
         
        }
    }
}
