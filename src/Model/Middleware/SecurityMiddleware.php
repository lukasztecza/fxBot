<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class SecurityMiddleware implements ApplicationMiddlewareInterface
{
    private $securityList;
    private $routedController;
    private $routedAction;

    public function __construct(array $securityList, $controller, string $action)
    {
        $this->securityList = $securityList;
        $this->controller = $controller;
        $this->action = $action;
        //@TODO add this middleware
    }

    public function process(Request $request) : Response
    {
//        var_dump($this->securityList);exit;
//@TODO prevent unauthorized access


        $controller = $this->controller;
        $action = $this->action;
        return $controller->$action($request);
    }
}
