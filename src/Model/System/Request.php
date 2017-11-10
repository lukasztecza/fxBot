<?php
namespace TinyApp\Model\System;

class Request
{
    const DEFAULT_INPUT_TYPE = 'query';

    const INPUT_TYPE_QUERY = 'query';
    const INPUT_TYPE_JSON = 'json';

    private $host;
    private $path;
    private $route;
    private $attributes;
    private $method;
    private $query;
    private $payload;
    private $files;
    private $input;
    private $cookies;
    private $server;
    private $routedController;
    private $routedAction;

    public function __construct(
        string $host,
        string $path,
        string $route,
        array $attributes,
        string $method,
        array $query,
        array $payload,
        array $files,
        string $input,
        array $cookies,
        array $server,
        string $routedController,
        string $routedAction
    ) {
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

    public function getHost() : string
    {
        return $this->host;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getRoute() : string
    {
        return $this->route;
    }

    public function getAttributes(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->attributes) : $this->attributes;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function isAjax() : bool
    {
        if(isset($this->server['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' === strtolower($this->server['HTTP_X_REQUESTED_WITH'])) {
            return true;
        }

        return false;
    }

    public function getQuery(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->query) : $this->query;
    }

    public function getPayload(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->payload) : $this->payload;
    }

    public function getFiles(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->files) : $this->files;
    }

    public function getInput(array $combinedKeys = [], string $type = self::DEFAULT_INPUT_TYPE) : array
    {
        switch ($type) {
            case self::INPUT_TYPE_QUERY:
                parse_str($this->input, $input);
                break;
            case self::INPUT_TYPE_JSON:
                $input = json_decode($this->input, true);
                break;
            default:
                throw new \Exception('Not supported input type rule ' . $selectedRule);
        }
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $input) : $input;
    }

    public function getCookies(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->cookies) : $this->cookies;
    }

    public function getServer(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->server) : $this->server;
    }

    public function getController() : string
    {
        return $this->routedController;
    }

    public function getAction() : string
    {
        return $this->routedAction;
    }

    private function getFromArray(array $combinedKeys, array $arrayToFilter)
    {
        $return = [];
        foreach ($combinedKeys as $combinedKey) {
            $nesting = explode('.', $combinedKey);
            $arrayChunk = $arrayToFilter;
            foreach ($nesting as $key) {
                if (isset($arrayChunk[$key])) {
                    $arrayChunk = $arrayChunk[$key];
                } else {
                    $arrayChunk = null;
                }
            }
            $return[$combinedKey] = $arrayChunk;
        }
        return $return;
    }
}
