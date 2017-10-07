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
        array $get,
        array $post,
        array $files,
        string $input,
        array $cookie,
        array $server,
        string $routedController,
        string $routedAction
    ) {
        $this->path = $path;
        $this->attributes = $attributes;
        $this->get = $get;
        $this->post = $post;
        $this->input = $input;
        $this->cookie = $cookie;
        $this->server = $server;
        $this->files = $files;
        $this->routedController = $routedController;
        $this->routedAction = $routedAction;
    }

    public function getPath() : string
    {
        return $this->path;
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

    public function getInput() : string
    {
        return $this->input;
    }

    public function getCookie(array $combinedKeys = []) : array
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
                if (!empty($arrayChunk[$key])) {
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
