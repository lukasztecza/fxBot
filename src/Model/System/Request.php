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
    private $input;
    private $cookie;
    private $server;
    private $files;
    private $routedController;
    private $routedAction;

    public function __construct(
        string $host,
        string $path,
        string $route,
        array $attributes,
        string $method,
        array $get,
        array $post,
        array $files,
        string $input,
        array $cookie,
        array $server,
        string $routedController,
        string $routedAction
    ) {
        $this->host = $host;
        $this->path = $path;
        $this->route = $route;
        $this->attributes = $attributes;
        $this->method = $method;
        $this->get = $get;
        $this->post = $post;
        $this->input = $input;
        $this->cookie = $cookie;
        $this->server = $server;
        $this->files = $files;
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

    public function getAttributes(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->attributes) : $this->attributes;
    }

    public function getQuery(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->get) : $this->get;
    }

    public function getPayload(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->post) : $this->post;
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
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->cookie) : $this->cookie;
    }

    public function getServer(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->server) : $this->server;
    }

    public function getFiles(array $combinedKeys = []) : array
    {
        return !empty($combinedKeys) ? $this->getFromArray($combinedKeys, $this->files) : $this->files;
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
