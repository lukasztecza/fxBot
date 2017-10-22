<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\Service\SessionService;
use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class SecurityMiddleware implements ApplicationMiddlewareInterface
{
    const LOGIN_ROUTE = '/login';

    private $securityList;
    private $routedController;
    private $routedAction;

    public function __construct(array $securityList, SessionService $sessionService, ControllerInterface $controller, string $action)
    {
        $this->securityList = $securityList;
        $this->sessionService = $sessionService;
        $this->controller = $controller;
        $this->action = $action;
    }

    public function process(Request $request) : Response
    {
        list($roles) = array_values($this->sessionService->get(['roles']));

        foreach ($this->securityList as $ruleKey => $rule) {
            if (!isset($rule['route'])) {
                throw new \Exception('Security rule with key ' . $ruleKey . ' must contain route and allow parameters ' . var_export($rule, true));
            }

            if ($rule['route'] === $request->getRoute()) {
                $included = true;

                if (empty($roles)) {
                    break;
                }

                if (isset($rule['methods']) && !in_array($request->getMethod(), $rule['methods'])) {
                    continue;
                }

                foreach ($roles as $role) {
                    if (in_array($role, $rule['allow'])) {
                        $permitted = true;
                    }
                }
            }
        }

        if (!empty($included) && empty($permitted)) {
            return new Response(null, [], [], ['Location' => '/login'], [['name' => 'previousPath', 'value' => $request->getPath()]]);
        }

        $controller = $this->controller;
        $action = $this->action;
        return $controller->$action($request);
    }
}
