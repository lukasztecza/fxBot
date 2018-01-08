<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\MiddlewareAbstract;

class ControllerMiddleware extends MiddlewareAbstract
{
    private $routedController;
    private $routedAction;

    public function __construct(ControllerInterface $controller, string $action)
    {
        $this->controller = $controller;
        $this->action = $action;
    }

    public function process(Request $request) : Response
    {
        $controller = $this->controller;
        $action = $this->action;
        return $controller->$action($request);
    }
}
