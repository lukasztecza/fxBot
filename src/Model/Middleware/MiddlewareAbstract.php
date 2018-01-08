<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\Middleware\MiddlewareInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

abstract class MiddlewareAbstract implements MiddlewareInterface
{
    private $next;

    public function __construct(MiddlewareInterface $next)
    {
        $this->next = $next;
    }

    protected function getNext() : MiddlewareInterface
    {
        return $this->next;
    }

    abstract public function process(Request $request) : Response;
}
