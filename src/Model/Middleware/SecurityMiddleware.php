<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\Service\SessionService;
use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\ApplicationMiddlewareAbstract;
use TinyApp\Model\Middleware\ApplicationMiddlewareInterface;

class SecurityMiddleware extends ApplicationMiddlewareAbstract
{
    const LOGIN_ROUTE = '/login';

    private $securityList;
    private $routedController;
    private $routedAction;

    public function __construct(ApplicationMiddlewareInterface $next, array $securityList, SessionService $sessionService)
    {
        parent::__construct($next);
        $this->securityList = $securityList;
        $this->sessionService = $sessionService;
    }

    public function process(Request $request) : Response
    {
        extract($this->sessionService->get(['roles']));

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
            $this->sessionService->set(['previousPath' => $request->getPath()]);
            return new Response(null, [], [], ['Location' => '/login']);
        }

        return $this->getNext()->process($request);
    }
}
