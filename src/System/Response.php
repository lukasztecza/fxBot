<?php
namespace TinyApp\System;

class Response
{
    private $variables;
    private $headers;

    public function __construct(array $variables, array $headers)
    {
        $this->variables = $variables;
        $this->headers = $headers;
    }

    public function variables(bool $escape = true)
    {
        //@TODO escape all keys and values
        if ($escape) {
            return $this->variables;
        } else {
            return $this->variables;
        }
    }

    public function headers()
    {
        return $this->headers;
    }
}
