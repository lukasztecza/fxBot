<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;

abstract class ApplicationMiddlewareAbstract implements ApplicationMiddlewareInterface
{
    private $next;

    public function __construct(ApplicationMiddlewareInterface $next)
    {
        $this->next = $next;
    }

    protected function getNext() : ApplicationMiddlewareInterface
    {
        return $this->next;
    }

    abstract public function process(Request $request) : Response;
}
