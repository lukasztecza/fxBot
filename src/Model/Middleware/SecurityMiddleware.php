<?php
namespace TinyApp\Model\Middleware;

use TinyApp\Model\Service\SessionService;
use TinyApp\Controller\ControllerInterface;
use TinyApp\Model\System\Request;
use TinyApp\Model\System\Response;
use TinyApp\Model\Middleware\MiddlewareAbstract;
use TinyApp\Model\Middleware\MiddlewareInterface;

class SecurityMiddleware extends MiddlewareAbstract
{
    private const LOGIN_ROUTE = '/login';

    private $securityList;
    private $sessionService;

    public function __construct(MiddlewareInterface $next, array $securityList, SessionService $sessionService)
    {
        parent::__construct($next);
        $this->securityList = $securityList;
        $this->sessionService = $sessionService;
    }

    public function process(Request $request) : Response
    {
        $roles = $this->sessionService->get(['roles'])['roles'];

        $included = $permitted = false;
        foreach ($this->securityList as $ruleKey => $rule) {
            if (!isset($rule['route']) || !isset($rule['allow'])) {
                throw new \Exception('Security rule with key ' . $ruleKey . ' must contain route and allow parameters ' . var_export($rule, true));
            }

            if (
                $rule['route'] === $request->getRoute() || (
                    substr($rule['route'], strlen($rule['route']) - 2, 2) === '/*' &&
                    strpos($request->getRoute(), substr($rule['route'], 0, strlen($rule['route']) - 2)) === 0
                )
            ) {
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

        if ($included && !$permitted) {
            $this->sessionService->set(['previousNotAllowedPath' => $request->getPath()]);
            return new Response(null, [], [], ['Location' => '/login']);
        }

        return $this->getNext()->process($request);
    }
}